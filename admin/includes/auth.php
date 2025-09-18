<?php
/**
 * 认证相关函数
 */

/**
 * 验证管理员密码
 */
function verify_admin_password($password) {
    // 首先尝试从users表获取密码
    $database = new Database();
    try {
        $stmt = $database->query("SELECT password FROM users WHERE username = 'admin'");
        $user = $stmt->fetch();
        if ($user) {
            return password_verify($password, $user['password']);
        }
    } catch (Exception $e) {
        // 如果users表查询失败，回退到settings表
    }
    
    // 回退到从settings表获取密码
    $settingsManager = get_settings_manager();
    $admin_password = $settingsManager->get('admin_password');
    if (!$admin_password) {
        // 首次安装，使用默认密码
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $settingsManager->set('admin_password', $admin_password);
    }
    return password_verify($password, $admin_password);
}

/**
 * 管理员登录
 */
function admin_login() {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_id'] = 1; // 管理员用户ID
}

/**
 * 管理员登出
 */
function admin_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * 检查是否已登录
 */
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 记录登录日志
 */
function log_login_attempt($username, $success) {
    $database = new Database();
    $stmt = $database->query("INSERT INTO login_logs (username, ip_address, success, login_time) VALUES (?, ?, ?, NOW())", [$username, $_SERVER['REMOTE_ADDR'], $success ? 1 : 0]);
}