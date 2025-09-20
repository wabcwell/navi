<?php
require_once '../includes/load.php';
require_once '../includes/fontawesome-icons.php';

// 获取链接ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

//  获取链接信息
$navigationLinkManager = get_navigation_link_manager();
$link = $navigationLinkManager->getLinkById($id);
if (!$link) {
    header('Location: index.php');
    exit;
}

// 获取所有分类
$categoryManager = get_category_manager();
$categories = $categoryManager->getAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否是文件上传请求
    if (isset($_POST['action']) && $_POST['action'] === 'upload_icon' && isset($_FILES['icon_upload'])) {
        // 处理图标上传
        $uploadResult = handleFileUpload($_FILES['icon_upload'], 'links');
        if ($uploadResult['success']) {
            // 返回JSON响应
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'file_url' => $uploadResult['path'],  // 已经包含/uploads/前缀
                'file_name' => basename($uploadResult['path']),
                'message' => '上传成功'
            ]);
            exit;
        } else {
            // 返回JSON响应
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $uploadResult['message']
            ]);
            exit;
        }
    }
    
    // 验证和处理表单数据
    $data = [
        'title' => $_POST['title'] ?? '',
        'url' => $_POST['url'] ?? '',
        'category_id' => $_POST['category_id'] ?? '',
        'description' => $_POST['description'] ?? '',
        'icon_type' => $_POST['icon_type'] ?? 'none',
        'icon_fontawesome' => $_POST['icon_fontawesome'] ?? '',
        'icon_fontawesome_color' => $_POST['icon_fontawesome_color'] ?? '#007bff',
        'icon_iconfont' => $_POST['iconfont_icon'] ?? '', // Iconfont图标
        'icon_url' => $_POST['icon_url'] ?? '',
        'icon_upload' => $_POST['uploaded_icon_path'] ?? '', // 从隐藏字段获取上传的图标路径
        'order_index' => intval($_POST['order_index'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // 处理图标删除
    if (isset($_POST['remove_icon']) && $_POST['remove_icon'] == '1') {
        // 如果有上传的图标文件，删除它
        if (!empty($link['icon_upload'])) {
            $uploadPath = '../../uploads/links/' . basename($link['icon_upload']);
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
        }
        $data['icon_upload'] = '';
    }
    
    
    
    // 更新链接
    if ($navigationLinkManager->updateLink($id, $data)) {
        // 记录操作日志
        $logsManager = get_logs_manager();
        $logsManager->addLinkOperationLog(
            $_SESSION['user_id'] ?? 0,
            '编辑',
            $id,
            $data['title'],
            [
                'before' => $link,
                'after' => $data
            ]
        );
        header('Location: index.php?success=1');
        exit;
    } else {
        $error = '更新失败';
    }
}

// 处理文件上传
function handleFileUpload($file, $type) {
    $uploadDir = '../../uploads/' . $type . '/';
    
    // 确保上传目录存在
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 检查文件类型
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    // 检查文件大小（最大2MB）
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => '文件大小超过限制'];
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // 移动上传文件
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // 返回包含/uploads/前缀的完整路径
        return ['success' => true, 'path' => '/uploads/' . $type . '/' . $filename];
    } else {
        return ['success' => false, 'message' => '文件上传失败'];
    }
}
?>

<?php 
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

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="linkForm">
                <!-- 第1行：链接标题 -->
                <div class="mb-3">
                    <label for="title" class="form-label">链接标题 *</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo htmlspecialchars($link['title']); ?>" required maxlength="100">
                    <div class="form-text">链接的显示名称，最多100个字符</div>
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
                                <div class="btn-group w-100 mb-3" role="group">
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_fontawesome" value="fontawesome" <?php echo ($link['icon_type'] ?? 'none') === 'fontawesome' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_fontawesome">Font Awesome</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_iconfont" value="iconfont" <?php echo ($link['icon_type'] ?? 'none') === 'iconfont' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_iconfont">Iconfont</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_upload" value="upload" <?php echo ($link['icon_type'] ?? 'none') === 'upload' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_upload">上传图片</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_url" value="url" <?php echo ($link['icon_type'] ?? 'none') === 'url' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_url">填写URL</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_type_none" value="none" <?php echo ($link['icon_type'] ?? 'none') === 'none' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_type_none">无图标</label>
                                </div>
                                
                                <!-- 隐藏字段 -->
                                <input type="hidden" name="current_icon" value="<?php 
                                    if ($link['icon_type'] === 'upload' && !empty($link['icon_upload'])) {
                                        echo htmlspecialchars($link['icon_upload']);
                                    } elseif ($link['icon_type'] === 'url' && !empty($link['icon_url'])) {
                                        echo htmlspecialchars($link['icon_url']);
                                    } elseif ($link['icon_type'] === 'fontawesome' && !empty($link['icon_fontawesome'])) {
                                        echo htmlspecialchars($link['icon_fontawesome']);
                                    } elseif ($link['icon_type'] === 'iconfont' && !empty($link['icon_iconfont'])) {
                                        echo htmlspecialchars($link['icon_iconfont']);
                                    } else {
                                        echo '';
                                    }
                                ?>">
                                <input type="hidden" name="remove_icon" value="0">
                                <input type="hidden" id="uploaded_icon_path" name="uploaded_icon_path" value="<?php echo htmlspecialchars($link['icon_upload'] ?? ''); ?>">
                                <!-- 添加final_icon和final_icon_type隐藏字段 -->
                                <input type="hidden" id="final_icon" name="final_icon" value="">
                                <input type="hidden" id="final_icon_type" name="final_icon_type" value="<?php echo $link['icon_type'] ?? 'none'; ?>">
                                <input type="hidden" id="iconfont_value" name="iconfont_value" value="<?php echo htmlspecialchars($link['icon_iconfont'] ?? ''); ?>">
                                
                                <!-- Font Awesome 图标选择 -->
                                <div id="fontawesome_section" class="icon-section" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <label for="icon_fontawesome" class="form-label">选择图标</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="icon_fontawesome" name="icon_fontawesome" 
                                                       placeholder="点击选择图标" readonly
                                                       value="<?php echo htmlspecialchars($link['icon_fontawesome'] ?? ''); ?>">
                                                <button type="button" class="btn btn-outline-secondary" id="openIconPicker">
                                                    <i class="fas fa-icons"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="icon_color" class="form-label">图标颜色</label>
                                            <input type="color" class="form-control form-control-color w-100" id="icon_color" name="icon_color" 
                                                   value="<?php echo htmlspecialchars($link['icon_fontawesome_color'] ?? '#007bff'); ?>" style="height: 38px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Iconfont 图标输入 -->
                                <div id="iconfont_section" class="icon-section" style="display: none;">
                                    <label for="iconfont_icon" class="form-label">Iconfont 图标</label>
                                    <input type="text" class="form-control" id="iconfont_icon" name="iconfont_icon" 
                                           placeholder="输入图标名称，如: icon-a-appround51"
                                           value="<?php echo htmlspecialchars($link['icon_iconfont'] ?? ''); ?>">
                                    <small class="form-text text-muted">输入iconfont图标类名，如: icon-a-appround51</small>
                                </div>

                                <!-- 上传图片 -->
                                <div id="upload_section" class="icon-section" style="display: none;">
                                    <label for="icon_upload_file" class="form-label">上传图标</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="icon_upload_file" name="icon_upload_file" 
                                               accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                                               onchange="this.nextElementSibling.value = this.files[0]?.name || '';">
                                        <button type="button" class="btn btn-outline-primary" id="upload_btn">
                                            <i class="bi bi-upload"></i> 上传
                                        </button>
                                    </div>
                                    <input type="text" class="form-control mt-2" id="uploaded_file_display" 
                                           placeholder="已上传文件路径" readonly style="background-color: #f8f9fa;"
                                           value="<?php echo !empty($link['icon_upload']) ? htmlspecialchars(basename($link['icon_upload'])) : ''; ?>">
                                    <small class="form-text text-muted">支持 JPG, PNG, GIF, WebP, SVG 格式，最大 2MB</small>
                                    <div id="upload_status" class="mt-2"></div>
                                </div>

                                <!-- URL输入 -->
                                <div id="url_section" class="icon-section" style="display: none;">
                                    <label for="icon_url" class="form-label">图标URL</label>
                                    <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                           placeholder="https://example.com/icon.png" 
                                           value="<?php echo htmlspecialchars($link['icon_url'] ?? ''); ?>">
                                    <div class="form-text">请输入有效的图片URL地址</div>
                                </div>
                            </div>

                            <!-- 图标预览 -->
                            <div class="col-md-5">
                                <label class="form-label">图标预览</label>
                                <div class="icon-preview-container text-center">
                            <div id="iconPreview" class="mb-2">
                                <?php if (!empty($link['icon_fontawesome'])): ?>
                                    <i class="fas <?php echo $link['icon_fontawesome']; ?>" style="font-size: 3rem; color: <?php echo htmlspecialchars($link['icon_fontawesome_color'] ?? '#007bff'); ?>"></i>
                                <?php elseif (!empty($link['icon_iconfont'])): ?>
                                    <svg class="icon" aria-hidden="true" style="font-size: 3em;">
                                        <use xlink:href="#<?php echo htmlspecialchars($link['icon_iconfont']); ?>"></use>
                                    </svg>
                                <?php elseif (!empty($link['icon_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($link['icon_url']); ?>" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                <?php elseif (!empty($link['icon_upload'])): ?>
                                    <img src="/uploads/links/<?php echo $link['icon_upload']; ?>" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
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
</div>

<script>
// 初始化 Font Awesome 图标数组
const fontAwesomeIcons = <?php echo json_encode(getFontAwesomeIcons()); ?>;

// 图标参数对象
const iconParams = {
    icon_fontawesome: document.getElementById('icon_fontawesome')?.value || '',
    icon_fontawesome_color: document.getElementById('icon_color')?.value || '#000000',
    icon_iconfont: document.getElementById('iconfont_icon')?.value || '',
    icon_upload: document.getElementById('uploaded_icon_path')?.value || '',
    icon_url: document.getElementById('icon_url')?.value || ''
};

// 更新已上传图标的预览
function updateUploadedIconPreview(imagePath) {
    const preview = document.getElementById('iconPreview');
    
    if (imagePath) {
        // 确保图片路径是完整的URL或相对路径
        let fullImagePath = imagePath;
        if (imagePath.startsWith('../')) {
            // 如果路径以../开头，替换为正确的绝对路径
            fullImagePath = imagePath.replace('../', '/uploads/');
        } else if (!imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            // 如果是相对路径，添加正确的前缀
            fullImagePath = '/uploads/' + imagePath;
        }
        // 如果已经是绝对路径（以/开头）或完整URL（以http开头），则不需要修改
        
        preview.innerHTML = `<img src="${fullImagePath}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
    }
}

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
    
    modalDiv.addEventListener('hidden.bs.modal', function() {
        modalDiv.remove();
    });
}

// 选择图标
function selectIcon(iconName) {
    // 保存完整的图标类名（包含fa-前缀）
    document.getElementById('icon_fontawesome').value = 'fa-' + iconName;
    // 同步更新iconParams对象
    iconParams.icon_fontawesome = 'fa-' + iconName;
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

// 更新图标预览
function updatePreview() {
    const iconTypeElement = document.querySelector('input[name="icon_type"]:checked');
    if (!iconTypeElement) return; // 如果没有选中的图标类型，直接返回
    
    const iconType = iconTypeElement.value;
    const previewContainer = document.getElementById('iconPreview');
    if (!previewContainer) return; // 如果预览容器不存在，直接返回
    
    switch(iconType) {
        case 'fontawesome':
            const iconFontAwesome = document.getElementById('icon_fontawesome');
            const iconValue = iconFontAwesome ? (iconFontAwesome.value || 'fa-folder') : 'fa-folder';
            // 使用完整的图标类名（带fa-前缀）
            const iconName = iconValue.replace(/^fa-/, '');
            
            const iconColorElement = document.getElementById('icon_color');
            const iconColor = iconColorElement ? iconColorElement.value : '#000000';
            
            previewContainer.innerHTML = `<i class="fas fa-${iconName} fa-3x" style="color: ${iconColor};"></i>`;
            break;
            
        case 'upload':
            const uploadedIconPathElement = document.getElementById('uploaded_icon_path');
            const uploadedPath = uploadedIconPathElement ? uploadedIconPathElement.value : '';
            
            if (uploadedPath) {
                updateUploadedIconPreview(uploadedPath);
                return;
            }
            
            // 没有已上传图片时，显示无预览状态（不显示本地文件）
            previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-image fa-3x"></i><p class="mt-2 mb-0">暂无图标</p></div>';
            break;
            
        case 'url':
            const iconUrlElement = document.getElementById('icon_url');
            const url = iconUrlElement ? iconUrlElement.value : '';
            
            if (url) {
                previewContainer.innerHTML = `<img src="${url}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-link fa-3x"></i><p class="mt-2 mb-0">请输入图标URL</p></div>';
            }
            break;
            
        case 'iconfont':
            const iconfontIconElement = document.getElementById('iconfont_icon');
            const iconfontValue = iconfontIconElement ? iconfontIconElement.value : '';
            
            if (iconfontValue) {
                previewContainer.innerHTML = `<svg class="icon" aria-hidden="true" style="font-size: 3em;"><use xlink:href="#${iconfontValue}"></use></svg>`;
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-icons fa-3x"></i><p class="mt-2 mb-0">请输入Iconfont类名</p></div>';
            }
            break;
            
        default:
            previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-ban fa-3x"></i><p class="mt-2 mb-0">无图标</p></div>';
    }
}

// 更新图标区域显示/隐藏
function updateIconSections() {
    // 隐藏所有图标区域
    document.querySelectorAll('.icon-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // 显示选中的图标区域
    const selectedTypeElement = document.querySelector('input[name="icon_type"]:checked');
    if (!selectedTypeElement) return; // 如果没有选中的图标类型，直接返回
    
    const selectedType = selectedTypeElement.value;
    const sectionId = selectedType + '_section';
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = 'block';
    }
    
    // 更新最终图标类型
    const finalIconTypeElement = document.getElementById('final_icon_type');
    if (finalIconTypeElement) {
        finalIconTypeElement.value = selectedType;
    }
    
    // 更新预览
    updatePreview();
}

// 添加事件监听器到图标类型选择器
document.querySelectorAll('input[name="icon_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateIconSections();
    });
});

// 添加事件监听器到图标相关输入字段
document.querySelectorAll('input[name="icon_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateIconSections();
    });
});

// 添加事件监听器到图标相关输入字段
document.addEventListener('DOMContentLoaded', function() {
    // 只有当元素存在时才添加事件监听器
    const iconFontAwesome = document.getElementById('icon_fontawesome');
    if (iconFontAwesome) {
        iconFontAwesome.addEventListener('input', function() {
            iconParams.icon_fontawesome = this.value;
            updatePreview();
        });
    }

    const iconColor = document.getElementById('icon_color');
    if (iconColor) {
        iconColor.addEventListener('input', function() {
            iconParams.icon_fontawesome_color = this.value;
            updatePreview();
        });
    }

    const iconUrl = document.getElementById('icon_url');
    if (iconUrl) {
        iconUrl.addEventListener('input', function() {
            iconParams.icon_url = this.value;
            updatePreview();
        });
    }

    const iconfontIcon = document.getElementById('iconfont_icon');
    if (iconfontIcon) {
        iconfontIcon.addEventListener('input', function() {
            iconParams.icon_iconfont = this.value;
            updatePreview();
        });
    }
});

// 监听表单提交
const linkForm = document.getElementById('linkForm');
if (linkForm) {
    linkForm.addEventListener('submit', function(e) {
        // 获取当前选中的图标类型
        const selectedIconTypeElement = document.querySelector('input[name="icon_type"]:checked');
        const selectedIconType = selectedIconTypeElement ? selectedIconTypeElement.value : 'none';
        
        // 安全地更新隐藏字段
        const finalIconType = document.getElementById('final_icon_type');
        if (finalIconType) {
            finalIconType.value = selectedIconType;
        }
        
        // 同步所有图标参数到隐藏字段
        const iconFontAwesome = document.getElementById('icon_fontawesome');
        if (iconFontAwesome) {
            iconFontAwesome.value = iconParams.icon_fontawesome || '';
        }
        
        const iconFontAwesomeColor = document.getElementById('icon_fontawesome_color');
        if (iconFontAwesomeColor) {
            iconFontAwesomeColor.value = iconParams.icon_fontawesome_color || '';
        }
        
        const uploadedIconPath = document.getElementById('uploaded_icon_path');
        if (uploadedIconPath) {
            uploadedIconPath.value = iconParams.icon_upload || '';
        }
        
        const iconUrl = document.getElementById('icon_url');
        if (iconUrl) {
            iconUrl.value = iconParams.icon_url || '';
        }
        
        const iconfontIcon = document.getElementById('iconfont_icon');
        if (iconfontIcon) {
            iconfontIcon.value = iconParams.icon_iconfont || '';
        }
        
        // 验证当前选中的标签页
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
        
        if (selectedIconType === 'iconfont' && (!iconParams.icon_iconfont || !iconParams.icon_iconfont.trim())) {
            e.preventDefault();
            alert('请输入Iconfont图标类名');
            return false;
        }
        
        // 同步所有图标参数到表单字段（确保后端能接收到所有值）
        const finalIcon = document.getElementById('final_icon');
        if (finalIcon) {
            finalIcon.value = JSON.stringify(iconParams);
        }
    });
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
            
            // 安全地更新隐藏字段，使用完整路径
            const uploadedIconPath = document.getElementById('uploaded_icon_path');
            if (uploadedIconPath) {
                uploadedIconPath.value = data.file_url;
            }
            
            // 更新文件显示字段
            const uploadedFileDisplay = document.getElementById('uploaded_file_display');
            if (uploadedFileDisplay) {
                uploadedFileDisplay.value = data.file_name;
            }
            
            const uploadStatus = document.getElementById('upload_status');
            if (uploadStatus) {
                uploadStatus.innerHTML = 
                    '<div class="alert alert-success alert-dismissible fade show" role="alert">上传成功<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
            
            // 重置文件上传控件
            const fileInput = document.getElementById('icon_upload_file');
            const uploadBtn = document.getElementById('upload_btn');
            if (fileInput) {
                fileInput.value = ''; // 清空文件输入框
            }
            if (uploadBtn) {
                uploadBtn.disabled = true; // 禁用上传按钮
            }
            
            updatePreview();
        } else {
            const uploadStatus = document.getElementById('uploadStatus');
            if (uploadStatus) {
                uploadStatus.innerHTML = 
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">上传失败: ' + data.error + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    })
    .catch(error => {
        const uploadStatus = document.getElementById('uploadStatus');
        if (uploadStatus) {
            uploadStatus.innerHTML = 
                '<div class="alert alert-danger alert-dismissible fade show" role="alert">上传出错: ' + error.message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    });
}

// 页面加载完成后初始化图标区域
document.addEventListener('DOMContentLoaded', function() {
    updateIconSections();
    
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
    
    // 添加事件监听器到图标相关输入字段
    const iconFontAwesome = document.getElementById('icon_fontawesome');
    if (iconFontAwesome) {
        iconFontAwesome.addEventListener('input', function() {
            iconParams.icon_fontawesome = this.value;
            updatePreview();
        });
    }

    const iconColor = document.getElementById('icon_color');
    if (iconColor) {
        iconColor.addEventListener('input', function() {
            iconParams.icon_fontawesome_color = this.value;
            updatePreview();
        });
    }

    const iconUrl = document.getElementById('icon_url');
    if (iconUrl) {
        iconUrl.addEventListener('input', function() {
            iconParams.icon_url = this.value;
            updatePreview();
        });
    }
});
</script>

    </div>
</div>

<?php include '../templates/footer.php'; ?>