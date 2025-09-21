<?php
require_once '../includes/load.php';
require_once '../includes/fontawesome-icons.php';

// 获取Font Awesome图标列表
$fontAwesomeIcons = getFontAwesomeIcons();

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取导航链接管理实例
$linkManager = get_navigation_link_manager();
$database = get_database();
$pdo = $database->getConnection();

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY order_index ASC, name ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

// 处理AJAX文件上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_icon') {
    // 验证文件上传
    if (!isset($_FILES['icon_upload']) || $_FILES['icon_upload']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => '文件上传失败']);
        exit();
    }
    
    // 处理文件上传
    $fileUpload = get_file_upload_manager('links');
    $upload_result = $fileUpload->upload($_FILES['icon_upload']);
    
    header('Content-Type: application/json');
    if ($upload_result['success']) {
        echo json_encode([
            'success' => true,
            'file_name' => $upload_result['file_name'],
            'file_url' => $upload_result['file_url']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $upload_result['error']
        ]);
    }
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'upload_icon')) {
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $order_index = intval($_POST['order_index'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证输入
    $errors = [];
    
    if (empty($title)) {
        $errors[] = '链接标题不能为空';
    } elseif (mb_strlen($title) > 100) {
        $errors[] = '标题不能超过100个字符';
    }
    
    if (empty($url)) {
        $errors[] = '链接地址不能为空';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = '请输入有效的URL地址';
    }
    
    if (mb_strlen($description) > 500) {
        $errors[] = '描述不能超过500个字符';
    }
    
    if ($category_id <= 0) {
        $errors[] = '请选择分类';
    } else {
        // 检查分类是否存在
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND is_active = 1");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $errors[] = '选择的分类不存在或已禁用';
        }
    }
    
    // 检查URL是否已存在
    if ($linkManager->isUrlExists($url)) {
        $errors[] = '该链接地址已存在';
    }
    
    // 处理图标
    $icon_filename = null;
    $icon_type = $_POST['final_icon_type'] ?? 'none';
    
    switch ($icon_type) {
        case 'fontawesome':
            $icon_class = trim($_POST['icon_fontawesome'] ?? '');
            $icon_color = trim($_POST['icon_fontawesome_color'] ?? '#000000');
            if ($icon_class) {
                // 验证Font Awesome图标类名格式（必须包含fas/far/fab前缀）
                if (!preg_match('/^fa[bsrlt]? [a-z0-9-]+$/', $icon_class)) {
                    $errors[] = '请输入有效的Font Awesome图标类名（例如：fas fa-home）';
                } else {
                    // 将图标类名和颜色组合成一个字符串存储在 icon_url 字段中
                    $icon_filename = $icon_class . '|' . $icon_color;
                }
            }
            break;
            
        case 'iconfont':
            $icon_iconfont = trim($_POST['icon_iconfont'] ?? '');
            if ($icon_iconfont) {
                $icon_filename = $icon_iconfont;
            } else {
                $errors[] = '请输入Iconfont图标类名';
            }
            break;
            
        case 'upload':
            // 检查是否有已上传的图片路径
            if (isset($_POST['uploaded_icon_path']) && !empty($_POST['uploaded_icon_path'])) {
                // 使用已上传的图片
                $icon_filename = $_POST['uploaded_icon_path'];
            } else {
                $errors[] = '请上传图片';
            }
            break;
            
        case 'url':
            $icon_url = trim($_POST['icon_url_input'] ?? '');
            if ($icon_url && filter_var($icon_url, FILTER_VALIDATE_URL)) {
                $icon_filename = $icon_url;
            } elseif ($icon_url) {
                $errors[] = '请输入有效的图标URL地址';
            } else {
                $errors[] = '请输入图标URL地址';
            }
            break;
            
        case 'none':
            // 无图标，不需要处理
            break;
    }
    
    // 如果没有错误，插入数据库
    if (empty($errors)) {
        try {
            // 准备链接数据
            $linkData = [
                'title' => $title,
                'url' => $url,
                'description' => $description,
                'category_id' => $category_id,
                'icon_type' => $_POST['final_icon_type'] ?? 'none',
                'icon_fontawesome' => $_POST['icon_fontawesome'] ?? '',
                'icon_fontawesome_color' => $_POST['icon_fontawesome_color'] ?? '',
                'icon_iconfont' => $_POST['icon_iconfont'] ?? '',
                'icon_url' => $_POST['icon_url_input'] ?? '',
                'icon_upload' => $_POST['uploaded_icon_path'] ?? '',
                'order_index' => $order_index,
                'is_active' => $is_active
            ];
            
            // 使用 NavigationLink 类创建链接
            $linkId = $linkManager->createLink($linkData);
            
            if ($linkId) {
                // 记录操作日志
                $logsManager = get_logs_manager();
                $logsManager->addLinkOperationLog(
                    $_SESSION['user_id'] ?? 0,
                    '新增',
                    $linkId,
                    $title,
                    [
                        'link_data' => $linkData
                    ]
                );
                
                // 重定向到成功页面
                header('Location: index.php?message=' . urlencode('链接添加成功'));
                exit();
            } else {
                $errors[] = '添加链接失败，请重试';
            }
        } catch (Exception $e) {
            $errors[] = '添加链接失败：' . $e->getMessage();
        }
    }
}

$page_title = '添加链接';
include '../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">仪表盘</a></li>
        <li class="breadcrumb-item"><a href="index.php">链接管理</a></li>
        <li class="breadcrumb-item active">添加链接</li>
    </ol>
</nav>

<div class="d-flex justify-content-end mb-4">
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 返回列表
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="linkForm">
            <!-- 第1行：链接标题 -->
            <div class="mb-3">
                <label for="title" class="form-label">链接标题 *</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                       maxlength="100" required>
                <div class="form-text">简短明了的标题，不超过100个字符</div>
            </div>
            
            <!-- 第2行：链接地址 -->
            <div class="mb-3">
                <label for="url" class="form-label">链接地址 *</label>
                <input type="url" class="form-control" id="url" name="url" 
                       value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>" required>
                <div class="form-text">请包含 http:// 或 https://</div>
            </div>
            
            <!-- 第3行：所属分类和排序权重 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category_id" class="form-label">所属分类 *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">选择分类</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="order_index" class="form-label">排序权重</label>
                        <input type="number" class="form-control" id="order_index" name="order_index" 
                               value="<?php echo intval($_POST['order_index'] ?? 0); ?>" 
                               min="0" max="999">
                        <div class="form-text">数字越大，排序越靠前</div>
                    </div>
                </div>
            </div>
            
            <!-- 第4行：描述 -->
            <div class="mb-3">
                <label for="description" class="form-label">描述</label>
                <textarea class="form-control" id="description" name="description" 
                          rows="3" maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="form-text">简要描述链接内容，不超过500个字符</div>
            </div>
            
            <!-- 链接图标模块 -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">链接图标</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <!-- 图标类型选择 -->
                            <div class="mb-3">
                                <label class="form-label">图标类型</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_fontawesome" value="fontawesome" <?php echo ($_POST['icon_type'] ?? 'fontawesome') === 'fontawesome' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_fontawesome">Font Awesome</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_iconfont" value="iconfont" <?php echo ($_POST['icon_type'] ?? 'fontawesome') === 'iconfont' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_iconfont">Iconfont</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_upload" value="upload" <?php echo ($_POST['icon_type'] ?? 'fontawesome') === 'upload' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_upload">上传图片</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_url" value="url" <?php echo ($_POST['icon_type'] ?? 'fontawesome') === 'url' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_url">URL地址</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_none" value="none" <?php echo ($_POST['icon_type'] ?? 'fontawesome') === 'none' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_none">无图标</label>
                                </div>
                            </div>

                            <!-- Font Awesome 图标 -->
                            <div id="fontawesome_section">
                                <label for="icon_fontawesome_input" class="form-label">Font Awesome 图标</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" id="icon_fontawesome_input" 
                                       value="<?php echo htmlspecialchars($default_icon ?? 'fas fa-link'); ?>" 
                                       placeholder="输入图标类名，如: fas fa-home">
                                    <button type="button" class="btn btn-outline-primary" onclick="openIconPicker()">
                                        <i class="fas fa-icons"></i> 选择图标
                                    </button>
                                </div>
                                
                                <label for="icon_fontawesome_color_input" class="form-label">图标颜色</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="icon_fontawesome_color_input" 
                                       value="<?php echo htmlspecialchars($default_color ?? '#000000'); ?>" 
                                       title="选择图标颜色">
                                
                                <!-- 隐藏字段用于保存图标和颜色 -->
                                <input type="hidden" id="icon_fontawesome" name="icon_fontawesome" value="">
                                <input type="hidden" id="icon_fontawesome_color" name="icon_fontawesome_color" value="">
                            </div>

                            <!-- Iconfont 图标输入 -->
                            <div id="iconfont_section" style="display: none;">
                                <label for="iconfont_icon" class="form-label">Iconfont 图标</label>
                                <input type="text" class="form-control" id="iconfont_icon" name="iconfont_icon" 
                                       placeholder="输入图标名称，如: icon-a-appround51" value="icon-a-appround51">
                                <small class="form-text text-muted">输入iconfont图标类名，如: icon-a-appround51</small>
                            </div>

                            <!-- 图片上传 -->
                            <div id="upload_section" style="display: none;">
                                <label for="icon_upload_file" class="form-label">上传图标</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="icon_upload_file" name="icon_upload" 
                                           accept="image/*">
                                    <button type="button" class="btn btn-outline-primary" id="upload_btn" disabled>
                                        <i class="bi bi-upload"></i> 上传
                                    </button>
                                </div>
                                <div class="form-text">支持 JPG、PNG、GIF、SVG 格式，建议尺寸 64x64 像素</div>
                                <div id="upload_status" class="mt-2"></div>
                                <!-- 隐藏字段用于保存已上传的图片路径 -->
                                <input type="hidden" id="uploaded_icon_path" name="uploaded_icon_path" value="">
                                <!-- 隐藏字段用于保存最终的图标类型 -->
                                <input type="hidden" id="final_icon_type" name="final_icon_type" value="">
                                <!-- 隐藏字段用于保存Iconfont图标 -->
                                <input type="hidden" id="icon_iconfont" name="icon_iconfont" value="">
                            </div>

                            <!-- URL输入 -->
                            <div id="url_section" style="display: none;">
                                <label for="icon_url" class="form-label">图标URL</label>
                                <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                       placeholder="https://example.com/icon.png" 
                                       value="<?php echo htmlspecialchars($_POST['icon_url'] ?? ''); ?>">
                                <div class="form-text">请输入有效的图片URL地址</div>
                                <!-- 隐藏字段用于保存图标URL -->
                                <input type="hidden" id="icon_url_input" name="icon_url_input" value="">
                            </div>
                            
                            <!-- 无图标 -->
                            <div id="none_section" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> 不显示图标
                                </div>
                            </div>
                        </div>

                        <!-- 图标预览 -->
                        <div class="col-md-5">
                            <label class="form-label">图标预览</label>
                            <div class="icon-preview-container text-center">
                                <div id="icon_preview" class="mb-2">
                                    <div class="text-muted">
                                        <i class="fas fa-image" style="font-size: 3rem;"></i>
                                        <p class="mt-2 mb-0">暂无图标</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 立即启用 -->
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                           value="1" <?php echo !isset($_POST['is_active']) || $_POST['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        立即启用
                    </label>
                </div>
            </div>
            
            <!-- 添加按钮 -->
            <div class="mb-3">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus"></i> 添加链接
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// 存储图标参数的对象
let iconParams = {
    icon_fontawesome: '<?php echo htmlspecialchars($default_icon ?? "fas fa-link"); ?>',
    icon_fontawesome_color: '<?php echo htmlspecialchars($default_color ?? "#000000"); ?>',
    icon_iconfont: 'icon-a-appround51',
    icon_upload: '',
    icon_url: ''
};

// Font Awesome 图标列表（已包含完整类名）
const fontAwesomeIcons = <?php echo json_encode($fontAwesomeIcons); ?>;

// 更新图标预览
function updatePreview() {
    const selectedType = document.querySelector('input[name="icon_type"]:checked').value;
    const previewContainer = document.getElementById('icon_preview');
    
    // 确保元素存在
    if (!previewContainer) {
        return;
    }
    
    switch(selectedType) {
        case 'fontawesome':
            const iconClass = iconParams.icon_fontawesome || 'fas fa-link';
            const iconColor = iconParams.icon_fontawesome_color || '#000000';
            previewContainer.innerHTML = `<i class="${iconClass}" style="color: ${iconColor}; font-size: 3rem;"></i>`;
            break;
            
        case 'iconfont':
            const iconfontValue = iconParams.icon_iconfont || 'icon-a-appround51';
            previewContainer.innerHTML = `<svg class="icon" aria-hidden="true" style="font-size: 3em;"><use xlink:href="#${iconfontValue}"></use></svg>`;
            
            // 确保iconfont SVG被正确渲染
            setTimeout(() => {
                const svg = previewContainer.querySelector('svg');
                if (svg) {
                    const use = svg.querySelector('use');
                    if (use && !use.getAttribute('xlink:href').startsWith('#')) {
                        use.setAttribute('xlink:href', '#' + iconfontValue);
                    }
                }
            }, 100);
            break;
            
        case 'upload':
            if (iconParams.icon_upload) {
                // 使用完整路径显示图片（不再添加 /uploads/links/ 前缀）
                previewContainer.innerHTML = `<img src="${iconParams.icon_upload}" alt="Uploaded Icon" style="max-width: 100px; max-height: 100px;">`;
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无图标</p></div>';
            }
            break;
            
        case 'url':
            if (iconParams.icon_url) {
                previewContainer.innerHTML = `<img src="${iconParams.icon_url}" alt="URL Icon" style="max-width: 100px; max-height: 100px;">`;
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-link" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请输入URL</p></div>';
            }
            break;
            
        default:
            previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-times" style="font-size: 2rem;"></i><p class="mt-2 mb-0">无图标</p></div>';
            break;
    }
}

// 上传图标
function uploadIcon(file) {
    const formData = new FormData();
    formData.append('icon_upload', file);
    formData.append('action', 'upload_icon');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 更新iconParams对象，使用完整路径
            iconParams.icon_upload = data.file_url;
            // 更新隐藏字段，使用完整路径
            document.getElementById('uploaded_icon_path').value = data.file_url;
            document.getElementById('upload_status').innerHTML = 
                '<div class="alert alert-success alert-dismissible fade show" role="alert">上传成功<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            updatePreview();
        } else {
            document.getElementById('upload_status').innerHTML = 
                '<div class="alert alert-danger alert-dismissible fade show" role="alert">上传失败: ' + data.error + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    })
    .catch(error => {
        document.getElementById('upload_status').innerHTML = 
            '<div class="alert alert-danger alert-dismissible fade show" role="alert">上传出错: ' + error.message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    });
}

// 打开Font Awesome图标选择器
function openIconPicker() {
    const modalDiv = document.createElement('div');
    modalDiv.className = 'modal fade';
    modalDiv.tabIndex = -1;
    modalDiv.setAttribute('aria-hidden', 'true');
    
    let iconGridHTML = '';
    fontAwesomeIcons.forEach(icon => {
        iconGridHTML += `
            <div class="col-2">
                <button type="button" class="btn btn-outline-secondary w-100 icon-btn" 
                        onclick="selectIcon('${icon}')" title="${icon}">
                    <i class="${icon} fa-lg"></i>
                </button>
            </div>`;
    });
    
    modalDiv.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">选择 Font Awesome 图标</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="iconSearch" placeholder="搜索图标... (使用英文单词搜索)">
                    </div>
                    <div class="row g-2" id="iconGrid" style="max-height: 400px; overflow-y: auto;">
                        ${iconGridHTML}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modalDiv);
    
    const modalInstance = new bootstrap.Modal(modalDiv);
    modalInstance.show();
    
    modalDiv.addEventListener('hidden.bs.modal', function() {
        modalDiv.remove();
    });
    
    document.getElementById('iconSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const buttons = document.querySelectorAll('.icon-btn');
        buttons.forEach(btn => {
            const iconName = btn.getAttribute('title');
            btn.parentElement.style.display = iconName.includes(searchTerm) ? 'block' : 'none';
        });
    });
}

// 选择图标 - 直接使用完整的图标类名，不再添加fa-前缀
function selectIcon(iconName) {
    // 直接使用完整的图标类名
    document.getElementById('icon_fontawesome_input').value = iconName;
    // 同步更新iconParams对象
    iconParams.icon_fontawesome = iconName;
    updatePreview();
    
    const modalElement = document.querySelector('.modal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        } else {
            modalElement.remove();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 初始化图标显示
    const iconSections = {
        fontawesome: document.getElementById('fontawesome_section'),
        iconfont: document.getElementById('iconfont_section'),
        upload: document.getElementById('upload_section'),
        url: document.getElementById('url_section'),
        none: document.getElementById('none_section')
    };
    
    // 隐藏所有区域
    Object.values(iconSections).forEach(section => section.style.display = 'none');
    
    // 显示默认选中区域
    const selectedType = '<?php echo ($_POST["icon_type"] ?? "fontawesome"); ?>';
    if (iconSections[selectedType]) {
        iconSections[selectedType].style.display = 'block';
    }
    document.getElementById('final_icon_type').value = selectedType;
    
    // 初始化表单字段值
    document.getElementById('icon_fontawesome_input').value = iconParams.icon_fontawesome;
    document.getElementById('icon_fontawesome_color_input').value = iconParams.icon_fontawesome_color;
    document.getElementById('iconfont_icon').value = iconParams.icon_iconfont;
    
    // 初始化图标预览
    updatePreview();
    
    // 图标类型切换
    const iconTypeRadios = document.querySelectorAll('input[name="icon_type"]');
    
    iconTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // 隐藏所有区域
            Object.values(iconSections).forEach(section => section.style.display = 'none');
            
            // 显示选中区域
            if (iconSections[this.value]) {
                iconSections[this.value].style.display = 'block';
            }
            
            // 更新隐藏字段
            document.getElementById('final_icon_type').value = this.value;
            
            // 更新预览
            updatePreview();
        });
    });
    
    // 监听Font Awesome图标和颜色变化
    document.getElementById('icon_fontawesome_input').addEventListener('input', function() {
        iconParams.icon_fontawesome = this.value;
        updatePreview();
    });
    
    // 监听Iconfont图标变化
    document.getElementById('iconfont_icon').addEventListener('input', function() {
        iconParams.icon_iconfont = this.value;
        updatePreview();
    });
    
    document.getElementById('icon_fontawesome_color_input').addEventListener('input', function() {
        iconParams.icon_fontawesome_color = this.value;
        updatePreview();
    });
    
    // 监听URL输入变化
    document.getElementById('icon_url').addEventListener('input', function() {
        iconParams.icon_url = this.value;
        updatePreview();
    });
    
    // 监听文件选择
    const fileInput = document.getElementById('icon_upload_file');
    const uploadBtn = document.getElementById('upload_btn');
    
    if (fileInput && uploadBtn) {
        fileInput.addEventListener('change', function() {
            uploadBtn.disabled = this.files.length === 0;
        });
        
        // 监听上传按钮点击
        uploadBtn.addEventListener('click', function() {
            if (fileInput.files.length > 0) {
                uploadIcon(fileInput.files[0]);
            }
        });
    }
    
    // 监听表单提交
    const linkForm = document.getElementById('linkForm');
    if (linkForm) {
        linkForm.addEventListener('submit', function(e) {
            // 获取当前选中的图标类型
            const selectedIconType = document.querySelector('input[name="icon_type"]:checked')?.value || 'none';
            
            // 更新隐藏字段
            document.getElementById('final_icon_type').value = selectedIconType;
            
            // 同步所有图标参数到隐藏字段（实现第7点要求）
            document.getElementById('icon_fontawesome').value = iconParams.icon_fontawesome || '';
            document.getElementById('icon_fontawesome_color').value = iconParams.icon_fontawesome_color || '';
            document.getElementById('icon_iconfont').value = iconParams.icon_iconfont || '';
            document.getElementById('uploaded_icon_path').value = iconParams.icon_upload || '';
            document.getElementById('icon_url_input').value = iconParams.icon_url || '';
            
            // 验证当前选中的标签页（实现第8、9点要求）
            if (selectedIconType === 'url' && (!iconParams.icon_url || !iconParams.icon_url.trim())) {
                e.preventDefault();
                alert('请填写网络图片地址');
                return false;
            }
            
            if (selectedIconType === 'upload' && (!iconParams.icon_upload || !iconParams.icon_upload.trim())) {
                e.preventDefault();
                alert('请上传图片');
                return false;
            }
        });
    }
});
</script>

<?php include '../templates/footer.php'; ?>