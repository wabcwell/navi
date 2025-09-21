<?php
require_once '../includes/load.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

$settingsManager = get_settings_manager();

// 处理AJAX背景图片上传
if (isset($_GET['ajax_upload_background']) && $_GET['ajax_upload_background'] == '1') {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['background_image'])) {
        echo json_encode(['success' => false, 'message' => '没有上传文件']);
        exit();
    }
    
    try {
        // 使用统一的文件上传管理器
        $fileUpload = get_file_upload_manager('backgrounds');
        $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
        
        $result = $fileUpload->upload($_FILES['background_image']);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true, 
                'file_name' => $result['file_name'],
                'file_url' => $result['file_url']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['error']]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '上传失败: ' . $e->getMessage()]);
    }
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax_upload_background'])) {
    $errors = [];
    
    // 获取表单数据
    $background_type = $_POST['background_type'] ?? 'color';
    $background_image = $settingsManager->get('background_image');
    $background_color = $settingsManager->get('background_color');
    $background_api = $settingsManager->get('background_api');
    
    // 始终检测并更新所有有值的字段，不管当前选中什么类型
    // 检测上传图片（如果隐藏字段有值）
    $uploaded_image = trim($_POST['background_image_uploaded'] ?? '');
    if (!empty($uploaded_image)) {
        // 删除旧背景图片
        if ($background_image && strpos($background_image, 'http') !== 0) {
            $old_bg_path = '../uploads/backgrounds/' . $background_image;
            if (file_exists($old_bg_path)) {
                unlink($old_bg_path);
            }
        }
        $background_image = $uploaded_image;
    }
    
    // 检测颜色值更新
    $color_value = trim($_POST['background_color_value'] ?? '');
    if (!empty($color_value)) {
        $background_color = $color_value;
    }
    
    // 检测API地址更新
    $api_url = trim($_POST['background_api_url'] ?? '');
    if (!empty($api_url)) {
        $background_api = $api_url;
    }
    
    // 根据背景类型进行验证（只验证当前选中类型是否必填项已填写）
    if ($background_type === 'api' && empty($background_api)) {
        $errors[] = '请填写第三方API地址';
    } elseif ($background_type === 'image' && empty($background_image)) {
        $errors[] = '请先上传背景图片';
    }
    
    // 如果没有错误，保存设置
    if (empty($errors)) {
        // 保存背景类型
        $settingsManager->set('background_type', $background_type);
        
        // 保存所有背景参数，不清空其他类型的参数
        $settingsManager->set('background_image', $background_image);
        $settingsManager->set('background_color', $background_color);
        $settingsManager->set('background_api', $background_api);
        
        // 保存透明度设置
        $settingsManager->set('bg-overlay', max(0, min(1, floatval($_POST['bg-overlay'] ?? 0.2))));
        $settingsManager->set('header_bg_transparency', max(0, min(1, floatval($_POST['header_bg_transparency'] ?? 0.85))));
        $settingsManager->set('category_bg_transparency', max(0, min(1, floatval($_POST['category_bg_transparency'] ?? 0.85))));
        $settingsManager->set('links_area_transparency', max(0, min(1, floatval($_POST['links_area_transparency'] ?? 0.85))));
        $settingsManager->set('link_card_transparency', max(0, min(1, floatval($_POST['link_card_transparency'] ?? 0.85))));
        
        // 保存iconfont设置
        $settingsManager->set('iconfont', trim($_POST['iconfont'] ?? ''));
        
        // 记录操作日志
        $logsManager = get_logs_manager();
        $settings_data = [
            'background_type' => $background_type,
            'background_color' => $background_color,
            'background_api' => $background_api,
            'background_image' => $background_image,
            'bg_overlay' => $_POST['bg_overlay'] ?? 0.2,
            'header_bg_transparency' => $_POST['header_bg_transparency'] ?? 0.85,
            'category_bg_transparency' => $_POST['category_bg_transparency'] ?? 0.85,
            'links_area_transparency' => $_POST['links_area_transparency'] ?? 0.85,
            'link_card_transparency' => $_POST['link_card_transparency'] ?? 0.85,
            'iconfont' => $_POST['iconfont'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $logsManager->addOperationLog([
            'userid' => $_SESSION['user_id'] ?? 0,
            'operation_module' => '设置',
            'operation_type' => '编辑',
            'operation_details' => array_merge($settings_data, ['target' => '外观设置']),
            'status' => '成功'
        ]);
        
        $_SESSION['success'] = '外观设置更新成功';
        header('Location: appearance.php');
        exit();
    }
}

// 获取当前设置
$settings = [
    'background_type' => $settingsManager->get('background_type', 'color'),
    'background_image' => $settingsManager->get('background_image', ''),
    'background_color' => $settingsManager->get('background_color', '#ffffff'),
    'background_api' => $settingsManager->get('background_api', ''),
    'bg-overlay' => $settingsManager->get('bg-overlay', 0.2),
    'header_bg_transparency' => $settingsManager->get('header_bg_transparency', 0.85),
    'category_bg_transparency' => $settingsManager->get('category_bg_transparency', 0.85),
    'links_area_transparency' => $settingsManager->get('links_area_transparency', 0.85),
    'link_card_transparency' => $settingsManager->get('link_card_transparency', 0.85),
    'iconfont' => $settingsManager->get('iconfont', ''),
];

$page_title = '外观设置';
include '../templates/header.php'; ?>

<style>
.background-preview {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 200px;
    border-radius: 0.375rem;
}

.color-preview {
    min-height: 200px;
    border-radius: 0.375rem;
}

.preview-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #6c757d;
    font-size: 14px;
}

/* 背景预览区域样式优化 */
#background_preview {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    width: 100%;
    height: 200px;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    position: relative;
    overflow: hidden;
}

#background_preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
</style>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- 顶部导航栏 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="nav nav-pills">
            <a href="general.php" class="nav-link">
                <i class="bi bi-gear"></i> 基本设置
            </a>
            <a href="appearance.php" class="nav-link active">
                <i class="bi bi-palette"></i> 外观设置
            </a>
            <a href="security.php" class="nav-link">
                <i class="bi bi-shield-lock"></i> 安全设置
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- 背景图片设置模块 -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">背景图片设置</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">背景类型</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="background_type" id="bg_none" 
                                           value="none" <?php echo $settings['background_type'] === 'none' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bg_none">
                                        无背景
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="background_type" id="bg_image" 
                                           value="image" <?php echo $settings['background_type'] === 'image' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bg_image">
                                        上传图片
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="background_type" id="bg_color" 
                                           value="color" <?php echo $settings['background_type'] === 'color' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bg_color">
                                        纯色背景
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="background_type" id="bg_api" 
                                           value="api" <?php echo $settings['background_type'] === 'api' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bg_api">
                                        第三方API
                                    </label>
                                </div>
                            </div>

                            <div id="bg_image_section" class="mb-3" style="display: <?php echo $settings['background_type'] === 'image' ? 'block' : 'none'; ?>">
                                <label for="background_image_file" class="form-label">选择背景图片</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="background_image_file" name="background_image_file" 
                                           accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <button type="button" class="btn btn-outline-secondary" id="upload_background_btn" disabled>
                                        <i class="bi bi-upload"></i> 上传
                                    </button>
                                </div>
                                <div id="upload_status" class="mt-2" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> 正在上传图片...
                                    </div>
                                </div>
                                <div id="upload_result" class="mt-2" style="display: none;">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> 图片上传成功！
                                    </div>
                                </div>
                                <div class="form-text">支持 JPG、PNG、GIF、WebP 格式</div>
                                <!-- 隐藏字段用于存储上传的图片文件名 -->
                                <input type="hidden" id="background_image_uploaded" name="background_image_uploaded" value="">
                                <!-- 隐藏字段用于存储现有的背景图片 -->
                                <input type="hidden" id="background_image_existing" name="background_image_existing" value="<?php echo htmlspecialchars($settings['background_image'] ?? ''); ?>">
                            </div>

                            <div id="bg_color_section" class="mb-3" style="display: <?php echo $settings['background_type'] === 'color' ? 'block' : 'none'; ?>">
                                <label for="background_color_value" class="form-label">背景颜色</label>
                                <input type="color" class="form-control form-control-color" id="background_color_value" 
                                       name="background_color_value" value="<?php echo $settings['background_color']; ?>" style="width: 50px; height: 38px;">
                                <input type="text" class="form-control mt-2" value="<?php echo $settings['background_color']; ?>" 
                                       id="background_color_text" placeholder="#ffffff">
                            </div>

                            <div id="bg_api_section" class="mb-3" style="display: <?php echo $settings['background_type'] === 'api' ? 'block' : 'none'; ?>">
                                <label for="background_api_url" class="form-label">API地址</label>
                                <input type="url" class="form-control" id="background_api_url" name="background_api_url" 
                                       value="<?php echo htmlspecialchars($settings['background_api'] ?? ''); ?>"
                                       placeholder="https://source.unsplash.com/1920x1080/?nature">
                                <div class="form-text">
                                    支持 Unsplash、Lorem Picsum 等图片API<br>
                                    示例：<code>https://source.unsplash.com/1920x1080/?nature,water</code>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">实时预览</h6>
                                </div>
                                <div class="card-body">
                                    <div id="background_preview" class="border rounded p-3" style="min-height: 200px; position: relative;">
                                        <div class="text-center text-muted" id="preview_placeholder">
                                            <i class="bi bi-image" style="font-size: 48px;"></i>
                                            <p class="mt-2 mb-0">背景预览</p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> 
                                            <span id="current_bg_type_info">当前类型：<?php echo $settings['background_type'] === 'none' ? '无背景' : ($settings['background_type'] === 'image' ? '上传图片' : ($settings['background_type'] === 'color' ? '纯色背景' : '第三方API')); ?></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">阿里iconfont设置</h5>
                            <div class="mb-3">
                                <label for="iconfont" class="form-label">阿里iconfont地址</label>
                                <input type="text" class="form-control" id="iconfont" name="iconfont" 
                                       value="<?php echo htmlspecialchars($settings['iconfont'] ?? ''); ?>"
                                       placeholder="//at.alicdn.com/t/font_1234567_abcd1234.js">
                                <div class="form-text">
                                    请输入自定义阿里云图标Symbol地址，例如：//at.alicdn.com/t/font_1234567_abcd1234.js
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">首页透明度设置</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bg-overlay" class="form-label">整体背景透明度</label>
                               <input type="range" class="form-range" id="bg-overlay" name="bg-overlay"
                                      min="0" max="1" step="0.05" value="<?php echo $settings['bg-overlay']; ?>">
                               <div class="form-text">整个网站背景的遮罩透明度: <span id="bg-overlay_value"><?php echo round($settings['bg-overlay'] * 100); ?>%</span></div>
                            </div>

                            <div class="mb-3">
                                <label for="header_bg_transparency" class="form-label">标题背景透明度</label>
                                <input type="range" class="form-range" id="header_bg_transparency" name="header_bg_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settings['header_bg_transparency']; ?>">
                                <div class="form-text">Logo和标题所在区域的透明度: <span id="header_transparency_value"><?php echo round($settings['header_bg_transparency'] * 100); ?>%</span></div>
                            </div>

                            <div class="mb-3">
                                <label for="category_bg_transparency" class="form-label">分类背景透明度</label>
                                <input type="range" class="form-range" id="category_bg_transparency" name="category_bg_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settings['category_bg_transparency']; ?>">
                                <div class="form-text">分类名称所在背景的透明度: <span id="category_transparency_value"><?php echo round($settings['category_bg_transparency'] * 100); ?>%</span></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="links_area_transparency" class="form-label">链接区域背景透明度</label>
                                <input type="range" class="form-range" id="links_area_transparency" name="links_area_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settings['links_area_transparency']; ?>">
                                <div class="form-text">链接区域整体的背景透明度: <span id="links_area_transparency_value"><?php echo round($settings['links_area_transparency'] * 100); ?>%</span></div>
                            </div>

                            <div class="mb-3">
                                <label for="link_card_transparency" class="form-label">链接卡片透明度</label>
                                <input type="range" class="form-range" id="link_card_transparency" name="link_card_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settings['link_card_transparency']; ?>">
                                <div class="form-text">单个链接卡片本身的透明度: <span id="link_card_transparency_value"><?php echo round($settings['link_card_transparency'] * 100); ?>%</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> 保存设置
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 背景图片上传功能
function initBackgroundUpload() {
    const fileInput = document.getElementById('background_image_file');
    const uploadBtn = document.getElementById('upload_background_btn');
    const uploadStatus = document.getElementById('upload_status');
    const uploadResult = document.getElementById('upload_result');
    // 移除了current_background_preview区域，使用右侧主要预览区域代替
    const uploadedImageField = document.getElementById('background_image_uploaded');
    
    // 文件选择变化时启用/禁用上传按钮
    fileInput.addEventListener('change', function() {
        uploadBtn.disabled = !this.files.length;
    });
    
    // 上传按钮点击事件
    uploadBtn.addEventListener('click', function() {
        const file = fileInput.files[0];
        if (!file) {
            alert('请先选择图片文件');
            return;
        }
        
        // 显示上传状态
        uploadStatus.style.display = 'block';
        uploadResult.style.display = 'none';
        uploadBtn.disabled = true;
        
        // 创建FormData对象
        const formData = new FormData();
        formData.append('background_image', file);
        
        // 发送AJAX请求 - 使用统一的文件上传接口
        fetch('appearance.php?ajax_upload_background=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            uploadStatus.style.display = 'none';
            
            if (data.success) {
                // 显示成功消息
                uploadResult.style.display = 'block';
                
                // 更新隐藏字段 - 保存完整路径
                uploadedImageField.value = data.file_url;
                
                // 重置文件输入控件
                fileInput.value = '';
                uploadBtn.disabled = true;
                
                // 立即更新右侧主要预览区域
                const mainPreview = document.getElementById('background_preview');
                const placeholder = document.getElementById('preview_placeholder');
                if (mainPreview && placeholder) {
                    // 创建图片对象来预加载，确保图片能正常加载
                    const img = new Image();
                    img.onload = function() {
                        mainPreview.style.backgroundImage = `url(${data.file_url})`;
                        mainPreview.style.backgroundColor = '';
                        placeholder.style.display = 'none';
                    };
                    img.onerror = function() {
                        console.error('图片加载失败:', data.file_url);
                        // 即使加载失败，也尝试设置背景
                        mainPreview.style.backgroundImage = `url(${data.file_url})`;
                        mainPreview.style.backgroundColor = '';
                        placeholder.style.display = 'none';
                    };
                    img.src = data.file_url;
                }
                
                // 3秒后隐藏成功消息
                setTimeout(() => {
                    uploadResult.style.display = 'none';
                }, 3000);
            } else {
                // 显示错误消息
                uploadResult.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> ${data.error}</div>`;
                uploadResult.style.display = 'block';
                uploadBtn.disabled = false;
            }
        })
        .catch(error => {
            uploadStatus.style.display = 'none';
            uploadResult.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> 上传失败，请重试</div>';
            uploadResult.style.display = 'block';
            uploadBtn.disabled = false;
            console.error('Upload error:', error);
        });
    });
}

// 背景设置相关函数
document.addEventListener('DOMContentLoaded', function() {
    // 初始化背景预览
    updateBackgroundPreview();
    
    // 初始化背景图片上传功能
    initBackgroundUpload();
    
    // 绑定背景类型切换事件
    document.querySelectorAll('input[name="background_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleBackgroundSections();
            updateBackgroundPreview();
        });
    });
    
    // 绑定背景颜色输入事件
    const bgColorInput = document.getElementById('background_color_value');
    const bgColorText = document.getElementById('background_color_text');
    
    bgColorInput.addEventListener('input', function() {
        bgColorText.value = this.value;
        updateBackgroundPreview();
    });
    
    bgColorText.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            bgColorInput.value = this.value;
            updateBackgroundPreview();
        }
    });
    
    // 绑定背景API输入事件
    document.getElementById('background_api_url').addEventListener('input', function() {
        updateBackgroundPreview();
    });
    
    // 绑定透明度滑块事件
    const transparencySliders = [
        { sliderId: 'bg-overlay', valueId: 'bg-overlay_value' },
        { sliderId: 'header_bg_transparency', valueId: 'header_transparency_value' },
        { sliderId: 'category_bg_transparency', valueId: 'category_transparency_value' },
        { sliderId: 'links_area_transparency', valueId: 'links_area_transparency_value' },
        { sliderId: 'link_card_transparency', valueId: 'link_card_transparency_value' }
    ];
    
    transparencySliders.forEach(item => {
        const slider = document.getElementById(item.sliderId);
        const valueSpan = document.getElementById(item.valueId);
        
        if (slider && valueSpan) {
            slider.addEventListener('input', function() {
                valueSpan.textContent = Math.round(this.value * 100) + '%';
            });
        }
    });
    
    // 表单提交前的验证
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const bgType = document.querySelector('input[name="background_type"]:checked').value;
            
            if (bgType === 'image') {
                const uploadedImage = document.getElementById('background_image_uploaded').value;
                const existingImage = document.getElementById('background_image_existing').value;
                // 检查是否有图片：新上传的 或 数据库中已有的
                if (!uploadedImage && !existingImage) {
                    e.preventDefault();
                    alert('请先上传背景图片');
                    return false;
                }
            } else if (bgType === 'api') {
                const apiUrl = document.getElementById('background_api_url').value;
                if (!apiUrl) {
                    e.preventDefault();
                    alert('请填写第三方API地址');
                    return false;
                }
            }
        });
    }
});

function toggleBackgroundSections() {
    const bgType = document.querySelector('input[name="background_type"]:checked').value;
    
    // 隐藏所有背景设置区域
    document.getElementById('bg_image_section').style.display = 'none';
    document.getElementById('bg_color_section').style.display = 'none';
    document.getElementById('bg_api_section').style.display = 'none';
    
    // 显示对应的背景设置区域
    if (bgType === 'image') {
        document.getElementById('bg_image_section').style.display = 'block';
    } else if (bgType === 'color') {
        document.getElementById('bg_color_section').style.display = 'block';
    } else if (bgType === 'api') {
        document.getElementById('bg_api_section').style.display = 'block';
    }
}

function updateBackgroundPreview() {
            const bgType = document.querySelector('input[name="background_type"]:checked').value;
            const preview = document.getElementById('background_preview');
            const placeholder = document.getElementById('preview_placeholder');
            const typeInfo = document.getElementById('current_bg_type_info');
            
            // 更新类型信息显示
            let typeText = '';
            if (bgType === 'none') {
                typeText = '无背景';
            } else if (bgType === 'image') {
                typeText = '上传图片';
            } else if (bgType === 'color') {
                typeText = '纯色背景';
            } else if (bgType === 'api') {
                typeText = '第三方API';
            }
            typeInfo.textContent = '当前类型：' + typeText;
            
            if (bgType === 'image') {
                // 基于background_image参数的值实现预览功能
                const uploadedImage = document.getElementById('background_image_uploaded');
                const existingImage = document.getElementById('background_image_existing');
                
                let imagePath = '';
                if (uploadedImage && uploadedImage.value) {
                    imagePath = uploadedImage.value;
                } else if (existingImage && existingImage.value) {
                    imagePath = existingImage.value;
                }
                
                if (imagePath) {
                    // 如果路径不包含 /uploads/backgrounds/，添加相对路径前缀
                    let fullPath = imagePath;
                    if (imagePath.indexOf('/uploads/backgrounds/') !== 0 && imagePath.indexOf('http') !== 0) {
                        fullPath = '../../uploads/backgrounds/' + imagePath;
                    }
                    preview.style.backgroundImage = 'url(' + fullPath + ')';
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    preview.style.backgroundRepeat = 'no-repeat';
                    preview.style.backgroundColor = '';
                    placeholder.style.display = 'none';
                } else {
                    preview.style.backgroundImage = '';
                    preview.style.backgroundColor = '#f8f9fa';
                    placeholder.style.display = 'block';
                }
            } else if (bgType === 'color') {
                // 基于background_color参数的值实现预览功能，预设颜色为白色
                const color = document.getElementById('background_color_value').value;
                preview.style.backgroundImage = '';
                preview.style.backgroundColor = color;
                placeholder.style.display = 'none';
            } else if (bgType === 'api') {
                // 基于background_api参数的值实现预览功能
                const apiUrl = document.getElementById('background_api_url').value;
                if (apiUrl) {
                    preview.style.backgroundImage = 'url(' + apiUrl + ')';
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                    preview.style.backgroundRepeat = 'no-repeat';
                    preview.style.backgroundColor = '';
                    placeholder.style.display = 'none';
                } else {
                    preview.style.backgroundImage = '';
                    preview.style.backgroundColor = '#f8f9fa';
                    placeholder.style.display = 'block';
                }
            } else {
                // 无背景类型
                preview.style.backgroundImage = '';
                preview.style.backgroundColor = '#f8f9fa';
                placeholder.style.display = 'block';
            }
        }
</script>

<?php include '../templates/footer.php'; ?>