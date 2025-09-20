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
$site_logo_iconfont = $settings['site_logo_iconfont'] ?? '';
$background_type = $settings['background_type'];
$background_color = $settings['background_color'];
$background_image = $settings['background_image'];
$background_api = $settings['background_api'];
$background_opacity = $settings['background_opacity'];
$header_bg_transparency = $settings['header_bg_transparency'] ?? 0.85;
$category_bg_transparency = $settings['category_bg_transparency'] ?? 0.85;
$links_area_transparency = $settings['links_area_transparency'] ?? 0.85;
$link_card_transparency = $settings['link_card_transparency'] ?? 0.85;
$show_footer = $settings['show_footer'];
$footer_content = $settings['footer_content'];
$overlay = $settings['bg-overlay'] ?? 0.2;

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="admin/assets/fontawesome/css/all.min.css">
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
                    <?php if ($site_logo_type === 'image' && $site_logo): ?>
                        <img src="<?php echo htmlspecialchars($site_logo); ?>" 
                             alt="<?php echo htmlspecialchars($site_name); ?>" 
                             style="max-height: 40px; max-width: 200px; vertical-align: middle; margin-right: 10px;">
                    <?php elseif ($site_logo_type === 'icon' && $site_logo): ?>
                        <i class="<?php echo htmlspecialchars($site_logo); ?>" 
                           style="color: <?php echo htmlspecialchars($site_logo_color); ?>; margin-right: 10px;"></i>
                    <?php elseif ($site_logo_type === 'iconfont' && $site_logo_iconfont): ?>
                        <svg class="icon" aria-hidden="true" style="width: 40px; height: 40px; margin-right: 10px; fill: <?php echo htmlspecialchars($site_logo_color); ?>">
                            <use xlink:href="#<?php echo htmlspecialchars($site_logo_iconfont); ?>"></use>
                        </svg>
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
                // 移除body透明度设置，避免整个页面半透明
                // document.body.style.opacity = backgroundOpacity;
            } else if (backgroundType === 'api' && backgroundApi) {
                document.body.classList.add('has-bg-image');
                document.body.style.backgroundImage = `url('${backgroundApi}')`;
                document.body.style.backgroundSize = 'cover';
                document.body.style.backgroundPosition = 'center';
                document.body.style.backgroundRepeat = 'no-repeat';
                document.body.style.backgroundColor = backgroundColor;
                // 移除body透明度设置，避免整个页面半透明
                // document.body.style.opacity = backgroundOpacity;
            } else if (backgroundType === 'color') {
                document.body.classList.add('bg-color');
                document.body.style.backgroundColor = backgroundColor;
                // 移除body透明度设置，避免整个页面半透明
                // document.body.style.opacity = backgroundOpacity;
            }
            
            // 移除背景覆盖层透明度设置，避免蒙层效果
            // if (backgroundOpacity && backgroundOpacity !== 1) {
            //     document.documentElement.style.setProperty('--bg-overlay', 'rgba(255, 255, 255, ' + (1 - backgroundOpacity) + ')');
            // }
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