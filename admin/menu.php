<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch all vendors
$stmtVendors = $pdo->query("SELECT * FROM vendors ORDER BY store_name ASC");
$vendors = $stmtVendors->fetchAll();

if (empty($vendors)) {
    echo '<div class="alert alert-danger">Tiada peniaga dijumpai.</div>';
    exit;
}

// Determine selected vendor (default to first vendor if not specified)
$selected_vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : $vendors[0]['id'];

// Get selected vendor details
$stmtCurr = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmtCurr->execute([$selected_vendor_id]);
$current_vendor = $stmtCurr->fetch();

if (!$current_vendor) {
    $current_vendor = $vendors[0];
    $selected_vendor_id = $current_vendor['id'];
}

// Fetch menu items for the selected vendor
$stmtMenu = $pdo->prepare("SELECT * FROM menu_items WHERE vendor_id = ? ORDER BY name ASC");
$stmtMenu->execute([$selected_vendor_id]);
$menu_items = $stmtMenu->fetchAll();
$total_products = count($menu_items);

$specialLink = "http://" . $_SERVER['HTTP_HOST'] . "/simple_ordering_multivendor/index.php?token=" . $current_vendor['token'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — Admin Platform</title>
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
        /* Layout Grid for Menu Management */
        .menu-layout-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        .vendor-select-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .vendor-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 0.85rem;
            transition: background 0.15s ease;
            margin-bottom: 0.25rem;
        }
        .vendor-list-item:hover {
            background-color: #f8fafc;
            color: #0b291a;
        }
        .vendor-list-item.active {
            background-color: #eaf3ed;
            color: #0b291a;
        }
        .menu-main-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .search-input-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .search-input-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 0.9rem;
            width: 100%;
        }
        .product-row-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }
        .product-row-item:last-child {
            border-bottom: none;
        }
        .product-row-item:hover {
            background-color: #fafafa;
        }
        .badge-tersedia {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.3rem 0.75rem;
            border-radius: 50rem;
        }
        .badge-habis {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.3rem 0.75rem;
            border-radius: 50rem;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-tambah-produk {
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
        .btn-tambah-produk:hover {
            background-color: #16422b;
            color: #ffffff;
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
                <h5 class="fw-bold mb-0 text-dark">Menu</h5>
            </div>
            <div>
                <a href="<?= htmlspecialchars($specialLink) ?>" target="_blank" class="btn-lihat-kedai">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i> Lihat kedai
                </a>
            </div>
        </div>

        <!-- Content Body -->
        <div class="dash-content-body">
            
            <div class="menu-layout-grid">
                
                <!-- Left Sidebar: Select Vendor List -->
                <div class="vendor-select-card">
                    <div class="fw-bold text-dark mb-3" style="font-size: 0.9rem;">Pilih peniaga</div>
                    <div class="search-input-box mb-3">
                        <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                        <input type="text" id="vendorSearchInput" placeholder="Cari peniaga" onkeyup="filterVendorList()">
                    </div>

                    <div class="vendor-list-container" id="vendorListContainer" style="max-height: 520px; overflow-y: auto;">
                        <?php foreach ($vendors as $v): ?>
                            <a href="menu.php?vendor_id=<?= $v['id'] ?>" class="vendor-list-item <?= $v['id'] == $selected_vendor_id ? 'active' : '' ?>">
                                <img src="../uploads/vendors/<?= htmlspecialchars($v['logo'] ?: 'default_logo.png') ?>" width="32" height="32" class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0" alt="">
                                <span class="text-truncate vendor-name-text"><?= htmlspecialchars($v['store_name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Main Area: Products List for Selected Vendor -->
                <div class="menu-main-card">
                    <div class="text-muted small mb-1">Menu untuk <strong class="text-dark"><?= htmlspecialchars($current_vendor['store_name']) ?></strong></div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 mt-2">
                        <div>
                            <h4 class="fw-bold text-dark mb-1 fs-5">Produk</h4>
                            <p class="text-muted small mb-0"><?= $total_products ?> produk</p>
                        </div>
                        <div>
                            <a href="../vendor/add_item.php?vendor_id=<?= $selected_vendor_id ?>" class="btn-tambah-produk">
                                <i class="fa-solid fa-plus"></i> Tambah produk
                            </a>
                        </div>
                    </div>

                    <!-- Search Filter Bar -->
                    <div class="mb-4">
                        <div class="search-input-box">
                            <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                            <input type="text" id="productSearch" placeholder="Cari produk..." onkeyup="filterProducts()">
                        </div>
                    </div>

                    <!-- Product Items List -->
                    <?php if (empty($menu_items)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-cube fs-1 opacity-50 mb-2"></i>
                            <p>Tiada produk dijumpai untuk peniaga ini.</p>
                        </div>
                    <?php else: ?>
                        <div id="productList">
                            <?php foreach ($menu_items as $item): ?>
                                <div class="product-row-item">
                                    <div class="d-flex align-items-center gap-3 overflow-hidden">
                                        <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" 
                                             alt="Product" width="50" height="50" 
                                             class="rounded-3 object-fit-cover border shadow-sm flex-shrink-0">
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-dark text-truncate product-name"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-success fw-semibold small">
                                                RM <?= number_format($item['price'], 2) ?> 
                                                <span class="text-muted fw-normal">&bull; <?= htmlspecialchars($item['category'] ?? 'Makanan') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                        <?php if ($item['is_available']): ?>
                                            <span class="badge-tersedia"><i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge-habis"><i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> Habis</span>
                                        <?php endif; ?>

                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" <?= $item['is_available'] ? 'checked' : '' ?> title="Tukar status">
                                        </div>

                                        <div class="d-flex align-items-center gap-2 ms-2">
                                            <a href="../vendor/edit_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Sunting">
                                                <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i>
                                            </a>
                                            <a href="../vendor/delete_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-light border text-danger" title="Padam" onclick="return confirm('Padam produk ini?');">
                                                <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <div class="text-muted text-center small pt-5">
                Prototip: data dan PIN disimpan dalam localStorage pelayar ini dan tidak selamat untuk produksi.
            </div>

        </div>
    </div>

</div>

<!-- Search Filtering Scripts -->
<script>
function filterVendorList() {
    let input = document.getElementById('vendorSearchInput').value.toLowerCase();
    let items = document.getElementsByClassName('vendor-list-item');
    
    for (let i = 0; i < items.length; i++) {
        let name = items[i].querySelector('.vendor-name-text').innerText.toLowerCase();
        if (name.includes(input)) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}

function filterProducts() {
    let input = document.getElementById('productSearch').value.toLowerCase();
    let rows = document.getElementsByClassName('product-row-item');
    
    for (let i = 0; i < rows.length; i++) {
        let name = rows[i].querySelector('.product-name').innerText.toLowerCase();
        if (name.includes(input)) {
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