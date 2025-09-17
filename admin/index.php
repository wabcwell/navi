<?php
require_once 'includes/load.php';

// 重定向到独立的登录页面
header('Location: ' . ADMIN_URL . '/login.php');
exit();