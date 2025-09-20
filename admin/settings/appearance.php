<?php
require_once '../includes/load.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

$settingsManager = get_settings_manager();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // 处理背景图片设置
    $background_type = $_POST['background_type'] ?? 'none';
    $background_image = $settingsManager->get('background_image');
    $background_color = $settingsManager->get('background_color');
    $background_api = $settingsManager->get('background_api');
    
    // 处理背景图片上传
    if ($background_type === 'image' && isset($_FILES['background_image_file']) && $_FILES['background_image_file']['error'] === UPLOAD_ERR_OK) {
        $fileUpload = get_file_upload_manager('backgrounds');
        $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $upload_result = $fileUpload->upload($_FILES['background_image_file']);
        if ($upload_result['success']) {
            // 删除旧背景图片
            if ($background_image && strpos($background_image, 'http') !== 0) {
                $old_bg_path = '../uploads/backgrounds/' . $background_image;
                if (file_exists($old_bg_path)) {
                    unlink($old_bg_path);
                }
            }
            $background_image = $upload_result['file_name'];
        } else {
            $errors[] = $upload_result['error'];
        }
    } elseif ($background_type === 'api') {
        $background_image = trim($_POST['background_api_url'] ?? '');
    } elseif ($background_type === 'color') {
        $background_color = trim($_POST['background_color_value'] ?? '#ffffff');
        $background_image = '';
    } elseif ($background_type === 'none') {
        $background_image = '';
        $background_color = '';
    }
    
    // 如果没有错误，保存设置
    if (empty($errors)) {
        $settingsManager->set('background_type', $background_type);
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
            'background_type' => $_POST['background_type'] ?? 'color',
            'background_color' => $_POST['background_color_value'] ?? '',
            'background_api' => $_POST['background_api_url'] ?? '',
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
    'background_color' => $settingsManager->get('background_color', '#f8f9fa'),
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
                                <input type="file" class="form-control" id="background_image_file" name="background_image_file" 
                                       accept=".jpg,.jpeg,.png,.gif,.webp">
                                <?php if ($settings['background_type'] === 'image' && $settings['background_image'] && strpos($settings['background_image'], 'http') !== 0): ?>
                                    <div class="mt-2">
                                        <img src="../../uploads/backgrounds/<?php echo $settings['background_image']; ?>" 
                                             alt="当前背景" style="max-height: 150px;" class="img-thumbnail">
                                        <input type="hidden" name="background_image_existing" value="<?php echo $settings['background_image']; ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">支持 JPG、PNG、GIF、WebP 格式</div>
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
                                       value="<?php echo $settings['background_type'] === 'api' ? htmlspecialchars($settings['background_image']) : ''; ?>"
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
                                       placeholder="//at.alicdn.com/t/font_1234567_abcd1234.css">
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
// 背景设置相关函数
document.addEventListener('DOMContentLoaded', function() {
    // 初始化背景预览
    updateBackgroundPreview();
    
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
        'bg-overlay',
        'header_bg_transparency', 
        'category_bg_transparency',
        'links_area_transparency',
        'link_card_transparency'
    ];
    
    transparencySliders.forEach(sliderId => {
        const slider = document.getElementById(sliderId);
        const valueSpan = document.getElementById(sliderId + '_value');
        
        if (slider && valueSpan) {
            slider.addEventListener('input', function() {
                valueSpan.textContent = Math.round(this.value * 100) + '%';
            });
        }
    });
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
    
    if (bgType === 'image') {
        const existingImage = document.querySelector('input[name="background_image_existing"]');
        if (existingImage && existingImage.value) {
            preview.style.backgroundImage = 'url(../../uploads/backgrounds/' + existingImage.value + ')';
            preview.style.backgroundColor = '';
            placeholder.style.display = 'none';
        } else {
            preview.style.backgroundImage = '';
            preview.style.backgroundColor = '#f8f9fa';
            placeholder.style.display = 'block';
        }
    } else if (bgType === 'color') {
        const color = document.getElementById('background_color_value').value;
        preview.style.backgroundImage = '';
        preview.style.backgroundColor = color;
        placeholder.style.display = 'none';
    } else if (bgType === 'api') {
        const apiUrl = document.getElementById('background_api_url').value;
        if (apiUrl) {
            preview.style.backgroundImage = 'url(' + apiUrl + ')';
            preview.style.backgroundColor = '';
            placeholder.style.display = 'none';
        } else {
            preview.style.backgroundImage = '';
            preview.style.backgroundColor = '#f8f9fa';
            placeholder.style.display = 'block';
        }
    } else {
        preview.style.backgroundImage = '';
        preview.style.backgroundColor = '#f8f9fa';
        placeholder.style.display = 'block';
    }
}
</script>

<?php include '../templates/footer.php'; ?>