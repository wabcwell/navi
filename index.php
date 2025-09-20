<?php
// PHP版本的导航网站主入口文件

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

// 获取前台数据
try {
    // 获取前台数据管理实例
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
    $header_bg_transparency = $settings['header_bg_transparency'];
    $category_bg_transparency = $settings['category_bg_transparency'];
    $links_area_transparency = $settings['links_area_transparency'];
    $link_card_transparency = $settings['link_card_transparency'];
    
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
    default:
        // 显示主页
        include 'views/home.php';
        break;
}
?>