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

$total_products = count($menu_items);
$available_count = 0;
$out_of_stock_count = 0;
foreach ($menu_items as $item) {
    if ($item['is_available']) {
        $available_count++;
    } else {
        $out_of_stock_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Peniaga — Kedai WA Kamek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #0b291a;
            --accent-lime: #b2ff81;
            --bg-cream: #f3f4ee;
        }
        body {
            background-color: var(--bg-cream);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #1a1a1a;
        }
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .dash-sidebar {
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar-brand-area {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .sidebar-menu {
            padding: 1rem;
            list-style: none;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1rem;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        .sidebar-menu li a:hover {
            background-color: #f8fafc;
            color: #0b291a;
        }
        .sidebar-menu li a.active {
            background-color: #eaf3ed;
            color: #0b291a;
        }
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
        }
        .vendor-profile-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        /* Main Content Area */
        .dash-main {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            width: calc(100% - 260px);
        }
        .dash-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dash-content-body {
            padding: 2rem 2.5rem;
            width: 100%;
        }
        /* Cards */
        .metric-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            height: 100%;
        }
        .metric-title {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .metric-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0b291a;
            margin-bottom: 0;
        }
        .dash-card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .store-link-input-group {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: monospace;
            font-size: 0.9rem;
            color: #334155;
        }
        .btn-lihat-kedai {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0b291a;
            border-radius: 0.5rem;
            padding: 0.4rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-lihat-kedai:hover {
            background: #f1f5f9;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #0b291a;
            background: transparent;
            border-bottom-color: #0b291a;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    
    <!-- Include Sidenav Modular File -->
    <?php include 'sidenav.php'; ?>

    <!-- Main Content -->
    <div class="dash-main">
        <!-- Topbar -->
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Panel peniaga</span>
                <h5 class="fw-bold mb-0 text-dark">Ringkasan: <?= htmlspecialchars($vendor['store_name']) ?></h5>
            </div>
            <div>
                <a href="<?= htmlspecialchars($specialLink) ?>" target="_blank" class="btn-lihat-kedai">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i> Lihat kedai
                </a>
            </div>
        </div>

        <!-- Content Body -->
        <div class="dash-content-body">
            
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <!-- Metrics Top Grid -->
            <div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">Status kedai <i class="fa-solid fa-power-off text-success"></i></div>
                        <div class="metric-value text-success text-capitalize"><?= htmlspecialchars($vendor['status']) ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">Jumlah produk <i class="fa-solid fa-cube text-muted"></i></div>
                        <div class="metric-value"><?= $total_products ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">Tersedia <i class="fa-solid fa-circle-check text-success"></i></div>
                        <div class="metric-value"><?= $available_count ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">Habis <i class="fa-solid fa-circle-xmark text-danger"></i></div>
                        <div class="metric-value"><?= $out_of_stock_count ?></div>
                    </div>
                </div>
            </div>

            <!-- Store Link Sharing Box -->
            <div class="dash-card-box">
                <div class="fw-bold mb-1"><i class="fa-solid fa-link me-2 text-success"></i>Pautan kedai & QR Kod khas</div>
                <div class="text-muted small mb-3">Kongsi pautan ini atau muat turun QR kod untuk diletakkan di meja atau gerai fizikal anda.</div>
                
                <div class="row align-items-center">
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <div class="store-link-input-group mb-2">
                            <span class="text-truncate"><?= htmlspecialchars($specialLink) ?></span>
                            <button class="btn btn-sm btn-light border ms-2 flex-shrink-0" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($specialLink) ?>'); alert('Store link copied to clipboard!');" title="Salin pautan">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end text-center">
                        <?php $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($specialLink); ?>
                        <a href="<?= $qrCodeUrl ?>" download="<?= htmlspecialchars($vendor['store_name']) ?>_QRCode.png" target="_blank" class="btn btn-sm btn-success fw-bold px-3 py-2" style="background-color: #0b291a; border-color: #0b291a;">
                            <i class="fa-solid fa-download me-1"></i> Muat Turun QR Kod
                        </a>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="vendorTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu-pane" type="button" role="tab"><i class="fa-solid fa-utensils me-1"></i> Menu Items</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-pane" type="button" role="tab"><i class="fa-solid fa-gear me-1"></i> Store Profile</button>
                </li>
            </ul>

            <div class="tab-content" id="vendorTabsContent">
                <!-- Menu Pane -->
                <div class="tab-pane fade show active" id="menu-pane" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark fs-5 mb-0">Manage Menu Items</h4>
                        <a href="add_item.php" class="btn btn-success btn-sm fw-bold shadow-sm px-3" style="background-color: #0b291a; border-color: #0b291a;">
                            <i class="fa-solid fa-plus me-1"></i> Add New Item
                        </a>
                    </div>

                    <?php if (empty($menu_items)): ?>
                        <div class="alert alert-info">You haven't added any menu items yet.</div>
                    <?php else: ?>
                        <div class="table-responsive bg-white rounded-3 shadow-sm border p-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
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
                                                <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" width="45" height="45" class="rounded object-fit-cover shadow-sm" alt="">
                                            </td>
                                            <td class="fw-semibold">
                                                <?= htmlspecialchars($item['name']) ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 40)) ?>...</small>
                                            </td>
                                            <td>
                                                <span class="text-success fw-bold">$<?= number_format($item['price'], 2) ?></span>
                                                <br><small class="text-muted">Stock: <strong><?= $item['stock'] ?></strong></small>
                                            </td>
                                            <td>
                                                <?php if ($item['is_available']): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary border-0"><i class="fa-solid fa-pen"></i></a>
                                                <a href="delete_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger border-0 btn-delete"><i class="fa-solid fa-trash"></i></a>
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
                    <div class="card shadow-sm border">
                        <div class="card-body p-4 bg-white rounded-3">
                            <h4 class="fw-bold fs-5 mb-3">Store Profile Management</h4>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Store Name *</label>
                                    <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($vendor['store_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($vendor['description']) ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($vendor['address']) ?></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Number</label>
                                        <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($vendor['contact_number']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">WhatsApp Number *</label>
                                        <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($vendor['whatsapp_number']) ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-4 align-items-center">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Store Status</label>
                                        <select name="status" class="form-select">
                                            <option value="open" <?= $vendor['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                            <option value="closed" <?= $vendor['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <label class="form-label small fw-bold d-block">Current Logo</label>
                                        <img src="../uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" width="55" height="55" class="rounded-circle object-fit-cover shadow-sm border" alt="">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Change Logo</label>
                                        <input type="file" name="logo" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-dark w-100 fw-bold shadow-sm py-2" style="background-color: #0b291a;">Save Profile Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-muted text-center small pt-5">
                Kedai WA Kamek Vendor Panel
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>