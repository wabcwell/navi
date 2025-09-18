<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/includes/load.php';

// 获取前台数据管理实例
$frontendData = get_frontend_data_manager();

// 获取前台页面数据
$pageData = $frontendData->getHomePageData();

// 提取数据
$categories = $pageData['categories'];
$linksByCategory = $pageData['linksByCategory'];
$settings = $pageData['settings'];

// 设置变量
$site_name = $settings['site_name'];
$site_description = $settings['site_description'];
$site_logo_type = $settings['site_logo_type'];
$site_logo = $settings['site_logo'];
$site_logo_color = $settings['site_logo_color'];
$background_type = $settings['background_type'];
$background_color = $settings['background_color'];
$background_image = $settings['background_image'];
$background_api = $settings['background_api'];
$background_opacity = $settings['background_opacity'];
$header_bg_opacity = $settings['header_bg_opacity'];
$category_bg_opacity = $settings['category_bg_opacity'];
$links_area_opacity = $settings['links_area_opacity'];
$link_card_opacity = $settings['link_card_opacity'];
$show_footer = $settings['show_footer'];
$footer_content = $settings['footer_content'];

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
                document.body.style.opacity = backgroundOpacity;
            } else if (backgroundType === 'api' && backgroundApi) {
                document.body.classList.add('has-bg-image');
                document.body.style.backgroundImage = `url('${backgroundApi}')`;
                document.body.style.backgroundSize = 'cover';
                document.body.style.backgroundPosition = 'center';
                document.body.style.backgroundRepeat = 'no-repeat';
                document.body.style.backgroundColor = backgroundColor;
                document.body.style.opacity = backgroundOpacity;
            } else if (backgroundType === 'color') {
                document.body.classList.add('bg-color');
                document.body.style.backgroundColor = backgroundColor;
                document.body.style.opacity = backgroundOpacity;
            }
            
            // 设置背景覆盖层透明度
            if (backgroundOpacity && backgroundOpacity !== 1) {
                document.documentElement.style.setProperty('--bg-overlay', 'rgba(255, 255, 255, ' + (1 - backgroundOpacity) + ')');
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