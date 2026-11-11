<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// ----------------------------------------------------
// 1. FETCH OVERALL SYSTEM METRICS FOR REPORTING
// ----------------------------------------------------

// Total Vendors Count & Status breakdown
$stmtVendorsCount = $pdo->query("
    SELECT 
        COUNT(*) as total_vendors,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_vendors,
        SUM(CASE WHEN status = 'open' AND is_active = 1 THEN 1 ELSE 0 END) as open_vendors
    FROM vendors
");
$vendorStats = $stmtVendorsCount->fetch();

// Total Menu Items & Availability breakdown
$stmtMenuStats = $pdo->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_items,
        SUM(CASE WHEN is_available = 0 THEN 1 ELSE 0 END) as unavailable_items
    FROM menu_items
");
$menuStats = $stmtMenuStats->fetch();

// ----------------------------------------------------
// 2. FETCH VENDOR PERFORMANCE & DETAILS
// ----------------------------------------------------
$stmtVendors = $pdo->query("
    SELECT 
        v.*, 
        u.username,
        COUNT(m.id) as total_items,
        SUM(CASE WHEN m.is_available = 1 THEN 1 ELSE 0 END) as available_items,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COUNT(o.id) as total_orders
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    LEFT JOIN menu_items m ON v.id = m.vendor_id
    LEFT JOIN orders o ON v.id = o.vendor_id
    GROUP BY v.id
    ORDER BY total_revenue DESC
");
$vendors = $stmtVendors->fetchAll();

// Base URL helper
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = "$protocol://$host/simple_ordering_multivendor/index.php?token=";
?>
<?php require_once '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-secondary">
        <i class="fa-solid fa-chart-line me-2 text-warning"></i>Admin Performance Dashboard
    </h2>
    <a href="add_vendor.php" class="btn btn-main fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add New Vendor
    </a>
</div>

<!-- ==================================================== -->
<!-- SUMMARY ANALYTICS CARDS (METRICS FOR REPORTING)      -->
<!-- ==================================================== -->
<div class="row g-3 mb-4">
    <!-- Total Vendors -->
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Vendors</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= $vendorStats['total_vendors'] ?></h3>
                        <small class="text-success fw-semibold"><?= $vendorStats['active_vendors'] ?> Active Accounts</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="fa-solid fa-store fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Currently Open Stores -->
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Open Stores</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= $vendorStats['open_vendors'] ?></h3>
                        <small class="text-muted">
                            <?= $vendorStats['total_vendors'] > 0 ? round(($vendorStats['open_vendors'] / $vendorStats['total_vendors']) * 100, 1) : 0 ?>% Open Rate
                        </small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="fa-solid fa-door-open fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Listed Menu Items -->
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Menu Items</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?= $menuStats['total_items'] ?></h3>
                        <small class="text-success fw-semibold"><?= $menuStats['available_items'] ?> Available</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                        <i class="fa-solid fa-utensils fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Health / Availability Rate -->
    <div class="col-md-3">
        <div class="card shadow-sm border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Menu Health</h6>
                        <?php 
                            $availabilityRate = $menuStats['total_items'] > 0 
                                ? round(($menuStats['available_items'] / $menuStats['total_items']) * 100, 1) 
                                : 0;
                        ?>
                        <h3 class="fw-bold mb-0 text-dark"><?= $availabilityRate ?>%</h3>
                        <small class="text-muted"><?= $menuStats['unavailable_items'] ?> Items Unavailable</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                        <i class="fa-solid fa-circle-check fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- VENDOR PERFORMANCE MONITORING TABLE                  -->
<!-- ==================================================== -->
<div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i>Vendor Performance Overview</h5>
        <span class="badge bg-secondary"><?= count($vendors) ?> Vendors</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($vendors)): ?>
            <p class="text-muted text-center py-4">No vendors registered yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Logo</th>
                            <th>Store Details</th>
                            <th>WhatsApp</th>
                            <th>Total Revenue</th>
                            <th>Store Link & QR</th>
                            <th>Status</th>
                            <th>Menu Metrics</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $v): ?>
                            <?php 
                                $vendorMenuRate = $v['total_items'] > 0 
                                    ? round(($v['available_items'] / $v['total_items']) * 100) 
                                    : 0;
                                $storeLink = $baseUrl . $v['token'];
                                $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($storeLink);
                            ?>
                            <tr>
                                <td>
                                    <img src="../uploads/vendors/<?= htmlspecialchars($v['logo'] ?: 'default_logo.png') ?>" 
                                         width="45" height="45" class="rounded-circle object-fit-cover shadow-sm" alt="">
                                </td>
                                <td>
                                    <span class="fw-bold d-block text-dark"><?= htmlspecialchars($v['store_name']) ?></span>
                                    <small class="text-muted"><i class="fa-solid fa-user me-1"></i><?= htmlspecialchars($v['username']) ?></small>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $v['whatsapp_number']) ?>" 
                                       target="_blank" class="text-decoration-none text-success fw-semibold small">
                                        <i class="fa-brands fa-whatsapp me-1 fs-6"></i><?= htmlspecialchars($v['whatsapp_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">RM<?= number_format($v['total_revenue'], 2) ?></span>
                                    <br><small class="text-muted"><?= $v['total_orders'] ?> Orders Placed</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="<?= htmlspecialchars($storeLink) ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Open Menu Link">
                                            <i class="fa-solid fa-link"></i>
                                        </a>
                                        <!-- Button trigger modal for QR -->
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#qrModal<?= $v['id'] ?>" title="View QR Code">
                                            <i class="fa-solid fa-qrcode"></i>
                                        </button>
                                    </div>

                                    <!-- QR Code Modal for each vendor -->
                                    <div class="modal fade" id="qrModal<?= $v['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header bg-dark text-white py-2">
                                                    <h6 class="modal-title fw-bold"><?= htmlspecialchars($v['store_name']) ?> QR</h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center p-4">
                                                    <div class="bg-light p-3 rounded mb-3 border">
                                                        <img src="<?= $qrCodeUrl ?>" alt="QR Code" width="150" height="150" class="img-fluid">
                                                    </div>
                                                    <a href="<?= $qrCodeUrl ?>" download="<?= htmlspecialchars($v['store_name']) ?>_QR.png" target="_blank" class="btn btn-sm btn-success w-100 fw-bold">
                                                        <i class="fa-solid fa-download me-1"></i> Download QR
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($v['status'] === 'open'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-door-open me-1"></i>Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fa-solid fa-door-closed me-1"></i>Closed</span>
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
                                    <div class="d-flex align-items-center">
                                        <div class="me-2 fw-bold small text-secondary">
                                            <?= $v['total_items'] ?> Items (<?= $vendorMenuRate ?>% Avail.)
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px; width: 140px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= $vendorMenuRate ?>%" 
                                             aria-valuenow="<?= $vendorMenuRate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="edit_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Vendor">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a href="delete_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete" title="Delete Vendor">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
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