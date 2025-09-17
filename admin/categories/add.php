<?php
require_once '../includes/load.php';

// 检查是否登录
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// 获取分类管理实例
$categoryManager = get_category_manager();

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
            case 'font':
                $icon_data['icon_fontawesome'] = trim($_POST['font_icon']);
                $icon_data['icon_fontawesome_color'] = trim($_POST['icon_color']);
                break;
            case 'url':
                $icon_data['icon_color_url'] = trim($_POST['icon_url']);
                break;
            case 'upload':
                // 使用新的文件上传类处理文件上传
                if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
                    $fileUpload = get_file_upload_manager('categories');
                    $result = $fileUpload->upload($_FILES['icon_file']);
                    
                    if ($result['success']) {
                        $icon_data['icon_color_upload'] = $result['file_name'];
                    }
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
        
        // 创建新分类
        $categoryId = $categoryManager->create($categoryData);
        
        $_SESSION['success'] = '分类添加成功！';
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = '添加分类失败：' . $e->getMessage();
        // 保留表单数据以便重新填写
        $formData = $_POST;
    }
}

include '../templates/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../dashboard.php">仪表盘</a></li>
        <li class="breadcrumb-item"><a href="index.php">分类管理</a></li>
        <li class="breadcrumb-item active">添加分类</li>
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
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <small class="form-text text-muted">分类的显示名称</small>
                    </div>
                    
                    <div class="mb-3" style="display: none;">
                        <label for="slug" class="form-label">别名</label>
                        <input type="hidden" class="form-control" id="slug" name="slug" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">描述</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">分类的简短描述</small>
                    </div>
                    
                    <!-- 图标设置模块下移到这里 -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">图标设置</label>
                        
                        <!-- 图标类型选择 -->
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_font" value="font" checked>
                            <label class="btn btn-outline-primary" for="icon_type_font">Font Awesome</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_upload" value="upload">
                            <label class="btn btn-outline-primary" for="icon_type_upload">上传图片</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_url" value="url">
                            <label class="btn btn-outline-primary" for="icon_type_url">网络地址</label>
                        </div>
                        
                        <!-- 预览区域 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <small class="text-muted">预览</small>
                            </div>
                            <div class="card-body icon-preview-container">
                                <div id="icon_preview">
                                    <i class="fas fa-folder fa-3x" style="color: #007bff;"></i>
                                </div>
                                <div id="icon_preview_text" class="text-muted small mt-2">当前图标</div>
                            </div>
                        </div>
                        
                        <!-- Font Awesome 图标选择 - 颜色选择器在同一行 -->
                        <div id="icon_font_section" class="icon-section mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="font_icon" class="form-label">选择图标</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="font_icon" name="font_icon" 
                                               placeholder="输入图标名称，如: folder" value="folder">
                                        <button type="button" class="btn btn-outline-secondary" onclick="openIconPicker()">
                                            <i class="bi bi-grid-3x3-gap"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="icon_color" class="form-label">图标颜色</label>
                                    <input type="color" class="form-control form-control-color w-100" id="icon_color" name="icon_color" 
                                           value="#007bff" style="height: 38px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 图片上传 -->
                        <div id="icon_upload_section" class="icon-section mb-3" style="display: none;">
                            <label for="icon_file" class="form-label">上传图标</label>
                            <input type="file" class="form-control" id="icon_file" name="icon_file" 
                                   accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
                            <small class="form-text text-muted">支持 JPG, PNG, GIF, WebP, SVG 格式，最大 2MB</small>
                        </div>
                        
                        <!-- URL 输入 -->
                        <div id="icon_url_section" class="icon-section mb-3" style="display: none;">
                            <label for="icon_url" class="form-label">图标地址</label>
                            <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                   placeholder="https://example.com/icon.png">
                            <small class="form-text text-muted">输入完整的图标URL地址</small>
                        </div>
                        
                        <!-- 隐藏字段 -->
                        <input type="hidden" id="final_icon" name="final_icon" value="">
                        <input type="hidden" id="final_icon_type" name="final_icon_type" value="font">
                    </div>
                    
                    <!-- 颜色选择器移动到了Font Awesome部分的同一行 -->
                    <div class="mb-3">
                        <label for="color" class="form-label">分类颜色</label>
                        <input type="color" class="form-control form-control-color w-50" id="color" name="color" 
                               value="<?php echo htmlspecialchars($_POST['color'] ?? '#007bff'); ?>">
                        <small class="form-text text-muted">用于分类边框和主题色</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_index" class="form-label">排序</label>
                        <input type="number" class="form-control" id="order_index" name="order_index" 
                               value="<?php echo intval($_POST['order_index'] ?? 0); ?>" min="0">
                        <small class="form-text text-muted">数字越大越靠前</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo isset($_POST['is_active']) || !isset($_POST['submit']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                启用分类
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i> 保存分类
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
    
    if (!document.getElementById('slug').value) {
        document.getElementById('slug').value = slug;
    }
});

// 图标类型切换
const iconTypeRadios = document.querySelectorAll('input[name="icon_type"]');
const iconSections = {
    font: document.getElementById('icon_font_section'),
    upload: document.getElementById('icon_upload_section'),
    url: document.getElementById('icon_url_section')
};

iconTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        // 隐藏所有区域
        Object.values(iconSections).forEach(section => section.style.display = 'none');
        
        // 显示选中区域
        iconSections[this.value].style.display = 'block';
        
        // 更新隐藏字段
        document.getElementById('final_icon_type').value = this.value;
        
        // 更新预览
        updateIconPreview();
    });
});

// 更新图标预览
function updateIconPreview() {
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    const previewContainer = document.getElementById('icon_preview');
    const previewText = document.getElementById('icon_preview_text');
    
    switch(iconType) {
        case 'font':
            const iconName = document.getElementById('font_icon').value || 'folder';
            const iconColor = document.getElementById('icon_color').value;
            previewContainer.innerHTML = `<i class="fas fa-${iconName} fa-3x" style="color: ${iconColor};"></i>`;
            previewText.textContent = `Font Awesome: ${iconName}`;
            document.getElementById('final_icon').value = iconName;
            break;
            
        case 'upload':
            const fileInput = document.getElementById('icon_file');
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height: 60px;">`;
                    previewText.textContent = '上传图片预览';
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                previewContainer.innerHTML = '<i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>';
                previewText.textContent = '等待上传图片';
            }
            break;
            
        case 'url':
            const url = document.getElementById('icon_url').value;
            if (url) {
                previewContainer.innerHTML = `<img src="${url}" class="img-fluid" style="max-height: 60px;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodG0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjZGRkIi8+Cjx0ZXh0IHg9IjMyIiB5PSIzMiIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+RXJyb3I8L3RleHQ+Cjwvc3ZnPg==';">`;
                previewText.textContent = '网络图标预览';
                document.getElementById('final_icon').value = url;
            } else {
                previewContainer.innerHTML = '<i class="fas fa-link fa-3x text-muted"></i>';
                previewText.textContent = '请输入图标URL';
            }
            break;
    }
}

// Font Awesome 图标选择器
function openIconPicker() {
    const modal = new bootstrap.Modal(document.createElement('div'));
    const modalDiv = document.createElement('div');
    modalDiv.className = 'modal fade';
    modalDiv.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">选择 Font Awesome 图标</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="iconSearch" placeholder="搜索图标...">
                    </div>
                    <div class="row g-2" id="iconGrid" style="max-height: 400px; overflow-y: auto;">
                        ${getFontAwesomeIcons().map(icon => `
                            <div class="col-2">
                                <button type="button" class="btn btn-outline-secondary w-100 icon-btn" 
                                        onclick="selectIcon('${icon}')" title="${icon}">
                                    <i class="fas fa-${icon} fa-lg"></i>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modalDiv);
    const modalInstance = new bootstrap.Modal(modalDiv);
    modalInstance.show();
    
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
    document.getElementById('font_icon').value = iconName;
    updateIconPreview();
    document.querySelector('.modal').remove();
}

// Font Awesome 图标列表
function getFontAwesomeIcons() {
    return [
        'folder', 'folder-open', 'file', 'file-alt', 'file-code', 'file-text', 'archive',
        'home', 'building', 'store', 'warehouse', 'industry', 'city', 'map-marker-alt',
        'user', 'users', 'user-friends', 'user-tie', 'user-graduate', 'user-cog',
        'heart', 'star', 'bookmark', 'thumbs-up', 'award', 'trophy', 'medal',
        'cog', 'tools', 'wrench', 'hammer', 'screwdriver', 'cogs', 'sliders-h',
        'chart-bar', 'chart-line', 'chart-pie', 'analytics', 'poll', 'tachometer-alt',
        'shopping-cart', 'shopping-bag', 'gift', 'credit-card', 'money-bill-wave',
        'camera', 'image', 'images', 'photo-video', 'film', 'music', 'play',
        'book', 'books', 'graduation-cap', 'school', 'university', 'chalkboard-teacher',
        'laptop', 'desktop', 'tablet-alt', 'mobile-alt', 'server', 'database',
        'globe', 'network-wired', 'wifi', 'broadcast-tower', 'satellite',
        'car', 'bus', 'train', 'plane', 'ship', 'rocket', 'bicycle',
        'coffee', 'utensils', 'pizza-slice', 'ice-cream', 'cocktail',
        'gamepad', 'dice', 'puzzle-piece', 'chess', 'headphones',
        'envelope', 'phone', 'fax', 'mail-bulk', 'paper-plane',
        'lock', 'key', 'shield-alt', 'fire-extinguisher', 'first-aid',
        'calendar', 'clock', 'stopwatch', 'hourglass-half', 'bell',
        'lightbulb', 'eye', 'search', 'filter', 'sort', 'download', 'upload'
    ];
}

// 事件监听
document.getElementById('font_icon').addEventListener('input', updateIconPreview);
document.getElementById('icon_color').addEventListener('input', updateIconPreview);
document.getElementById('icon_url').addEventListener('input', updateIconPreview);
document.getElementById('icon_file').addEventListener('change', updateIconPreview);

// 表单提交前设置最终值
document.querySelector('form').addEventListener('submit', function() {
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    document.getElementById('final_icon_type').value = iconType;
    
    switch(iconType) {
        case 'font':
            const iconName = document.getElementById('font_icon').value;
            const iconColor = document.getElementById('font_color').value;
            document.getElementById('final_icon').value = JSON.stringify({
                type: 'font',
                name: iconName,
                color: iconColor
            });
            break;
        case 'upload':
            // 文件上传由PHP处理
            break;
        case 'url':
            document.getElementById('final_icon').value = document.getElementById('icon_url').value;
            break;
    }
});

// 初始化
updateIconPreview();
</script>

<?php include '../templates/footer.php'; ?>