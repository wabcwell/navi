<?php
// PHP版本的导航网站主入口文件

// 加载配置文件
require_once 'config.php';

// 引入后台数据库连接函数
require_once 'admin/includes/init.php';

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 加载背景设置和网站信息
try {
    $pdo = get_db_connection();
    
    // 加载背景设置
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'background_%'");
    $stmt->execute();
    $bg_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $background_type = $bg_settings['background_type'] ?? 'color';
    $background_color = $bg_settings['background_color'] ?? '#f3f4f6';
    $background_image = $bg_settings['background_image'] ?? '';
    $background_api = $bg_settings['background_api'] ?? 'https://picsum.photos/1920/1080';
    $background_opacity = $bg_settings['background_opacity'] ?? '1';
    
    // 加载网站信息和透明度设置
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name', 'site_description', 'header_bg_opacity', 'category_bg_opacity', 'links_area_opacity', 'link_card_opacity')");
    $stmt->execute();
    $site_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $site_name = $site_settings['site_name'] ?? '我的导航网站';
    $site_description = $site_settings['site_description'] ?? '一个简洁美观的导航网站';
    $header_bg_opacity = $site_settings['header_bg_opacity'] ?? '0.85';
    $category_bg_opacity = $site_settings['category_bg_opacity'] ?? '0.85';
    $links_area_opacity = $site_settings['links_area_opacity'] ?? '0.85';
    $link_card_opacity = $site_settings['link_card_opacity'] ?? '0.85';
    
} catch (PDOException $e) {
    // 如果数据库连接失败，使用默认设置
    $background_type = 'color';
    $background_color = '#f3f4f6';
    $background_image = '';
    $background_api = 'https://www.dmoe.cc/random.php';
    $background_opacity = '1';
    $site_name = '我的导航网站';
    $site_description = '一个简洁美观的导航网站';
    $header_bg_opacity = '0.85';
    $category_bg_opacity = '0.85';
    $links_area_opacity = '0.85';
    $link_card_opacity = '0.85';
}

// 获取当前页面
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// 路由处理
switch ($page) {
    case 'api':
        require_once 'api.php';
        break;
    case 'admin':
        header('Location: admin/');
        exit();
        break;
    default:
        // 显示主页
        include 'views/home.php';
        break;
}
?>