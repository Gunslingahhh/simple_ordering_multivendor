<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch vendor record
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$user_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    echo '<div class="alert alert-danger">Vendor profile not found. Please contact admin.</div>';
    require_once '../includes/footer.php';
    exit;
}

$vendor_id = $vendor['id'];

// Construct the secure special link using the vendor's token
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$specialLink = "$protocol://$host/simple_ordering_multivendor/index.php?token=" . $vendor['token'];

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $store_name = trim($_POST['store_name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $status = $_POST['status'];

    if (!empty($store_name) && !empty($whatsapp_number)) {
        $logo = $vendor['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];
            $fileSize = $_FILES['logo']['size'];
            $fileType = $_FILES['logo']['type'];
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (in_array($fileType, $allowedTypes) && $fileSize <= 5 * 1024 * 1024) {
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = '../uploads/vendors/' . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $logo = $newFileName;
                }
            } else {
                $error = 'Invalid logo image or exceeds 5MB.';
            }
        }

        if (empty($error)) {
            $stmtUp = $pdo->prepare("UPDATE vendors SET store_name = ?, description = ?, address = ?, contact_number = ?, whatsapp_number = ?, status = ?, logo = ? WHERE id = ?");
            $stmtUp->execute([$store_name, $description, $address, $contact_number, $whatsapp_number, $status, $logo, $vendor_id]);
            $success = 'Profile updated successfully!';
            // Refresh vendor data
            $stmt->execute([$user_id]);
            $vendor = $stmt->fetch();
        }
    } else {
        $error = 'Store name and WhatsApp number are required.';
    }
}

// Fetch menu items
$stmtMenu = $pdo->prepare("SELECT * FROM menu_items WHERE vendor_id = ? ORDER BY name ASC");
$stmtMenu->execute([$vendor_id]);
$menu_items = $stmtMenu->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-secondary"><i class="fa-solid fa-store me-2" style="color: #2e7d32;"></i>Vendor Dashboard: <?= htmlspecialchars($vendor['store_name']) ?></h2>
    <div>
        <a href="<?= htmlspecialchars($specialLink) ?>" target="_blank" class="btn btn-outline-dark me-2">
            <i class="fa-solid fa-eye me-1"></i> View My Store
        </a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- ==================================================== -->
<!-- SPECIAL STORE LINK & QR CODE CARD                    -->
<!-- ==================================================== -->
<div class="card shadow-sm mb-4 border-0 border-start border-4" style="border-color: #b2ff81 !important;">
    <div class="card-body bg-white rounded p-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-link me-2 text-success"></i>Your Secure Store Link & QR Code</h5>
                <p class="text-muted small mb-3">Share this unguessable link or print the QR code for your physical stall or tables so customers can scan and order directly.</p>
                
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control font-monospace" value="<?= htmlspecialchars($specialLink) ?>" id="storeLinkInput" readonly>
                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('storeLinkInput').value); alert('Store link copied to clipboard!');">
                        <i class="fa-solid fa-copy me-1"></i> Copy Link
                    </button>
                </div>
            </div>
            <div class="col-lg-4 text-center border-start-lg">
                <?php 
                    // Generate QR Code URL via public QR API
                    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($specialLink);
                ?>
                <div class="p-2 bg-light d-inline-block rounded shadow-sm border">
                    <img src="<?= $qrCodeUrl ?>" alt="Store QR Code" width="120" height="120" class="img-fluid">
                </div>
                <div class="mt-2">
                    <a href="<?= $qrCodeUrl ?>" download="<?= htmlspecialchars($vendor['store_name']) ?>_QRCode.png" target="_blank" class="btn btn-sm btn-success fw-bold">
                        <i class="fa-solid fa-download me-1"></i> Download QR Code
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="vendorTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold text-dark" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu-pane" type="button" role="tab">Menu Items</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold text-dark" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab">Store Profile</button>
    </li>
</ul>

<div class="tab-content" id="vendorTabsContent">
    <!-- Menu Pane -->
    <div class="tab-pane fade show active" id="menu-pane" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-secondary">Manage Menu Items</h4>
            <a href="add_item.php" class="btn btn-main fw-bold shadow-sm" style="background-color: #b2ff81; color: #1a1a1a;">
                <i class="fa-solid fa-plus me-1"></i> Add New Item
            </a>
        </div>

        <?php if (empty($menu_items)): ?>
            <div class="alert alert-info">You haven't added any menu items yet.</div>
        <?php else: ?>
            <div class="table-responsive bg-white rounded shadow-sm p-3">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Item Name</th>
                            <th>Price / Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" width="50" height="50" class="rounded object-fit-cover" alt="">
                                </td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?>...</small>
                                </td>
                                <td>
                                    <span class="text-success fw-bold">$<?= number_format($item['price'], 2) ?></span>
                                    <br><small class="text-muted">Stock: <strong><?= $item['stock'] ?></strong></small>
                                </td>
                                <td>
                                    <?php if ($item['is_available']): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    <a href="delete_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Pane -->
    <div class="tab-pane fade" id="profile-pane" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 bg-white rounded">
                <h4 class="fw-bold mb-3">Store Profile Management</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Store Name *</label>
                        <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($vendor['store_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($vendor['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($vendor['address']) ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($vendor['contact_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Number *</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($vendor['whatsapp_number']) ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label">Store Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?= $vendor['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="closed" <?= $vendor['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-center">
                            <label class="form-label d-block">Current Logo</label>
                            <img src="../uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" width="60" height="60" class="rounded-circle object-fit-cover shadow-sm" alt="">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Change Logo</label>
                            <input type="file" name="logo" class="form-control">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-dark w-100 fw-bold shadow-sm">Save Profile Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>