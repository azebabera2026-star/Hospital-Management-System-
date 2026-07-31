<?php
require_once __DIR__ . '/../database.php';
require_login();

$user = current_user();
$userId = (int)($user['id'] ?? 0);
$isForced = !empty($_GET['forced']) || !empty($user['must_change_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf_or_die();

	$currentPassword = (string)($_POST['current_password'] ?? '');
	$newPassword     = (string)($_POST['new_password'] ?? '');
	$confirmPassword = (string)($_POST['confirm_password'] ?? '');

	// Fetch full current user record from DB to verify password hash
	$dbUser = users_get($userId);
	if (!$dbUser) {
		flash_add('error', 'User session invalid. Please log in again.');
		redirect('../auth/login.php');
		exit;
	}

	// 1. Verify current password
	if (!password_verify($currentPassword, $dbUser['password_hash'])) {
		flash_add('error', 'Current password is incorrect.');
		redirect('change-password.php' . ($isForced ? '?forced=1' : ''));
		exit;
	}

	// 2. Check password confirmation
	if ($newPassword !== $confirmPassword) {
		flash_add('error', 'New password and confirmation do not match.');
		redirect('change-password.php' . ($isForced ? '?forced=1' : ''));
		exit;
	}

	// 3. Prevent using the exact same password
	if (password_verify($newPassword, $dbUser['password_hash'])) {
		flash_add('error', 'New password cannot be the same as your current password.');
		redirect('change-password.php' . ($isForced ? '?forced=1' : ''));
		exit;
	}

	// 4. Enforce strong password policy
	$policyError = validate_strong_password($newPassword);
	if ($policyError !== null) {
		flash_add('error', $policyError);
		redirect('change-password.php' . ($isForced ? '?forced=1' : ''));
		exit;
	}

	// 5. Update password & clear must_change_password flag
	try {
		user_change_password($userId, $newPassword, true);

		// Update active session flag
		$_SESSION['user']['must_change_password'] = false;

		flash_add('success', 'Your password has been changed successfully!');
		redirect('index.php');
		exit;
	} catch (Throwable $e) {
		flash_add('error', 'Failed to change password: ' . htmlspecialchars($e->getMessage()));
		redirect('change-password.php' . ($isForced ? '?forced=1' : ''));
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Change Password — MediCore HMS</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen">

	<?php include __DIR__ . '/sidebar.php'; ?>

	<main class="md:ml-64 max-w-xl mx-auto p-6">
		<div class="mb-6">
			<a class="text-blue-600 hover:underline text-sm" href="index.php">← Back to Dashboard</a>
		</div>

		<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
			<div class="mb-5 pb-4 border-b border-slate-100">
				<h1 class="text-xl font-bold text-slate-900">Change Password</h1>
				<p class="text-sm text-slate-500 mt-1">Update your account password. Must meet all security requirements.</p>
			</div>

			<?php render_flash(); ?>

			<?php if ($isForced): ?>
				<div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded text-amber-800 text-sm">
					⚠️ <strong>Default Password Warning:</strong> You must change your temporary default password before continuing to use the system.
				</div>
			<?php endif; ?>

			<form method="post" action="change-password.php<?= $isForced ? '?forced=1' : '' ?>" class="space-y-4">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

				<div>
					<label class="block text-sm font-medium mb-1" for="current_password">Current Password *</label>
					<input id="current_password" name="current_password" type="password" required
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
						placeholder="••••••••" autocomplete="current-password" />
				</div>

				<div>
					<label class="block text-sm font-medium mb-1" for="new_password">New Password *</label>
					<input id="new_password" name="new_password" type="password" required
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
						placeholder="••••••••" autocomplete="new-password" />
				</div>

				<div>
					<label class="block text-sm font-medium mb-1" for="confirm_password">Confirm New Password *</label>
					<input id="confirm_password" name="confirm_password" type="password" required
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
						placeholder="••••••••" autocomplete="new-password" />
				</div>

				<!-- Password Policy Rules -->
				<div class="p-3 bg-slate-50 rounded border border-slate-200 text-xs text-slate-600 space-y-1">
					<p class="font-semibold text-slate-700">Password Policy Requirements:</p>
					<ul class="list-disc list-inside space-y-0.5">
						<li>At least 8 characters long</li>
						<li>At least one uppercase letter (A-Z)</li>
						<li>At least one lowercase letter (a-z)</li>
						<li>At least one number (0-9)</li>
						<li>At least one special character (e.g., !@#$%^&amp;*)</li>
					</ul>
				</div>

				<div class="flex items-center gap-3 pt-2">
					<button type="submit"
						class="rounded bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700 transition-colors">
						Update Password
					</button>
					<?php if (!$isForced): ?>
						<a href="index.php" class="text-sm text-slate-500 hover:underline">Cancel</a>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</main>

</body>

</html>
