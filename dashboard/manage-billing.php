<?php
require_once __DIR__ . '/../database.php';
require_any_role([ROLE_ADMIN, ROLE_STAFF]);

$pdo  = get_db();
$user = current_user();

// ── Helpers ────────────────────────────────────────────────────────────────────
function invoice_total(PDO $pdo, int $invoiceId): float
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity * unit_price), 0) FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);
    return (float)$stmt->fetchColumn();
}

// ── POST actions ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_invoice') {
        $patientId     = (int)($_POST['patient_id'] ?? 0);
        $appointmentId = ($_POST['appointment_id'] ?? '') !== '' ? (int)$_POST['appointment_id'] : null;
        $dueDate       = trim($_POST['due_date'] ?? '') ?: null;
        $notes         = trim($_POST['notes'] ?? '');

        if ($patientId > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO invoices (patient_id, appointment_id, due_date, notes)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$patientId, $appointmentId, $dueDate, $notes ?: null]);
            $invoiceId = (int)$pdo->lastInsertId();

            // Insert line items
            $descs  = $_POST['item_desc']  ?? [];
            $qtys   = $_POST['item_qty']   ?? [];
            $prices = $_POST['item_price'] ?? [];
            foreach ($descs as $i => $desc) {
                $desc  = trim($desc);
                $qty   = (float)($qtys[$i] ?? 1);
                $price = (float)($prices[$i] ?? 0);
                if ($desc !== '' && $price > 0) {
                    $pdo->prepare(
                        "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price)
                         VALUES (?,?,?,?)"
                    )->execute([$invoiceId, $desc, $qty, $price]);
                }
            }
            flash_add('success', "Invoice #$invoiceId created.");
        } else {
            flash_add('error', 'Invalid patient selected.');
        }
    }

    if ($action === 'update_status') {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $status    = $_POST['status'] ?? '';
        $paid      = (float)($_POST['paid_amount'] ?? 0);
        $allowed   = ['unpaid', 'paid', 'partially_paid', 'cancelled'];
        if ($invoiceId > 0 && in_array($status, $allowed, true)) {
            $pdo->prepare("UPDATE invoices SET status=?, paid_amount=? WHERE id=?")
                ->execute([$status, $paid, $invoiceId]);
            flash_add('success', "Invoice #$invoiceId updated.");
        }
    }

    if ($action === 'delete_invoice' && has_role(ROLE_ADMIN)) {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        if ($invoiceId > 0) {
            $pdo->prepare("DELETE FROM invoices WHERE id=?")->execute([$invoiceId]);
            flash_add('success', "Invoice #$invoiceId deleted.");
        }
    }

    redirect('manage-billing.php');
    exit;
}

// ── GET — list invoices ────────────────────────────────────────────────────────
$pg    = get_pagination_params(15);
$q     = $pg['q'];
$where = $q ? "WHERE (p.first_name LIKE ? OR p.last_name LIKE ? OR i.id = ?)" : "";
$args  = $q ? ["%$q%", "%$q%", is_numeric($q) ? (int)$q : 0] : [];

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN patients p ON p.id=i.patient_id $where")
    ->execute($args) ? $pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN patients p ON p.id=i.patient_id $where")->execute($args) : 0;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN patients p ON p.id=i.patient_id $where");
$countStmt->execute($args);
$total = (int)$countStmt->fetchColumn();

$listStmt = $pdo->prepare(
    "SELECT i.*, CONCAT(p.first_name,' ',p.last_name) AS patient_name,
            COALESCE((SELECT SUM(quantity*unit_price) FROM invoice_items ii WHERE ii.invoice_id=i.id),0) AS total_amount
     FROM invoices i
     JOIN patients p ON p.id=i.patient_id
     $where
     ORDER BY i.issued_at DESC
     LIMIT {$pg['limit']} OFFSET {$pg['offset']}"
);
$listStmt->execute($args);
$invoices  = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = max(1, (int)ceil($total / $pg['per']));

// Dropdown data
$patients     = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM patients WHERE deleted_at IS NULL ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
$appointments = $pdo->query("SELECT a.id, CONCAT(p.first_name,' ',p.last_name,' — ',DATE_FORMAT(a.appointment_date,'%d %b %Y')) AS label FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.deleted_at IS NULL ORDER BY a.appointment_date DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

$isAdmin = has_role(ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Billing — MediCore HMS</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

<?php include __DIR__ . '/partials/nav.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-bold text-slate-900">Billing & Invoices</h1>
        <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
                class="inline-flex items-center gap-1 rounded bg-blue-600 text-white px-3 py-2 text-sm font-medium hover:bg-blue-700">
            + New Invoice
        </button>
    </div>

    <?php render_flash(); ?>

    <!-- Search -->
    <form method="get" class="mb-4 flex gap-2">
        <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search patient name or invoice #"
               class="rounded border border-slate-300 px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        <button class="rounded bg-slate-700 text-white px-3 py-2 text-sm hover:bg-slate-800">Search</button>
        <?php if ($q): ?><a href="manage-billing.php" class="rounded border px-3 py-2 text-sm hover:bg-slate-100">Clear</a><?php endif; ?>
    </form>

    <!-- Invoices table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 text-slate-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Patient</th>
                    <th class="px-4 py-3 text-left">Issued</th>
                    <th class="px-4 py-3 text-left">Due</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($invoices as $inv): ?>
                <?php
                    $balance   = $inv['total_amount'] - $inv['paid_amount'];
                    $statusCls = match($inv['status']) {
                        'paid'          => 'bg-emerald-100 text-emerald-800',
                        'partially_paid'=> 'bg-amber-100 text-amber-800',
                        'cancelled'     => 'bg-slate-100 text-slate-500',
                        default         => 'bg-red-100 text-red-800',
                    };
                ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-slate-500">#<?= $inv['id'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= htmlspecialchars($inv['patient_name']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= date('d M Y', strtotime($inv['issued_at'])) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '—' ?></td>
                    <td class="px-4 py-3 text-right font-medium">৳<?= number_format($inv['total_amount'], 2) ?></td>
                    <td class="px-4 py-3 text-right text-emerald-700">৳<?= number_format($inv['paid_amount'], 2) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusCls ?>">
                            <?= ucfirst(str_replace('_', ' ', $inv['status'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button onclick='openEditModal(<?= json_encode($inv) ?>)'
                                class="text-blue-600 hover:underline text-xs mr-2">Edit</button>
                        <?php if ($isAdmin): ?>
                        <form method="post" class="inline" onsubmit="return confirm('Delete invoice #<?= $inv['id'] ?>?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_invoice">
                            <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                            <button class="text-red-500 hover:underline text-xs">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No invoices found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 flex gap-1">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&q=<?= urlencode($q) ?>"
               class="px-3 py-1 rounded border text-sm <?= $p === $pg['page'] ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-slate-100' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</main>

<!-- ── Create Invoice Modal ───────────────────────────────────────────────── -->
<div id="modal-create" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold text-slate-800">New Invoice</h2>
            <button onclick="document.getElementById('modal-create').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="post" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_invoice">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Patient *</label>
                    <select name="patient_id" required class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">Select patient…</option>
                        <?php foreach ($patients as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= htmlspecialchars($pt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Appointment (optional)</label>
                    <select name="appointment_id" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="">None</option>
                        <?php foreach ($appointments as $ap): ?>
                            <option value="<?= $ap['id'] ?>"><?= htmlspecialchars($ap['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Due Date</label>
                    <input type="date" name="due_date" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"/>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="Optional notes" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"/>
                </div>
            </div>

            <!-- Line items -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700">Line Items</span>
                    <button type="button" onclick="addLineItem()" class="text-xs text-blue-600 hover:underline">+ Add Item</button>
                </div>
                <div id="line-items" class="space-y-2">
                    <div class="flex gap-2 items-center line-item">
                        <input type="text" name="item_desc[]" placeholder="Description" class="flex-1 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
                        <input type="number" name="item_qty[]"  step="0.01" value="1"    placeholder="Qty"   class="w-20 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
                        <input type="number" name="item_price[]" step="0.01" value="0"  placeholder="Price" class="w-28 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
                        <button type="button" onclick="this.closest('.line-item').remove()" class="text-red-400 hover:text-red-600">✕</button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                        class="px-4 py-2 text-sm rounded border hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit/Update Invoice Modal ─────────────────────────────────────────── -->
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold text-slate-800">Update Invoice</h2>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form method="post" id="edit-form" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="invoice_id" id="edit-invoice-id"/>
            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select name="status" id="edit-status" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="unpaid">Unpaid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Amount Paid (৳)</label>
                <input type="number" step="0.01" name="paid_amount" id="edit-paid"
                       class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"/>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="px-4 py-2 text-sm rounded border hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(inv) {
    document.getElementById('edit-invoice-id').value = inv.id;
    document.getElementById('edit-status').value     = inv.status;
    document.getElementById('edit-paid').value       = inv.paid_amount;
    document.getElementById('modal-edit').classList.remove('hidden');
}

function addLineItem() {
    const container = document.getElementById('line-items');
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center line-item';
    row.innerHTML = `
        <input type="text"   name="item_desc[]"  placeholder="Description" class="flex-1 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
        <input type="number" name="item_qty[]"   step="0.01" value="1"    placeholder="Qty"   class="w-20 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
        <input type="number" name="item_price[]" step="0.01" value="0"  placeholder="Price" class="w-28 rounded border border-slate-300 px-2 py-1.5 text-sm" required/>
        <button type="button" onclick="this.closest('.line-item').remove()" class="text-red-400 hover:text-red-600">✕</button>`;
    container.appendChild(row);
}
</script>
</body>
</html>
