<?php
require_once 'config.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // 查询透明度设置
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%opacity%'");
    $stmt->execute();
    $opacity_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "透明度设置值：\n";
    foreach ($opacity_settings as $setting) {
        echo $setting['setting_key'] . " = " . $setting['setting_value'] . "\n";
    }
} catch (PDOException $e) {
    echo "数据库查询错误: " . $e->getMessage();
}
?>