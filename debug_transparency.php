<?php
// 获取当前设置
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%opacity%'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $header_bg_opacity = $settings['header_bg_opacity'] ?? '0.85';
    $category_bg_opacity = $settings['category_bg_opacity'] ?? '0.85';
    $links_area_opacity = $settings['links_area_opacity'] ?? '0.85';
    $link_card_opacity = $settings['link_card_opacity'] ?? '0.85';
    
} catch (Exception $e) {
    $header_bg_opacity = '0.85';
    $category_bg_opacity = '0.85';
    $links_area_opacity = '0.85';
    $link_card_opacity = '0.85';
}

// 转换为不透明度值进行测试
$header_bg_transparency = 1 - floatval($header_bg_opacity);
$category_bg_transparency = 1 - floatval($category_bg_opacity);
$links_area_transparency = 1 - floatval($links_area_opacity);
$link_card_transparency = 1 - floatval($link_card_opacity);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>透明度调试测试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        .color-box {
            width: 100px;
            height: 50px;
            display: inline-block;
            margin: 5px;
            border: 1px solid #ccc;
        }
        .explanation {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>透明度设置调试测试</h1>
        
        <div class="explanation">
            <h3>概念说明</h3>
            <p><strong>透明度 (Opacity)</strong>: 值为 0 时完全透明，值为 1 时完全不透明</p>
            <p><strong>不透明度 (Transparency)</strong>: 值为 0 时完全不透明，值为 1 时完全透明</p>
            <p>两者关系: 不透明度 = 1 - 透明度</p>
        </div>
        
        <div class="test-section">
            <h2>当前数据库中的设置值</h2>
            <table>
                <tr>
                    <th>设置项</th>
                    <th>存储值 (透明度)</th>
                    <th>计算的不透明度值</th>
                </tr>
                <tr>
                    <td>头部背景透明度</td>
                    <td><?php echo $header_bg_opacity; ?></td>
                    <td><?php echo $header_bg_transparency; ?></td>
                </tr>
                <tr>
                    <td>分类背景透明度</td>
                    <td><?php echo $category_bg_opacity; ?></td>
                    <td><?php echo $category_bg_transparency; ?></td>
                </tr>
                <tr>
                    <td>链接区域透明度</td>
                    <td><?php echo $links_area_opacity; ?></td>
                    <td><?php echo $links_area_transparency; ?></td>
                </tr>
                <tr>
                    <td>链接卡片透明度</td>
                    <td><?php echo $link_card_opacity; ?></td>
                    <td><?php echo $link_card_transparency; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="test-section">
            <h2>视觉效果对比测试</h2>
            <p>使用透明度值 (当前设置):</p>
            <div class="color-box" style="background: rgba(255, 0, 0, <?php echo $header_bg_opacity; ?>);"></div>
            <div class="color-box" style="background: rgba(0, 255, 0, <?php echo $category_bg_opacity; ?>);"></div>
            <div class="color-box" style="background: rgba(0, 0, 255, <?php echo $links_area_opacity; ?>);"></div>
            <div class="color-box" style="background: rgba(255, 255, 0, <?php echo $link_card_opacity; ?>);"></div>
            
            <p>使用不透明度值 (如果概念混淆):</p>
            <div class="color-box" style="background: rgba(255, 0, 0, <?php echo $header_bg_transparency; ?>);"></div>
            <div class="color-box" style="background: rgba(0, 255, 0, <?php echo $category_bg_transparency; ?>);"></div>
            <div class="color-box" style="background: rgba(0, 0, 255, <?php echo $links_area_transparency; ?>);"></div>
            <div class="color-box" style="background: rgba(255, 255, 0, <?php echo $link_card_transparency; ?>);"></div>
        </div>
        
        <div class="test-section">
            <h2>结论</h2>
            <p>如果视觉效果与预期相反（比如设置0.2却显示为80%不透明），则说明系统实际应用的是不透明度概念。</p>
            <p>在这种情况下，需要修改代码将设置值转换为实际透明度值：实际透明度 = 1 - 设置值</p>
        </div>
    </div>
</body>
</html>