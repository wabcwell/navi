<?php
/**
 * 网站示例数据初始化脚本
 * 创建8个分类和80个链接的示例数据
 */

require_once 'config.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 开始事务
    $pdo->beginTransaction();
    
    // 清空现有数据
    echo "正在清空现有数据...\n";
    $pdo->exec("DELETE FROM navigation_links");
    $pdo->exec("DELETE FROM categories");
    
    // 定义8个分类
    $categories = [
        [
            'name' => '搜索引擎',
            'description' => '常用搜索引擎集合',
            'icon' => 'fas fa-search',
            'icon_color' => '#4285f4',
            'color' => '#4285f4',
            'order_index' => 1
        ],
        [
            'name' => '社交媒体',
            'description' => '主流社交平台',
            'icon' => 'fas fa-users',
            'icon_color' => '#1877f2',
            'color' => '#1877f2',
            'order_index' => 2
        ],
        [
            'name' => '开发工具',
            'description' => '程序员必备开发工具',
            'icon' => 'fas fa-code',
            'icon_color' => '#24292e',
            'color' => '#24292e',
            'order_index' => 3
        ],
        [
            'name' => '设计资源',
            'description' => '设计素材和工具网站',
            'icon' => 'fas fa-palette',
            'icon_color' => '#ff6b35',
            'color' => '#ff6b35',
            'order_index' => 4
        ],
        [
            'name' => '学习平台',
            'description' => '在线学习和技术教程',
            'icon' => 'fas fa-graduation-cap',
            'icon_color' => '#0f997f',
            'color' => '#0f997f',
            'order_index' => 5
        ],
        [
            'name' => '新闻资讯',
            'description' => '科技新闻和资讯网站',
            'icon' => 'fas fa-newspaper',
            'icon_color' => '#ff6600',
            'color' => '#ff6600',
            'order_index' => 6
        ],
        [
            'name' => '云服务',
            'description' => '云计算和存储服务',
            'icon' => 'fas fa-cloud',
            'icon_color' => '#00a1f1',
            'color' => '#00a1f1',
            'order_index' => 7
        ],
        [
            'name' => '娱乐休闲',
            'description' => '休闲娱乐和视频网站',
            'icon' => 'fas fa-gamepad',
            'icon_color' => '#ff0000',
            'color' => '#ff0000',
            'order_index' => 8
        ]
    ];
    
    // 插入分类
    echo "正在创建分类...\n";
    $categoryStmt = $pdo->prepare("INSERT INTO categories (name, description, icon, icon_color, color, order_index, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $categoryMap = [];
    
    foreach ($categories as $category) {
        $categoryStmt->execute([
            $category['name'],
            $category['description'],
            $category['icon'],
            $category['icon_color'],
            $category['color'],
            $category['order_index']
        ]);
        $categoryMap[$category['name']] = $pdo->lastInsertId();
    }
    
    // 定义80个链接
    $links = [
        // 搜索引擎 (10个)
        ['Google', 'https://www.google.com', '全球最大的搜索引擎', '搜索引擎'],
        ['百度', 'https://www.baidu.com', '国内最大的搜索引擎', '搜索引擎'],
        ['必应', 'https://www.bing.com', '微软的搜索引擎', '搜索引擎'],
        ['DuckDuckGo', 'https://duckduckgo.com', '隐私保护的搜索引擎', '搜索引擎'],
        ['Yandex', 'https://yandex.com', '俄罗斯搜索引擎', '搜索引擎'],
        ['搜狗', 'https://www.sogou.com', '搜狗搜索引擎', '搜索引擎'],
        ['360搜索', 'https://www.so.com', '360搜索引擎', '搜索引擎'],
        ['Ecosia', 'https://www.ecosia.org', '环保搜索引擎', '搜索引擎'],
        ['Startpage', 'https://www.startpage.com', '隐私搜索引擎', '搜索引擎'],
        ['Qwant', 'https://www.qwant.com', '欧洲隐私搜索引擎', '搜索引擎'],
        
        // 社交媒体 (10个)
        ['微博', 'https://weibo.com', '中国主流社交媒体平台', '社交媒体'],
        ['Twitter', 'https://twitter.com', '全球社交媒体平台', '社交媒体'],
        ['Facebook', 'https://facebook.com', '全球最大的社交网络', '社交媒体'],
        ['Instagram', 'https://instagram.com', '图片分享社交平台', '社交媒体'],
        ['LinkedIn', 'https://linkedin.com', '职业社交平台', '社交媒体'],
        ['Reddit', 'https://reddit.com', '社区讨论平台', '社交媒体'],
        ['知乎', 'https://www.zhihu.com', '中文问答社区', '社交媒体'],
        ['豆瓣', 'https://www.douban.com', '文化娱乐社区', '社交媒体'],
        ['小红书', 'https://www.xiaohongshu.com', '生活方式分享平台', '社交媒体'],
        ['TikTok', 'https://www.tiktok.com', '短视频社交平台', '社交媒体'],
        
        // 开发工具 (10个)
        ['GitHub', 'https://github.com', '代码托管和协作平台', '开发工具'],
        ['GitLab', 'https://gitlab.com', 'DevOps平台', '开发工具'],
        ['VS Code', 'https://code.visualstudio.com', '微软代码编辑器', '开发工具'],
        ['Stack Overflow', 'https://stackoverflow.com', '程序员问答社区', '开发工具'],
        ['MDN Web Docs', 'https://developer.mozilla.org', 'Web开发文档', '开发工具'],
        ['npm', 'https://www.npmjs.com', 'Node.js包管理器', '开发工具'],
        ['Docker Hub', 'https://hub.docker.com', '容器镜像仓库', '开发工具'],
        ['Postman', 'https://www.postman.com', 'API测试工具', '开发工具'],
        ['CodePen', 'https://codepen.io', '前端代码在线编辑器', '开发工具'],
        ['JSFiddle', 'https://jsfiddle.net', '在线代码测试工具', '开发工具'],
        
        // 设计资源 (10个)
        ['Dribbble', 'https://dribbble.com', '设计师作品展示平台', '设计资源'],
        ['Behance', 'https://behance.net', '创意设计作品展示', '设计资源'],
        ['Figma', 'https://figma.com', '在线设计协作工具', '设计资源'],
        ['Adobe Creative Cloud', 'https://creativecloud.com', 'Adobe创意套件', '设计资源'],
        ['Canva', 'https://www.canva.com', '在线设计平台', '设计资源'],
        ['Unsplash', 'https://unsplash.com', '免费高质量图片', '设计资源'],
        ['Pexels', 'https://www.pexels.com', '免费图片和视频', '设计资源'],
        ['Freepik', 'https://www.freepik.com', '免费设计素材', '设计资源'],
        ['Iconfont', 'https://www.iconfont.cn', '阿里巴巴矢量图标库', '设计资源'],
        ['LottieFiles', 'https://lottiefiles.com', '动画设计资源', '设计资源'],
        
        // 学习平台 (10个)
        ['Coursera', 'https://www.coursera.org', '在线课程平台', '学习平台'],
        ['edX', 'https://www.edx.org', '在线学习平台', '学习平台'],
        ['Udemy', 'https://www.udemy.com', '在线课程市场', '学习平台'],
        ['Khan Academy', 'https://www.khanacademy.org', '免费教育平台', '学习平台'],
        ['Codecademy', 'https://www.codecademy.com', '编程学习平台', '学习平台'],
        ['LeetCode', 'https://leetcode.com', '算法练习平台', '学习平台'],
        ['慕课网', 'https://www.imooc.com', 'IT技能学习平台', '学习平台'],
        ['极客时间', 'https://time.geekbang.org', 'IT知识服务', '学习平台'],
        ['网易云课堂', 'https://study.163.com', '在线学习平台', '学习平台'],
        ['腾讯课堂', 'https://ke.qq.com', '在线教育平台', '学习平台'],
        
        // 新闻资讯 (10个)
        ['TechCrunch', 'https://techcrunch.com', '科技新闻网站', '新闻资讯'],
        ['The Verge', 'https://www.theverge.com', '科技新闻和评论', '新闻资讯'],
        ['Ars Technica', 'https://arstechnica.com', '科技新闻和分析', '新闻资讯'],
        ['Engadget', 'https://engadget.com', '科技新闻和评测', '新闻资讯'],
        ['36氪', 'https://36kr.com', '科技创业资讯', '新闻资讯'],
        ['虎嗅', 'https://www.huxiu.com', '科技商业资讯', '新闻资讯'],
        ['品玩', 'https://www.pingwest.com', '科技生活方式', '新闻资讯'],
        ['爱范儿', 'https://www.ifanr.com', '科技媒体', '新闻资讯'],
        ['少数派', 'https://sspai.com', '数字生活指南', '新闻资讯'],
        ['CSDN', 'https://www.csdn.net', 'IT技术社区', '新闻资讯'],
        
        // 云服务 (10个)
        ['AWS', 'https://aws.amazon.com', '亚马逊云服务', '云服务'],
        ['Microsoft Azure', 'https://azure.microsoft.com', '微软云服务', '云服务'],
        ['Google Cloud', 'https://cloud.google.com', '谷歌云服务', '云服务'],
        ['阿里云', 'https://www.aliyun.com', '阿里巴巴云服务', '云服务'],
        ['腾讯云', 'https://cloud.tencent.com', '腾讯云服务', '云服务'],
        ['华为云', 'https://www.huaweicloud.com', '华为云服务', '云服务'],
        ['百度云', 'https://cloud.baidu.com', '百度云服务', '云服务'],
        ['DigitalOcean', 'https://www.digitalocean.com', '云服务器提供商', '云服务'],
        ['Vercel', 'https://vercel.com', '前端部署平台', '云服务'],
        ['Netlify', 'https://www.netlify.com', '静态网站托管', '云服务'],
        
        // 娱乐休闲 (10个)
        ['YouTube', 'https://www.youtube.com', '全球最大的视频网站', '娱乐休闲'],
        ['Bilibili', 'https://www.bilibili.com', '哔哩哔哩视频网站', '娱乐休闲'],
        ['Netflix', 'https://www.netflix.com', '在线流媒体平台', '娱乐休闲'],
        ['Spotify', 'https://www.spotify.com', '音乐流媒体平台', '娱乐休闲'],
        ['Steam', 'https://store.steampowered.com', '游戏购买平台', '娱乐休闲'],
        ['Epic Games', 'https://www.epicgames.com', '游戏平台', '娱乐休闲'],
        ['网易云音乐', 'https://music.163.com', '音乐流媒体', '娱乐休闲'],
        ['QQ音乐', 'https://y.qq.com', '音乐流媒体', '娱乐休闲'],
        ['爱奇艺', 'https://www.iqiyi.com', '在线视频平台', '娱乐休闲'],
        ['腾讯视频', 'https://v.qq.com', '在线视频平台', '娱乐休闲']
    ];
    
    // 插入链接
    echo "正在创建链接...\n";
    $linkStmt = $pdo->prepare("INSERT INTO navigation_links (title, url, description, category_id, icon_url, order_index, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    
    $orderIndex = 0;
    foreach ($links as $link) {
        $categoryId = $categoryMap[$link[3]];
        $iconUrl = 'https://www.google.com/s2/favicons?domain=' . parse_url($link[1], PHP_URL_HOST) . '&sz=32';
        
        $linkStmt->execute([
            $link[0],
            $link[1],
            $link[2],
            $categoryId,
            $iconUrl,
            $orderIndex++
        ]);
    }
    
    // 提交事务
    $pdo->commit();
    
    echo "✅ 数据初始化完成！\n";
    echo "📊 创建了 " . count($categories) . " 个分类\n";
    echo "🔗 创建了 " . count($links) . " 个链接\n";
    echo "\n现在您可以访问 http://localhost:8000 查看效果！\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>