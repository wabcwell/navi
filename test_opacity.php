<?php
// 获取透明度设置
$header_bg_opacity = '1';
$category_bg_opacity = '1';
$links_area_opacity = '1';
$link_card_opacity = '1';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>透明度测试</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --header-bg-opacity: <?php echo $header_bg_opacity; ?>;
            --category-bg-opacity: <?php echo $category_bg_opacity; ?>;
            --links-area-opacity: <?php echo $links_area_opacity; ?>;
            --link-card-opacity: <?php echo $link_card_opacity; ?>;
        }
        
        .test-container {
            padding: 20px;
            margin: 20px;
            border: 1px solid #ccc;
        }
        
        .test-header {
            background: rgba(255, 255, 255, var(--header-bg-opacity)) !important;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-category {
            background: rgba(255, 255, 255, var(--links-area-opacity)) !important;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-category-header {
            background: rgba(255, 255, 255, var(--category-bg-opacity)) !important;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .test-card {
            background: rgba(255, 255, 255, var(--link-card-opacity)) !important;
            padding: 10px;
            margin: 10px 0;
            display: inline-block;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>透明度测试页面</h1>
        
        <div class="test-header">
            <h2>测试标题背景</h2>
            <p>这个区域应该完全透明（如果设置正确）</p>
        </div>
        
        <div class="test-category">
            <div class="test-category-header">
                <h3>测试分类标题</h3>
            </div>
            <p>分类区域背景应该完全透明（如果设置正确）</p>
            
            <div class="test-card">
                <h4>测试卡片1</h4>
                <p>卡片应该完全透明（如果设置正确）</p>
            </div>
            
            <div class="test-card">
                <h4>测试卡片2</h4>
                <p>卡片应该完全透明（如果设置正确）</p>
            </div>
        </div>
        
        <script>
            // 检查CSS变量值
            const root = getComputedStyle(document.documentElement);
            console.log('CSS变量值:');
            console.log('--header-bg-opacity:', root.getPropertyValue('--header-bg-opacity'));
            console.log('--category-bg-opacity:', root.getPropertyValue('--category-bg-opacity'));
            console.log('--links-area-opacity:', root.getPropertyValue('--links-area-opacity'));
            console.log('--link-card-opacity:', root.getPropertyValue('--link-card-opacity'));
            
            // 检查实际背景色
            const testHeader = document.querySelector('.test-header');
            const testCategory = document.querySelector('.test-category');
            const testCategoryHeader = document.querySelector('.test-category-header');
            const testCard = document.querySelector('.test-card');
            
            console.log('\n实际背景色:');
            console.log('Header background:', getComputedStyle(testHeader).background);
            console.log('Category background:', getComputedStyle(testCategory).background);
            console.log('Category header background:', getComputedStyle(testCategoryHeader).background);
            console.log('Card background:', getComputedStyle(testCard).background);
        </script>
    </div>
</body>
</html>