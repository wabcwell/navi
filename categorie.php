<?php
// 分类页面 - 展示指定分类下的所有链接

// 加载配置文件
require_once __DIR__ . '/admin/config.php';

// 引入后台数据库连接函数
require_once 'admin/includes/load.php';

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查是否传入了分类ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // 如果没有传入ID，重定向到首页
    header('Location: index.php');
    exit();
}

$categoryId = intval($_GET['id']);

// 获取前台数据管理实例
try {
    $frontendData = get_frontend_data_manager();
    $pageData = $frontendData->getHomePageData();
    
    // 从数据中提取设置
    $settings = $pageData['settings'];
    $background_type = $settings['background_type'];
    $background_color = $settings['background_color'];
    $background_image = $settings['background_image'];
    $background_api = $settings['background_api'];
    $background_opacity = $settings['background_opacity'];
    $site_name = $settings['site_name'];
    $site_description = $settings['site_description'];
    $header_bg_transparency = $settings['header_bg_transparency'] ?? 0.85;
    $category_bg_transparency = $settings['category_bg_transparency'] ?? 0.85;
    $links_area_transparency = $settings['links_area_transparency'] ?? 0.85;
    $link_card_transparency = $settings['link_card_transparency'] ?? 0.85;
    $show_footer = $settings['show_footer'];
    $footer_content = $settings['footer_content'];
    $overlay = $settings['bg-overlay'] ?? 0.2;
    
} catch (PDOException $e) {
    // 如果数据库连接失败，使用默认设置
    $background_type = 'color';
    $background_color = '#f3f4f6';
    $background_image = '';
    $background_api = 'https://www.dmoe.cc/random.php';
    $background_opacity = '1';
    $site_name = '我的导航网站';
    $site_description = '一个简洁美观的导航网站';
    $header_bg_transparency = '0.85';
    $category_bg_transparency = '0.85';
    $links_area_transparency = '0.85';
    $link_card_transparency = '0.85';
    $show_footer = true;
    $footer_content = '&copy; 2024 导航网站. All rights reserved.';
    $overlay = '0.2';
}

// 获取分类管理器和链接管理器
try {
    $categoryManager = get_category_manager();
    $navigationLinkManager = get_navigation_link_manager();
    
    // 获取指定分类的信息
    $category = $categoryManager->getById($categoryId);
    
    // 如果分类不存在或未激活，重定向到首页
    if (!$category || !$category['is_active']) {
        header('Location: index.php');
        exit();
    }
    
    // 获取该分类下的所有链接（只获取激活的链接）
    $links = $navigationLinkManager->getLinksByCategory($categoryId, true);
    
} catch (Exception $e) {
    // 如果获取数据失败，重定向到首页
    header('Location: index.php');
    exit();
}

// 设置页面标题为分类名称
$page_title = $category['name'] . ' - ' . $site_name;

// 获取前台Logo设置
$site_logo_type = $settings['site_logo_type'];
$site_logo_image = $settings['site_logo_image'] ?? '';
$site_logo_color = $settings['site_logo_color'];
$site_logo_iconfont = $settings['site_logo_iconfont'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="admin/assets/fontawesome/css/all.css">
    <!-- 网站图标 -->
    <?php if (!empty($settings['site_icon'])): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($settings['site_icon']); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo htmlspecialchars($settings['site_icon']); ?>">
    <?php endif; ?>
    <!-- Iconfont -->
    <script src="<?php echo htmlspecialchars($settings['iconfont'] ?? ''); ?>"></script>
    <style>
        :root {
            --header-bg-opacity: <?php echo htmlspecialchars(1 - floatval($header_bg_transparency)); ?>;
            --category-bg-opacity: <?php echo htmlspecialchars(1 - floatval($category_bg_transparency)); ?>;
            --links-area-opacity: <?php echo htmlspecialchars(1 - floatval($links_area_transparency)); ?>;
            --link-card-opacity: <?php echo htmlspecialchars(1 - floatval($link_card_transparency)); ?>;
            --bg-overlay: rgba(255, 255, 255, <?php echo htmlspecialchars(floatval($overlay)); ?>);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div>
                <h1>
                    <?php if ($site_logo_type === 'image' && $site_logo_image): ?>
                        <img src="<?php echo htmlspecialchars($site_logo_image); ?>" 
                             alt="<?php echo htmlspecialchars($site_name); ?>" 
                             style="max-height: 40px; max-width: 200px; vertical-align: middle; margin-right: 10px;">
                    <?php elseif ($site_logo_type === 'icon'): ?>
                        <i class="fas fa-compass" 
                           style="color: <?php echo htmlspecialchars($site_logo_color); ?>; margin-right: 10px;"></i>
                    <?php elseif ($site_logo_type === 'iconfont' && $site_logo_iconfont): ?>
                        <svg class="icon" aria-hidden="true" style="width: 40px; height: 40px; margin-right: 10px; fill: <?php echo htmlspecialchars($site_logo_color); ?>">
                            <use xlink:href="#<?php echo htmlspecialchars($site_logo_iconfont); ?>"></use>
                        </svg>
                    <?php else: ?>
                        <i class="fas fa-compass" style="margin-right: 10px;"></i>
                    <?php endif; ?>
                    <a href="index.php" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($site_name); ?></a>
                </h1>
                <p class="subtitle"><?php echo htmlspecialchars($category['name']); ?> - <?php echo htmlspecialchars($category['description'] ?? '分类下的所有链接'); ?></p>
            </div>
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="搜索链接..." />
            </div>
        </div>
    </header>

    <main class="main">
        <section class="category-section">
            <div class="category-header" style="--category-color: <?php echo htmlspecialchars($category['color'] ?: '#007bff'); ?>">
                <div class="card-icon">
                    <?php 
                    // 根据icon_type字段显示不同的图标
                    $iconType = $category['icon_type'] ?? 'fontawesome';
                    
                    if ($iconType === 'fontawesome' && !empty($category['icon_fontawesome'])): 
                        $iconColor = $category['icon_fontawesome_color'] ?? $category['color'];
                        $iconClass = $category['icon_fontawesome'];
                        
                        // 确保有正确的前缀
                        if (!preg_match('/^(fas|far|fab)\s/i', $iconClass)) {
                            $iconClass = 'fas ' . $iconClass;
                        }
                    ?>
                        <i class="<?php echo htmlspecialchars($iconClass); ?>" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                    <?php elseif ($iconType === 'upload' && !empty($category['icon_upload'])): ?>
                         <img src="<?php echo htmlspecialchars($category['icon_upload']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php elseif ($iconType === 'url' && !empty($category['icon_url'])): ?>
                        <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php elseif ($iconType === 'iconfont' && !empty($category['icon_iconfont'])): 
                        $iconColor = $category['icon_iconfont_color'] ?? $category['color'] ?? '#007bff';
                        ?>
                        <svg class="icon" aria-hidden="true" style="width: 100%; height: 100%; fill: <?php echo htmlspecialchars($iconColor); ?>;">
                            <use xlink:href="#<?php echo htmlspecialchars($category['icon_iconfont']); ?>"></use>
                        </svg>
                    <?php else: ?>
                        <?php $iconColor = $category['icon_fontawesome_color'] ?? $category['color']; ?>
                        <i class="fas fa-folder" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                    <?php endif; ?>
                </div>
                <?php echo htmlspecialchars($category['name']); ?>
                <span class="count"><?php echo count($links); ?></span>
            </div>
            
            <div class="links-grid">
                <?php if (empty($links)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <i class="fas fa-link" style="font-size: 3rem; color: #999; margin-bottom: 20px;"></i>
                        <h3>暂无链接</h3>
                        <p>该分类下还没有添加任何链接</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($links as $link): ?>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                           class="link-card" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           data-id="<?php echo $link['id']; ?>"
                           title="<?php echo htmlspecialchars($link['description']); ?>">
                            <div class="link-icon">
                                <?php 
                                // 根据icon_type字段显示不同类型的图标
                                $iconType = $link['icon_type'] ?? 'none';
                                
                                switch ($iconType) {
                                    case 'fontawesome':
                                        // 显示FontAwesome图标
                                        $iconClass = $link['icon_fontawesome'] ?? '';
                                        $iconColor = $link['icon_fontawesome_color'] ?? '#007bff';
                                        
                                        if ($iconClass) {
                                            // 确保有正确的前缀
                                            if (!preg_match('/^(fas|far|fab)\s/i', $iconClass)) {
                                                $iconClass = 'fas ' . $iconClass;
                                            }
                                            ?>
                                            <i class="<?php echo htmlspecialchars($iconClass); ?>" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                                            <?php
                                        } else {
                                            // 没有FontAwesome图标时显示默认图标
                                            ?>
                                            <i class="fas fa-external-link-alt"></i>
                                            <?php
                                        }
                                        break;
                                        
                                    case 'upload':
                                        // 显示上传的图片
                                        $iconUpload = $link['icon_upload'] ?? '';
                                        
                                        if ($iconUpload) {
                                            ?>
                                            <img src="<?php echo htmlspecialchars($iconUpload); ?>" 
                                                 alt="<?php echo htmlspecialchars($link['title']); ?>" 
                                                 style="width: 20px; height: 20px; object-fit: contain;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <i class="fas fa-external-link-alt" style="display: none; font-size: 0.875rem;"></i>
                                            <?php
                                        } else {
                                            // 没有上传图片时显示默认图标
                                            ?>
                                            <i class="fas fa-external-link-alt"></i>
                                            <?php
                                        }
                                        break;
                                        
                                    case 'url':
                                        // 显示URL图片
                                        $iconUrl = $link['icon_url'] ?? '';
                                        
                                        if ($iconUrl) {
                                            ?>
                                            <img src="<?php echo htmlspecialchars($iconUrl); ?>" 
                                                 alt="<?php echo htmlspecialchars($link['title']); ?>" 
                                                 style="width: 20px; height: 20px; object-fit: contain;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <i class="fas fa-external-link-alt" style="display: none; font-size: 0.875rem;"></i>
                                            <?php
                                        } else {
                                            // 没有URL图片时显示默认图标
                                            ?>
                                            <i class="fas fa-external-link-alt"></i>
                                            <?php
                                        }
                                        break;
                                        
                                    case 'iconfont':
                                        // 显示iconfont图标
                                        $iconIconfont = $link['icon_iconfont'] ?? '';
                                        
                                        if ($iconIconfont) {
                                            $iconColor = $link['icon_iconfont_color'] ?? $link['color'] ?? '#007bff';
                                            ?>
                                            <svg class="icon" aria-hidden="true" style="width: 100%; height: 100%; fill: <?php echo htmlspecialchars($iconColor); ?>;">
                                                <use xlink:href="#<?php echo htmlspecialchars($iconIconfont); ?>"></use>
                                            </svg>
                                            <?php
                                        } else {
                                            // 没有iconfont图标时显示默认图标
                                            ?>
                                            <i class="fas fa-external-link-alt"></i>
                                            <?php
                                        }
                                        break;
                                        
                                    case 'none':
                                    default:
                                        // 显示预设的默认图片
                                        ?>
                                        <i class="fas fa-external-link-alt"></i>
                                        <?php
                                        break;
                                }
                                ?>
                            </div>
                            <div class="link-content">
                                <h3><?php echo htmlspecialchars($link['title']); ?></h3>
                                <p><?php echo htmlspecialchars($link['description']); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php if ($show_footer): ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <?php echo $footer_content; ?>
            </div>
            <div class="footer-links">
                <a href="index.php">首页</a>
                <a href="?page=admin">管理</a>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <script>
        // 背景图片支持
        document.addEventListener('DOMContentLoaded', function() {
            const backgroundType = '<?php echo $background_type; ?>';
            const backgroundColor = '<?php echo $background_color; ?>';
            const backgroundImage = '<?php echo $background_image; ?>';
            const backgroundApi = '<?php echo $background_api; ?>';
            const backgroundOpacity = parseFloat('<?php echo $background_opacity; ?>');
            
            // 移除所有背景类
            document.body.classList.remove('has-bg-image', 'bg-color');
            
            if (backgroundType === 'image' && backgroundImage) {
                document.body.classList.add('has-bg-image');
                document.body.style.backgroundImage = `url('uploads/backgrounds/${backgroundImage}')`;
                document.body.style.backgroundSize = 'cover';
                document.body.style.backgroundPosition = 'center';
                document.body.style.backgroundRepeat = 'no-repeat';
                document.body.style.backgroundColor = backgroundColor;
            } else if (backgroundType === 'api' && backgroundApi) {
                document.body.classList.add('has-bg-image');
                document.body.style.backgroundImage = `url('${backgroundApi}')`;
                document.body.style.backgroundSize = 'cover';
                document.body.style.backgroundPosition = 'center';
                document.body.style.backgroundRepeat = 'no-repeat';
                document.body.style.backgroundColor = backgroundColor;
            } else if (backgroundType === 'color') {
                document.body.classList.add('bg-color');
                document.body.style.backgroundColor = backgroundColor;
            }
        });

        // 搜索功能
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const linkCards = document.querySelectorAll('.link-card');
            
            linkCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // 链接点击统计
        document.querySelectorAll('.link-card').forEach(card => {
            card.addEventListener('click', function() {
                const linkId = this.getAttribute('data-id');
                if (linkId) {
                    // 发送异步请求记录点击
                    fetch(`/api/link-click.php?id=${linkId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    }).catch(error => console.error('点击统计失败:', error));
                }
            });
        });
    </script>
</body>
</html>