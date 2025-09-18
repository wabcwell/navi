<?php
/**
 * 管理后台核心加载文件
 * 集中加载所有必要的函数和类
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(__DIR__)) . '/');
}

// 加载配置文件
require_once ABSPATH . 'config.php';

// 引入初始化文件（已重构，功能分散到各相关类中）
//require_once __DIR__ . '/init.php';

// 引入公共函数库
require_once __DIR__ . '/functions.php';

// 引入核心类
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Category.php';
require_once __DIR__ . '/NavigationLink.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Logs.php';
require_once __DIR__ . '/FileUpload.php';
require_once __DIR__ . '/FrontendData.php';

// 引入认证相关函数
require_once __DIR__ . '/auth.php';

/**
 * 获取数据库实例
 * 使用Database类创建数据库连接实例
 * 
 * @return Database 数据库实例
 */
function get_database() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database;
}

/**
 * 获取分类管理实例
 * 使用Category类创建分类管理实例
 * 
 * @return Category 分类管理实例
 */
function get_category_manager() {
    static $category = null;
    if ($category === null) {
        $database = get_database();
        $category = new Category($database);
    }
    return $category;
}

/**
 * 获取导航链接管理实例
 * 使用NavigationLink类创建导航链接管理实例
 * 
 * @return NavigationLink 导航链接管理实例
 */
function get_navigation_link_manager() {
    static $navigationLink = null;
    if ($navigationLink === null) {
        $database = get_database();
        $navigationLink = new NavigationLink($database);
    }
    return $navigationLink;
}

/**
 * 获取设置管理实例
 * 使用Settings类创建设置管理实例
 * 
 * @return Settings 设置管理实例
 */
function get_settings_manager() {
    static $settings = null;
    if ($settings === null) {
        $database = get_database();
        $settings = new Settings($database);
    }
    return $settings;
}

/**
 * 获取用户管理实例
 * 使用User类创建用户管理实例
 * 
 * @return User 用户管理实例
 */
function get_user_manager() {
    static $user = null;
    if ($user === null) {
        $database = get_database();
        $user = new User($database);
    }
    return $user;
}

/**
 * 获取日志管理实例
 * 使用Logs类创建日志管理实例
 * 
 * @return Logs 日志管理实例
 */
function get_logs_manager() {
    static $logs = null;
    if ($logs === null) {
        $database = get_database();
        $logs = new Logs($database);
    }
    return $logs;
}

/**
 * 获取文件上传管理实例
 * 使用FileUpload类创建文件上传管理实例
 * 
 * @param string $uploadType 上传类型 (categories, links, backgrounds, settings)
 * @param array $allowedTypes 允许的文件类型
 * @param int $maxFileSize 最大文件大小
 * @return FileUpload 文件上传管理实例
 */
function get_file_upload_manager($uploadType = 'general', $allowedTypes = [], $maxFileSize = 5242880) {
    return new FileUpload($uploadType, $allowedTypes, $maxFileSize);
}