<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch all vendors with user info
$stmt = $pdo->query("SELECT v.*, u.username FROM vendors v JOIN users u ON v.user_id = u.id ORDER BY v.created_at DESC");
$vendors = $stmt->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-secondary"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Admin Dashboard</h2>
    <a href="add_vendor.php" class="btn btn-warning fw-bold"><i class="fa-solid fa-plus me-1"></i> Add New Vendor</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="card-title mb-3">Registered Vendors</h4>
        <?php if (empty($vendors)): ?>
            <p class="text-muted">No vendors registered yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Logo</th>
                            <th>Store Name</th>
                            <th>Username</th>
                            <th>WhatsApp</th>
                            <th>Status</th>
                            <th>Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $v): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/vendors/<?= htmlspecialchars($v['logo'] ?: 'default_logo.png') ?>" width="40" height="40" class="rounded-circle object-fit-cover" alt="">
                                </td>
                                <td class="fw-semibold"><?= htmlspecialchars($v['store_name']) ?></td>
                                <td><?= htmlspecialchars($v['username']) ?></td>
                                <td><?= htmlspecialchars($v['whatsapp_number']) ?></td>
                                <td>
                                    <?php if ($v['status'] === 'open'): ?>
                                        <span class="badge bg-success">Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($v['is_active']): ?>
                                        <span class="badge bg-primary">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
