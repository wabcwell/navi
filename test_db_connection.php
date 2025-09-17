<?php
/**
 * 测试数据库连接
 */

// 引入数据库配置和类
require_once 'config.php';
require_once 'admin/includes/Database.php';

try {
    // 创建数据库连接
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "数据库连接成功！\n";
    echo "数据库信息:\n";
    echo "- 主机: " . DB_HOST . "\n";
    echo "- 端口: " . DB_PORT . "\n";
    echo "- 数据库名: " . DB_NAME . "\n";
    
    // 测试查询
    $stmt = $pdo->query("SELECT VERSION() as version");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- MySQL版本: " . $row['version'] . "\n";
    
} catch (Exception $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>