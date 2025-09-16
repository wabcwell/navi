<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';

$error = '';

// 检查是否已登录
if (is_logged_in()) {
    header('Location: ' . ADMIN_URL . '/dashboard.php');
    exit();
}

// 处理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif ($username === 'admin' && verify_admin_password($password)) {
        admin_login();
        log_login_attempt($username, true);
        header('Location: ' . ADMIN_URL . '/dashboard.php');
        exit();
    } else {
        $error = '用户名或密码错误';
        log_login_attempt($username, false);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理登录 - 导航网站</title>
    <link href="<?php echo ADMIN_URL; ?>/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>/assets/bootstrap/icons/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="bi bi-shield-lock"></i>
            <h2 class="h4 mb-0">管理后台登录</h2>
            <p class="text-muted">请输入管理员账号密码</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right"></i> 登录
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">默认用户名: admin<br>首次登录请使用安装时设置的密码</small>
        </div>
    </div>
</body>
</html>