<?php
/**
 * 数据库连接测试脚本
 */

require_once 'config.php';

function test_database_connection() {
    try {
        echo "正在测试数据库连接...\n";
        echo "主机: " . DB_HOST . "\n";
        echo "端口: " . DB_PORT . "\n";
        echo "数据库: " . DB_NAME . "\n";
        echo "用户: " . DB_USER . "\n";
        
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5 // 5秒超时
        ]);
        
        echo "✅ 数据库连接成功！\n";
        
        // 测试查询
        $stmt = $pdo->query("SELECT 1");
        echo "✅ 数据库查询正常！\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
        echo "错误代码: " . $e->getCode() . "\n";
        
        // 提供可能的解决方案
        echo "\n可能的解决方案:\n";
        echo "1. 检查MySQL服务是否运行\n";
        echo "2. 检查IP地址和端口是否正确\n";
        echo "3. 检查用户名和密码是否正确\n";
        echo "4. 检查防火墙设置\n";
        echo "5. 尝试使用localhost代替IP地址\n";
        
        return false;
    }
}

// 运行测试
test_database_connection();