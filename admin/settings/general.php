<?php
require_once '../includes/load.php';
require_once '../includes/fontawesome-icons.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: ../login.php');
    exit();
}

// 处理AJAX图标上传
if (isset($_GET['ajax_upload_icon']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // 检查是否是AJAX请求
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            throw new Exception('无效的请求类型');
        }
        
        // 验证文件上传
        if (!isset($_FILES['site_icon'])) {
            throw new Exception('未找到上传文件');
        }
        
        if ($_FILES['site_icon']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败，错误代码: ' . $_FILES['site_icon']['error']);
        }
        
        // 获取文件上传管理器
        $uploadManager = get_file_upload_manager('settings');
        $uploadManager->setAllowedTypes(['ico', 'png', 'jpg', 'jpeg']);
        
        // 处理文件上传
        $uploadResult = $uploadManager->upload($_FILES['site_icon']);
        
        if ($uploadResult['success']) {
            // 获取设置管理器并保存新图标
            $settingsManager = get_settings_manager();
            
            // 保存新图标到数据库（存储完整路径）
            $settingsManager->set('site_icon', '/uploads/settings/' . $uploadResult['file_name']);
            
            echo json_encode([
                'success' => true,
                'message' => '图标上传成功',
                'path' => $uploadResult['file_url']
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

// 处理AJAX Logo上传
if (isset($_GET['ajax_upload']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // 检查是否是AJAX请求
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            throw new Exception('无效的请求类型');
        }
        
        // 验证文件上传
        if (!isset($_FILES['site_logo'])) {
            throw new Exception('未找到上传文件');
        }
        
        if ($_FILES['site_logo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败，错误代码: ' . $_FILES['site_logo']['error']);
        }
        
        // 获取文件上传管理器
        $uploadManager = get_file_upload_manager('settings');
        
        // 处理文件上传
        $uploadResult = $uploadManager->upload($_FILES['site_logo']);
        
        if ($uploadResult['success']) {
            echo json_encode([
                'success' => true,
                'message' => '文件上传成功',
                'path' => $uploadResult['file_url'],
                'filename' => $uploadResult['file_name']
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
    
    // 处理网站图标
    $site_icon = $settingsManager->get('site_icon');
    
    // 处理网站图标上传
    if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === UPLOAD_ERR_OK) {
        $fileUpload = get_file_upload_manager('settings');
        $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png', 'ico']);
        $upload_result = $fileUpload->upload($_FILES['site_icon']);
        if ($upload_result['success']) {
            $site_icon = '/uploads/settings/' . $upload_result['file_name'];
        } else {
            $errors[] = $upload_result['error'];
        }
    }

    // 处理网站Logo设置
    $site_logo_type = $_POST['site_logo_type'] ?? 'image';
    $site_logo_image = $settingsManager->get('site_logo_image', ''); // 最后一次上传的图片
    $site_logo_icon = $settingsManager->get('site_logo_icon', 'fas fa-home'); // 最后一次设置的图标
    $site_logo_color = $settingsManager->get('site_logo_color', '#007bff');

    // 处理删除Logo
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        if ($site_logo_image && !str_starts_with($site_logo_image, 'fas ')) {
            $old_logo_path = '../uploads/settings/' . basename($site_logo_image);
            if (file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
        }
        $site_logo_image = '';
        $site_logo_icon = '';
    }

    // 处理网站Logo设置
    $site_logo_type = $_POST['site_logo_type'] ?? 'image';
    $site_logo_image = $settingsManager->get('site_logo_image', ''); // 最后一次上传的图片
    $site_logo_icon = $settingsManager->get('site_logo_icon', 'fas fa-home'); // 最后一次设置的图标
    $site_logo_color = $settingsManager->get('site_logo_color', '#007bff');

    // 处理删除Logo
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        if ($site_logo_image && !str_starts_with($site_logo_image, 'fas ')) {
            $old_logo_path = '../uploads/settings/' . basename($site_logo_image);
            if (file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
        }
        $site_logo_image = '';
        $site_logo_icon = '';
    }

    // 根据Logo类型处理数据
    if ($site_logo_type === 'image') {
        // 处理图片上传
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $fileUpload = get_file_upload_manager('settings');
            $fileUpload->setAllowedTypes(['jpg', 'jpeg', 'png']);
            $upload_result = $fileUpload->upload($_FILES['site_logo']);
            if ($upload_result['success']) {
                $site_logo_image = '/uploads/settings/' . $upload_result['file_name'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        // 验证图片类型是否有图片
        if (empty($site_logo_image)) {
            $errors[] = '请上传Logo图片';
        }
    } elseif ($site_logo_type === 'icon') {
        // 更新图标和颜色
        $site_logo_icon = trim($_POST['site_logo_icon'] ?? '');
        $site_logo_color = trim($_POST['site_logo_color'] ?? '#007bff');
    } elseif ($site_logo_type === 'iconfont') {
        // 更新Iconfont图标
        $site_logo_iconfont = trim($_POST['site_logo_iconfont'] ?? '');
        if (empty($site_logo_iconfont)) {
            $errors[] = '请输入Iconfont图标名称';
        }
    }
    
    // 如果没有错误，保存设置
    if (empty($errors)) {
        // 调试信息
        error_log("开始保存设置...");
        error_log("site_icon值: " . $site_icon);
        
        $result1 = $settingsManager->set('site_name', $site_name);
        $result2 = $settingsManager->set('site_description', $site_description);
        $result3 = $settingsManager->set('site_keywords', $site_keywords);
        $result4 = $settingsManager->set('site_url', $site_url);

        $result5 = $settingsManager->set('items_per_page', $items_per_page);
        $result6 = $settingsManager->set('maintenance_mode', $maintenance_mode);
        $result7 = $settingsManager->set('maintenance_message', $maintenance_message);
        $result8 = $settingsManager->set('site_icon', $site_icon);
        $result9 = $settingsManager->set('site_logo_type', $site_logo_type);
        $result10 = $settingsManager->set('site_logo_color', $site_logo_color);
        $result11 = $settingsManager->set('site_logo_image', $site_logo_image); // 保存最后一次上传的图片
        $result12 = $settingsManager->set('site_logo_icon', $site_logo_icon); // 保存最后一次设置的图标
        $result13 = $settingsManager->set('site_logo_iconfont', $site_logo_iconfont ?? ''); // 保存Iconfont图标
        
        // 页脚设置
        $result14 = $settingsManager->set('footer_content', trim($_POST['footer_content'] ?? ''));

        $result15 = $settingsManager->set('show_footer', isset($_POST['show_footer']) ? 1 : 0);
        
        // 上传设置
        $result16 = $settingsManager->set('upload_max_size', max(1, min(100, intval($_POST['upload_max_size'] ?? 10))));
        $result17 = $settingsManager->set('upload_allowed_types', trim($_POST['upload_allowed_types'] ?? 'jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar'));
        
        error_log("设置保存结果: site_icon=" . ($result8 ? '成功' : '失败'));
        
        // 记录操作日志
        $logsManager = get_logs_manager();
        $logsManager->addOperationLog([
            'userid' => $_SESSION['user_id'] ?? 0,
            'operation_module' => '设置',
            'operation_type' => '编辑',
            'operation_details' => [
                'site_name' => $_POST['site_name'] ?? '',
                'site_description' => $_POST['site_description'] ?? '',
                'site_keywords' => $_POST['site_keywords'] ?? '',
                'items_per_page' => $_POST['items_per_page'] ?? 20,
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                'target' => '基本设置',
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'status' => '成功'
        ]);
        
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
    'site_logo_type' => $settingsManager->get('site_logo_type', 'image'),
    'site_logo_image' => $settingsManager->get('site_logo_image', ''),
    'site_logo_icon' => $settingsManager->get('site_logo_icon', 'fas fa-home'),
    'site_logo_iconfont' => $settingsManager->get('site_logo_iconfont', ''),
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
            <a href="appearance.php" class="nav-link">
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
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="site_icon" class="form-label">网站图标</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="site_icon" name="site_icon" 
                                           accept=".ico,.png,.jpg,.jpeg"
                                           onchange="this.nextElementSibling.value = this.files[0]?.name || '';">
                                    <button type="button" class="btn btn-outline-primary" id="upload_icon_btn">
                                        <i class="bi bi-upload"></i> 上传
                                    </button>
                                </div>
                                <!-- 已上传文件路径显示 - 已移除 */ -->
                                <small class="form-text text-muted">建议尺寸：32×32像素，支持ICO、PNG、JPG格式</small>
                                <div id="upload_icon_status" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <!-- 图标预览区域 -->
                            <div class="mb-3">
                                <label class="form-label">图标预览</label>
                                <div class="icon-preview-container text-center">
                                    <div id="icon_preview_container" style="display: <?php echo !empty($settings['site_icon']) ? 'block' : 'none'; ?>;">
                                        <img id="icon_preview" 
                                             src="<?php echo !empty($settings['site_icon']) ? '/uploads/settings/' . htmlspecialchars($settings['site_icon']) : '#'; ?>" 
                                             alt="图标预览" 
                                             style="width: 32px; height: 32px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <?php if (empty($settings['site_icon'])): ?>
                                        <div class="text-muted">
                                            <i class="fas fa-image" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">暂无图标</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                            <!-- 图标类型选择 -->
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_image" value="image" <?php echo (!isset($settings['site_logo_type']) || empty($settings['site_logo_type']) || $settings['site_logo_type'] === 'image') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="logo_type_image">上传图片</label>
                                
                                <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_icon" value="icon" <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'icon') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="logo_type_icon">Font Awesome</label>
                                
                                <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_iconfont" value="iconfont" <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'iconfont') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="logo_type_iconfont">Iconfont</label>
                                
                                <input type="radio" class="btn-check" name="site_logo_type" id="logo_type_none" value="none" <?php echo (isset($settings['site_logo_type']) && $settings['site_logo_type'] === 'none') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="logo_type_none">无Logo</label>
                            </div>
                            
                            <!-- 隐藏字段 -->
                            <input type="hidden" name="remove_logo" value="0">
                            <input type="hidden" id="uploaded_logo_path" name="uploaded_logo_path" value="<?php echo htmlspecialchars($settings['site_logo_image'] ?? ''); ?>">
                            
                            <!-- 图片上传 -->
                            <div id="logo_image_section" class="logo-section" style="display: none;">
                                <label for="site_logo" class="form-label">上传Logo</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="site_logo" name="site_logo" 
                                           accept="image/png,image/jpg,image/jpeg"
                                           onchange="this.nextElementSibling.value = this.files[0]?.name || '';">
                                    <button type="button" class="btn btn-outline-primary" id="upload_logo_btn">
                                        <i class="bi bi-upload"></i> 上传
                                    </button>
                                </div>
                                <!-- 已上传文件路径显示 - 已移除 */ -->
                                <small class="form-text text-muted">支持 JPG、PNG格式，建议尺寸 200×50 像素</small>
                                <div id="upload_logo_status" class="mt-2"></div>
                            </div>

                            <!-- Font Awesome 图标选择 -->
                            <div id="logo_icon_section" class="logo-section" style="display: none;">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="site_logo_icon" class="form-label">选择图标</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="site_logo_icon" name="site_logo_icon" 
                                                   placeholder="点击选择图标" readonly
                                                   value="<?php echo htmlspecialchars($settings['site_logo_icon'] ?? 'fas fa-home'); ?>">
                                            <button type="button" class="btn btn-outline-secondary" id="openLogoIconPicker">
                                                <i class="fas fa-icons"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="site_logo_color" class="form-label">图标颜色</label>
                                        <input type="color" class="form-control form-control-color w-100" id="site_logo_color" name="site_logo_color" 
                                               value="<?php echo htmlspecialchars($settings['site_logo_color'] ?? '#007bff'); ?>" style="height: 38px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Iconfont 图标输入 -->
                            <div id="logo_iconfont_section" class="logo-section" style="display: none;">
                                <label for="site_logo_iconfont" class="form-label">Iconfont 图标</label>
                                <input type="text" class="form-control" id="site_logo_iconfont" name="site_logo_iconfont" 
                                       placeholder="输入图标名称，如: icon-a-appround51"
                                       value="<?php echo htmlspecialchars($settings['site_logo_iconfont'] ?? ''); ?>">
                                <small class="form-text text-muted">输入iconfont图标类名，如: icon-a-appround51</small>
                            </div>
                        </div>

                        <!-- Logo预览 -->
                        <div class="col-md-5">
                            <label class="form-label">Logo预览</label>
                            <div class="logo-preview-container text-center">
                                <div id="logoPreview" class="mb-2">
                                    <?php if (!empty($settings['site_logo_icon']) && $settings['site_logo_type'] === 'icon'): ?>
                                        <i class="<?php echo htmlspecialchars($settings['site_logo_icon']); ?>" style="font-size: 3rem; color: <?php echo htmlspecialchars($settings['site_logo_color'] ?? '#007bff'); ?>"></i>
                                    <?php elseif (!empty($settings['site_logo_iconfont']) && $settings['site_logo_type'] === 'iconfont'): ?>
                                        <svg class="icon" aria-hidden="true" style="font-size: 3em;">
                                            <use xlink:href="#<?php echo htmlspecialchars($settings['site_logo_iconfont']); ?>"></use>
                                        </svg>
                                    <?php elseif (!empty($settings['site_logo_image']) && $settings['site_logo_type'] === 'image'): 
                                        $logo_path = $settings['site_logo_image'];
                                        // 如果路径已经是绝对路径，直接使用；否则进行转换
                                        if (!str_starts_with($logo_path, '/')) {
                                            $logo_path = str_replace('../uploads/', '/uploads/', $logo_path);
                                        }
                                    ?>
                                        <img src="<?php echo htmlspecialchars($logo_path); ?>" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                                    <?php else: ?>
                                        <div class="text-muted">
                                            <i class="fas fa-image" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">暂无Logo</p>
                                        </div>
                                    <?php endif; 
                                    ?>
                                </div>
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
    // 更新文件名显示
    const fileName = this.files[0]?.name || '';
    const uploadedIconDisplay = document.getElementById('uploaded_icon_display');
    if (uploadedIconDisplay) {
        uploadedIconDisplay.value = fileName;
    }
    
    // 预览图片
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





// 图片预览函数
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        // 显示预览容器
                        const container = preview.parentElement;
                        if (container && container.id.includes('_container')) {
                            container.style.display = 'block';
                        }
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Logo图标参数对象
        let logoIconParams = {
            type: '<?php echo $settings['site_logo_type'] ?? 'image'; ?>',
            icon: '<?php echo htmlspecialchars($settings['site_logo_icon'] ?? 'fas fa-home'); ?>',
            color: '<?php echo htmlspecialchars($settings['site_logo_color'] ?? '#007bff'); ?>',
            image: '<?php echo htmlspecialchars($settings['site_logo_image'] ?? ''); ?>',
            uploadedPath: '<?php echo htmlspecialchars($settings['site_logo_image'] ?? ''); ?>'
        };

        // Logo类型切换功能
        function toggleLogoType() {
            const imageRadio = document.getElementById('logo_type_image');
            const iconRadio = document.getElementById('logo_type_icon');
            const iconfontRadio = document.getElementById('logo_type_iconfont');
            const noneRadio = document.getElementById('logo_type_none');
            const imageSection = document.getElementById('logo_image_section');
            const iconSection = document.getElementById('logo_icon_section');
            const iconfontSection = document.getElementById('logo_iconfont_section');

            if (imageSection) {
                imageSection.style.display = imageRadio && imageRadio.checked ? 'block' : 'none';
            }
            if (iconSection) {
                iconSection.style.display = iconRadio && iconRadio.checked ? 'block' : 'none';
            }
            if (iconfontSection) {
                iconfontSection.style.display = iconfontRadio && iconfontRadio.checked ? 'block' : 'none';
            }
            
            // 切换类型时更新预览
            updateLogoPreview();
        }

        // 更新Logo预览
        function updateLogoPreview() {
            const container = document.getElementById('logoPreview');
            if (!container) return;

            const imageRadio = document.getElementById('logo_type_image');
            const iconRadio = document.getElementById('logo_type_icon');
            const iconfontRadio = document.getElementById('logo_type_iconfont');
            const noneRadio = document.getElementById('logo_type_none');

            let html = '';

            if (imageRadio && imageRadio.checked) {
                const imagePath = document.getElementById('uploaded_logo_path').value;
                if (imagePath) {
                    // 如果路径已经是绝对路径，直接使用；否则进行转换
                    const displayPath = imagePath.startsWith('/') ? imagePath : imagePath.replace('../uploads/', '/uploads/');
                    html = `<img src="${displayPath}" class="image-preview" style="max-width: 100px; max-height: 100px; border-radius: 8px;">`;
                } else {
                    html = '<div class="text-muted"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无Logo</p></div>';
                }
            } else if (iconRadio && iconRadio.checked) {
                const iconClass = document.getElementById('site_logo_icon').value;
                const iconColor = document.getElementById('site_logo_color').value;
                html = `<i class="${iconClass}" style="font-size: 3rem; color: ${iconColor};"></i>`;
            } else if (iconfontRadio && iconfontRadio.checked) {
                const iconfontValue = document.getElementById('site_logo_iconfont').value;
                if (iconfontValue) {
                    html = `<svg class="icon" aria-hidden="true" style="font-size: 3em;"><use xlink:href="#${iconfontValue}"></use></svg>`;
                } else {
                    html = '<div class="text-muted"><i class="fas fa-icons" style="font-size: 2rem;"></i><p class="mt-2 mb-0">请输入Iconfont类名</p></div>';
                }
            } else if (noneRadio && noneRadio.checked) {
                html = '<div class="text-muted"><i class="fas fa-image" style="font-size: 2rem;"></i><p class="mt-2 mb-0">暂无Logo</p></div>';
            }

            container.innerHTML = html;
        }

        // Logo图片上传
        function uploadLogo() {
            const fileInput = document.getElementById('site_logo');
            const statusDiv = document.getElementById('upload_logo_status');
            
            if (!fileInput.files || !fileInput.files[0]) {
                statusDiv.innerHTML = '<div class="alert alert-warning">请选择要上传的文件</div>';
                return;
            }
            
            const formData = new FormData();
            formData.append('site_logo', fileInput.files[0]);
            
            statusDiv.innerHTML = '<div class="alert alert-info">正在上传...</div>';
            
            fetch('?ajax_upload=1', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logoIconParams.uploadedPath = data.path;
                    document.getElementById('uploaded_logo_path').value = data.path;
                    statusDiv.innerHTML = '<div class="alert alert-success">上传成功！</div>';
                    updateLogoPreview();
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger">上传失败：' + data.message + '</div>';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="alert alert-danger">上传出错：' + error.message + '</div>';
            });
        }

        // 打开Logo图标选择器
        function openLogoIconPicker() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">选择Font Awesome图标</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="iconSearch" placeholder="搜索图标...">
                            </div>
                            <div class="icon-grid" id="iconGrid" style="max-height: 400px; overflow-y: auto;">
                                <?php
                                $icons = getFontAwesomeIcons();
                                foreach ($icons as $icon):
                                ?>
                                <div class="icon-item" data-icon="fas fa-<?php echo htmlspecialchars($icon); ?>" style="cursor: pointer; padding: 10px; text-align: center; display: inline-block; width: 80px;">
                                    <i class="fas fa-<?php echo htmlspecialchars($icon); ?> fa-2x"></i><br><small><?php echo htmlspecialchars($icon); ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // 图标选择事件
            modal.querySelectorAll('.icon-item').forEach(item => {
                item.addEventListener('click', function() {
                    const icon = this.getAttribute('data-icon');
                    document.getElementById('site_logo_icon').value = icon;
                    updateLogoPreview();
                    bsModal.hide();
                    // 让Bootstrap的hidden事件处理清理工作，避免重复移除
                });
            });
            
            // 搜索功能
            modal.querySelector('#iconSearch').addEventListener('input', function() {
                const search = this.value.toLowerCase();
                modal.querySelectorAll('.icon-item').forEach(item => {
                    const iconName = item.getAttribute('data-icon').toLowerCase();
                    const smallText = item.querySelector('small').textContent.toLowerCase();
                    if (iconName.includes(search) || smallText.includes(search)) {
                        item.style.display = 'inline-block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // 模态框关闭时清理
            modal.addEventListener('hidden.bs.modal', function() {
                // 安全地移除模态框，检查是否还在DOM中
                if (modal && modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
            });
        }

        // 选择Logo图标
        function selectLogoIcon(iconName) {
            // 添加完整的Font Awesome类名前缀
            const fullIconClass = `fas fa-${iconName}`;
            document.getElementById('site_logo_icon').value = fullIconClass;
            updateLogoPreview();
            
            // 这个函数用于处理静态模态框中的图标选择
            // 关闭模态框
            const modal = document.getElementById('logoIconModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
                // 清理DOM - 安全移除
                setTimeout(() => {
                    if (modal && modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                }, 100);
            }
        }

        // 图标上传函数
        function uploadIcon() {
            const fileInput = document.getElementById('site_icon');
            if (!fileInput.files || !fileInput.files[0]) {
                alert('请先选择要上传的图标文件');
                return;
            }
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('site_icon', file);
            
            // 显示上传状态
            const statusDiv = document.getElementById('upload_icon_status');
            statusDiv.innerHTML = '<div class="alert alert-info">正在上传图标...</div>';
            
            fetch('?ajax_upload_icon=1', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 显示预览 - 使用数据库路径
                    const previewImg = document.getElementById('icon_preview');
                    const previewContainer = document.getElementById('icon_preview_container');
                    if (previewImg && previewContainer) {
                        previewImg.src = data.path; // data.path 应该包含完整的上传路径
                        previewContainer.style.display = 'block';
                    }
                    
                    // 更新状态
                    statusDiv.innerHTML = '<div class="alert alert-success">图标上传成功！</div>';
                    
                    // 3秒后移除状态提示
                    setTimeout(() => {
                        statusDiv.innerHTML = '';
                    }, 3000);
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger">上传失败：' + data.message + '</div>';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="alert alert-danger">上传出错：' + error.message + '</div>';
            });
        }

        // 使用包含文件中的图标列表函数

        // 初始化Logo设置
        document.addEventListener('DOMContentLoaded', function() {
            toggleLogoType();
            updateLogoPreview();
            
            // 初始化网站图标预览 - 直接读取数据库中的图标
            const siteIconPath = '<?php echo !empty($settings["site_icon"]) ? htmlspecialchars($settings["site_icon"]) : ""; ?>';
            const siteIconFilename = '<?php echo !empty($settings["site_icon"]) ? htmlspecialchars(basename($settings["site_icon"])) : ""; ?>';
            
            if (siteIconPath && siteIconFilename) {
                // 显示图标预览
                const iconPreview = document.getElementById('icon_preview');
                const iconPreviewContainer = document.getElementById('icon_preview_container');
                if (iconPreview && iconPreviewContainer) {
                    iconPreview.src = siteIconPath;
                    iconPreviewContainer.style.display = 'block';
                }
            }
            
            const logoTypeRadios = document.querySelectorAll('input[name="site_logo_type"]');
            logoTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleLogoType);
            });
            
            // 监听图标和颜色变化
            const iconInput = document.getElementById('site_logo_icon');
            const colorInput = document.getElementById('site_logo_color');
            const iconfontInput = document.getElementById('site_logo_iconfont');
            if (iconInput) iconInput.addEventListener('input', updateLogoPreview);
            if (colorInput) colorInput.addEventListener('input', updateLogoPreview);
            if (iconfontInput) iconfontInput.addEventListener('input', updateLogoPreview);
            
            // 绑定图标选择器按钮
            const iconPickerBtn = document.getElementById('openLogoIconPicker');
            if (iconPickerBtn) {
                iconPickerBtn.addEventListener('click', openLogoIconPicker);
            }
            
            // 上传按钮事件
            const uploadBtn = document.getElementById('upload_logo_btn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', uploadLogo);
            }
            
            // 图标上传按钮事件
            const iconUploadBtn = document.getElementById('upload_icon_btn');
            if (iconUploadBtn) {
                iconUploadBtn.addEventListener('click', uploadIcon);
            }
            
            // 表单提交前检查
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const logoType = document.querySelector('input[name="site_logo_type"]:checked').value;
                    
                    if (logoType === 'image') {
                        const uploadedPath = document.getElementById('uploaded_logo_path').value;
                        if (!uploadedPath) {
                            e.preventDefault();
                            alert('请先上传Logo图片');
                            return false;
                        }
                    } else if (logoType === 'iconfont') {
                        const iconfontValue = document.getElementById('site_logo_iconfont').value;
                        if (!iconfontValue) {
                            e.preventDefault();
                            alert('请输入Iconfont图标名称');
                            return false;
                        }
                    }
                });
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
    </script>

<?php include '../templates/footer.php'; ?>