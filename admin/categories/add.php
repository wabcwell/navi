<?php
require_once '../includes/load.php';

// 检查是否登录
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

// 处理AJAX文件上传
if (isset($_GET['ajax_upload']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // 检查是否是AJAX请求
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            throw new Exception('无效的请求类型');
        }
        
        // 验证文件上传
        if (!isset($_FILES['icon_file'])) {
            throw new Exception('未找到上传文件');
        }
        
        if ($_FILES['icon_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败，错误代码: ' . $_FILES['icon_file']['error']);
        }
        
        // 获取文件上传管理器
        $uploadManager = get_file_upload_manager('categories');
        
        // 处理文件上传
        $uploadResult = $uploadManager->upload($_FILES['icon_file']);
        
        if ($uploadResult['success']) {
            // 调试：记录上传结果
            error_log('上传成功 - file_url: ' . $uploadResult['file_url']);
            error_log('上传成功 - file_path: ' . $uploadResult['file_path']);
            error_log('上传成功 - file_name: ' . $uploadResult['file_name']);
            echo json_encode([
                'success' => true,
                'message' => '文件上传成功',
                'path' => $uploadResult['file_url'],
                'file_name' => $uploadResult['file_name'],
                'debug' => [
                    'file_url' => $uploadResult['file_url'],
                    'file_path' => $uploadResult['file_path'],
                    'file_name' => $uploadResult['file_name']
                ]
            ]);
        } else {
            throw new Exception($uploadResult['error']);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
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
                // 使用已上传的图片路径（通过AJAX上传）
                if (!empty($_POST['uploaded_icon_path'])) {
                    $icon_data['icon_color_upload'] = $_POST['uploaded_icon_path'];
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
                            <div class="input-group">
                                <input type="file" class="form-control" id="icon_file" name="icon_file" 
                                       accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                                       onchange="this.nextElementSibling.value = this.files[0]?.name || '';">
                                <button type="button" class="btn btn-outline-primary" id="upload_btn" disabled>
                                    <i class="bi bi-upload"></i> 上传
                                </button>
                            </div>
                            <input type="text" class="form-control mt-2" id="uploaded_file_display" 
                                   placeholder="已上传文件路径" readonly style="background-color: #f8f9fa;">
                            <small class="form-text text-muted">支持 JPG, PNG, GIF, WebP, SVG 格式，最大 2MB</small>
                            <div id="upload_status" class="mt-2"></div>
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
                        <input type="hidden" id="uploaded_icon_path" name="uploaded_icon_path" value="">
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
            // 预览模块只预览已上传的服务器图片，完全解耦
            const uploadedPath = document.getElementById('uploaded_icon_path').value;
            if (uploadedPath) {
                updateUploadedIconPreview(uploadedPath);
                return;
            }
            
            // 没有已上传图片时，显示无预览状态（不显示本地文件）
            previewContainer.innerHTML = '<i class="fas fa-image fa-3x text-muted"></i>';
            previewText.textContent = '无预览';
            break;
            
        case 'url':
            const url = document.getElementById('icon_url').value;
            if (url) {
                previewContainer.innerHTML = `<img src="${url}" class="img-fluid" style="max-height: 60px;">`;
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
document.getElementById('icon_file').addEventListener('change', function() {
    const uploadBtn = document.getElementById('upload_btn');
    const fileInput = this;
    const statusDiv = document.getElementById('upload_status');
    
    if (fileInput.files && fileInput.files[0]) {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
        statusDiv.innerHTML = ''; // 清除之前的状态信息
        // 文件选择完全解耦：只控制上传按钮状态，不影响预览
    } else {
        uploadBtn.disabled = true;
    }
});

// 图标类型切换时检查是否有已上传的图片
document.querySelectorAll('input[name="icon_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        updateIconPreview();
    });
});

// 上传按钮点击事件
document.getElementById('upload_btn').addEventListener('click', function() {
    const fileInput = document.getElementById('icon_file');
    const uploadBtn = this;
    const statusDiv = document.getElementById('upload_status');
    
    if (!fileInput.files || !fileInput.files[0]) {
        statusDiv.innerHTML = '<div class="alert alert-warning">请先选择图片文件</div>';
        return;
    }
    
    // 创建FormData对象
    const formData = new FormData();
    formData.append('icon_file', fileInput.files[0]);
    formData.append('ajax_upload', '1');
    
    // 禁用上传按钮
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 上传中...';
    statusDiv.innerHTML = '<div class="alert alert-info">正在上传图片...</div>';
    
    // 发送AJAX请求到当前页面处理上传
    fetch('?ajax_upload=1', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('上传响应数据:', data); // 调试用
        console.log('上传路径:', data.path); // 调试用
        console.log('文件名:', data.file_name); // 调试用
        if (data.success) {
            // 上传成功
            if (data.path) {
                document.getElementById('uploaded_icon_path').value = data.path;
                statusDiv.innerHTML = '<div class="alert alert-success">图片上传成功！</div>';
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
                
                // 在文件路径显示框中显示已上传文件路径
                const fileName = data.file_name || data.path.split('/').pop(); // 优先使用返回的文件名
                document.getElementById('uploaded_file_display').value = fileName;
                
                // 3秒后清除状态信息
                setTimeout(() => {
                    statusDiv.innerHTML = '';
                }, 3000);
                
                // 更新预览：只使用服务器返回的图片URL，完全解耦
                updateUploadedIconPreview(data.path);
                
                // 清除文件输入框的本地文件选择（不影响预览，只是清理界面）
                fileInput.value = '';
                
                // 调试：显示上传成功的详细信息
                console.log('上传成功 - 服务器图片URL:', data.path);
            } else {
                throw new Error('上传成功但路径为空');
            }
        } else {
            // 上传失败
            statusDiv.innerHTML = '<div class="alert alert-danger">上传失败：' + data.message + '</div>';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
        }
    })
    .catch(error => {
        console.error('上传错误:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger">上传出错：' + error.message + '</div>';
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
        
        // 显示更详细的错误信息
        if (error.stack) {
            console.error('错误堆栈:', error.stack);
        }
    });
});

// 更新已上传图标的预览（完全解耦，只负责显示服务器图片URL）
function updateUploadedIconPreview(filePath) {
    const previewContainer = document.getElementById('icon_preview');
    const previewText = document.getElementById('icon_preview_text');
    
    console.log('预览服务器图片URL:', filePath);
    
    // 直接使用服务器返回的URL，不做任何处理
    previewContainer.innerHTML = `<img src="${filePath}" class="img-fluid" style="max-height: 60px;" alt="服务器图片">`;
    previewText.textContent = '服务器图片';
}

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
            // 使用已上传的图片路径（通过AJAX上传）
            const uploadedPath = document.getElementById('uploaded_icon_path').value;
            if (uploadedPath) {
                document.getElementById('final_icon').value = uploadedPath;
            }
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