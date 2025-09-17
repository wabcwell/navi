<?php
require_once '../includes/load.php';

// 检查是否登录
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// 获取分类管理实例
$categoryManager = get_category_manager();

// 检查是否有ID参数
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$category_id = intval($_GET['id']);

// 获取分类信息
$category = $categoryManager->getById($category_id);

if (!$category) {
    header('Location: index.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $color = trim($_POST['color']);
        $order_index = intval($_POST['order_index']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // 处理图标
        $icon_type = $_POST['icon_type'];
        $icon_data = [];
        
        switch ($icon_type) {
            case 'fontawesome':
                $icon_data['icon_fontawesome'] = trim($_POST['icon_fontawesome']);
                $icon_data['icon_fontawesome_color'] = trim($_POST['icon_color']);
                break;
            case 'url':
                $icon_data['icon_color_url'] = trim($_POST['icon_url']);
                break;
            case 'upload':
                // 处理文件上传
                if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['icon_upload'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
                    
                    if (in_array($file['type'], $allowed_types)) {
                        $upload_dir = '../uploads/categories/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'category_icon_' . uniqid() . '.' . $extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // 删除旧图标
                            if (!empty($category['icon_color_upload']) && file_exists($upload_dir . $category['icon_color_upload'])) {
                                unlink($upload_dir . $category['icon_color_upload']);
                            }
                            $icon_data['icon_color_upload'] = $filename;
                        }
                    }
                } elseif (isset($_POST['current_icon']) && !empty($_POST['current_icon'])) {
                    // 保留现有图标
                    $icon_data['icon_color_upload'] = $_POST['current_icon'];
                }
                break;
        }
        
        // 准备分类数据
        $categoryData = array_merge([
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'order_index' => $order_index,
            'is_active' => $is_active,
            'icon_type' => $icon_type
        ], $icon_data);
        
        // 更新分类
        $result = $categoryManager->update($category_id, $categoryData);
        
        if ($result) {
            $_SESSION['success'] = '分类更新成功！';
        } else {
            $_SESSION['error'] = '分类更新失败：未找到该分类。';
        }
        
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = '更新分类失败：' . $e->getMessage();
    }
}

include '../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">仪表盘</a></li>
        <li class="breadcrumb-item"><a href="index.php">分类管理</a></li>
        <li class="breadcrumb-item active">编辑分类</li>
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
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3" style="display: none;">
                        <label for="slug" class="form-label">别名</label>
                        <input type="hidden" class="form-control" id="slug" name="slug" 
                               value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">描述</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- 图标设置模块下移到这里 -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">图标设置</label>
                        
                        <!-- 图标类型选择 -->
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_fontawesome" value="fontawesome" <?php echo strpos($category['icon'] ?? '', 'fa-') === 0 ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_fontawesome">Font Awesome</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_upload" value="upload" <?php echo (!empty($category['icon']) && strpos($category['icon'] ?? '', 'fa-') !== 0 && strpos($category['icon'] ?? '', 'http') !== 0) ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_upload">上传图片</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_url" value="url" <?php echo strpos($category['icon'] ?? '', 'http') === 0 ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_url">填写URL</label>
                        </div>
                        
                        <!-- 预览区域 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <small class="text-muted">预览</small>
                            </div>
                            <div class="card-body icon-preview-container">
                                <div id="iconPreview">
                                    <?php if (!empty($category['icon'])): ?>
                                        <?php if (strpos($category['icon'] ?? '', 'fa-') === 0): ?>
                                            <i class="fas <?php echo $category['icon'] ?? ''; ?>" style="font-size: 3rem; color: <?php echo htmlspecialchars($category['color'] ?? '#007bff'); ?>"></i>
                                        <?php elseif (strpos($category['icon'] ?? '', 'http') === 0): ?>
                                            <img src="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>" class="image-preview" style="max-width: 100px; max-height: 100px;">
                                        <?php else: ?>
                                            <img src="../uploads/categories/<?php echo $category['icon'] ?? ''; ?>" class="image-preview" style="max-width: 100px; max-height: 100px;">
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
                        
                        <!-- Font Awesome 图标选择 - 颜色选择器在同一行 -->
                        <div id="fontawesome_section" class="icon-section mb-3" style="display: none;">
                            <div class="row">
                                <div class="col-md-8">
                            <label for="icon_fontawesome" class="form-label">选择图标</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="icon_fontawesome" name="icon_fontawesome" 
                                       placeholder="点击选择图标" readonly
                                       value="<?php 
                                       $currentIcon = $category['icon'] ?? '';
                                       if (strpos($currentIcon, 'fa-') === 0) {
                                           echo htmlspecialchars($currentIcon);
                                       } elseif (strpos($currentIcon, 'fas ') === 0) {
                                           echo htmlspecialchars(str_replace('fas ', '', $currentIcon));
                                       } else {
                                           echo '';
                                       }
                                       ?>">
                                <button type="button" class="btn btn-outline-secondary" id="openIconPicker">
                                    <i class="fas fa-icons"></i>
                                </button>
                            </div>
                        </div>
                                <div class="col-md-4">
                                    <label for="icon_color" class="form-label">图标颜色</label>
                                    <input type="color" class="form-control form-control-color w-100" id="icon_color" name="icon_color" 
                                           value="<?php echo htmlspecialchars($category['icon_color'] ?? $category['color'] ?? '#007bff'); ?>" style="height: 38px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 上传图片 -->
                        <div id="upload_section" class="icon-section mb-3" style="display: none;">
                            <label for="icon_upload" class="form-label">上传图片</label>
                            <input type="file" class="form-control" id="icon_upload" name="icon_upload" 
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                        
                        <!-- URL 输入 -->
                        <div id="url_section" class="icon-section mb-3" style="display: none;">
                            <label for="icon_url" class="form-label">图标URL</label>
                            <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                   placeholder="https://example.com/icon.png"
                                   value="<?php echo strpos($category['icon'] ?? '', 'http') === 0 ? htmlspecialchars($category['icon'] ?? '') : ''; ?>">
                        </div>
                        
                        <!-- 隐藏字段 -->
                        <input type="hidden" name="current_icon" value="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>">
                        <input type="hidden" name="remove_icon" value="0">
                    </div>
                    
                    <!-- 分类颜色设置 -->
                    <div class="mb-3">
                        <label for="color" class="form-label">分类颜色</label>
                        <input type="color" class="form-control form-control-color w-50" id="color" name="color" 
                               value="<?php echo htmlspecialchars($category['color'] ?? '#007bff'); ?>">
                        <small class="form-text text-muted">用于分类边框和主题色</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">排序</label>
                        <input type="number" class="form-control" id="order_index" name="order_index" 
                               value="<?php echo $category['order_index']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                启用分类
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i> 更新分类
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> 取消
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('name').addEventListener('input', function() {
    var name = this.value;
    var slug = name.toLowerCase()
                   .replace(/[^\w\s-]/g, '')
                   .replace(/[\s_-]+/g, '-')
                   .replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
});

// 图标类型切换
function updateIconSections() {
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    const fontawesomeSection = document.getElementById('fontawesome_section');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');
    
    fontawesomeSection.style.display = iconType === 'fontawesome' ? 'block' : 'none';
    uploadSection.style.display = iconType === 'upload' ? 'block' : 'none';
    urlSection.style.display = iconType === 'url' ? 'block' : 'none';
    
    updatePreview();
}

// 更新预览
function updatePreview() {
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    const color = document.getElementById('icon_color') ? document.getElementById('icon_color').value : document.getElementById('color').value;
    const preview = document.getElementById('iconPreview');
    const currentIcon = document.querySelector('input[name="current_icon"]').value;
    
    let previewHtml = '';
    
    switch (iconType) {
        case 'fontawesome':
            const iconClass = document.getElementById('icon_fontawesome').value;
            const displayIcon = iconClass || currentIcon;
            if (displayIcon && (displayIcon.startsWith('fa-') || displayIcon.startsWith('fas '))) {
                const finalIcon = displayIcon.startsWith('fas ') ? displayIcon : `fas ${displayIcon}`;
                previewHtml = `<i class="${finalIcon}" style="font-size: 3rem; color: ${color}"></i>`;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-icons" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请选择图标</p></div>';
            }
            break;
            
        case 'upload':
            const uploadFile = document.getElementById('icon_upload').files[0];
            if (uploadFile) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
                }
                reader.readAsDataURL(uploadFile);
                return;
            } else {
                // 显示现有图标
                if (currentIcon && !currentIcon.startsWith('fa-') && !currentIcon.startsWith('http') && currentIcon !== '') {
                    previewHtml = `<img src="/uploads/categories/${currentIcon}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
                } else {
                    previewHtml = '<div class="text-muted"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无图标</p></div>';
                }
            }
            break;
            
        case 'url':
            const url = document.getElementById('icon_url').value;
            if (url) {
                previewHtml = `<img src="${url}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
            } else if (currentIcon && currentIcon.startsWith('http')) {
                previewHtml = `<img src="${currentIcon}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-link" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请输入URL</p></div>';
            }
            break;
    }
    
    preview.innerHTML = previewHtml;
}

// 事件监听
document.querySelectorAll('input[name="icon_type"]').forEach(radio => {
    radio.addEventListener('change', updateIconSections);
});

document.getElementById('color').addEventListener('input', updatePreview);
document.getElementById('icon_fontawesome').addEventListener('input', updatePreview);
document.getElementById('icon_url').addEventListener('input', updatePreview);

document.getElementById('icon_upload').addEventListener('change', updatePreview);

// Font Awesome 图标选择器
let iconModal = null;

document.getElementById('openIconPicker').addEventListener('click', function() {
    if (!iconModal) {
        const modalHtml = `
            <div class="modal fade" id="iconModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">选择 Font Awesome 图标</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2" id="iconGrid">
                                ${getFontAwesomeIcons().map(icon => `
                                    <div class="col-2">
                                        <button type="button" class="btn btn-outline-secondary icon-btn w-100" data-icon="${icon}">
                                            <i class="fas ${icon}"></i>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        iconModal = new bootstrap.Modal(document.getElementById('iconModal'));
        
        // 图标选择事件
        document.querySelectorAll('#iconGrid .icon-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const icon = this.dataset.icon;
                document.getElementById('icon_fontawesome').value = icon;
                updatePreview();
                iconModal.hide();
            });
        });
    }
    
    iconModal.show();
});

// Font Awesome 图标列表
function getFontAwesomeIcons() {
    return [
        'fa-home', 'fa-user', 'fa-cog', 'fa-envelope', 'fa-phone', 'fa-map-marker-alt',
        'fa-heart', 'fa-star', 'fa-book', 'fa-file', 'fa-folder', 'fa-folder-open',
        'fa-download', 'fa-upload', 'fa-share', 'fa-link', 'fa-unlink', 'fa-edit',
        'fa-trash', 'fa-search', 'fa-filter', 'fa-sort', 'fa-list', 'fa-th',
        'fa-chart-bar', 'fa-chart-line', 'fa-chart-pie', 'fa-calendar', 'fa-clock',
        'fa-shopping-cart', 'fa-credit-card', 'fa-money-bill', 'fa-tag', 'fa-tags',
        'fa-image', 'fa-photo-video', 'fa-camera', 'fa-video', 'fa-music', 'fa-play',
        'fa-cloud', 'fa-server', 'fa-database', 'fa-code', 'fa-terminal', 'fa-laptop',
        'fa-mobile-alt', 'fa-tablet-alt', 'fa-desktop', 'fa-wifi', 'fa-globe',
        'fa-shield-alt', 'fa-lock', 'fa-key', 'fa-eye', 'fa-eye-slash', 'fa-bell',
        'fa-flag', 'fa-bookmark', 'fa-thumbs-up', 'fa-thumbs-down', 'fa-smile',
        'fa-frown', 'fa-meh', 'fa-save', 'fa-print', 'fa-copy', 'fa-paste',
        'fa-cut', 'fa-undo', 'fa-redo', 'fa-sync', 'fa-refresh', 'fa-spinner',
        'fa-check', 'fa-times', 'fa-plus', 'fa-minus', 'fa-question', 'fa-info',
        'fa-exclamation', 'fa-exclamation-triangle', 'fa-exclamation-circle'
    ];
}

// 初始化
updateIconSections();

// 页面加载时确保正确的图标类型被选中
window.addEventListener('DOMContentLoaded', function() {
    // 确保正确的图标类型区域显示
    setTimeout(() => {
        updateIconSections();
        updatePreview();
    }, 100);
});
</script>

<?php include '../templates/footer.php'; ?>