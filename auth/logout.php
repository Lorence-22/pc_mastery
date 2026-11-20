<?php
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['user_id'])) log_activity('logout');
$_SESSION = [];
session_destroy();
redirect('auth/login.php');
