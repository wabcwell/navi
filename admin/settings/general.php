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
    $site_name = trim($_POST['site_name'] ?? '');
    $site_description = trim($_POST['site_description'] ?? '');
    $site_keywords = trim($_POST['site_keywords'] ?? '');
    $site_url = trim($_POST['site_url'] ?? '');

    $items_per_page = intval($_POST['items_per_page'] ?? 20);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $maintenance_message = trim($_POST['maintenance_message'] ?? '');
    
    // 验证
    $errors = [];
    
    if (empty($site_name)) {
        $errors[] = '网站名称不能为空';
    }
    

    
    if ($items_per_page < 1 || $items_per_page > 100) {
        $errors[] = '每页显示数量必须在1-100之间';
    }
    
    // 验证上传设置
    $upload_max_size = intval($_POST['upload_max_size'] ?? 10);
    if ($upload_max_size < 1 || $upload_max_size > 100) {
        $errors[] = '最大上传大小必须在1-100MB之间';
    }
    
    $upload_allowed_types = trim($_POST['upload_allowed_types'] ?? '');
    if (empty($upload_allowed_types)) {
        $errors[] = '允许的文件类型不能为空';
    }
    
    // 处理网站图标上传
    $site_icon = $settingsManager->get('site_icon');
    if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === UPLOAD_ERR_OK) {
        $fileUpload = get_file_upload_manager('settings');
        $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png', 'ico']);
        $upload_result = $fileUpload->upload($_FILES['site_icon']);
        if ($upload_result['success']) {
            // 删除旧图标
            if ($site_icon) {
                $old_icon_path = '../uploads/settings/' . $site_icon;
                if (file_exists($old_icon_path)) {
                    unlink($old_icon_path);
                }
            }
            $site_icon = $upload_result['file_name'];
        } else {
            $errors[] = $upload_result['error'];
        }
    }

    // 处理网站Logo设置
    $site_logo_type = $_POST['site_logo_type'] ?? 'image';
    $site_logo = $settingsManager->get('site_logo');
    $site_logo_image = $settingsManager->get('site_logo_image', ''); // 最后一次上传的图片
    $site_logo_icon = $settingsManager->get('site_logo_icon', 'fas fa-home'); // 最后一次设置的图标
    $site_logo_color = $settingsManager->get('site_logo_color', '#007bff');

    // 处理删除Logo
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === 'on') {
        if ($site_logo && !str_starts_with($site_logo, 'fas ')) {
            $old_logo_path = '../uploads/settings/' . $site_logo;
            if (file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
        }
        $site_logo = '';
        $site_logo_image = '';
        $site_logo_icon = '';
    }

    if ($site_logo_type === 'image') {
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $fileUpload = get_file_upload_manager('settings');
            $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png']);
        $upload_result = $fileUpload->upload($_FILES['site_logo']);
            if ($upload_result['success']) {
                $site_logo = $upload_result['file_name'];
                $site_logo_image = '/uploads/settings/' . $upload_result['file_name']; // 保存相对于根目录的路径
            } else {
                $errors[] = $upload_result['error'];
            }
        } else {
            // 使用最后一次上传的图片
            $site_logo = $site_logo_image;
        }
    } elseif ($site_logo_type === 'icon') {
        $site_logo_icon = trim($_POST['site_logo_icon'] ?? '');
        $site_logo_color = trim($_POST['site_logo_color'] ?? '#007bff');
        $site_logo = $site_logo_icon; // 使用最后一次设置的图标
    } else {
        // 无Logo
        $site_logo = '';
    }

    $settingsManager->set('site_logo_type', $site_logo_type);
    $settingsManager->set('site_logo_color', $site_logo_color);

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
        $settingsManager->set('site_name', $site_name);
        $settingsManager->set('site_description', $site_description);
        $settingsManager->set('site_keywords', $site_keywords);
        $settingsManager->set('site_url', $site_url);

        $settingsManager->set('items_per_page', $items_per_page);
        $settingsManager->set('maintenance_mode', $maintenance_mode);
        $settingsManager->set('maintenance_message', $maintenance_message);
        $settingsManager->set('site_icon', $site_icon);
        $settingsManager->set('site_logo', $site_logo);
        $settingsManager->set('site_logo_type', $site_logo_type);
        $settingsManager->set('site_logo_color', $site_logo_color);
        $settingsManager->set('site_logo_image', $site_logo_image); // 保存最后一次上传的图片
        $settingsManager->set('site_logo_icon', $site_logo_icon); // 保存最后一次设置的图标
        $settingsManager->set('background_type', $background_type);
        $settingsManager->set('background_image', $background_image);
        $settingsManager->set('background_color', $background_color);
        $settingsManager->set('background_api', $background_api);
        
        // 页脚设置
        $settingsManager->set('footer_content', trim($_POST['footer_content'] ?? ''));

        $settingsManager->set('show_footer', isset($_POST['show_footer']) ? 1 : 0);
        
        // 上传设置
        $settingsManager->set('upload_max_size', max(1, min(100, intval($_POST['upload_max_size'] ?? 10))));
        $settingsManager->set('upload_allowed_types', trim($_POST['upload_allowed_types'] ?? 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar'));
        
        // 保存透明度设置
        $settingsManager->set('header_bg_transparency', max(0, min(1, floatval($_POST['header_bg_transparency'] ?? 0.85))));
        $settingsManager->set('category_bg_transparency', max(0, min(1, floatval($_POST['category_bg_transparency'] ?? 0.85))));
        $settingsManager->set('links_area_transparency', max(0, min(1, floatval($_POST['links_area_transparency'] ?? 0.85))));
        $settingsManager->set('link_card_transparency', max(0, min(1, floatval($_POST['link_card_transparency'] ?? 0.85))));
        
        $_SESSION['success'] = '网站设置更新成功';
        header('Location: general.php');
        exit();
    }
}

// 获取当前设置
$settings = [
    'site_name' => $settingsManager->get('site_name', '导航站'),
    'site_description' => $settingsManager->get('site_description', '一个简洁的导航网站'),
    'site_keywords' => $settingsManager->get('site_keywords', '导航,网站导航,网址导航'),
    'site_url' => $settingsManager->get('site_url', ''),

    'items_per_page' => $settingsManager->get('items_per_page', 20),
    'maintenance_mode' => $settingsManager->get('maintenance_mode', 0),
    'maintenance_message' => $settingsManager->get('maintenance_message', '网站正在维护中，请稍后再访问'),
    'site_icon' => $settingsManager->get('site_icon'),
    'site_logo' => $settingsManager->get('site_logo'),
    'site_logo_type' => $settingsManager->get('site_logo_type', 'image'),
    'site_logo_image' => $settingsManager->get('site_logo_image', ''),
    'site_logo_icon' => $settingsManager->get('site_logo_icon', 'fas fa-home'),
    'site_logo_color' => $settingsManager->get('site_logo_color', '#007bff'),
    'background_type' => $settingsManager->get('background_type', 'color'),
    'background_image' => $settingsManager->get('background_image', ''),
    'background_color' => $settingsManager->get('background_color', '#f8f9fa'),
    'background_api' => $settingsManager->get('background_api', ''),
    'footer_content' => $settingsManager->get('footer_content', '© 2024 导航站. All rights reserved.'),
    'show_footer' => $settingsManager->get('show_footer', 1),
];

$page_title = '网站设置';
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
            <a href="general.php" class="nav-link active">
                <i class="bi bi-gear"></i> 基本设置
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
                    <!-- 网站信息模块 -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">网站信息</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">网站名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_description" class="form-label">网站描述</label>
                                <textarea class="form-control" id="site_description" name="site_description" 
                                          rows="2"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_keywords" class="form-label">关键词</label>
                                <input type="text" class="form-control" id="site_keywords" name="site_keywords" 
                                       value="<?php echo htmlspecialchars($settings['site_keywords']); ?>"
                                       placeholder="多个关键词用逗号分隔">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_url" class="form-label">网站URL</label>
                                <input type="url" class="form-control" id="site_url" name="site_url" 
                                       value="<?php echo htmlspecialchars($settings['site_url']); ?>"
                                       placeholder="https://example.com">
                            </div>
                        </div>
                    </div>

                    <!-- 网站图标设置 -->
                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">网站图标设置</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_icon" class="form-label">网站图标</label>
                                <input type="file" class="form-control" id="site_icon" name="site_icon" 
                                       accept=".ico,.png,.jpg,.jpeg" onchange="previewImage(this, 'icon_preview')">
                                <div class="mt-2" id="icon_preview_container" style="display: none;">
                                    <img id="icon_preview" src="#" alt="图标预览" style="width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                                <?php if ($settings['site_icon']): ?>
                                    <div class="mt-2">
                                        <img src="../uploads/settings/<?php echo $settings['site_icon']; ?>" 
                                             class="image-preview" style="width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 4px;">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_icon" name="remove_icon">
                                            <label class="form-check-label" for="remove_icon">
                                                删除图标
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">建议尺寸：32×32像素，支持ICO、PNG、JPG格式</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Logo预览区域 -->
                        </div>
                    </div>

                    <!-- 网站Logo设置 -->
                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">网站Logo设置</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label class="form-label">Logo类型</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_image" 
                                           value="image" <?php echo (!isset($settings['site_logo_type']) || empty($settings['site_logo_type']) || $settings['site_logo_type'] === 'image') ? 'checked' : ''; ?>
                                           onchange="toggleLogoType()">
                                    <label class="btn btn-outline-primary" for="logo_type_image">上传图片</label>
                                    
                                    <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_icon" 
                                           value="icon" <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'icon') ? 'checked' : ''; ?>
                                           onchange="toggleLogoType()">
                                    <label class="btn btn-outline-primary" for="logo_type_icon">Font Awesome</label>
                                    
                                    <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_none" 
                                           value="none" <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'none') ? 'checked' : ''; ?>
                                           onchange="toggleLogoType()">
                                    <label class="btn btn-outline-primary" for="logo_type_none">无Logo</label>
                                </div>
                            </div>

                            <!-- 图片上传区域 -->
                            <div id="logo_image_section" style="display: <?php echo (empty($settings['site_logo_type']) || $settings['site_logo_type'] === 'image') ? 'block' : 'none'; ?>">
                                <label for="site_logo" class="form-label">上传Logo</label>
                                <input type="file" class="form-control" id="site_logo" name="site_logo" 
                                       accept=".png,.jpg,.jpeg" onchange="previewLogoImage(this)">
                                <div class="form-text">支持 JPG、PNG格式，建议尺寸 200×50 像素</div>
                            </div>

                            <!-- Font Awesome图标区域 -->
                            <div id="logo_icon_section" style="display: <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'icon') ? 'block' : 'none'; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="site_logo_icon" class="form-label">图标类名</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="site_logo_icon" name="site_logo_icon" 
                                                   value="<?php echo htmlspecialchars($settings['site_logo_icon'] ?? 'fas fa-home'); ?>"
                                                   placeholder="例如：fas fa-home" onchange="updateLogoPreview()">
                                            <button type="button" class="btn btn-outline-secondary" id="openLogoIconPicker">
                                                <i class="fas fa-icons"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">输入Font Awesome图标类名</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="site_logo_color" class="form-label">图标颜色</label>
                                        <input type="color" class="form-control form-control-color w-100" id="site_logo_color" name="site_logo_color" 
                                               value="<?php echo $settingsManager->get('site_logo_color', '#007bff'); ?>" 
                                               style="height: 38px;" onchange="updateLogoPreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Logo预览</h6>
                                </div>
                                <div class="card-body text-center" style="min-height: 120px;">
                                    <div id="logoPreviewContainer" 
                                         data-image-preview="<?php echo htmlspecialchars(str_replace('../uploads/', '/uploads/', $settings['site_logo_image'] ?? '')); ?>"
                                         data-icon-preview="<?php echo htmlspecialchars($settings['site_logo_icon'] ?? 'fas fa-home'); ?>"
                                         data-icon-color-preview="<?php echo htmlspecialchars($settings['site_logo_color'] ?? '#007bff'); ?>">
                                        <?php 
                                        // 根据当前选择的类型显示对应的预览
                                        if (empty($settings['site_logo_type']) || $settings['site_logo_type'] === 'image'): ?>
                                            <?php if (isset($settings['site_logo_image']) && $settings['site_logo_image']): ?>
                                                <img src="<?php echo htmlspecialchars(str_replace('../uploads/', '/uploads/', $settings['site_logo_image'])); ?>" 
                                                     alt="Logo预览" style="max-height: 80px;" class="img-thumbnail">
                                            <?php else: ?>
                                                <p class="text-muted">暂无图片Logo</p>
                                            <?php endif; ?>
                                        <?php elseif ($settings['site_logo_type'] === 'icon'): ?>
                                            <?php if (isset($settings['site_logo_icon']) && $settings['site_logo_icon']): ?>
                                                <i class="<?php echo htmlspecialchars($settings['site_logo_icon']); ?>" 
                                                   style="font-size: 40px; color: <?php echo $settingsManager->get('site_logo_color', '#007bff'); ?>"></i>
                                            <?php else: ?>
                                                <i class="fas fa-home" style="font-size: 40px; color: #007bff;"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p class="text-muted">无Logo</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!-- 背景图片设置模块 -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <hr>
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
                            <h5 class="mb-3">首页透明度设置</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="header_bg_transparency" class="form-label">标题背景透明度</label>
                                <input type="range" class="form-range" id="header_bg_transparency" name="header_bg_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settingsManager->get('header_bg_transparency', 0.85); ?>">
                                <div class="form-text">Logo和标题所在区域的透明度: <span id="header_transparency_value"><?php echo round($settingsManager->get('header_bg_transparency', 0.85) * 100); ?>%</span></div>
                            </div>

                            <div class="mb-3">
                                <label for="category_bg_transparency" class="form-label">分类背景透明度</label>
                                <input type="range" class="form-range" id="category_bg_transparency" name="category_bg_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settingsManager->get('category_bg_transparency', 0.85); ?>">
                                <div class="form-text">分类名称所在背景的透明度: <span id="category_transparency_value"><?php echo round($settingsManager->get('category_bg_transparency', 0.85) * 100); ?>%</span></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="links_area_transparency" class="form-label">链接区域背景透明度</label>
                                <input type="range" class="form-range" id="links_area_transparency" name="links_area_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settingsManager->get('links_area_transparency', 0.85); ?>">
                                <div class="form-text">链接区域整体的背景透明度: <span id="links_area_transparency_value"><?php echo round($settingsManager->get('links_area_transparency', 0.85) * 100); ?>%</span></div>
                            </div>

                            <div class="mb-3">
                                <label for="link_card_transparency" class="form-label">链接卡片透明度</label>
                                <input type="range" class="form-range" id="link_card_transparency" name="link_card_transparency" 
                                       min="0" max="1" step="0.05" value="<?php echo $settingsManager->get('link_card_transparency', 0.85); ?>">
                                <div class="form-text">单个链接卡片本身的透明度: <span id="link_card_transparency_value"><?php echo round($settingsManager->get('link_card_transparency', 0.85) * 100); ?>%</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">功能设置</h5>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                           name="maintenance_mode" value="1" 
                                           <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="maintenance_mode">
                                        维护模式
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">每页显示数量</label>
                                <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                       value="<?php echo $settings['items_per_page']; ?>" min="1" max="100">
                                <div class="form-text">
                                    控制首页每个分类下显示多少个链接，以及管理后台每页显示的记录数量
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <hr>
                            <h5 class="mb-3">页脚信息设置</h5>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="footer_content" class="form-label">页脚内容</label>
                                <textarea class="form-control" id="footer_content" name="footer_content" 
                                          rows="6" placeholder="支持HTML标签，可插入版权信息、备案号、联系方式等"><?php echo htmlspecialchars($settings['footer_content'] ?? '© 2024 导航站. All rights reserved.'); ?></textarea>
                                <div class="form-text">
                                    支持以下HTML标签：&lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, &lt;span&gt;, &lt;div&gt;, &lt;small&gt;<br>
                                    示例：&lt;p&gt;© 2024 导航站 &lt;a href="/about"&gt;关于我们&lt;/a&gt;&lt;/p&gt;
                                </div>
                            </div>
                            

                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="show_footer" 
                                           name="show_footer" value="1" 
                                           <?php echo ($settings['show_footer'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_footer">
                                        显示页脚
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">页脚预览</h6>
                                </div>
                                <div class="card-body">
                                    <div id="footer_preview" class="border rounded p-3 text-center" style="min-height: 100px; background-color: #f8f9fa;">
                                        <div id="footer_preview_content">
                                            <?php 
                                            $preview_content = $settings['footer_content'] ?? '© 2024 导航站. All rights reserved.';
                                            echo $preview_content; 
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="maintenance_message_container" 
                         style="display: <?php echo $settings['maintenance_mode'] ? 'block' : 'none'; ?>">
                        <label for="maintenance_message" class="form-label">维护提示信息</label>
                        <textarea class="form-control" id="maintenance_message" name="maintenance_message" 
                                  rows="2"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                    </div>
                    
                    <hr>
                    <h5 class="mb-3">上传设置</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="upload_max_size" class="form-label">最大上传大小 (MB)</label>
                                <input type="number" class="form-control" id="upload_max_size" name="upload_max_size" 
                                       value="<?php echo $settingsManager->get('upload_max_size', 10); ?>" 
                                       min="1" max="100" required>
                                <div class="form-text">单个文件的最大上传大小，1-100MB</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="upload_allowed_types" class="form-label">允许的文件类型</label>
                                <input type="text" class="form-control" id="upload_allowed_types" name="upload_allowed_types" 
                                       value="<?php echo htmlspecialchars($settingsManager->get('upload_allowed_types', 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar')); ?>" required>
                                <div class="form-text">用逗号分隔的文件扩展名，如：jpg,png,pdf</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">服务器限制</h6>
                            <ul class="list-unstyled small text-muted">
                                <li>PHP 最大上传: <?php echo ini_get('upload_max_filesize'); ?></li>
                                <li>POST 最大大小: <?php echo ini_get('post_max_size'); ?></li>
                                <li>内存限制: <?php echo ini_get('memory_limit'); ?></li>
                                <li>最大执行时间: <?php echo ini_get('max_execution_time'); ?>s</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> 保存设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('maintenance_mode').addEventListener('change', function() {
    document.getElementById('maintenance_message_group').style.display = this.checked ? 'block' : 'none';
});

document.getElementById('site_icon').addEventListener('change', function() {
    previewImage(this, 'iconPreview');
});

document.getElementById('site_logo').addEventListener('change', function() {
    previewImage(this, 'logoPreview');
});

// Logo类型切换
function toggleLogoType() {
    const imageRadio = document.getElementById('logo_type_image');
    const iconRadio = document.getElementById('logo_type_icon');
    const noneRadio = document.getElementById('logo_type_none');
    const imageSection = document.getElementById('logo_image_section');
    const iconSection = document.getElementById('logo_icon_section');

    if (imageSection) imageSection.style.display = imageRadio.checked ? 'block' : 'none';
    if (iconSection) iconSection.style.display = iconRadio.checked ? 'block' : 'none';

    updateLogoPreview();
}





// Logo类型切换功能
        function toggleLogoType() {
            const imageRadio = document.getElementById('logo_type_image');
            const iconRadio = document.getElementById('logo_type_icon');
            const noneRadio = document.getElementById('logo_type_none');
            const imageSection = document.getElementById('logo_image_section');
            const iconSection = document.getElementById('logo_icon_section');

            if (imageSection) {
                imageSection.style.display = imageRadio && imageRadio.checked ? 'block' : 'none';
            }
            if (iconSection) {
                iconSection.style.display = iconRadio && iconRadio.checked ? 'block' : 'none';
            }
            
            // 切换类型时更新预览
            updateLogoPreview();
        }

        // 更新Logo预览
        function updateLogoPreview() {
            const container = document.getElementById('logoPreviewContainer');
            if (!container) return;

            const imageRadio = document.getElementById('logo_type_image');
            const iconRadio = document.getElementById('logo_type_icon');
            const noneRadio = document.getElementById('logo_type_none');

            let html = '';

            if (imageRadio && imageRadio.checked) {
                // 图片类型：只显示图片选项的上传预览 - 完全独立的数据
                const imagePreview = container.dataset.imagePreview;
                if (imagePreview) {
                    html = `<img src="${imagePreview}" alt="Logo预览" style="max-height: 80px;" class="img-thumbnail">`;
                } else {
                    html = '<p class="text-muted">暂无图片Logo</p>';
                }
            } else if (iconRadio && iconRadio.checked) {
                // 图标类型：只显示图标选项的设置预览 - 完全独立的数据
                const iconPreview = container.dataset.iconPreview || 'fas fa-home';
                const iconColorPreview = container.dataset.iconColorPreview || '#007bff';
                
                const iconClassInput = document.getElementById('site_logo_icon');
                const iconColorInput = document.getElementById('site_logo_color');
                
                const iconClass = iconClassInput ? iconClassInput.value : iconPreview;
                const iconColor = iconColorInput ? iconColorInput.value : iconColorPreview;
                
                html = `<i class="${iconClass}" style="font-size: 40px; color: ${iconColor};"></i>`;
            } else if (noneRadio && noneRadio.checked) {
                // 无Logo类型：显示无Logo状态
                html = '<p class="text-muted">无Logo</p>';
            }

            container.innerHTML = html;
        }

        // 图片预览功能
        function previewLogoImage(input) {
            const container = document.getElementById('logoPreviewContainer');
            if (!container || !input.files || !input.files[0]) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // 更新预览区域显示新上传的图片 - 只存储到图片专用数据
                container.innerHTML = `<img src="${e.target.result}" alt="Logo预览" style="max-height: 80px;" class="img-thumbnail">`;
                // 存储到图片专用的数据属性，与图标数据完全隔离
                container.dataset.imagePreview = e.target.result; // 使用DataURL作为临时预览
            };
            reader.readAsDataURL(input.files[0]);
        }

        // Font Awesome 图标选择器
        function openLogoIconPicker() {
            // 移除已存在的模态框
            const existingModal = document.getElementById('logoIconModal');
            if (existingModal) {
                const modalInstance = bootstrap.Modal.getInstance(existingModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                existingModal.remove();
            }
            
            const modalDiv = document.createElement('div');
            modalDiv.id = 'logoIconModal';
            modalDiv.className = 'modal fade';
            modalDiv.setAttribute('tabindex', '-1');
            modalDiv.setAttribute('aria-hidden', 'true');
            
            const modalContent = document.createElement('div');
            modalContent.className = 'modal-dialog modal-lg';
            modalContent.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">选择 Font Awesome 图标</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="logoIconSearch" placeholder="搜索图标..." autocomplete="off">
                        </div>
                        <div class="row g-2" id="logoIconGrid" style="max-height: 400px; overflow-y: auto;">
                            ${getFontAwesomeIcons().map(icon => `
                                <div class="col-2">
                                    <button type="button" class="btn btn-outline-secondary w-100 logo-icon-btn" 
                                            onclick="selectLogoIcon('${icon}')" title="${icon}">
                                        <i class="fas fa-${icon} fa-lg"></i>
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
            modalDiv.appendChild(modalContent);
            document.body.appendChild(modalDiv);
            
            // 创建模态框实例
            const modalInstance = new bootstrap.Modal(modalDiv, {
                backdrop: 'static',
                keyboard: true,
                focus: true
            });
            modalInstance.show();
            
            // 搜索功能
            const searchInput = modalDiv.querySelector('#logoIconSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const buttons = modalDiv.querySelectorAll('.logo-icon-btn');
                    buttons.forEach(btn => {
                        const iconName = btn.getAttribute('title');
                        btn.parentElement.style.display = iconName.includes(searchTerm) ? 'block' : 'none';
                    });
                });
            }
            
            // 清理事件监听
            modalDiv.addEventListener('hidden.bs.modal', function() {
                setTimeout(() => {
                    if (modalDiv.parentNode) {
                        modalDiv.remove();
                    }
                }, 50);
            });
        }

        // 选择Logo图标
        function selectLogoIcon(iconName) {
            // 添加完整的Font Awesome类名前缀
            const fullIconClass = `fas fa-${iconName}`;
            document.getElementById('site_logo_icon').value = fullIconClass;
            updateLogoPreview();
            
            // 关闭并清理模态框
            const modal = document.getElementById('logoIconModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                // 清理DOM
                setTimeout(() => {
                    if (modal && modal.parentNode) {
                        modal.remove();
                    }
                }, 100);
            }
        }

        // Font Awesome 图标列表
        function getFontAwesomeIcons() {
            return [
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
                'lightbulb', 'eye', 'search', 'filter', 'sort', 'download', 'upload',
                'folder', 'folder-open', 'file', 'file-alt', 'file-code', 'file-text', 'archive'
            ];
        }

        // 初始化Logo设置
        document.addEventListener('DOMContentLoaded', function() {
            toggleLogoType();
            updateLogoPreview();
            
            const logoTypeRadios = document.querySelectorAll('input[name="site_logo_type"]');
            logoTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleLogoType);
            });
            
            // 监听图标和颜色变化
            const iconInput = document.getElementById('site_logo_icon');
            const colorInput = document.getElementById('site_logo_color');
            if (iconInput) iconInput.addEventListener('input', updateLogoPreview);
            if (colorInput) colorInput.addEventListener('input', updateLogoPreview);
            
            // 绑定图标选择器按钮
            const iconPickerBtn = document.getElementById('openLogoIconPicker');
            if (iconPickerBtn) {
                iconPickerBtn.addEventListener('click', openLogoIconPicker);
            }
            
            // 页脚预览功能
            const footerContent = document.getElementById('footer_content');
            const footerPreview = document.getElementById('footer_preview_content');
            
            if (footerContent && footerPreview) {
                // 实时预览页脚内容
                footerContent.addEventListener('input', function() {
                    let content = this.value;
                    // 简单的HTML标签白名单过滤
                    content = content.replace(/<script[^>]*>.*?<\/script>/gi, '');
                    content = content.replace(/<iframe[^>]*>.*?<\/iframe>/gi, '');
                    content = content.replace(/on\w+\s*=/gi, '');
                    
                    footerPreview.innerHTML = content || '<span class="text-muted">暂无内容</span>';
                });
                
                // 显示/隐藏页脚切换
                const showFooter = document.getElementById('show_footer');
                if (showFooter) {
                    showFooter.addEventListener('change', function() {
                        const footerSection = document.getElementById('footer_preview').parentElement.parentElement;
                        footerSection.style.display = this.checked ? 'block' : 'none';
                    });
                }
            }
        });

// 透明度滑块实时更新（直接显示透明度值）
function initTransparencySliders() {
    const transparencySliders = [
        'header_bg_transparency',
        'category_bg_transparency',
        'links_area_transparency',
        'link_card_transparency'
    ];

    transparencySliders.forEach(sliderId => {
        const slider = document.getElementById(sliderId);
        const valueDisplay = document.getElementById(sliderId + '_value');
        
        if (slider && valueDisplay) {
            // 初始化显示值
            const initialValue = parseFloat(slider.value);
            valueDisplay.textContent = Math.round(initialValue * 100) + '%';

            // 添加滑动事件监听
            slider.addEventListener('input', function() {
                // 直接显示透明度值
                const transparency = parseFloat(this.value);
                valueDisplay.textContent = Math.round(transparency * 100) + '%';
            });
        }
    });
}

// 确保DOM加载完成后执行初始化
document.addEventListener('DOMContentLoaded', initTransparencySliders);
</script>

<script>
function updateBackgroundPreview() {
    const type = document.querySelector('input[name="background_type"]:checked').value;
    const preview = document.getElementById('background_preview');
    
    preview.style.backgroundImage = '';
    preview.style.backgroundColor = '';
    preview.innerHTML = '';
    
    switch(type) {
        case 'image':
            const imageUrl = document.getElementById('background_image_existing')?.value || '';
            if (imageUrl) {
                preview.style.backgroundImage = `url('../../uploads/backgrounds/${imageUrl}')`;
            } else {
                const fileInput = document.getElementById('background_image_file');
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.style.backgroundImage = `url('${e.target.result}')`;
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    preview.innerHTML = '<div class="preview-content">暂无背景图片</div>';
                }
            }
            break;
        case 'color':
            const color = document.getElementById('background_color_value').value;
            preview.style.backgroundColor = color;
            break;
        case 'api':
            const apiUrl = document.getElementById('background_api_url').value;
            if (apiUrl) {
                preview.style.backgroundImage = `url('${apiUrl}')`;
            } else {
                preview.innerHTML = '<div class="preview-content">请输入API地址</div>';
            }
            break;
        case 'none':
            preview.style.backgroundColor = '#f8f9fa';
            preview.innerHTML = '<div class="preview-content">无背景</div>';
            break;
    }
}

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('background_preview').style.backgroundImage = `url('${e.target.result}')`;
        };
        reader.readAsDataURL(file);
    }
}

function toggleBackgroundFields() {
    const type = document.querySelector('input[name="background_type"]:checked').value;
    
    document.getElementById('bg_image_section').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('bg_color_section').style.display = type === 'color' ? 'block' : 'none';
    document.getElementById('bg_api_section').style.display = type === 'api' ? 'block' : 'none';
    
    updateBackgroundPreview();
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    toggleBackgroundFields();
    
    // 监听背景类型变化
    document.querySelectorAll('input[name="background_type"]').forEach(radio => {
        radio.addEventListener('change', toggleBackgroundFields);
    });
    
    // 监听颜色变化
    document.getElementById('background_color_value').addEventListener('input', updateBackgroundPreview);
    document.getElementById('background_color_text').addEventListener('input', function() {
        document.getElementById('background_color_value').value = this.value;
        updateBackgroundPreview();
    });
    
    // 监听API地址变化
    document.getElementById('background_api_url').addEventListener('input', updateBackgroundPreview);
    
    // 监听图片上传
    document.getElementById('background_image_file').addEventListener('change', handleImageUpload);
    
    // 初始化预览
    updateBackgroundPreview();
});

// 图片预览功能
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = document.getElementById(previewId + '_container');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
    }
}
</script>

<?php include '../templates/footer.php'; ?>