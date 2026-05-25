<?php
require_once '../includes/session.php';
destroySession();
header('Location: /securevault/auth/login.php');
exit;