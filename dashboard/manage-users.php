<?php
require_once __DIR__ . '/../database.php';
require_any_role([ROLE_ADMIN]);
$doctors = doctors_options();

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_die();
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');
    $role     = (string)($_POST['role'] ?? ROLE_STAFF);
    $linkDoc  = ($_POST['linked_doctor_id'] ?? '') !== '' ? (int)$_POST['linked_doctor_id'] : null;

    if ($password !== $confirm) {
      flash_add('error', 'Passwords do not match.');
      redirect('./manage-users.php');
      exit;
    }

    if ($username && $email && $password) {
      try {
        create_user($username, $email, $password, $role, $linkDoc, true); // must_change_password = 1 for new users
        flash_add('success', "User '$username' created successfully.");
      } catch (InvalidArgumentException $e) {
        flash_add('error', $e->getMessage());
      } catch (Throwable $e) {
        flash_add('error', 'Could not create user (username or email may already exist).');
      }
    } else {
      flash_add('error', 'Please fill in all required fields.');
    }
    redirect('./manage-users.php');
    exit;
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        users_update($id, $_POST);
        flash_add('success', 'User updated successfully.');
      } catch (InvalidArgumentException $e) {
        flash_add('error', $e->getMessage());
      } catch (Throwable $e) {
        flash_add('error', 'Failed to update user.');
      }
    }
    redirect('./manage-users.php');
    exit;
  }

  if ($action === 'admin_change_password') {
    $userId  = (int)($_POST['user_id'] ?? 0);
    $newPass = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($userId <= 0) {
      flash_add('error', 'Invalid user selected.');
      redirect('./manage-users.php');
      exit;
    }

    if ($newPass !== $confirm) {
      flash_add('error', 'New password and confirmation do not match.');
      redirect('./manage-users.php');
      exit;
    }

    try {
      user_change_password($userId, $newPass, true);
      flash_add('success', "Password updated successfully for user ID #$userId.");
    } catch (InvalidArgumentException $e) {
      flash_add('error', $e->getMessage());
    } catch (Throwable $e) {
      flash_add('error', 'Failed to change user password.');
    }
    redirect('./manage-users.php');
    exit;
  }
}

if (($_GET['delete'] ?? '') !== '') {
  $delId = (int)$_GET['delete'];
  $me = current_user();
  if ($delId === (int)($me['id'] ?? 0)) {
    flash_add('error', 'You cannot delete your own account while logged in.');
  } else {
    users_delete($delId);
    flash_add('success', 'User deleted.');
  }
  redirect('./manage-users.php');
  exit;
}

$p = get_pagination_params(10);
$data = users_list($p['q'], $p['limit'], $p['offset']);
$rows = $data['rows'];
$pages = (int) ceil(max(1, (int)$data['total']) / $p['per']);
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = $editId ? users_get($editId) : null;
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — MediCore HMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="md:ml-64 max-w-6xl mx-auto p-6">
    <div class="mb-6"><a class="text-blue-600 hover:underline text-sm" href="index.php">← Back to Dashboard</a></div>

    <div class="flex items-center justify-between gap-4 mb-4">
      <h1 class="text-2xl font-bold text-slate-900"><?= $editRow ? 'Edit User #' . (int)$editRow['id'] : 'Create New User' ?></h1>
      <?php if ($editRow): ?>
        <a href="./manage-users.php" class="inline-flex items-center px-3 py-2 rounded bg-slate-600 text-white hover:bg-slate-700 text-sm">Cancel Edit</a>
      <?php endif; ?>
    </div>

    <?php render_flash(); ?>

    <form method="get" class="mb-6 flex gap-2">
      <input class="border rounded px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-blue-500 focus:outline-none" name="q" placeholder="Search username, email, role..." value="<?= htmlspecialchars($p['q']) ?>" />
      <button class="rounded bg-slate-800 text-white px-4 py-2 text-sm hover:bg-slate-900">Search</button>
      <?php if ($p['q']): ?><a href="./manage-users.php" class="rounded border px-3 py-2 text-sm hover:bg-slate-100">Clear</a><?php endif; ?>
    </form>

    <!-- Create / Edit Form -->
    <form method="post" class="bg-white border rounded-xl p-6 shadow-sm space-y-4 mb-8">
      <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>" /><?php endif; ?>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Username *</label>
          <input class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" name="username" value="<?= htmlspecialchars($editRow['username'] ?? '') ?>" required />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Email *</label>
          <input class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" name="email" type="email" value="<?= htmlspecialchars($editRow['email'] ?? '') ?>" required />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Role *</label>
          <select name="role" class="border border-slate-300 rounded px-3 py-2 text-sm w-full focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <?php foreach (['staff' => 'Staff / Frontdesk', 'doctor' => 'Doctor', 'admin' => 'Administrator'] as $rVal => $rLabel): ?>
              <option value="<?= $rVal ?>" <?= ($editRow['role'] ?? 'staff') === $rVal ? 'selected' : '' ?>><?= $rLabel ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Linked Doctor (optional)</label>
          <select name="linked_doctor_id" class="border border-slate-300 rounded px-3 py-2 text-sm w-full focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="">-- None --</option>
            <?php foreach ($doctors as $d): ?>
              <option value="<?= (int)$d['id'] ?>" <?= $editRow && (int)($editRow['linked_doctor_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!$editRow): ?>
          <div>
            <label class="block text-sm font-medium mb-1">Password *</label>
            <input class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" name="password" type="password" required placeholder="••••••••" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Confirm Password *</label>
            <input class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" name="confirm_password" type="password" required placeholder="••••••••" />
          </div>
        <?php endif; ?>
      </div>

      <?php if (!$editRow): ?>
        <p class="text-xs text-slate-500">🔒 Passwords must be at least 8 chars long with uppercase, lowercase, number & special character.</p>
      <?php endif; ?>

      <div class="flex items-center gap-2 pt-2">
        <button class="rounded bg-blue-600 text-white px-4 py-2 text-sm font-semibold hover:bg-blue-700 transition-colors">
          <?= $editRow ? 'Save Changes' : 'Create User' ?>
        </button>
        <?php if ($editRow): ?><a class="text-slate-600 hover:underline text-sm" href="./manage-users.php">Cancel</a><?php endif; ?>
      </div>
    </form>

    <!-- Users Table -->
    <h2 class="text-lg font-bold text-slate-900 mb-3">All System Users</h2>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700 uppercase text-xs">
          <tr>
            <th class="text-left px-4 py-3">ID</th>
            <th class="text-left px-4 py-3">Username</th>
            <th class="text-left px-4 py-3">Email</th>
            <th class="text-left px-4 py-3">Role</th>
            <th class="text-left px-4 py-3">Password Status</th>
            <th class="text-right px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-3 font-mono text-slate-500">#<?= (int)$r['id'] ?></td>
              <td class="px-4 py-3 font-medium"><?= htmlspecialchars($r['username']) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($r['email']) ?></td>
              <?php
              $role = (string)($r['role'] ?? 'staff');
              $roleCls = 'border px-2 py-0.5 rounded text-xs font-medium';
              if ($role === 'admin') {
                $roleCls .= ' border-purple-200 bg-purple-50 text-purple-700';
              } elseif ($role === 'doctor') {
                $roleCls .= ' border-sky-200 bg-sky-50 text-sky-700';
              } else {
                $roleCls .= ' border-slate-200 bg-slate-50 text-slate-700';
              }
              ?>
              <td class="px-4 py-3"><span class="<?= $roleCls ?>"><?= htmlspecialchars(ucfirst($role)) ?></span></td>
              <td class="px-4 py-3">
                <?php if (!empty($r['must_change_password'])): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                    ⚠️ Default (Change Required)
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium">
                    ✓ Secure
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-right space-x-1">
                <button onclick='openChangePasswordModal(<?= (int)$r['id'] ?>, <?= json_encode($r['username']) ?>)'
                        class="inline-flex items-center px-2.5 py-1 rounded border border-amber-300 text-amber-800 bg-amber-50 hover:bg-amber-100 text-xs font-medium">
                  🔑 Change Password
                </button>
                <a class="inline-flex items-center px-2.5 py-1 rounded border border-blue-200 text-blue-700 hover:bg-blue-50 text-xs font-medium" href="?edit=<?= (int)$r['id'] ?>">Edit</a>
                <a class="inline-flex items-center px-2.5 py-1 rounded border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium" href="?delete=<?= (int)$r['id'] ?>" onclick="return confirm('Delete user <?= htmlspecialchars($r['username']) ?>?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr>
              <td class="px-4 py-6 text-center text-slate-400" colspan="6">No users found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav aria-label="pagination" class="mt-4">
        <ul class="flex gap-2">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li><a class="px-3 py-1 rounded border text-sm <?= $i === $p['page'] ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-slate-100' ?>" href="?page=<?= $i ?>&per=<?= $p['per'] ?>&q=<?= urlencode($p['q']) ?>"><?= $i ?></a></li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </main>

  <!-- ── Admin Change Password Modal ───────────────────────────────────────── -->
  <div id="modal-admin-pass" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
      <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="font-semibold text-slate-800" id="modal-pass-title">Change User Password</h2>
        <button onclick="document.getElementById('modal-admin-pass').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
      </div>
      <form method="post" class="p-6 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="action" value="admin_change_password">
        <input type="hidden" name="user_id" id="modal-pass-user-id" />

        <div>
          <label class="block text-sm font-medium mb-1">New Password *</label>
          <input type="password" name="new_password" required placeholder="••••••••"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Confirm New Password *</label>
          <input type="password" name="confirm_password" required placeholder="••••••••"
            class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <div class="p-3 bg-slate-50 rounded border text-xs text-slate-600 space-y-0.5">
          <p class="font-semibold text-slate-700">Password Policy:</p>
          <p>• Min 8 characters with uppercase, lowercase, digit &amp; special symbol.</p>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="document.getElementById('modal-admin-pass').classList.add('hidden')"
            class="px-4 py-2 text-sm rounded border hover:bg-slate-50">Cancel</button>
          <button type="submit" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">
            Update User Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openChangePasswordModal(userId, username) {
      document.getElementById('modal-pass-user-id').value = userId;
      document.getElementById('modal-pass-title').innerText = 'Change Password for: ' + username;
      document.getElementById('modal-admin-pass').classList.remove('hidden');
    }
  </script>
</body>

</html>