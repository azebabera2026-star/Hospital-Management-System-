<?php
define('DB_HOST', getenv('HMS_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('HMS_DB_NAME') ?: 'hospital_management');
define('DB_USER', getenv('HMS_DB_USER') ?: 'root');
define('DB_PASS', getenv('HMS_DB_PASS') ?: '');

define('BASE_PATH', __DIR__);
define('BASE_URL', getenv('HMS_BASE_URL') ?: '/');
define('APP_ENV', getenv('HMS_ENV') ?: 'development');

// ── AI Configuration ─────────────────────────────────────────────────────────
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'gemini');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('AI_MODEL', getenv('AI_MODEL') ?: 'gemini-1.5-flash');
define('AI_TEMPERATURE', (float)(getenv('AI_TEMPERATURE') ?: 0.7));
// ── Guard: block insecure defaults in production ────────────────────────────
if (APP_ENV === 'production' && DB_PASS === '') {
	error_log('[HMS] WARNING: Running in production with an empty database password.');
}

// ── Secure session configuration ─────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
	ini_set('session.cookie_secure', '1');
}
ini_set('session.gc_maxlifetime', '7200'); // 2-hour session lifetime

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// ── HTTP Security Headers ────────────────────────────────────────────────────
if (!headers_sent()) {
	header('X-Frame-Options: DENY');
	header('X-Content-Type-Options: nosniff');
	header('X-XSS-Protection: 1; mode=block');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
	if (APP_ENV === 'production') {
		header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'");
	} else {
		header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'");
	}
}

// ── Role constants ───────────────────────────────────────────────────────────
const ROLE_ADMIN  = 'admin';
const ROLE_STAFF  = 'staff';
const ROLE_DOCTOR = 'doctor';

// ── Auth helpers ─────────────────────────────────────────────────────────────
function is_logged_in(): bool
{
	return isset($_SESSION['user']);
}

function current_user(): ?array
{
	return $_SESSION['user'] ?? null;
}

/**
 * Validates a password against strong security policy rules:
 * - Minimum 8 characters
 * - At least one uppercase letter (A-Z)
 * - At least one lowercase letter (a-z)
 * - At least one numeric digit (0-9)
 * - At least one special character (!@#$%^&*...)
 *
 * Returns null if valid, or an error string if invalid.
 */
function validate_strong_password(string $password): ?string
{
	if (strlen($password) < 8) {
		return 'Password must be at least 8 characters long.';
	}
	if (!preg_match('/[A-Z]/', $password)) {
		return 'Password must contain at least one uppercase letter (A-Z).';
	}
	if (!preg_match('/[a-z]/', $password)) {
		return 'Password must contain at least one lowercase letter (a-z).';
	}
	if (!preg_match('/[0-9]/', $password)) {
		return 'Password must contain at least one number (0-9).';
	}
	if (!preg_match('/[^A-Za-z0-9]/', $password)) {
		return 'Password must contain at least one special character (e.g. !@#$%^&*).';
	}
	return null;
}

function require_login(): void
{
	if (!is_logged_in()) {
		redirect('../auth/login.php?error=login_required');
	}
	$user = current_user();
	$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
	if (!empty($user['must_change_password']) && $currentScript !== 'change-password.php' && $currentScript !== 'logout.php') {
		flash_add('error', 'Security notice: You must change your default password before continuing.');
		redirect('../dashboard/change-password.php?forced=1');
	}
}

function require_role(string $role): void
{
	require_login();
	$user = current_user();
	if (!$user || ($user['role'] ?? null) !== $role) {
		http_response_code(403);
		echo '<h1 style="font-family:sans-serif;margin:2rem">403 — Forbidden</h1>';
		exit;
	}
}

function has_role(string $role): bool
{
	$user = current_user();
	return $user && (($user['role'] ?? null) === $role);
}

function require_any_role(array $roles): void
{
	require_login();
	$userRole = current_user()['role'] ?? null;
	if (!in_array($userRole, $roles, true)) {
		http_response_code(403);
		echo '<h1 style="font-family:sans-serif;margin:2rem">403 — Forbidden</h1>';
		exit;
	}
}

// ── Redirect helper ──────────────────────────────────────────────────────────
function redirect(string $path): void
{
	$isAbsoluteUrl = (stripos($path, 'http://') === 0) || (stripos($path, 'https://') === 0);
	if ($isAbsoluteUrl || (substr($path, 0, 1) === '/')) {
		header('Location: ' . $path);
		exit;
	}
	if (strpos($path, './') === 0 || strpos($path, '../') === 0) {
		header('Location: ' . $path);
		exit;
	}
	$base = defined('BASE_URL') ? (string)BASE_URL : '';
	if ($base && $base !== '/') {
		$base = rtrim($base, '/') . '/';
		$url  = $base . ltrim($path, '/');
	} else {
		$dir = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/\\') . '/';
		$url = $dir . ltrim($path, '/');
	}
	header('Location: ' . $url);
	exit;
}

// ── CSRF helpers ─────────────────────────────────────────────────────────────
function csrf_token(): string
{
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function verify_csrf_or_die(): void
{
	$token = $_POST['csrf_token'] ?? '';
	if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		http_response_code(400);
		echo 'Invalid or missing CSRF token.';
		exit;
	}
}

// ── Flash messages ───────────────────────────────────────────────────────────
function flash_add(string $type, string $message): void
{
	$_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_consume(): array
{
	$msgs = $_SESSION['flash'] ?? [];
	unset($_SESSION['flash']);
	return $msgs;
}

function render_flash(): void
{
	$msgs = flash_consume();
	if (!$msgs) return;
	echo '<div class="space-y-2 my-3">';
	foreach ($msgs as $m) {
		$isErr = ($m['type'] === 'error');
		$cls   = $isErr
			? 'border border-red-200 bg-red-50 text-red-800'
			: 'border border-emerald-200 bg-emerald-50 text-emerald-800';
		echo '<p class="' . $cls . ' px-3 py-2 rounded">' . htmlspecialchars($m['message']) . '</p>';
	}
	echo '</div>';
}

// ── Pagination helper ─────────────────────────────────────────────────────────
function get_pagination_params(int $defaultPerPage = 10): array
{
	$page = max(1, (int)($_GET['page'] ?? 1));
	$per  = (int)($_GET['per'] ?? $defaultPerPage);
	if ($per <= 0) $per = $defaultPerPage;
	if ($per > 100) $per = 100;
	$offset = ($page - 1) * $per;
	$q      = trim((string)($_GET['q'] ?? ''));
	return ['page' => $page, 'per' => $per, 'offset' => $offset, 'limit' => $per, 'q' => $q];
}

// ── Rate-limit store (file-based, zero dependencies) ─────────────────────────
function rate_limit_check(string $key, int $max = 5, int $windowSec = 900): bool
{
	$dir  = sys_get_temp_dir() . '/hms_rate_limits';
	if (!is_dir($dir)) {
		@mkdir($dir, 0700, true);
	}
	$file = $dir . '/' . hash('sha256', $key) . '.json';
	$now  = time();

	$attempts = [];
	if (file_exists($file)) {
		$data = @json_decode(file_get_contents($file), true);
		if (is_array($data)) {
			$attempts = array_filter($data, fn($t) => ($now - $t) < $windowSec);
		}
	}

	if (count($attempts) >= $max) {
		return false;
	}

	$attempts[] = $now;
	file_put_contents($file, json_encode(array_values($attempts)), LOCK_EX);
	return true;
}

function rate_limit_clear(string $key): void
{
	$dir  = sys_get_temp_dir() . '/hms_rate_limits';
	$file = $dir . '/' . hash('sha256', $key) . '.json';
	if (file_exists($file)) {
		@unlink($file);
	}
}
