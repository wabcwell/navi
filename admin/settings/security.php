<?php
require_once '../includes/load.php';

// 检查登录状态
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

// 获取设置管理实例
$settingsManager = get_settings_manager();

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
        $settingsManager->set('admin_password', $hashed_password);
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
        $settingsManager->set('max_login_attempts', $max_login_attempts);
        $settingsManager->set('lockout_duration', $lockout_duration);
        $settingsManager->set('session_timeout', $session_timeout);
        $settingsManager->set('ip_whitelist', $ip_whitelist);
        $settingsManager->set('ip_blacklist', $ip_blacklist);
        
        $_SESSION['success'] = '安全设置更新成功';
        header('Location: security.php');
        exit();
    }
}

// 获取当前设置
$settings = [
    'max_login_attempts' => $settingsManager->get('max_login_attempts', 5),
    'lockout_duration' => $settingsManager->get('lockout_duration', 30),
    'session_timeout' => $settingsManager->get('session_timeout', 1800),
    'ip_whitelist' => $settingsManager->get('ip_whitelist', ''),
    'ip_blacklist' => $settingsManager->get('ip_blacklist', ''),
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
                            <div class="card">
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-12">
                                                <h5 class="mb-3">管理员账户设置</h5>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="admin_username" class="form-label">管理员用户名</label>
                                                    <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                                           value="<?php echo htmlspecialchars($settingsManager->get('admin_username', 'admin')); ?>" 
                                                           required>
                                                    <div class="form-text">用于登录后台管理的用户名</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="admin_email" class="form-label">管理员邮箱</label>
                                                    <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                                           value="<?php echo htmlspecialchars($settingsManager->get('admin_email', '')); ?>">
                                                    <div class="form-text">用于接收系统通知的邮箱地址</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="admin_password" class="form-label">新密码</label>
                                                    <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                                           minlength="6">
                                                    <div class="form-text">留空则不修改密码，至少6位字符</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="admin_password_confirm" class="form-label">确认新密码</label>
                                                    <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" 
                                                           minlength="6">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        <h5 class="mb-3">安全设置</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="session_timeout" class="form-label">会话超时时间(分钟)</label>
                                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                                           value="<?php echo $settingsManager->get('session_timeout', 30); ?>" 
                                                           min="1" max="1440" required>
                                                    <div class="form-text">管理员会话在无操作多长时间后自动过期</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="max_login_attempts" class="form-label">最大登录尝试次数</label>
                                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                           value="<?php echo $settingsManager->get('max_login_attempts', 5); ?>" 
                                                           min="1" max="20" required>
                                                    <div class="form-text">登录失败多少次后锁定账户</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="login_lockout_duration" class="form-label">登录锁定时长(分钟)</label>
                                                    <input type="number" class="form-control" id="login_lockout_duration" name="login_lockout_duration" 
                                                           value="<?php echo $settingsManager->get('login_lockout_duration', 30); ?>" 
                                                           min="1" max="1440" required>
                                                    <div class="form-text">账户被锁定后多长时间自动解锁</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="password_min_length" class="form-label">密码最小长度</label>
                                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                                           value="<?php echo $settingsManager->get('password_min_length', 6); ?>" 
                                                           min="4" max="50" required>
                                                    <div class="form-text">管理员密码的最小字符长度</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" 
                                                           value="1" <?php echo $settingsManager->get('enable_2fa', 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enable_2fa">
                                                        启用两步验证
                                                    </label>
                                                    <div class="form-text">为管理员账户启用两步验证以增强安全性</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save"></i> 保存设置
                                            </button>
                                        </div>
                                    </form>
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