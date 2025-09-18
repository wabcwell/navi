<?php
require_once '../includes/load.php';
require_once '../includes/User.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: login.php');
    exit();
}

// 获取设置管理实例
$settingsManager = get_settings_manager();

// 创建 User 实例
$userManager = new User();

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
        try {
            // 使用 User 类更新管理员密码（假设管理员用户ID为1）
            $userManager->updateUser(1, ['password' => $new_password]);
            
            $_SESSION['success'] = '密码修改成功';
            header('Location: security.php');
            exit();
        } catch (Exception $e) {
            $errors[] = '密码修改失败: ' . $e->getMessage();
        }
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
        
        <!-- 密码修改表单 -->
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
        
        <!-- 安全设置表单 -->
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <h5 class="mb-3">安全设置</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_login_attempts" class="form-label">最大登录尝试次数</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                       value="<?php echo $settingsManager->get('max_login_attempts', 5); ?>" 
                                       min="1" max="10" required>
                                <div class="form-text">登录失败多少次后锁定账户</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="lockout_duration" class="form-label">锁定时间(分钟)</label>
                                <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                       value="<?php echo $settingsManager->get('lockout_duration', 30); ?>" 
                                       min="1" max="60" required>
                                <div class="form-text">账户被锁定后多长时间自动解锁</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">会话超时时间(分钟)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                       value="<?php echo $settingsManager->get('session_timeout', 1800); ?>" 
                                       min="300" max="7200" required>
                                <div class="form-text">管理员会话在无操作多长时间后自动过期</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ip_whitelist" class="form-label">IP白名单</label>
                                <textarea class="form-control" id="ip_whitelist" name="ip_whitelist" rows="2" 
                                          placeholder="每行一个IP地址，留空表示允许所有IP"><?php echo htmlspecialchars($settingsManager->get('ip_whitelist', '')); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ip_blacklist" class="form-label">IP黑名单</label>
                        <textarea class="form-control" id="ip_blacklist" name="ip_blacklist" rows="2" 
                                  placeholder="每行一个IP地址，留空表示不限制"><?php echo htmlspecialchars($settingsManager->get('ip_blacklist', '')); ?></textarea>
                    </div>
                    
                    <div class="text-end">
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