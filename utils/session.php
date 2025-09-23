<?php
// Simple session utilities for user persistence across pages

if (!function_exists('session_start_if_needed')) {
	function session_start_if_needed(): void {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
	}
}

if (!function_exists('get_current_user_session')) {
	function get_current_user_session(): ?array {
		session_start_if_needed();
		return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
	}
}

if (!function_exists('user_display_name')) {
	function user_display_name(?array $u): string {
		if (!$u) return '';
		if (!empty($u['users_username'])) return (string)$u['users_username'];
		$fn = trim(($u['users_firstname'] ?? '') . ' ' . ($u['users_lastname'] ?? ''));
		if ($fn !== '') return $fn;
		return (string)($u['users_email'] ?? '');
	}
}

if (!function_exists('user_initial')) {
	function user_initial(?array $u): string {
		$name = user_display_name($u);
		return $name !== '' ? strtoupper(substr($name, 0, 1)) : 'U';
	}
}

if (!function_exists('user_image_url')) {
	function user_image_url(?array $u): string {
		return $u && !empty($u['users_image_url']) ? (string)$u['users_image_url'] : '';
	}
}

?>
