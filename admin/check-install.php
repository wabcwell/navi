<?php
/**
 * 管理后台安装检查脚本
 * 用于验证所有必要的配置和文件是否正确
 */

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP版本必须 >= 7.4.0，当前版本: ' . PHP_VERSION);
}

// 检查必要的扩展
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('缺少必要的PHP扩展: ' . implode(', ', $missing_extensions));
}

// 检查配置文件
$config_file = __DIR__ . '/../config.php';
if (!file_exists($config_file)) {
    die('配置文件不存在: ' . $config_file);
}

// 检查必要的目录
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/categories',
    __DIR__ . '/uploads/links',
    __DIR__ . '/uploads/files'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            die('无法创建目录: ' . $dir);
        }
    }
    if (!is_writable($dir)) {
        die('目录不可写: ' . $dir);
    }
}

// 尝试连接数据库
try {
    require_once $config_file;
    
    if (class_exists('Database')) {
        $pdo = Database::getInstance()->getConnection();
        echo "✅ 数据库连接成功\n";
    } else {
        // 回退到传统方式
        require_once __DIR__ . '/includes/init.php';
        $pdo = get_db_connection();
        echo "✅ 数据库连接成功 (传统方式)\n";
    }
    
    // 检查必要的表
    $tables = [
        'users',
        'categories', 
        'navigation_links',
        'files',
        'settings',
        'admin_logs',
        'login_logs',
        'error_logs',
        'operation_logs'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM {$table} LIMIT 1");
            echo "✅ 表 {$table} 存在\n";
        } catch (Exception $e) {
            echo "❌ 表 {$table} 不存在: " . $e->getMessage() . "\n";
        }
    }
    
    // 检查默认管理员
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            echo "✅ 默认管理员用户存在\n";
        } else {
            echo "⚠️ 默认管理员用户不存在，需要创建\n";
        }
    } catch (Exception $e) {
        echo "❌ 检查管理员用户失败: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "请检查数据库配置是否正确\n";
}

// 检查常量定义
echo "\n=== 常量定义检查 ===\n";
if (defined('SITE_URL')) {
    echo "✅ SITE_URL: " . SITE_URL . "\n";
} else {
    echo "❌ SITE_URL 未定义\n";
}

if (defined('DB_HOST')) {
    echo "✅ DB_HOST: " . DB_HOST . "\n";
} else {
    echo "❌ DB_HOST 未定义\n";
}

// 检查权限
echo "\n=== 权限检查 ===\n";
$admin_dir = __DIR__;
if (is_writable($admin_dir)) {
    echo "✅ 管理目录可写: {$admin_dir}\n";
} else {
    echo "⚠️ 管理目录不可写: {$admin_dir}\n";
}

echo "\n=== 系统信息 ===\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "操作系统: " . PHP_OS . "\n";
echo "服务器: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "当前时间: " . date('Y-m-d H:i:s') . "\n";

echo "\n=== 安装检查完成 ===\n";
echo "如果以上检查都通过，可以访问: http://localhost:8000/admin/\n";
echo "默认登录: admin / admin123\n";