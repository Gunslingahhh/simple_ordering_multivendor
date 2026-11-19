<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch metrics & vendors from database
$stmtVendors = $pdo->query("SELECT v.*, u.username FROM vendors v JOIN users u ON v.user_id = u.id ORDER BY v.id DESC");
$vendors = $stmtVendors->fetchAll();

$total_vendors = count($vendors);
$active_vendors = 0;
$paused_vendors = 0;

foreach ($vendors as $v) {
    if (($v['status'] ?? 'open') === 'open') {
        $active_vendors++;
    } else {
        $paused_vendors++;
    }
}

// Total products across all vendors
$stmtProdCount = $pdo->query("SELECT COUNT(*) FROM menu_items");
$total_products = $stmtProdCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan — Admin Platform</title>
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
        .admin-main-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .vendor-row-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }
        .vendor-row-item:last-child {
            border-bottom: none;
        }
        .vendor-row-item:hover {
            background-color: #fafafa;
        }
        .search-input-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 320px;
        }
        .search-input-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 100%;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-daftar-peniaga {
            background-color: #0b291a;
            color: #ffffff;
            border-radius: 50rem;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 10px rgba(11, 41, 26, 0.2);
            transition: background 0.2s ease;
        }
        .btn-daftar-peniaga:hover {
            background-color: #16422b;
            color: #ffffff;
        }
        .btn-pulihkan {
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
        .btn-pulihkan:hover {
            background: #f1f5f9;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    
    <!-- Include Admin Sidenav -->
    <?php include 'sidenav.php'; ?>

    <!-- Main Content -->
    <div class="dash-main">
        <!-- Topbar -->
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Admin platform</span>
                <h5 class="fw-bold mb-0 text-dark">Ringkasan</h5>
            </div>
            <div>
                <a href="#" class="btn-pulihkan" onclick="alert('Demo data restored successfully!');">
                    <i class="fa-solid fa-rotate-right" style="font-size: 0.75rem;"></i> Pulihkan data demo
                </a>
            </div>
        </div>

        <!-- Content Body -->
        <div class="dash-content-body">
            
            <!-- Metrics 4-Card Grid -->
            <div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">
                            Jumlah peniaga
                            <i class="fa-solid fa-store text-muted"></i>
                        </div>
                        <div class="metric-value"><?= $total_vendors ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">
                            Aktif
                            <i class="fa-solid fa-circle-check text-success"></i>
                        </div>
                        <div class="metric-value"><?= $active_vendors ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">
                            Tamat / dijeda
                            <i class="fa-solid fa-circle-xmark text-danger"></i>
                        </div>
                        <div class="metric-value"><?= $paused_vendors ?></div>
                    </div>
                </div>
                <div class="col">
                    <div class="metric-card">
                        <div class="metric-title">
                            Jumlah produk
                            <i class="fa-solid fa-cube text-muted"></i>
                        </div>
                        <div class="metric-value"><?= $total_products ?></div>
                    </div>
                </div>
            </div>

            <!-- Main Vendors List Card -->
            <div class="admin-main-card">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1 fs-5">Peniaga berdaftar</h4>
                        <p class="text-muted small mb-0"><?= $total_vendors ?> peniaga</p>
                    </div>
                    <div>
                        <a href="add_vendor.php" class="btn-daftar-peniaga">
                            <i class="fa-solid fa-plus"></i> Daftar peniaga
                        </a>
                    </div>
                </div>

                <!-- Search Filter Bar -->
                <div class="mb-4">
                    <div class="search-input-box">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                        <input type="text" id="adminVendorSearch" placeholder="Cari nama, slug atau bandar" onkeyup="filterAdminVendors()">
                    </div>
                </div>

                <!-- Vendor Items List -->
                <?php if (empty($vendors)): ?>
                    <div class="text-center py-5 text-muted">Tiada peniaga berdaftar ditemui.</div>
                <?php else: ?>
                    <div id="vendorListContainer">
                        <?php foreach ($vendors as $v): ?>
                            <?php 
                                $v_slug = $v['username'] ?? 'store';
                                // Count products for this vendor
                                $stmtPCount = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE vendor_id = ?");
                                $stmtPCount->execute([$v['id']]);
                                $v_prod_count = $stmtPCount->fetchColumn();
                            ?>
                            <div class="vendor-row-item">
                                <!-- Left: Logo & Info -->
                                <div class="d-flex align-items-center gap-3 overflow-hidden">
                                    <img src="../uploads/vendors/<?= htmlspecialchars($v['logo'] ?: 'default_logo.png') ?>" 
                                         alt="Logo" width="48" height="48" 
                                         class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0">
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate vendor-search-text">
                                            <?= htmlspecialchars($v['store_name']) ?> 
                                            <span class="text-muted fw-normal" style="font-size: 0.75rem;">v<?= str_pad($v['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        <div class="text-muted small text-truncate vendor-search-text">
                                            /store?vendor=<?= htmlspecialchars($v_slug) ?> &bull; <?= htmlspecialchars($v['city'] ?? 'Kuching') ?> &bull; <?= $v_prod_count ?> produk
                                        </div>
                                        <div class="text-success small fw-semibold" style="font-size: 0.75rem;">
                                            <i class="fa-solid fa-circle" style="font-size: 0.45rem;"></i> Langganan aktif &bull; 24 hari lagi
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Controls & Actions -->
                                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small text-muted">Jeda</span>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" checked title="Status aktif/jeda">
                                        </div>
                                        <span class="small fw-semibold text-success">Aktif</span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 ms-2">
                                        <button class="btn btn-sm btn-light border text-secondary" title="Salin pautan" onclick="alert('Pautan disalin!');">
                                            <i class="fa-regular fa-copy" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <a href="impersonate.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Log masuk sebagai peniaga">
                                            <i class="fa-solid fa-right-to-bracket" style="font-size: 0.75rem;"></i>
                                        </a>
                                        <a href="edit_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Sunting">
                                            <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i>
                                        </a>
                                        <a href="delete_vendor.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-light border text-danger" title="Padam" onclick="return confirm('Padam peniaga ini?');">
                                            <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <div class="text-muted text-center small pt-5">
                Kedai WA Kamek Admin Platform
            </div>

        </div>
    </div>

</div>

<!-- Search Filter Script -->
<script>
function filterAdminVendors() {
    let input = document.getElementById('adminVendorSearch').value.toLowerCase();
    let rows = document.getElementsByClassName('vendor-row-item');
    
    for (let i = 0; i < rows.length; i++) {
        let text = rows[i].innerText.toLowerCase();
        if (text.includes(input)) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>