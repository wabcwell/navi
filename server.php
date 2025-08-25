<?php
// PHP内置服务器启动脚本

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 加载配置
require_once 'config.php';

// 获取命令行参数
$host = Config::SERVER_HOST;
$port = Config::SERVER_PORT;

// 允许自定义端口
$args = getopt('h:p:', ['host:', 'port:']);
if (isset($args['h']) || isset($args['host'])) {
    $host = $args['h'] ?? $args['host'];
}
if (isset($args['p']) || isset($args['port'])) {
    $port = $args['p'] ?? $args['port'];
}

// 启动PHP内置服务器
$command = "php -S {$host}:{$port} -t . router.php";

echo "=== PHP内置服务器 ===\n";
echo "启动服务器: {$host}:{$port}\n";
echo "访问地址: http://{$host}:{$port}\n";
echo "按 Ctrl+C 停止服务器\n\n";

// 创建路由器文件
$routerContent = '<?php
// PHP内置服务器路由文件

// API路由
if (strpos($_SERVER[\'REQUEST_URI\'], \'/api/\') === 0) {
    require_once \'api.php\';
    exit;
}

// 静态文件处理
$path = parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH);
$file = __DIR__ . $path;

// 如果文件存在，直接返回
if (php_sapi_name() === \'cli-server\') {
    if (is_file($file)) {
        return false; // 让PHP内置服务器处理静态文件
    }
}

// 默认路由到主页
if ($path === \'/\' || $path === \'/index.php\') {
    require_once \'views/home.php\';
    exit;
}

// 404处理
http_response_code(404);
echo \'<h1>404 Not Found</h1>\';
exit;
?>';

file_put_contents('router.php', $routerContent);

// 执行命令
system($command);

// 清理路由器文件
@unlink('router.php');
?>