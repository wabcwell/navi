<?php
/**
 * 测试配置文件解析
 */

echo "=== 配置文件解析测试 ===\n";

// 测试1: 检查config.php是否正确加载
if (file_exists('config.php')) {
    echo "✅ config.php 文件存在\n";
    require_once 'config.php';
} else {
    echo "❌ config.php 文件不存在\n";
    exit();
}

// 测试2: 检查数据库常量是否定义
$constants = [
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET', 'SITE_INSTALLED'
];

foreach ($constants as $const) {
    if (defined($const)) {
        echo "✅ $const: " . constant($const) . "\n";
    } else {
        echo "❌ $const 未定义\n";
    }
}

// 测试3: 检查数据库连接字符串构建
echo "\n=== 数据库连接字符串 ===\n";
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
echo "DSN: $dsn\n";

// 测试4: 检查文件路径
echo "\n=== 路径检查 ===\n";
echo "当前目录: " . getcwd() . "\n";
echo "ABSPATH: " . (defined('ABSPATH') ? ABSPATH : '未定义') . "\n";
echo "config.php 路径: " . realpath('config.php') . "\n";

// 测试5: 尝试数据库连接
echo "\n=== 数据库连接测试 ===\n";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    echo "✅ 数据库连接成功\n";
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
}