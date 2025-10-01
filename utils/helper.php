<?php
// Audit logging helper utilities
// Provides: ensure_admin_activity_logs_table, log_admin_action, h
// - Auto-creates a modern admin_activity_logs table if missing
// - Works with legacy schema (admin_activity_logs_action/admin_activity_logs_details) if present

if (!function_exists('ensure_admin_activity_logs_table')) {
	function ensure_admin_activity_logs_table($connections)
	{
		if (!$connections) return false;
		// If table already exists, do nothing
		$exists = false;
		if ($res = @mysqli_query($connections, "SHOW TABLES LIKE 'admin_activity_logs'")) {
			$exists = @mysqli_num_rows($res) > 0; @mysqli_free_result($res);
		}
		if ($exists) return true;

		// Create modern schema
		$sql = "CREATE TABLE IF NOT EXISTS admin_activity_logs (
			id INT AUTO_INCREMENT PRIMARY KEY,
			timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			users_id INT NULL,
			user_email VARCHAR(255) NULL,
			ip_address VARCHAR(64) NULL,
			http_method VARCHAR(16) NULL,
			request_path VARCHAR(255) NULL,
			action_type VARCHAR(64) NOT NULL,
			target VARCHAR(255) DEFAULT '' NOT NULL,
			target_id VARCHAR(64) DEFAULT '' NOT NULL,
			details TEXT NULL,
			previous_value LONGTEXT NULL,
			new_value LONGTEXT NULL,
			INDEX idx_action_type (action_type),
			INDEX idx_timestamp (timestamp),
			INDEX idx_target (target),
			INDEX idx_users_id (users_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		@mysqli_query($connections, $sql);
		return true;
	}
}

if (!function_exists('log_admin_action')) {
	/**
	 * Log an admin action.
	 *
	 * @param mysqli $connections Active DB connection
	 * @param string $actionType  e.g., 'additions','updates','price_changes','stock_changes','auth_login'
	 * @param array  $opts        target, target_id, details (string|array), previous, new, users_id(optional)
	 */
	function log_admin_action($connections, string $actionType, array $opts = [])
	{
		if (!$connections) return false;
		// Ensure table exists if missing (creates modern schema only when absent)
		ensure_admin_activity_logs_table($connections);

		if (session_status() === PHP_SESSION_NONE) {
			@session_start();
		}

		// Resolve admin user id/email
		$userId = isset($opts['users_id']) ? (int)$opts['users_id'] : null;
		$userEmail = null;
		if ($userId === null) {
			if (!empty($_SESSION['user']['users_id'])) {
				$userId = (int)$_SESSION['user']['users_id'];
				$userEmail = (string)($_SESSION['user']['users_email'] ?? null);
			} elseif (!empty($_SESSION['users_id'])) {
				$userId = (int)$_SESSION['users_id'];
				$userEmail = isset($_SESSION['users_email']) ? (string)$_SESSION['users_email'] : null;
			}
		}
		if (!$userEmail && $userId) {
			if ($stmt = @mysqli_prepare($connections, "SELECT users_email FROM users WHERE users_id = ? LIMIT 1")) {
				@mysqli_stmt_bind_param($stmt, 'i', $userId);
				@mysqli_stmt_execute($stmt);
				@mysqli_stmt_bind_result($stmt, $em);
				if (@mysqli_stmt_fetch($stmt)) { $userEmail = $em; }
				@mysqli_stmt_close($stmt);
			}
		}

		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$method = $_SERVER['REQUEST_METHOD'] ?? '';
		$path = $_SERVER['REQUEST_URI'] ?? '';

		$target = isset($opts['target']) ? (string)$opts['target'] : '';
		$targetId = isset($opts['target_id']) ? (string)$opts['target_id'] : '';
		$details = $opts['details'] ?? null;
		if (is_array($details) || is_object($details)) {
			$details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} elseif ($details !== null) {
			$details = (string)$details;
		}
		$prev = $opts['previous'] ?? null;
		$new  = $opts['new'] ?? null;
		$prevJson = $prev !== null ? (is_string($prev) ? $prev : json_encode($prev, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) : null;
		$newJson  = $new  !== null ? (is_string($new)  ? $new  : json_encode($new,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))  : null;

		// Detect schema: modern vs legacy
		$hasRich = false; $hasLegacy = false;
		if ($res = @mysqli_query($connections, "SHOW COLUMNS FROM admin_activity_logs LIKE 'action_type'")) {
			$hasRich = @mysqli_num_rows($res) > 0; @mysqli_free_result($res);
		}
		if (!$hasRich) {
			if ($res = @mysqli_query($connections, "SHOW COLUMNS FROM admin_activity_logs LIKE 'admin_activity_logs_action'")) {
				$hasLegacy = @mysqli_num_rows($res) > 0; @mysqli_free_result($res);
			}
		}

		if ($hasRich) {
			if ($stmt = @mysqli_prepare($connections, "INSERT INTO admin_activity_logs (users_id, user_email, ip_address, http_method, request_path, action_type, target, target_id, details, previous_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
				@mysqli_stmt_bind_param($stmt, 'issssssssss', $userId, $userEmail, $ip, $method, $path, $actionType, $target, $targetId, $details, $prevJson, $newJson);
				@mysqli_stmt_execute($stmt);
				@mysqli_stmt_close($stmt);
				return true;
			}
			return false;
		} elseif ($hasLegacy) {
			// Insert minimal info into legacy schema; pack extra context into details JSON
			$adminId = $userId ?: null;
			$legacyDetails = [
				'target' => $target,
				'target_id' => $targetId,
				'details' => $details,
				'previous' => $prevJson ? json_decode($prevJson, true) : null,
				'new' => $newJson ? json_decode($newJson, true) : null,
				'ip' => $ip,
				'method' => $method,
				'path' => $path,
				'user_email' => $userEmail
			];
			$legacyJson = json_encode($legacyDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($stmt = @mysqli_prepare($connections, "INSERT INTO admin_activity_logs (admin_id, admin_activity_logs_action, admin_activity_logs_details) VALUES (?, ?, ?)")) {
				@mysqli_stmt_bind_param($stmt, 'iss', $adminId, $actionType, $legacyJson);
				@mysqli_stmt_execute($stmt);
				@mysqli_stmt_close($stmt);
				return true;
			}
			return false;
		} else {
			// Table might not exist yet; try create modern and insert
			ensure_admin_activity_logs_table($connections);
			if ($stmt = @mysqli_prepare($connections, "INSERT INTO admin_activity_logs (users_id, user_email, ip_address, http_method, request_path, action_type, target, target_id, details, previous_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
				@mysqli_stmt_bind_param($stmt, 'issssssssss', $userId, $userEmail, $ip, $method, $path, $actionType, $target, $targetId, $details, $prevJson, $newJson);
				@mysqli_stmt_execute($stmt);
				@mysqli_stmt_close($stmt);
				return true;
			}
			return false;
		}
	}
}

if (!function_exists('h')) {
	function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

?>
