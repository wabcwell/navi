<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/includes/init.php';

// 获取设置
$site_name = get_site_setting('site_name', '导航');
$site_description = get_site_setting('site_description', '我的导航网站');
$site_logo_type = get_site_setting('site_logo_type', 'icon');
$site_logo = get_site_setting('site_logo', 'fas fa-compass');
$site_logo_color = get_site_setting('site_logo_color', '#007bff');

// 背景设置
$background_type = get_site_setting('background_type', 'color');
$background_color = get_site_setting('background_color', '#f8fafc');
$background_image = get_site_setting('background_image', '');
$background_api = get_site_setting('background_api', 'https://picsum.photos/1920/1080');
$background_opacity = get_site_setting('background_opacity', '1');

// 透明度设置
$header_bg_opacity = get_site_setting('header_bg_opacity', '0.9');
$category_bg_opacity = get_site_setting('category_bg_opacity', '0.8');
$links_area_opacity = get_site_setting('links_area_opacity', '0.7');
$link_card_opacity = get_site_setting('link_card_opacity', '0.8');

// 页脚设置
$show_footer = get_site_setting('show_footer', '1') == '1';
$footer_content = get_site_setting('footer_content', '&copy; 2024 导航网站. All rights reserved.');

// 获取数据库连接
$pdo = get_db_connection();

// 获取分类和链接
$categories = [];
$linksByCategory = [];

try {
    // 使用install.php中的正确表名和列名
    // 获取启用的分类
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY order_index ASC, id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($categories)) {
        // 获取每个分类的链接数量 (使用navigation_links表)
        $stmt = $pdo->prepare("SELECT category_id, COUNT(*) as link_count FROM navigation_links WHERE is_active = 1 AND category_id IN (" . implode(',', array_fill(0, count($categories), '?')) . ") GROUP BY category_id");
        $stmt->execute(array_column($categories, 'id'));
        $linkCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $linkCountMap = [];
        foreach ($linkCounts as $count) {
            $linkCountMap[$count['category_id']] = $count['link_count'];
        }
        
        foreach ($categories as &$category) {
            $category['link_count'] = $linkCountMap[$category['id']] ?? 0;
        }
        unset($category);
        
        // 获取启用的链接 (使用navigation_links表)
        $stmt = $pdo->query("SELECT * FROM navigation_links WHERE is_active = 1 ORDER BY order_index ASC, id ASC");
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 按分类分组链接
        foreach ($links as $link) {
            if (!isset($linksByCategory[$link['category_id']])) {
                $linksByCategory[$link['category_id']] = [];
            }
            $linksByCategory[$link['category_id']][] = $link;
        }
    }
} catch (Exception $e) {
    $categories = [];
    $linksByCategory = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="admin/assets/fontawesome/css/all.min.css">
    <style>
        :root {
            --header-bg-opacity: <?php echo htmlspecialchars(1 - floatval($header_bg_opacity)); ?>;
            --category-bg-opacity: <?php echo htmlspecialchars(1 - floatval($category_bg_opacity)); ?>;
            --links-area-opacity: <?php echo htmlspecialchars(1 - floatval($links_area_opacity)); ?>;
            --link-card-opacity: <?php echo htmlspecialchars(1 - floatval($link_card_opacity)); ?>;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div>
                <h1>
                    <?php if ($site_logo_type === 'image' && $site_logo): ?>
                        <img src="uploads/settings/<?php echo htmlspecialchars($site_logo); ?>" 
                             alt="<?php echo htmlspecialchars($site_name); ?>" 
                             style="max-height: 40px; max-width: 200px; vertical-align: middle; margin-right: 10px;">
                    <?php elseif ($site_logo_type === 'icon' && $site_logo): ?>
                        <i class="<?php echo htmlspecialchars($site_logo); ?>" 
                           style="color: <?php echo htmlspecialchars($site_logo_color); ?>; margin-right: 10px;"></i>
                    <?php else: ?>
                        <i class="fas fa-compass" style="margin-right: 10px;"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($site_name); ?>
                </h1>
                <p class="subtitle"><?php echo htmlspecialchars($site_description); ?></p>
            </div>
            <div class="search-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="搜索网站..." />
            </div>
        </div>
    </header>

    <main class="main">
        <?php if (empty($categories)): ?>
            <div class="empty-state">
                <i class="fas fa-database"></i>
                <h3>暂无数据</h3>
                <p>请先运行数据库初始化</p>
                <code>php db-init.php</code>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <section class="category-section">
                    <div class="category-header" style="--category-color: <?php echo htmlspecialchars($category['color'] ?: '#007bff'); ?>">
                        <div class="card-icon">
                            <?php if ($category['icon']): ?>
                                <?php 
                                $icon = trim($category['icon']);
                                if (preg_match('/^(fa-|fas\s|far\s|fab\s)/i', $icon)): 
                                    $displayIcon = $icon;
                                    if (!preg_match('/^(fas|far|fab)\s/i', $icon)) {
                                        $displayIcon = 'fas ' . $icon;
                                    }
                                    $iconColor = $category['icon_color'] ?? $category['color'];
                                ?>
                                    <i class="<?php echo htmlspecialchars($displayIcon); ?>" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                                <?php elseif (strpos($icon, 'bi-') === 0): ?>
                                    <?php $iconColor = $category['icon_color'] ?? $category['color']; ?>
                                    <i class="<?php echo htmlspecialchars($icon); ?>" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                                <?php else: ?>
                                    <img src="/uploads/categories/<?php echo $category['icon']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                <?php endif; ?>
                            <?php else: ?>
                                <?php $iconColor = $category['icon_color'] ?? $category['color']; ?>
                                <i class="fas fa-folder" style="color: <?php echo htmlspecialchars($iconColor); ?>"></i>
                            <?php endif; ?>
                        </div>
                        <?php echo htmlspecialchars($category['name']); ?>
                        <span class="count"><?php echo $category['link_count']; ?></span>
                    </div>
                    
                    <div class="links-grid">
                        <?php 
                        $categoryLinks = $linksByCategory[$category['id']] ?? [];
                        foreach ($categoryLinks as $link): 
                        ?>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                               class="link-card" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               data-id="<?php echo $link['id']; ?>"
                               title="<?php echo htmlspecialchars($link['description']); ?>">
                                <div class="link-icon">
                                    <?php if ($link['icon_url']): ?>
                                        <?php 
                                        $icon = trim($link['icon_url']);
                                        $parts = explode('|', $icon);
                                        $icon_class = trim($parts[0]);
                                        
                                        if (preg_match('/^(fa-|fas\s|far\s|fab\s)/i', $icon_class)): 
                                            $displayIcon = $icon_class;
                                            if (!preg_match('/^(fas|far|fab)\s/i', $icon_class)) {
                                                $displayIcon = 'fas ' . $icon_class;
                                            }
                                        ?>
                                            <i class="<?php echo htmlspecialchars($displayIcon); ?>"></i>
                                        <?php elseif (strpos($icon_class, 'bi-') === 0): ?>
                                            <i class="<?php echo htmlspecialchars($icon_class); ?>"></i>
                                        <?php else: ?>
                                            <img src="<?php 
                                            $icon_path = $icon_class;
                                            if (!filter_var($icon_path, FILTER_VALIDATE_URL) && !empty($icon_path)) {
                                                echo 'uploads/links/' . htmlspecialchars($icon_path);
                                            } else {
                                                echo htmlspecialchars($icon_path);
                                            }
                                        ?>" 
                                                 alt="<?php echo htmlspecialchars($link['title']); ?>" 
                                                 style="width: 20px; height: 20px; object-fit: contain;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <i class="fas fa-external-link-alt" style="display: none; font-size: 0.875rem;"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="link-content">
                                    <h3><?php echo htmlspecialchars($link['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($link['description']); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php if ($show_footer): ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <?php echo $footer_content; ?>
            </div>
            <div class="footer-links">
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
            const backgroundOpacity = '<?php echo $background_opacity; ?>';
            
            const body = document.body;
            
            // 设置背景透明度
            if (backgroundOpacity && backgroundOpacity !== '1') {
                document.documentElement.style.setProperty('--bg-overlay', 'rgba(255, 255, 255, ' + backgroundOpacity + ')');
            }
            
            switch(backgroundType) {
                case 'color':
                    body.className = 'bg-color';
                    body.style.backgroundColor = backgroundColor || '#f8fafc';
                    break;
                    
                case 'image':
                    if (backgroundImage) {
                        body.className = 'has-bg-image';
                        body.style.backgroundImage = "url('" + backgroundImage + "')";
                    }
                    break;
                    
                case 'api':
                    body.className = 'has-bg-image';
                    body.style.backgroundImage = "url('" + backgroundApi + "')";
                    break;
                    
                case 'gradient':
                    body.className = 'bg-color';
                    body.style.background = backgroundColor || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    break;
            }
        });
    </script>
    <script src="assets/js/script.js"></script>
</body>
</html>