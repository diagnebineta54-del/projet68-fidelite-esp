<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . BASE_URL . (est_connecte() ? 'dashboard.php' : 'login.php'));
exit;
