<?php
// PHP版本的主页视图
require_once 'config.php';

// 获取背景设置（从index.php传递过来）
    $background_type = isset($background_type) ? $background_type : 'color';
    $background_color = isset($background_color) ? $background_color : '#f3f4f6';
    $background_image = isset($background_image) ? $background_image : '';
    $background_api = isset($background_api) ? $background_api : 'https://picsum.photos/1920/1080';
    $background_opacity = isset($background_opacity) ? $background_opacity : '1';
    
    // 获取Logo设置
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_logo_type', 'site_logo', 'site_logo_color')");
        $stmt->execute();
        $logo_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $site_logo_type = $logo_settings['site_logo_type'] ?? 'image';
        $site_logo = $logo_settings['site_logo'] ?? '';
        $site_logo_color = $logo_settings['site_logo_color'] ?? '#007bff';
        
    } catch (Exception $e) {
        $site_logo_type = 'image';
        $site_logo = '';
        $site_logo_color = '#007bff';
    }
    
    // 获取页脚设置
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('footer_content', 'show_footer')");
        $stmt->execute();
        $footer_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $footer_content = $footer_settings['footer_content'] ?? '© 2024 导航站. All rights reserved.';
        $show_footer = $footer_settings['show_footer'] ?? 1;
        
    } catch (Exception $e) {
        $footer_content = '© 2024 导航站. All rights reserved.';
        $show_footer = 1;
    }
    
    // 获取透明度设置并转换为不透明度值
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%opacity%'");
        $stmt->execute();
        $opacity_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // 将用户设置的透明度值转换为CSS使用的不透明度值
        $header_bg_opacity = 1 - floatval($opacity_settings['header_bg_opacity'] ?? '0.15');
        $category_bg_opacity = 1 - floatval($opacity_settings['category_bg_opacity'] ?? '0.15');
        $links_area_opacity = 1 - floatval($opacity_settings['links_area_opacity'] ?? '0.15');
        $link_card_opacity = 1 - floatval($opacity_settings['link_card_opacity'] ?? '0.15');
        
    } catch (Exception $e) {
        // 默认情况下，使用85%的不透明度（即15%透明度）
        $header_bg_opacity = 0.85;
        $category_bg_opacity = 0.85;
        $links_area_opacity = 0.85;
        $link_card_opacity = 0.85;
    }

// 获取数据库连接
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 获取所有分类和链接
    $stmt = $pdo->query("
        SELECT c.*, COUNT(l.id) as link_count 
        FROM categories c 
        LEFT JOIN navigation_links l ON c.id = l.category_id AND l.is_active = 1 
        WHERE c.is_active = 1 
        GROUP BY c.id 
        ORDER BY c.order_index ASC
    ");
    $categories = $stmt->fetchAll();
    
    // 获取所有链接
    $stmt = $pdo->query("
        SELECT l.*, c.name as category_name, c.color as category_color 
        FROM navigation_links l 
        JOIN categories c ON l.category_id = c.id 
        WHERE l.is_active = 1 
        ORDER BY c.order_index ASC, l.order_index ASC
    ");
    $links = $stmt->fetchAll();
    
    // 按分类分组链接
    $linksByCategory = [];
    foreach ($links as $link) {
        $linksByCategory[$link['category_id']][] = $link;
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="admin/assets/fontawesome/css/all.min.css">
    <style>
        :root {
            --header-bg-opacity: <?php echo htmlspecialchars($header_bg_opacity); ?>;
            --category-bg-opacity: <?php echo htmlspecialchars($category_bg_opacity); ?>;
            --links-area-opacity: <?php echo htmlspecialchars($links_area_opacity); ?>;
            --link-card-opacity: <?php echo htmlspecialchars($link_card_opacity); ?>;
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
                document.documentElement.style.setProperty('--bg-overlay', `rgba(255, 255, 255, ${backgroundOpacity})`);
            }
            
            switch(backgroundType) {
                case 'color':
                    body.className = 'bg-color';
                    body.style.backgroundColor = backgroundColor || '#f8fafc';
                    break;
                    
                case 'image':
                    if (backgroundImage) {
                        body.className = 'has-bg-image';
                        body.style.backgroundImage = `url('${backgroundImage}')`;
                    }
                    break;
                    
                case 'api':
                    body.className = 'has-bg-image';
                    body.style.backgroundImage = `url('${backgroundApi}')`;
                    break;
                    
                case 'gradient':
                    body.className = 'bg-color';
                    body.style.background = backgroundColor || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    break;
            }
        });
    </script>
    <script src="js/script.js"></script>
</body>
</html>