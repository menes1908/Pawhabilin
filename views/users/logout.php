<?php
session_start();
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../utils/helper.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destroy the session
$uid = isset($_SESSION['user']['users_id']) ? (int)$_SESSION['user']['users_id'] : null;
$email = isset($_SESSION['user']['users_email']) ? (string)$_SESSION['user']['users_email'] : null;
if ($uid) {
    log_admin_action($connections, 'auth_logout', [
        'users_id' => $uid,
        'target' => 'user',
        'target_id' => (string)$uid,
        'details' => ['message' => 'Logout', 'email' => $email],
        'previous' => null,
        'new' => null
    ]);
}

session_destroy();

// Redirect to site homepage
header('Location: ../../index');
exit();
