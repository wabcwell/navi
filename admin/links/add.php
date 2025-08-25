<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: ../index.php');
    exit();
}

$pdo = get_db_connection();

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY order_index ASC, name ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $display_order = intval($_POST['display_order'] ?? 0);
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
    $stmt = $pdo->prepare("SELECT id FROM navigation_links WHERE url = ?");
    $stmt->execute([$url]);
    if ($stmt->fetch()) {
        $errors[] = '该链接地址已存在';
    }
    
    // 处理图标
    $icon_filename = null;
    $icon_type = $_POST['icon_type'] ?? 'none';
    
    switch ($icon_type) {
        case 'fontawesome':
            $icon_class = trim($_POST['icon_fontawesome'] ?? '');
            $icon_color = trim($_POST['icon_color'] ?? '#007bff');
            if ($icon_class) {
                // 验证Font Awesome图标类名格式
                if (!preg_match('/^fa-[a-z0-9-]+$/', $icon_class)) {
                    $errors[] = '请输入有效的Font Awesome图标类名（例如：fa-home）';
                } else {
                    $icon_filename = $icon_class . '|' . $icon_color;
                }
            }
            break;
            
        case 'upload':
            if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_result = handle_file_upload($_FILES['icon_upload'], 'links');
                if ($upload_result['success']) {
                    $icon_filename = $upload_result['filename'];
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
                    $icon_filename = $icon_url;
                }
            }
            break;
            
        case 'none':
        default:
            $icon_filename = null;
            break;
    }
    
    // 如果没有错误，保存数据
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO navigation_links (title, url, description, category_id, icon_url, order_index, is_active, click_count, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");
            $stmt->execute([
                $title,
                $url,
                $description,
                $category_id,
                $icon_filename,
                $display_order,
                $is_active
            ]);
            
            $_SESSION['success'] = '链接添加成功';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $errors[] = '数据库错误：' . $e->getMessage();
            
            // 如果上传了图标但保存失败，删除图标文件
            if ($icon_filename) {
                $icon_path = '../uploads/links/' . $icon_filename;
                if (file_exists($icon_path)) {
                    unlink($icon_path);
                }
            }
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
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_fontawesome" value="fontawesome" <?php echo ($_POST['icon_type'] ?? 'upload') === 'fontawesome' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_fontawesome">Font Awesome</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_upload" value="upload" <?php echo ($_POST['icon_type'] ?? 'upload') === 'upload' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_upload">上传图片</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_url" value="url" <?php echo ($_POST['icon_type'] ?? 'upload') === 'url' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_url">URL地址</label>
                                    
                                    <input type="radio" class="btn-check" name="icon_type" id="icon_none" value="none" <?php echo ($_POST['icon_type'] ?? 'upload') === 'none' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="icon_none">无图标</label>
                                </div>
                            </div>

                            <!-- Font Awesome 图标选择 -->
                            <div id="fontawesome_section" style="display: none;">
                                <div class="row">
                                    <div class="col-8">
                                        <label for="icon_fontawesome_input" class="form-label">选择图标</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="icon_fontawesome_input" name="icon_fontawesome" 
                                                   placeholder="例如：fa-home" 
                                                   value="<?php echo htmlspecialchars($_POST['icon_fontawesome'] ?? ''); ?>">
                                            <button type="button" class="btn btn-outline-secondary" id="openIconPicker">
                                                <i class="fas fa-icons"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label for="icon_color" class="form-label">图标颜色</label>
                                        <input type="color" class="form-control form-control-color w-100" id="icon_color" 
                                               name="icon_color" value="<?php echo htmlspecialchars($_POST['icon_color'] ?? '#007bff'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- 图片上传 -->
                            <div id="upload_section" style="display: none;">
                                <label for="icon_upload" class="form-label">上传图标</label>
                                <input type="file" class="form-control" id="icon_upload" name="icon_upload" 
                                       accept="image/*">
                                <div class="form-text">支持 JPG、PNG、GIF、SVG 格式，建议尺寸 64x64 像素</div>
                            </div>

                            <!-- URL输入 -->
                            <div id="url_section" style="display: none;">
                                <label for="icon_url" class="form-label">图标URL</label>
                                <input type="url" class="form-control" id="icon_url" name="icon_url" 
                                       placeholder="https://example.com/icon.png" 
                                       value="<?php echo htmlspecialchars($_POST['icon_url'] ?? ''); ?>">
                                <div class="form-text">请输入有效的图片URL地址</div>
                            </div>
                        </div>

                        <!-- 图标预览 -->
                        <div class="col-md-5">
                            <label class="form-label">图标预览</label>
                            <div class="icon-preview-container text-center">
                                <div id="iconPreview" class="mb-2">
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
            const iconClass = document.getElementById('icon_fontawesome_input')?.value || 'fa-link';
            if (iconClass) {
                previewHtml = `<i class="fas ${iconClass}" style="font-size: 3rem; color: ${color}"></i>`;
            } else {
                previewHtml = '<div class="text-muted"><i class="fas fa-icons" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请选择图标</p></div>';
            }
            break;
            
        case 'upload':
            const uploadFile = document.getElementById('icon_upload')?.files?.[0];
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
            const url = document.getElementById('icon_url')?.value;
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
    radio.addEventListener('change', updateIconSections);
});

document.getElementById('icon_color').addEventListener('input', updatePreview);
document.getElementById('icon_fontawesome_input').addEventListener('input', updatePreview);
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
                document.getElementById('icon_fontawesome_input').value = icon;
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
        'fa-exclamation', 'fa-exclamation-triangle', 'fa-exclamation-circle',
        'fa-github', 'fa-gitlab', 'fa-bitbucket', 'fa-stack-overflow', 'fa-reddit',
        'fa-twitter', 'fa-facebook', 'fa-instagram', 'fa-linkedin', 'fa-youtube',
        'fa-discord', 'fa-slack', 'fa-telegram', 'fa-whatsapp', 'fa-weixin'
    ];
}

// 初始化
updateIconSections();

// 表单验证
(function() {
    'use strict';
    var form = document.getElementById('linkForm');
    form.addEventListener('submit', function(event) {
        const title = document.getElementById('title').value.trim();
        const url = document.getElementById('url').value.trim();
        const category = document.getElementById('category_id').value;

        if (!title || !url || !category) {
            event.preventDefault();
            event.stopPropagation();
            alert('请填写所有必填项');
        }
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php include '../templates/footer.php'; ?>