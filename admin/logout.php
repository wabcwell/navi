<?php
require_once 'includes/load.php';
require_once 'includes/auth.php';

admin_logout();
header('Location: index.php');
exit();
?>