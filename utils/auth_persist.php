<?php
/**
 * auth_persist.php
 * Lightweight persistent login helper without altering existing logic.
 *
 * HOW IT WORKS:
 *  - When a user is logged in (has $_SESSION['users_id']), we set a signed cookie (paw_auth)
 *    containing: userId|signature. Signature = HMAC-SHA256(userId, APP_PERSIST_SECRET).
 *  - When a request arrives WITH the cookie but WITHOUT an active session, we validate signature
 *    and (optionally) verify the user still exists, then silently restore $_SESSION['users_id'].
 *  - This lets users keep access after PHP session expiry, until they explicitly logout.
 *
 * SECURITY NOTES (simple version):
 *  - No rotation / revoke list. Removing cookie on logout recommended (update logout page to clear paw_auth).
 *  - HMAC secret stored here; for production move to environment configuration.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!defined('APP_PERSIST_SECRET')) {
    // Random-ish static secret; replace with env var in production.
    define('APP_PERSIST_SECRET', 'pawhabilin_local_dev_secret_ChangeMe_2025');
}

$cookieName = 'paw_auth';
$maxAge = 60 * 60 * 24 * 30; // 30 days

/** Generate signature */
$sign = function(string $userId): string {
    return hash_hmac('sha256', $userId, APP_PERSIST_SECRET);
};

// If session exists ensure cookie present & fresh
if (!empty($_SESSION['users_id'])) {
    $uid = (string)$_SESSION['users_id'];
    $expected = $uid . '|' . $sign($uid);
    if (empty($_COOKIE[$cookieName]) || $_COOKIE[$cookieName] !== $expected) {
        setcookie($cookieName, $expected, time() + $maxAge, '/', '', false, true); // HttpOnly
    }
} else {
    // No active session, attempt auto-restore via cookie
    if (!empty($_COOKIE[$cookieName])) {
        $parts = explode('|', $_COOKIE[$cookieName], 2);
        if (count($parts) === 2) {
            [$rawUid, $sig] = $parts;
            if (ctype_digit($rawUid) && hash_equals($sign($rawUid), $sig)) {
                // Optionally confirm user exists once to avoid resurrecting deleted users
                $dbPath = __DIR__ . '/../database.php';
                if (file_exists($dbPath)) {
                    require_once $dbPath;
                }
                if (isset($connections) && $connections) {
                    $res = mysqli_query($connections, 'SELECT users_id FROM users WHERE users_id=' . (int)$rawUid . ' LIMIT 1');
                    if ($res && mysqli_fetch_assoc($res)) {
                        $_SESSION['users_id'] = (int)$rawUid;
                    }
                    if ($res) { mysqli_free_result($res); }
                } else {
                    // If DB not available just trust cookie (fallback)
                    $_SESSION['users_id'] = (int)$rawUid;
                }
            }
        }
    }
}
