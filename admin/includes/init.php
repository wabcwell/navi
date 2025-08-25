<?php
/**
 * 管理后台初始化文件
 * 包含配置、认证、数据库连接等核心功能
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(__DIR__)) . '/');
}

// 引入根目录配置文件
require_once ABSPATH . 'config.php';

// 定义管理后台常量
if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', __DIR__ . '/../');
}

// 动态生成SITE_URL（如果未定义）
if (!defined('SITE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $protocol . '://' . $host);
}

if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', SITE_URL . '/admin');
}

// 上传配置
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
define('UPLOAD_PATH', ADMIN_PATH . 'uploads/');

// 分页配置
define('ITEMS_PER_PAGE', 20);

// 错误报告
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

/**
 * 检查用户是否已登录
 */
function check_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ' . ADMIN_URL . '/index.php');
        exit();
    }
}

/**
 * 获取数据库连接
 */
function get_db_connection() {
    global $pdo;
    if (!isset($pdo)) {
        try {
            // 尝试使用Config类
            if (class_exists('Database')) {
                $pdo = Database::getInstance()->getConnection();
            } else {
                // 回退到传统方式
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $pdo = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            }
        } catch (PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }
    return $pdo;
}

/**
 * 获取网站设置
 */
function get_site_setting($key, $default = null) {
    $pdo = get_db_connection();
    // 尝试新的列名
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        if ($result) {
            return $result['setting_value'];
        }
    } catch (Exception $e) {
        // 回退到旧的列名（兼容性）
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            if ($result) {
                return $result['value'];
            }
        } catch (Exception $e2) {
            // 两个都不存在，返回默认值
        }
    }
    return $default;
}

/**
 * 设置网站配置
 */
function set_site_setting($key, $value) {
    $pdo = get_db_connection();
    try {
        // 使用新的表结构
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        // 回退到旧的表结构
        try {
            $stmt = $pdo->prepare("REPLACE INTO settings (name, value) VALUES (?, ?)");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e2) {
            return false;
        }
    }
}