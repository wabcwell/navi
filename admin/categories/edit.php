<?php
require_once '../includes/load.php';
require_once '../includes/fontawesome-icons.php';

// 检查是否登录
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取分类管理实例
$categoryManager = get_category_manager();

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
            echo json_encode([
                'success' => true,
                'message' => '文件上传成功',
                'path' => $uploadResult['file_url'],
                'file_name' => $uploadResult['file_name']
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
        $icon_type = $_POST['final_icon_type'] ?? $_POST['icon_type'];
        $final_icon = $_POST['final_icon'] ?? '';
        
        $icon_data = [];
        
        switch ($icon_type) {
            case 'fontawesome':
                // 解析Font Awesome图标数据
                $icon_info = json_decode($final_icon, true);
                if ($icon_info && isset($icon_info['type']) && $icon_info['type'] === 'font') {
                    $icon_data['icon_fontawesome'] = $icon_info['name'] ?? '';
                    $icon_data['icon_fontawesome_color'] = $icon_info['color'] ?? $color;
                }
                // 清除其他图标类型的数据
                $icon_data['icon_iconfont'] = null;
                $icon_data['icon_upload'] = null;
                $icon_data['icon_url'] = null;
                break;
            case 'iconfont':
                $icon_data['icon_iconfont'] = $final_icon;
                // 清除其他图标类型的数据
                $icon_data['icon_fontawesome'] = null;
                $icon_data['icon_fontawesome_color'] = null;
                $icon_data['icon_upload'] = null;
                $icon_data['icon_url'] = null;
                break;
            case 'url':
                $icon_data['icon_url'] = $final_icon;
                // 清除其他图标类型的数据
                $icon_data['icon_fontawesome'] = null;
                $icon_data['icon_fontawesome_color'] = null;
                $icon_data['icon_iconfont'] = null;
                $icon_data['icon_upload'] = null;
                break;
            case 'upload':
                // 使用已上传的图片路径（通过AJAX上传）
                if (!empty($final_icon)) {
                    $icon_data['icon_upload'] = $final_icon;
                    
                    // 删除旧图标
                    if (!empty($category['icon_upload']) && file_exists('../uploads/categories/' . $category['icon_upload'])) {
                        unlink('../uploads/categories/' . $category['icon_upload']);
                    }
                } elseif (isset($_POST['current_icon']) && !empty($_POST['current_icon'])) {
                    // 保留现有图标
                    $icon_data['icon_upload'] = $_POST['current_icon'];
                }
                // 清除其他图标类型的数据
                $icon_data['icon_fontawesome'] = null;
                $icon_data['icon_fontawesome_color'] = null;
                $icon_data['icon_iconfont'] = null;
                $icon_data['icon_url'] = null;
                break;
        }
        
        // 准备分类数据
        $categoryData = array_merge([
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'order_index' => $order_index,
            'is_active' => $is_active,
            'icon_type' => $icon_type,
            'icon' => $final_icon
        ], $icon_data);
        
        // 更新分类
        $result = $categoryManager->update($category_id, $categoryData);
        
        if ($result) {
            // 记录操作日志
            $logsManager = get_logs_manager();
            $logsManager->addCategoryOperationLog(
                $_SESSION['user_id'] ?? 0,
                '编辑',
                $category_id,
                $name,
                [
                    'before' => $category,
                    'after' => $categoryData
                ]
            );
            
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

// 设置页面标题
$page_title = '编辑分类';

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
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_fontawesome" value="fontawesome" <?php echo ($category['icon_type'] ?? 'fontawesome') === 'fontawesome' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_fontawesome">Font Awesome</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_iconfont" value="iconfont" <?php echo ($category['icon_type'] ?? 'fontawesome') === 'iconfont' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_iconfont">Iconfont</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_upload" value="upload" <?php echo ($category['icon_type'] ?? 'fontawesome') === 'upload' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_upload">上传图片</label>
                            
                            <input type="radio" class="btn-check" name="icon_type" id="icon_type_url" value="url" <?php echo ($category['icon_type'] ?? 'fontawesome') === 'url' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary" for="icon_type_url">填写URL</label>
                        </div>
                        
                        <!-- 预览区域 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <small class="text-muted">预览</small>
                            </div>
                            <div class="card-body icon-preview-container">
                                <div id="iconPreview">
                                    <?php if (!empty($category['icon_fontawesome'])): ?>
                                        <i class="<?php echo $category['icon_fontawesome']; ?>" style="font-size: 3rem; color: <?php echo htmlspecialchars($category['icon_fontawesome_color'] ?? $category['color'] ?? '#007bff'); ?>"></i>
                                    <?php elseif (!empty($category['icon_iconfont'])): ?>
                                        <svg class="icon" aria-hidden="true" style="font-size: 3em;">
                                            <use xlink:href="#<?php echo htmlspecialchars($category['icon_iconfont']); ?>"></use>
                                        </svg>
                                    <?php elseif (!empty($category['icon_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                    <?php elseif (!empty($category['icon_upload'])): ?>
                                        <img src="../uploads/categories/<?php echo $category['icon_upload']; ?>" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
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
                                               placeholder="输入图标类名，如: fas fa-folder"
                                               value="<?php echo htmlspecialchars($category['icon_fontawesome'] ?? ''); ?>">
                                        <button type="button" class="btn btn-outline-secondary" id="openIconPicker">
                                            <i class="fas fa-icons"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="icon_color" class="form-label">图标颜色</label>
                                    <input type="color" class="form-control form-control-color w-100" id="icon_color" name="icon_color" 
                                           value="<?php echo htmlspecialchars($category['icon_fontawesome_color'] ?? $category['color'] ?? '#007bff'); ?>" style="height: 38px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Iconfont 图标输入 -->
                        <div id="iconfont_section" class="icon-section mb-3" style="display: none;">
                            <label for="iconfont_icon" class="form-label">Iconfont 图标</label>
                            <input type="text" class="form-control" id="iconfont_icon" name="iconfont_icon" 
                                   placeholder="输入图标名称，如: icon-a-appround51"
                                   value="<?php echo htmlspecialchars($category['icon_iconfont'] ?? ''); ?>">
                            <small class="form-text text-muted">输入iconfont图标类名，如: icon-a-appround51</small>
                        </div>
                        
                        <!-- 上传图片 -->
                        <div id="upload_section" class="icon-section mb-3" style="display: none;">
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
                                   placeholder="已上传文件路径" readonly style="background-color: #f8f9fa;"
                                   value="<?php echo !empty($category['icon_upload']) ? htmlspecialchars(basename($category['icon_upload'])) : ''; ?>">
                            <small class="form-text text-muted">支持 JPG, PNG, GIF, WebP, SVG 格式，最大 2MB</small>
                            <div id="upload_status" class="mt-2"></div>
                        </div>
                        
                        <!-- URL 输入 -->
                        <div id="url_section" class="icon-section mb-3" style="display: none;">
                            <label for="icon_url" class="form-label">图标URL</label>
                            <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                   placeholder="https://example.com/icon.png"
                                   value="<?php echo htmlspecialchars($category['icon_url'] ?? ''); ?>">
                            <small class="form-text text-muted">请输入完整的图片URL地址</small>
                        </div>
                        
                        <!-- 隐藏字段 -->
                        <input type="hidden" name="current_icon" value="<?php 
                            if ($category['icon_type'] === 'upload' && !empty($category['icon_upload'])) {
                                echo htmlspecialchars($category['icon_upload']);
                            } elseif ($category['icon_type'] === 'url' && !empty($category['icon_url'])) {
                                echo htmlspecialchars($category['icon_url']);
                            } elseif ($category['icon_type'] === 'fontawesome' && !empty($category['icon_fontawesome'])) {
                                echo htmlspecialchars($category['icon_fontawesome']);
                            } elseif ($category['icon_type'] === 'iconfont' && !empty($category['icon_iconfont'])) {
                                echo htmlspecialchars($category['icon_iconfont']);
                            } else {
                                echo '';
                            }
                        ?>">
                        <input type="hidden" name="remove_icon" value="0">
                        <input type="hidden" id="uploaded_icon_path" name="uploaded_icon_path" value="<?php echo htmlspecialchars($category['icon_upload'] ?? ''); ?>">
                        <!-- 添加final_icon和final_icon_type隐藏字段 -->
                        <input type="hidden" id="final_icon" name="final_icon" value="">
                        <input type="hidden" id="final_icon_type" name="final_icon_type" value="fontawesome">
                        <!-- 添加iconfont隐藏字段 -->
                        <input type="hidden" id="iconfont_value" name="iconfont_value" value="<?php echo htmlspecialchars($category['icon_iconfont'] ?? ''); ?>">
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

// 创建iconParams对象存储图标相关参数
const iconParams = {
    icon_type: "<?php echo $category['icon_type'] ?? 'fontawesome'; ?>",
    icon_fontawesome: document.getElementById('icon_fontawesome').value || "<?php echo $category['icon_fontawesome'] ?? 'fas fa-folder'; ?>",
    icon_fontawesome_color: document.getElementById('icon_color').value || "<?php echo $category['icon_fontawesome_color'] ?? $category['color'] ?? '#007bff'; ?>",
    icon_iconfont: document.getElementById('iconfont_icon').value || "<?php echo $category['icon_iconfont'] ?? ''; ?>",
    icon_upload: document.getElementById('uploaded_icon_path').value || "<?php echo $category['icon_upload'] ?? ''; ?>",
    icon_url: document.getElementById('icon_url').value || "<?php echo $category['icon_url'] ?? ''; ?>"
};

// 表单提交前的验证和图标参数同步
document.querySelector('form').addEventListener('submit', function(e) {
    // 获取选中的图标类型
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    
    // 设置最终图标类型
    document.getElementById('final_icon_type').value = iconType;
    
    // 检查网络地址标签页但未填写地址的情况
    if (iconType === 'url' && !document.getElementById('icon_url').value.trim()) {
        e.preventDefault();
        alert('请填写网络图标地址');
        return;
    }
    
    // 检查上传图片标签页但未上传图片的情况
    if (iconType === 'upload' && (!iconParams.icon_upload || iconParams.icon_upload.trim() === '')) {
        e.preventDefault();
        alert('请上传图片文件');
        return;
    }
    
    // 根据当前选中的标签页设置最终值
    switch(iconType) {
        case 'fontawesome':
            const iconValue = iconParams.icon_fontawesome;
            const iconColor = iconParams.icon_fontawesome_color;
            document.getElementById('final_icon').value = JSON.stringify({
                type: 'font',
                name: iconValue,
                color: iconColor
            });
            break;
        case 'iconfont':
            document.getElementById('final_icon').value = iconParams.icon_iconfont;
            break;
        case 'upload':
            // 使用已上传的图片路径
            const uploadedPath = iconParams.icon_upload;
            if (uploadedPath) {
                document.getElementById('final_icon').value = uploadedPath;
            }
            break;
        case 'url':
            document.getElementById('final_icon').value = iconParams.icon_url;
            break;
    }
});

document.getElementById('color').addEventListener('input', function() {
    updatePreview();
    // 同步更新iconParams对象
    iconParams.category_color = this.value;
});

document.getElementById('icon_fontawesome').addEventListener('input', function() {
    updatePreview();
    // 同步更新iconParams对象
    iconParams.icon_fontawesome = this.value;
});

document.getElementById('icon_color').addEventListener('input', function() {
    updatePreview();
    // 同步更新iconParams对象
    iconParams.icon_fontawesome_color = this.value;
});

document.getElementById('icon_url').addEventListener('input', function() {
    updatePreview();
    // 同步更新iconParams对象
    iconParams.icon_url = this.value;
});

document.getElementById('iconfont_icon').addEventListener('input', function() {
    updatePreview();
    // 同步更新iconParams对象
    iconParams.icon_iconfont = this.value;
});

// 文件上传处理
document.getElementById('icon_file').addEventListener('change', function() {
    const uploadBtn = document.getElementById('upload_btn');
    const fileDisplay = document.getElementById('uploaded_file_display');
    const statusDiv = document.getElementById('upload_status');
    
    if (this.files && this.files[0]) {
        uploadBtn.disabled = false;
        fileDisplay.value = this.files[0].name;
        // 清除之前的状态信息
        statusDiv.innerHTML = '';
        // 重置上传按钮文本
        uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
    } else {
        uploadBtn.disabled = true;
        fileDisplay.value = '';
        statusDiv.innerHTML = '';
        uploadBtn.innerHTML = '<i class="bi bi-upload"></i> 上传';
    }
});

// 上传按钮点击事件
document.getElementById('upload_btn').addEventListener('click', function() {
    const fileInput = document.getElementById('icon_file');
    const statusDiv = document.getElementById('upload_status');
    
    if (!fileInput.files || !fileInput.files[0]) {
        statusDiv.innerHTML = '<div class="alert alert-warning">请先选择文件</div>';
        return;
    }
    
    const formData = new FormData();
    formData.append('icon_file', fileInput.files[0]);
    
    // 禁用上传按钮
    this.disabled = true;
    this.innerHTML = '<i class="bi bi-hourglass-split"></i> 上传中...';
    
    // 发送AJAX请求
    fetch('edit.php?ajax_upload=1', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 上传成功
            statusDiv.innerHTML = '<div class="alert alert-success">上传成功</div>';
            
            // 更新隐藏字段
            document.getElementById('uploaded_icon_path').value = data.file_name;
            
            // 更新文件显示
            document.getElementById('uploaded_file_display').value = data.file_name;
            
            // 同步更新iconParams对象
            iconParams.icon_upload = data.path;
            
            // 更新预览
            updateUploadedIconPreview(data.path);
            
            // 重置上传按钮状态为初始状态
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-upload"></i> 上传';
            
        } else {
            // 上传失败
            statusDiv.innerHTML = '<div class="alert alert-danger">上传失败：' + data.message + '</div>';
            
            // 重置上传按钮
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-upload"></i> 上传';
        }
    })
    .catch(error => {
        console.error('上传错误:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger">上传失败：网络错误</div>';
        
        // 重置上传按钮
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-upload"></i> 上传';
    });
});

// 更新已上传图标的预览
function updateUploadedIconPreview(imagePath) {
    const preview = document.getElementById('iconPreview');
    
    if (imagePath) {
        // 确保图片路径是完整的URL或相对路径
        let fullImagePath = imagePath;
        if (!imagePath.startsWith('http') && !imagePath.startsWith('/')) {
            // 如果是相对路径，添加正确的前缀
            fullImagePath = '../' + imagePath;
        }
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
    
    // 构建图标网格HTML - 直接使用完整的图标类名
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

// 选择图标 - 直接使用完整的图标类名，不再添加fa-前缀
function selectIcon(iconName) {
    // 直接使用完整的图标类名
    document.getElementById('icon_fontawesome').value = iconName;
    // 同步更新iconParams对象
    iconParams.icon_fontawesome = iconName;
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

// 更新图标预览
function updatePreview() {
    const iconType = document.querySelector('input[name="icon_type"]:checked').value;
    const previewContainer = document.getElementById('iconPreview');
    
    switch(iconType) {
        case 'fontawesome':
            const iconValue = document.getElementById('icon_fontawesome').value || 'fas fa-folder';
            // 直接使用完整的图标类名，不再拼接前缀
            const iconColor = document.getElementById('icon_color').value;
            previewContainer.innerHTML = `<i class="${iconValue} fa-3x" style="color: ${iconColor};"></i>`;
            break;
            
        case 'iconfont':
            const iconfontValue = document.getElementById('iconfont_icon').value;
            if (iconfontValue) {
                previewContainer.innerHTML = `<svg class="iconfont-svg" style="width: 48px; height: 48px; fill: currentColor;"><use xlink:href="#${iconfontValue}"></use></svg>`;
                // 设置定时器确保SVG正确渲染
                setTimeout(() => {
                    const svg = previewContainer.querySelector('svg use');
                    if (svg && !svg.getAttribute('xlink:href').startsWith('#')) {
                        svg.setAttribute('xlink:href', '#' + iconfontValue);
                    }
                }, 100);
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-font fa-3x"></i><p class="mt-2 mb-0">请输入图标名称</p></div>';
            }
            break;
            
        case 'upload':
            // 预览模块只预览已上传的服务器图片，完全解藕
            const uploadedPath = document.getElementById('uploaded_icon_path').value;
            if (uploadedPath) {
                updateUploadedIconPreview(uploadedPath);
                return;
            }
            
            // 没有已上传图片时，显示无预览状态（不显示本地文件）
            previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-image fa-3x"></i><p class="mt-2 mb-0">暂无图标</p></div>';
            break;
            
        case 'url':
            const url = document.getElementById('icon_url').value;
            if (url) {
                previewContainer.innerHTML = `<img src="${url}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
            } else {
                previewContainer.innerHTML = '<div class="text-muted"><i class="fas fa-link fa-3x"></i><p class="mt-2 mb-0">请输入图标URL</p></div>';
            }
            break;
    }
}

// 更新图标区域显示/隐藏
function updateIconSections() {
    // 隐藏所有图标区域
    document.querySelectorAll('.icon-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // 显示选中的图标区域
    const selectedType = document.querySelector('input[name="icon_type"]:checked').value;
    const sectionId = selectedType + '_section';
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = 'block';
    }
    
    // 更新最终图标类型
    document.getElementById('final_icon_type').value = selectedType;
    
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
document.getElementById('icon_fontawesome').addEventListener('input', function() {
    iconParams.icon_fontawesome = this.value;
    updatePreview();
});

document.getElementById('icon_color').addEventListener('input', function() {
    iconParams.icon_fontawesome_color = this.value;
    updatePreview();
});

document.getElementById('icon_url').addEventListener('input', function() {
    iconParams.icon_url = this.value;
    updatePreview();
});

document.getElementById('iconfont_icon').addEventListener('input', function() {
    iconParams.icon_iconfont = this.value;
    updatePreview();
});

// 页面加载完成后初始化图标区域
document.addEventListener('DOMContentLoaded', function() {
    updateIconSections();
});
</script>

<?php include '../templates/footer.php'; ?>