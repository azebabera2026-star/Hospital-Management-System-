<?php
require_once __DIR__ . '/../database.php';

if (isset($_SESSION['user']) && $_SESSION['user'] !== null) {
	redirect('../dashboard/index.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf_or_die();

	$identifier = trim($_POST['identifier'] ?? '');
	$password   = (string)($_POST['password'] ?? '');

	if ($identifier === '' || $password === '') {
		flash_add('error', 'Please provide username/email and password.');
		redirect('login.php?error=missing');
		exit;
	}

	// ── Rate limit: max 5 attempts per 15 minutes per IP + identifier ──────
	$ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	$rlKeyIp  = 'login:ip:' . $ip;
	$rlKeyUser = 'login:user:' . strtolower($identifier);

	if (!rate_limit_check($rlKeyIp, 10, 900) || !rate_limit_check($rlKeyUser, 5, 900)) {
		flash_add('error', 'Too many failed login attempts. Please wait 15 minutes before trying again.');
		redirect('login.php?error=ratelimited');
		exit;
	}

	$user = find_user_by_username_or_email($identifier);

	if (!$user || !password_verify($password, $user['password_hash'])) {
		flash_add('error', 'Invalid credentials.');
		redirect('login.php?error=invalid');
		exit;
	}

	// ── Successful login: clear rate limits & regenerate session ID ─────────
	rate_limit_clear($rlKeyIp);
	rate_limit_clear($rlKeyUser);
	session_regenerate_id(true); // prevents session fixation attacks

	$_SESSION['user'] = [
		'id'                   => (int)$user['id'],
		'username'             => $user['username'],
		'email'                => $user['email'],
		'role'                 => $user['role'],
		'linked_doctor_id'     => $user['linked_doctor_id'] !== null ? (int)$user['linked_doctor_id'] : null,
		'must_change_password' => !empty($user['must_change_password']),
	];

	audit_log('auth.login', 'user', (int)$user['id'], ['username' => $user['username']]);

	if (!empty($user['must_change_password'])) {
		flash_add('error', 'Default password detected. You must change your password before continuing.');
		redirect('../dashboard/change-password.php?forced=1');
		exit;
	}

	redirect('../dashboard/index.php');
	exit;
} else {
	include_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Login — MediCore HMS</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center">
	<main class="w-full max-w-md px-6">
		<div class="mb-6 text-center">
			<h1 class="text-2xl font-bold text-slate-900">MediCore HMS</h1>
			<p class="text-sm text-slate-500 mt-1">Hospital Management System — Sign In</p>
		</div>
		<?php render_flash(); ?>
		<form method="post" action="login.php" class="space-y-4 bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
			<div>
				<label class="block text-sm font-medium mb-1" for="identifier">Username or Email</label>
				<input id="identifier" name="identifier" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
					placeholder="username or email" required autocomplete="username" />
			</div>
			<div>
				<label class="block text-sm font-medium mb-1" for="password">Password</label>
				<input id="password" name="password" type="password"
					class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
					placeholder="••••••••" required autocomplete="current-password" />
			</div>
			<button type="submit"
				class="w-full rounded bg-blue-600 text-white py-2 text-sm font-semibold hover:bg-blue-700 transition-colors">
				Sign In
			</button>
		</form>
		<p class="mt-4 text-sm text-center text-slate-500">
			<a href="../" class="text-blue-600 hover:underline">Home</a>
			·
			<a class="text-blue-600 hover:underline" href="signup.php">Create account</a>
		</p>
	</main>
</body>

</html>
<?php
	exit;
}
