<?php
require_once '../includes/load.php';

// 检查登录状态
if (!User::checkLogin()) {
    header('Location: ../login.php');
    exit();
}

// settings目录默认跳转到general.php
header('Location: general.php');
exit();