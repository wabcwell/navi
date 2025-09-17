<?php
require_once 'includes/load.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$pdo = get_db_connection();

// 处理密码修改
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = '请输入当前密码';
    } elseif (!verify_admin_password($current_password)) {
        $errors[] = '当前密码不正确';
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = '新密码长度不能少于6位';
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = '两次输入的新密码不一致';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        set_site_setting('admin_password', $hashed_password);
        $_SESSION['success'] = '密码修改成功';
        header('Location: security.php');
        exit();
    }
}

// 处理安全设置
if (isset($_POST['update_security'])) {
    $max_login_attempts = intval($_POST['max_login_attempts'] ?? 5);
    $lockout_duration = intval($_POST['lockout_duration'] ?? 30);
    $session_timeout = intval($_POST['session_timeout'] ?? 1800);
    $ip_whitelist = trim($_POST['ip_whitelist'] ?? '');
    $ip_blacklist = trim($_POST['ip_blacklist'] ?? '');
    
    $errors = [];
    
    if ($max_login_attempts < 1 || $max_login_attempts > 10) {
        $errors[] = '最大登录尝试次数必须在1-10之间';
    }
    
    if ($lockout_duration < 1 || $lockout_duration > 60) {
        $errors[] = '锁定时间必须在1-60分钟之间';
    }
    
    if ($session_timeout < 300 || $session_timeout > 7200) {
        $errors[] = '会话超时时间必须在5分钟-2小时之间';
    }
    
    if (empty($errors)) {
        set_site_setting('max_login_attempts', $max_login_attempts);
        set_site_setting('lockout_duration', $lockout_duration);
        set_site_setting('session_timeout', $session_timeout);
        set_site_setting('ip_whitelist', $ip_whitelist);
        set_site_setting('ip_blacklist', $ip_blacklist);
        
        $_SESSION['success'] = '安全设置更新成功';
        header('Location: security.php');
        exit();
    }
}

// 获取当前设置
$settings = [
    'max_login_attempts' => get_site_setting('max_login_attempts', 5),
    'lockout_duration' => get_site_setting('lockout_duration', 30),
    'session_timeout' => get_site_setting('session_timeout', 1800),
    'ip_whitelist' => get_site_setting('ip_whitelist', ''),
    'ip_blacklist' => get_site_setting('ip_blacklist', ''),
];

$page_title = '安全设置';
include '../templates/header.php';
?>



<!-- 顶部导航栏 -->
<div class="row mb-4">
    <div class="col-12">
        <div class="nav nav-pills">
            <a href="general.php" class="nav-link">
                <i class="bi bi-gear"></i> 基本设置
            </a>
            <a href="security.php" class="nav-link active">
                <i class="bi bi-shield-lock"></i> 安全设置
            </a>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">当前密码</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6>密码安全提示</h6>
                                <ul class="mb-0">
                                    <li>密码长度至少6位</li>
                                    <li>包含大小写字母、数字和特殊字符</li>
                                    <li>不要使用常见密码</li>
                                    <li>定期更换密码</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="bi bi-key"></i> 修改密码
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="padding: 15px 20px;">
                <form method="POST">
                    <div class="row">
                        <div class="col-12">
                                <h6 class="text-primary mb-3">登录安全设置</h6>
                                <div class="row gx-3 mx-0">
                                    <div class="col-md-4 px-3">
                                        <div class="mb-3">
                                            <label for="max_login_attempts" class="form-label mb-1">最大登录尝试次数</label>
                                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                   value="<?php echo $settings['max_login_attempts']; ?>" min="1" max="10" style="margin-bottom: 4px;">
                                            <small class="form-text text-muted">连续登录失败达到此次数后账户将被锁定</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 px-3">
                                        <div class="mb-3">
                                            <label for="lockout_duration" class="form-label mb-1">锁定时间（分钟）</label>
                                            <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                                   value="<?php echo $settings['lockout_duration']; ?>" min="1" max="60" style="margin-bottom: 4px;">
                                            <small class="form-text text-muted">登录失败后的账户锁定持续时间</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 px-3">
                                        <div class="mb-3">
                                            <label for="session_timeout" class="form-label mb-1">会话超时时间（秒）</label>
                                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                                   value="<?php echo $settings['session_timeout']; ?>" min="300" max="7200" step="300" style="margin-bottom: 4px;">
                                            <small class="form-text text-muted">无操作后自动退出登录的时间</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-2">
                                <h6 class="text-primary mb-3">IP访问控制</h6>
                                <div class="row gx-3 mx-0">
                                    <div class="col-md-6 px-3">
                                        <div class="mb-3">
                                            <label for="ip_whitelist" class="form-label mb-1">IP白名单</label>
                                            <textarea class="form-control" id="ip_whitelist" name="ip_whitelist" 
                                                      rows="3" placeholder="每行一个IP地址" style="margin-bottom: 4px;"><?php echo htmlspecialchars($settings['ip_whitelist']); ?></textarea>
                                            <small class="form-text text-muted">只有这些IP可以访问后台（留空表示不限制）</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 px-3">
                                        <div class="mb-3">
                                            <label for="ip_blacklist" class="form-label mb-1">IP黑名单</label>
                                            <textarea class="form-control" id="ip_blacklist" name="ip_blacklist" 
                                                      rows="3" placeholder="每行一个IP地址" style="margin-bottom: 4px;"><?php echo htmlspecialchars($settings['ip_blacklist']); ?></textarea>
                                            <small class="form-text text-muted">这些IP将被禁止访问（留空表示不限制）</small>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="update_security" class="btn btn-primary">
                            <i class="bi bi-shield-check"></i> 保存安全设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>