<?php
require_once '../includes/load.php';
require_once '../includes/fontawesome-icons.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$pdo = get_db_connection();

// 获取链接ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

// 获取链接信息
$stmt = $pdo->prepare("SELECT * FROM navigation_links WHERE id = ?");
$stmt->execute([$id]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    $_SESSION['error'] = '链接不存在';
    header('Location: index.php');
    exit();
}

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY order_index ASC, name ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $display_order = intval($_POST['order_index'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $delete_icon = isset($_POST['delete_icon']) ? 1 : 0;
    
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
    
    // 检查URL是否已存在（排除当前链接）
    $stmt = $pdo->prepare("SELECT id FROM navigation_links WHERE url = ? AND id != ?");
    $stmt->execute([$url, $id]);
    if ($stmt->fetch()) {
        $errors[] = '该链接地址已存在';
    }
    
    // 处理图标
    $icon_filename = $link['icon_url'];
    $icon_type = $_POST['icon_type'] ?? 'none';
    
    switch ($icon_type) {
        case 'fontawesome':
            $icon_class = trim($_POST['icon_fontawesome_class'] ?? '');
            $icon_color = trim($_POST['icon_color'] ?? '#007bff');
            
            if (!empty($icon_class)) {
                // 验证图标类名格式
                if (!preg_match('/^[a-zA-Z0-9-]+$/', $icon_class)) {
                    $errors[] = '图标类名格式无效，只能包含字母、数字和连字符';
                } else {
                    // 删除旧图标（如果是本地文件）
                    if ($link['icon_url'] && !filter_var($link['icon_url'], FILTER_VALIDATE_URL) && strpos($link['icon_url'], '|') === false) {
                        $old_icon_path = '../uploads/links/' . $link['icon_url'];
                        if (file_exists($old_icon_path)) {
                            unlink($old_icon_path);
                        }
                    }
                    $icon_filename = $icon_class . '|' . $icon_color;
                }
            } else {
                $icon_filename = null;
            }
            break;
            
        case 'upload':
            if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
                $fileUpload = get_file_upload_manager('links');
                $upload_result = $fileUpload->upload($_FILES['icon_upload']);
                if ($upload_result['success']) {
                    // 删除旧图标（如果是本地文件）
                    if ($link['icon_url'] && !filter_var($link['icon_url'], FILTER_VALIDATE_URL) && strpos($link['icon_url'], '|') === false) {
                        $old_icon_path = '../uploads/links/' . $link['icon_url'];
                        if (file_exists($old_icon_path)) {
                            unlink($old_icon_path);
                        }
                    }
                    $icon_filename = $upload_result['file_name'];
                } else {
                    $errors[] = $upload_result['error'];
                }
            }
            break;
            
        case 'url':
            $icon_url = trim($_POST['icon_url'] ?? '');
            if ($icon_url) {
                if (!filter_var($icon_url, FILTER_VALIDATE_URL)) {
                    $errors[] = '请输入有效的图标URL地址';
                } else {
                    // 删除旧图标（如果是本地文件）
                    if ($link['icon_url'] && !filter_var($link['icon_url'], FILTER_VALIDATE_URL) && strpos($link['icon_url'], '|') === false) {
                        $old_icon_path = '../uploads/links/' . $link['icon_url'];
                        if (file_exists($old_icon_path)) {
                            unlink($old_icon_path);
                        }
                    }
                    $icon_filename = $icon_url;
                }
            } else {
                $icon_filename = null;
            }
            break;
            
        case 'none':
        default:
            // 删除旧图标（如果是本地文件）
            if ($link['icon_url'] && !filter_var($link['icon_url'], FILTER_VALIDATE_URL) && strpos($link['icon_url'], '|') === false) {
                $old_icon_path = '../uploads/links/' . $link['icon_url'];
                if (file_exists($old_icon_path)) {
                    unlink($old_icon_path);
                }
            }
            $icon_filename = null;
            break;
    }
    
    // 如果没有错误，更新数据
    if (empty($errors)) {
        try {
            // 获取图标相关数据
            $icon_type = $_POST['icon_type'] ?? 'none';
            $icon_fontawesome = $_POST['icon_fontawesome_class'] ?? '';
            $icon_color = $_POST['icon_color'] ?? '';
            $icon_url = $_POST['icon_url'] ?? '';
            
            // 处理不同类型的图标
            $icon_data = [
                'icon_type' => $icon_type,
                'icon_fontawesome' => $icon_fontawesome,
                'icon_fontawesome_color' => $icon_color,
                'icon_url' => $icon_url,
                'icon_upload' => $icon_filename
            ];
            
            // 更新数据库
            $stmt = $pdo->prepare("UPDATE navigation_links SET 
                                  title = ?, url = ?, description = ?, category_id = ?, 
                                  icon_type = ?, icon_fontawesome = ?, icon_fontawesome_color = ?, 
                                  icon_url = ?, icon_upload = ?, 
                                  order_index = ?, is_active = ?, updated_at = NOW() 
                                  WHERE id = ?");
            $stmt->execute([
                $title,
                $url,
                $description,
                $category_id,
                $icon_data['icon_type'],
                $icon_data['icon_fontawesome'],
                $icon_data['icon_fontawesome_color'],
                $icon_data['icon_url'],
                $icon_data['icon_upload'],
                $display_order,
                $is_active,
                $id
            ]);
            
            $_SESSION['success'] = '链接更新成功';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $errors[] = '数据库错误：' . $e->getMessage();
            
            // 如果上传了图标但更新失败，删除图标文件
            if ($icon_filename && $icon_filename != $link['icon_url']) {
                $icon_path = '../uploads/links/' . $icon_filename;
                if (file_exists($icon_path)) {
                    unlink($icon_path);
                }
            }
        }
    }
}

$page_title = '编辑链接';
include '../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">仪表盘</a></li>
        <li class="breadcrumb-item"><a href="index.php">链接管理</a></li>
        <li class="breadcrumb-item active">编辑链接</li>
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
                       value="<?php echo htmlspecialchars($link['title']); ?>" 
                       maxlength="100" required>
                <div class="form-text">简短明了的标题，不超过100个字符</div>
            </div>
            
            <!-- 第2行：链接地址 -->
            <div class="mb-3">
                <label for="url" class="form-label">链接地址 *</label>
                <input type="url" class="form-control" id="url" name="url" 
                       value="<?php echo htmlspecialchars($link['url']); ?>" required>
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
                                        <?php echo $link['category_id'] == $category['id'] ? 'selected' : ''; ?>>
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
                               value="<?php echo intval($link['order_index']); ?>" 
                               min="0" max="999">
                        <div class="form-text">数字越大，排序越靠前</div>
                    </div>
                </div>
            </div>
            
            <!-- 第4行：描述 -->
            <div class="mb-3">
                <label for="description" class="form-label">描述</label>
                <textarea class="form-control" id="description" name="description" 
                          rows="3" maxlength="500"><?php echo htmlspecialchars($link['description']); ?></textarea>
                <div class="form-text">简要描述链接内容，不超过500个字符</div>
            </div>
            
            <!-- 第5行：链接图标模块 -->
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
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_fontawesome" value="fontawesome" <?php echo !empty($link['icon_url']) && strpos($link['icon_url'] ?? '', '|') !== false ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_fontawesome">Font Awesome</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_upload" value="upload" <?php echo !empty($link['icon_url']) && strpos($link['icon_url'] ?? '', '|') === false && !filter_var($link['icon_url'], FILTER_VALIDATE_URL) ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_upload">上传图片</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_url" value="url" <?php echo !empty($link['icon_url']) && filter_var($link['icon_url'] ?? '', FILTER_VALIDATE_URL) ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_url">URL地址</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_none" value="none" <?php echo empty($link['icon_url'] ?? '') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_none">无图标</label>
                                </div>
                            </div>

                            <!-- Font Awesome 图标选择 -->
                            <div id="fontawesome_section" style="display: none;">
                                <div class="row">
                                    <div class="col-8">
                                        <label for="icon_fontawesome_class" class="form-label">选择图标</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="icon_fontawesome_class" name="icon_fontawesome_class" 
                                                   placeholder="例如：fa-home" 
                                                   value="<?php echo !empty($link['icon_url']) && strpos($link['icon_url'] ?? '', '|') !== false ? explode('|', $link['icon_url'] ?? '')[0] : ''; ?>">
                                            <button type="button" class="btn btn-outline-secondary" id="openIconPicker">
                                                <i class="fas fa-icons"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label for="icon_color" class="form-label">图标颜色</label>
                                        <input type="color" class="form-control form-control-color w-100" id="icon_color" 
                                               name="icon_color" value="<?php echo !empty($link['icon_url']) && strpos($link['icon_url'] ?? '', '|') !== false ? (@explode('|', $link['icon_url'] ?? '')[1] ?: '#007bff') : '#007bff'; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- 图片上传 -->
                            <div id="upload_section" style="display: none;">
                                <label for="icon_upload_file" class="form-label">上传图标</label>
                                <input type="file" class="form-control" id="icon_upload_file" name="icon_upload" 
                                       accept="image/*">
                                <div class="form-text">支持 JPG、PNG、GIF、SVG 格式，建议尺寸 64x64 像素</div>
                                <?php if (!empty($link['icon_url']) && strpos($link['icon_url'] ?? '', '|') === false && !filter_var($link['icon_url'] ?? '', FILTER_VALIDATE_URL)): ?>
                                    <div class="mt-2">
                                        <img src="../uploads/links/<?php echo htmlspecialchars($link['icon_url']); ?>" 
                                             style="max-width: 50px; max-height: 50px; border: 1px solid #ddd; border-radius: 4px;">
                                        <small class="text-muted d-block">当前图标</small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- URL输入 -->
                            <div id="url_section" style="display: none;">
                                <label for="icon_url_input" class="form-label">图标URL</label>
                                <input type="url" class="form-control" id="icon_url_input" name="icon_url" 
                                       placeholder="https://example.com/icon.png" 
                                       value="<?php echo !empty($link['icon_url']) && filter_var($link['icon_url'] ?? '', FILTER_VALIDATE_URL) ? htmlspecialchars($link['icon_url'] ?? '') : ''; ?>">
                                <div class="form-text">请输入有效的图片URL地址</div>
                            </div>
                        </div>

                        <!-- 图标预览 -->
                        <div class="col-md-5">
                            <label class="form-label">图标预览</label>
                            <div class="icon-preview-container text-center">
                                <div id="iconPreview" class="mb-2">
                                    <?php if ($link['icon_url'] ?? ''): ?>
                                    <?php if (strpos($link['icon_url'] ?? '', '|') !== false): ?>
                                            <?php 
                                                $iconInfo = explode('|', $link['icon_url'] ?? '');
                                                $iconClass = $iconInfo[0] ?? 'fa-link';
                                                $iconColor = $iconInfo[1] ?? '#007bff';
                                            ?>
                                            <i class="fas <?php echo htmlspecialchars($iconClass); ?>" style="font-size: 3rem; color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                                        <?php elseif (filter_var($link['icon_url'] ?? '', FILTER_VALIDATE_URL)): ?>
                                            <img src="<?php echo htmlspecialchars($link['icon_url'] ?? ''); ?>" class="image-preview" style="max-width: 100px; max-height: 100px;">
                                        <?php else: ?>
                                            <img src="../uploads/links/<?php echo htmlspecialchars($link['icon_url'] ?? ''); ?>" class="image-preview" style="max-width: 100px; max-height: 100px;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-muted">
                                            <i class="fas fa-image" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">暂无图标</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 第6行：立即启用 -->
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                           value="1" <?php echo $link['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        立即启用
                    </label>
                </div>
            </div>
            
            <!-- 第7行：保存修改按钮 -->
            <div class="mb-3">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> 保存修改
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// 图标类型切换
function updateIconSections() {
    const iconTypeRadios = document.querySelectorAll('input[name="icon_type"]');
    let iconType = 'none';
    for (const radio of iconTypeRadios) {
        if (radio.checked) {
            iconType = radio.value;
            break;
        }
    }
    
    const fontawesomeSection = document.getElementById('fontawesome_section');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');
    
    if (fontawesomeSection) fontawesomeSection.style.display = iconType === 'fontawesome' ? 'block' : 'none';
    if (uploadSection) uploadSection.style.display = iconType === 'upload' ? 'block' : 'none';
    if (urlSection) urlSection.style.display = iconType === 'url' ? 'block' : 'none';
    
    updatePreview();
}

// 更新预览
function updatePreview() {
    const iconTypeRadios = document.querySelectorAll('input[name="icon_type"]');
    let iconType = 'none';
    for (const radio of iconTypeRadios) {
        if (radio.checked) {
            iconType = radio.value;
            break;
        }
    }
    
    const color = document.getElementById('icon_color')?.value || '#007bff';
    const preview = document.getElementById('iconPreview');
    if (!preview) return;
    
    let previewHtml = '';
    
    switch (iconType) {
        case 'fontawesome':
            const iconClass = document.getElementById('icon_fontawesome_class')?.value || 'fa-link';
            if (iconClass) {
                previewHtml = `<i class="fas ${iconClass}" style="font-size: 3rem; color: ${color}"></i>`;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-icons" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请选择图标</p></div>';
            }
            break;
            
        case 'upload':
            const uploadFile = document.getElementById('icon_upload_file')?.files?.[0];
            if (uploadFile) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="image-preview" style="max-width: 100px; max-height: 100px;">`;
                }
                reader.readAsDataURL(uploadFile);
                return;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无图标</p></div>';
            }
            break;
            
        case 'url':
            const url = document.getElementById('icon_url_input')?.value;
            if (url) {
                previewHtml = `<img src="${url}" class="image-preview" style="max-width: 100px; max-height: 100px;" onerror="this.onerror=null; this.innerHTML='<i class=\'fas fa-link\' style=\'font-size: 2rem; color: #6c757d;\'></i>';">`;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-link" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请输入URL</p></div>';
            }
            break;
            
        case 'none':
            previewHtml = '<div class="text-muted"><i class="fas fa-times" style="font-size: 2rem;"></i><p class="mt-2 mb-0">无图标</p></div>';
            break;
    }
    
    preview.innerHTML = previewHtml;
}

// 事件监听
document.querySelectorAll('input[name="icon_type"]').forEach(radio => {
    radio?.addEventListener('change', updateIconSections);
});

document.getElementById('icon_color')?.addEventListener('input', updatePreview);
document.getElementById('icon_fontawesome_class')?.addEventListener('input', updatePreview);
document.getElementById('icon_url_input')?.addEventListener('input', updatePreview);
document.getElementById('icon_upload_file')?.addEventListener('change', updatePreview);

// Font Awesome 图标选择器
function openIconPicker() {
    // 创建模态框容器
    const modalDiv = document.createElement('div');
    modalDiv.className = 'modal fade';
    modalDiv.tabIndex = -1;
    modalDiv.setAttribute('aria-hidden', 'true');
    
    // 构建图标网格HTML
    let iconGridHTML = '';
    fontAwesomeIcons.forEach(icon => {
        iconGridHTML += `
            <div class="col-2">
                <button type="button" class="btn btn-outline-secondary w-100 icon-btn" 
                        onclick="selectIcon('${icon}')" title="${icon}">
                    <i class="fas fa-${icon} fa-lg"></i>
                </button>
            </div>`;
    });
    
    // 设置模态框内容
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
    
    // 添加到页面body中
    document.body.appendChild(modalDiv);
    
    // 初始化并显示模态框
    const modalInstance = new bootstrap.Modal(modalDiv);
    modalInstance.show();
    
    // 添加模态框隐藏事件监听器
    modalDiv.addEventListener('hidden.bs.modal', function() {
        modalDiv.remove();
    });
    
    // 搜索功能
    document.getElementById('iconSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const buttons = document.querySelectorAll('.icon-btn');
        buttons.forEach(btn => {
            const iconName = btn.getAttribute('title');
            btn.parentElement.style.display = iconName.includes(searchTerm) ? 'block' : 'none';
        });
    });
}

// 选择图标
function selectIcon(iconName) {
    // 保存完整的图标类名（包含fa-前缀）
    document.getElementById('icon_fontawesome_class').value = 'fa-' + iconName;
    updatePreview();
    
    // 正确隐藏模态框，确保背景遮罩层也被清除
    const modalElement = document.querySelector('.modal');
    if (modalElement) {
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        } else {
            // 如果实例不存在，直接移除元素
            modalElement.remove();
        }
    }
}

document.getElementById('openIconPicker').addEventListener('click', function() {
    openIconPicker();
});

// 初始化 Font Awesome 图标数组
const fontAwesomeIcons = <?php echo json_encode(getFontAwesomeIcons()); ?>;

// 初始化
if (typeof updateIconSections === 'function') {
    updateIconSections();
}

// 检测当前图标类型并设置默认选择
<?php
if ($link['icon_url']) {
    if (strpos($link['icon_url'], '|') !== false) {
        // Font Awesome 图标
        $parts = explode('|', $link['icon_url']);
        echo "document.getElementById('icon_fontawesome').checked = true;";
        echo "if(document.getElementById('icon_fontawesome_class')) document.getElementById('icon_fontawesome_class').value = '" . addslashes($parts[0]) . "';";
        echo "if(document.getElementById('icon_color')) document.getElementById('icon_color').value = '" . addslashes($parts[1] ?? '#007bff') . "';";
    } elseif (filter_var($link['icon_url'], FILTER_VALIDATE_URL)) {
        // URL 图标
        echo "document.getElementById('icon_url').checked = true;";
    } else {
        // 上传图标
        echo "document.getElementById('icon_upload').checked = true;";
    }
} else {
    echo "document.getElementById('icon_none').checked = true;";
}
?>

updateIconSections();

// 预览URL图片
function previewUrlImage(url) {
    if (url && isValidUrl(url)) {
        const preview = document.getElementById('url_preview');
        const previewImg = document.getElementById('url_preview_img');
        preview.style.display = 'block';
        previewImg.src = url;
        
        // 检查图片是否有效
        previewImg.onerror = function() {
            preview.style.display = 'none';
        }
    } else {
        document.getElementById('url_preview').style.display = 'none';
    }
}

// 清除上传
function clearUpload() {
    document.getElementById('icon_upload_file').value = '';
    document.getElementById('upload_preview').style.display = 'none';
    document.getElementById('upload_preview_img').src = '';
}

// 清除URL
function clearUrl() {
    document.getElementById('icon_url_input').value = '';
    document.getElementById('url_preview').style.display = 'none';
    document.getElementById('url_preview_img').src = '';
}

// 验证URL
function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// 表单验证
(function() {
    'use strict';
    var form = document.getElementById('linkForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php include '../templates/footer.php'; ?>