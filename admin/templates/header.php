<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>导航网站管理后台</title>
    
    <!-- Bootstrap CSS -->
<link href="/admin/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="/admin/assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="/admin/assets/fontawesome/css/all.min.css" rel="stylesheet">
    <!-- Iconfont -->
    <script src="<?php echo Settings::getIconfontUrl(); ?>"></script>
    <!-- 网站图标 -->
    <?php 
    $site_icon = Settings::getSiteSetting('site_icon');
    if (!empty($site_icon)): 
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($site_icon); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo htmlspecialchars($site_icon); ?>">
    <?php endif; ?>
    <!-- 左侧导航样式 -->
    <link href="/admin/assets/css/sidebar.css" rel="stylesheet">
    <style>
        .icon-preview-container {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .icon-section {
            transition: all 0.3s ease;
        }
        .icon-btn:hover {
            background-color: #0d6efd;
            color: white;
        }
        .icon-btn {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            padding: 0.5rem;
        }
        .image-preview {
            max-width: 100%;
            height: auto;
            border-radius: 0.375rem;
        }
        /* 页面操作按钮样式 */
        .page-header {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-right: 1.5rem;
        }
        .page-actions {
            margin-left: auto;
            padding-left: 1rem;
        }
    </style>
</head>
<body>
    <?php if (User::checkLogin()): ?>
    <!-- 左侧导航 -->
    <nav class="sidebar">
        <a href="/admin/dashboard.php" class="sidebar-brand">
            <i class="bi bi-speedometer2"></i>
            <span>导航管理</span>
        </a>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/admin/dashboard.php">
                    <i class="bi bi-house"></i>
                    <span>仪表盘</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>" 
                   href="/admin/categories/">
                    <i class="bi bi-folder"></i>
                    <span>分类管理</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/links/') !== false ? 'active' : ''; ?>" 
                   href="/admin/links/">
                    <i class="bi bi-link-45deg"></i>
                    <span>链接管理</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/files/') !== false ? 'active' : ''; ?>" 
                   href="/admin/files/">
                    <i class="bi bi-image"></i>
                    <span>文件管理</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/logs.php') !== false ? 'active' : ''; ?>" 
                   href="/admin/settings/logs.php">
                    <i class="bi bi-journal-text"></i>
                    <span>操作记录</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false && strpos($_SERVER['REQUEST_URI'], '/settings/logs.php') === false ? 'active' : ''; ?>" 
                   href="/admin/settings/general.php">
                    <i class="bi bi-gear"></i>
                    <span>系统设置</span>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <a href="/" class="logout-btn" target="_blank" style="margin-bottom: 10px;">
                <i class="bi bi-house-door"></i>
                <span>返回前台</span>
            </a>
            <a href="/admin/logout.php" class="logout-btn" onclick="return confirm('确定要退出吗？')">
                <i class="bi bi-box-arrow-right"></i>
                <span>退出登录</span>
            </a>
        </div>
    </nav>
    
    <!-- 移动端遮罩 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 主内容区域 -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <?php 
                // 根据当前页面添加对应的图标
                $current_page = basename($_SERVER['PHP_SELF']);
                $request_uri = $_SERVER['REQUEST_URI'];
                $page_icon = '';
                
                if ($current_page == 'dashboard.php') {
                    $page_icon = 'bi-house';
                } elseif (strpos($request_uri, '/categories/') !== false) {
                    // 为添加和编辑分类页面设置特殊图标
                    if ($current_page == 'add.php') {
                        $page_icon = 'bi-folder-plus';
                    } elseif ($current_page == 'edit.php') {
                        $page_icon = 'bi-folder-check';
                    } else {
                        $page_icon = 'bi-folder';
                    }
                } elseif (strpos($request_uri, '/links/') !== false) {
                    // 为添加和编辑链接页面设置特殊图标
                    if ($current_page == 'add.php') {
                        $page_icon = 'bi-plus-circle';
                    } elseif ($current_page == 'edit.php') {
                        $page_icon = 'bi-pencil-square';
                    } else {
                        $page_icon = 'bi-link-45deg';
                    }
                } elseif (strpos($request_uri, '/files/') !== false) {
                    $page_icon = 'bi-image';
                } elseif (strpos($request_uri, '/settings/logs.php') !== false) {
                    $page_icon = 'bi-journal-text';
                } elseif (strpos($request_uri, '/settings/') !== false) {
                    $page_icon = 'bi-gear';
                }
                
                if ($page_icon && isset($page_title)) {
                    echo '<i class="bi ' . $page_icon . '"></i> ';
                }
                ?>
                <?php echo isset($page_title) ? $page_title : '管理后台'; ?>
            </h1>
            <?php if (isset($breadcrumb)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <?php foreach ($breadcrumb as $item): ?>
                            <?php if (isset($item['url'])): ?>
                                <li class="breadcrumb-item"><a href="<?php echo $item['url']; ?>"><?php echo $item['title']; ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page"><?php echo $item['title']; ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>