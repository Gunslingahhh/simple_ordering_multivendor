<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
session_start();

$currentVendorPage = 'dashboard.php';
$vendorRoleLabel = 'Panel peniaga';

/**
 * Resolve menu item image filename from `menu_items.image` to a valid web path,
 * falling back to `placehold.co` when empty, `default_food.png`, or not found on disk.
 */
function resolveMenuItemImage(string $imageFilename, string $itemName): string
{
    $trimmed = trim($imageFilename);
    $fallbackText = urlencode(strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $itemName), 0, 3) ?: 'MN'));
    $placeholderUrl = 'https://placehold.co/96x96/f4f5ef/0e1f17?text=' . $fallbackText;

    if ($trimmed === '' || strtolower($trimmed) === 'default_food.png') {
        return $placeholderUrl;
    }

    if (preg_match('#^https?://#i', $trimmed)) {
        return $trimmed;
    }

    $cleanRelative = ltrim(str_replace('\\', '/', $trimmed), '/');
    $candidateWebPaths = [
        '../uploads/menu/' . basename($cleanRelative),
        '../uploads/' . basename($cleanRelative),
        '../assets/images/' . basename($cleanRelative),
        '../' . $cleanRelative,
    ];

    foreach ($candidateWebPaths as $webPath) {
        $diskPath = __DIR__ . '/' . $webPath;
        if (file_exists($diskPath) && is_file($diskPath)) {
            return $webPath;
        }
    }

    // Return the primary uploads path if running from a remote/custom mount, or placehold.co fallback
    return $placeholderUrl;
}

// Resolve active vendor ID from session or default to vendor 1 (Roti Berkaus)
$activeVendorId = (int) ($_SESSION['vendor_id'] ?? 1);
if (!empty($_SESSION['user_id']) && isset($pdo) && $pdo instanceof PDO) {
    try {
        $lookupStmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = :user_id LIMIT 1");
        $lookupStmt->execute([':user_id' => (int) $_SESSION['user_id']]);
        $foundVendor = $lookupStmt->fetch(PDO::FETCH_ASSOC);
        if ($foundVendor) {
            $activeVendorId = (int) $foundVendor['id'];
            $_SESSION['vendor_id'] = $activeVendorId;
        }
    } catch (PDOException $e) {
        // Keep resolved vendor ID fallback
    }
}

$flashSuccess = '';
$flashError = '';

// Handle POST actions (Add Item, Edit Item, Delete Item, Update Store Profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_item') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;

            if ($name === '' || $price < 0) {
                $flashError = 'Sila masukkan nama item dan harga yang sah.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_items (vendor_id, name, description, price, stock, image, is_available)
                    VALUES (:vendor_id, :name, :description, :price, :stock, 'default_food.png', :is_available)
                ");
                $stmt->execute([
                    ':vendor_id' => $activeVendorId,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':is_available' => $isAvailable,
                ]);
                $flashSuccess = 'Item menu berjaya ditambah.';
            }
        } elseif ($action === 'edit_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;

            if ($itemId > 0 && $name !== '' && $price >= 0) {
                $stmt = $pdo->prepare("
                    UPDATE menu_items
                    SET name = :name,
                        description = :description,
                        price = :price,
                        stock = :stock,
                        is_available = :is_available
                    WHERE id = :id AND vendor_id = :vendor_id
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':is_available' => $isAvailable,
                    ':id' => $itemId,
                    ':vendor_id' => $activeVendorId,
                ]);
                $flashSuccess = 'Item menu berjaya dikemas kini.';
            } else {
                $flashError = 'Maklumat kemas kini item tidak sah.';
            }
        } elseif ($action === 'delete_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if ($itemId > 0) {
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = :id AND vendor_id = :vendor_id");
                $stmt->execute([
                    ':id' => $itemId,
                    ':vendor_id' => $activeVendorId,
                ]);
                $flashSuccess = 'Item menu berjaya dipadam.';
            }
        } elseif ($action === 'update_store') {
            $storeName = trim($_POST['store_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contactNumber = trim($_POST['contact_number'] ?? '');
            $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
            $status = (($_POST['status'] ?? 'open') === 'closed') ? 'closed' : 'open';

            if ($storeName !== '' && $whatsappNumber !== '') {
                $stmt = $pdo->prepare("
                    UPDATE vendors
                    SET store_name = :store_name,
                        description = :description,
                        address = :address,
                        contact_number = :contact_number,
                        whatsapp_number = :whatsapp_number,
                        status = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':store_name' => $storeName,
                    ':description' => $description,
                    ':address' => $address,
                    ':contact_number' => $contactNumber,
                    ':whatsapp_number' => $whatsappNumber,
                    ':status' => $status,
                    ':id' => $activeVendorId,
                ]);
                $_SESSION['vendor_store_name'] = $storeName;
                $flashSuccess = 'Profil kedai berjaya disimpan.';
            } else {
                $flashError = 'Nama kedai dan nombor WhatsApp wajib diisi.';
            }
        }
    } catch (PDOException $e) {
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

// Fetch real vendor & menu items (including `image`) from `food_ordering_db`
$vendor = [
    'id' => $activeVendorId,
    'token' => '9226f954b98b2ae5bae55d8501d8d716',
    'user_id' => 2,
    'store_name' => 'Roti Berkaus',
    'description' => 'Bukan Steam Bun Tapi Roti Berkaus',
    'address' => 'Bumi',
    'contact_number' => '01298765432',
    'whatsapp_number' => '601298765432',
    'logo' => '29e2efe9fba27398da8af6ea2a98ba7d.jpg',
    'status' => 'open',
    'is_active' => 1,
];

$menuItems = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vStmt = $pdo->prepare("SELECT * FROM vendors WHERE id = :id LIMIT 1");
        $vStmt->execute([':id' => $activeVendorId]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $vendor = array_merge($vendor, $vRow);
            $_SESSION['vendor_store_name'] = $vendor['store_name'];
        }

        $mStmt = $pdo->prepare("SELECT id, vendor_id, name, description, price, stock, image, is_available FROM menu_items WHERE vendor_id = :vendor_id ORDER BY id ASC");
        $mStmt->execute([':vendor_id' => $activeVendorId]);
        $menuItems = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan data kedai atau menu.';
    }
}

$totalProducts = count($menuItems);
$availableCount = 0;
$soldOutCount = 0;

foreach ($menuItems as $item) {
    $isAvail = !empty($item['is_available']) && ((int) ($item['stock'] ?? 0) > 0);
    if ($isAvail) {
        $availableCount++;
    } else {
        $soldOutCount++;
    }
}

$vendorStoreName = $vendor['store_name'];
$storeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vendorStoreName), 0, 2) ?: 'RB');
$vendorLogoUrl = 'https://placehold.co/64x64/14261d/9df557?text=' . urlencode($storeCode);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/vendor/dashboard.php')), '/\\');
$storeShareUrl = $scheme . '://' . $host . ($baseDir !== '' ? $baseDir : '') . '/index.php?token=' . urlencode($vendor['token']);
$qrCodePlaceholderUrl = 'https://placehold.co/400x400/ffffff/0e1f17?text=' . urlencode('QR: ' . $vendor['token']);

$activeTab = (($_POST['action'] ?? '') === 'update_store') ? 'profile' : 'menu';
$isOpen = (($vendor['status'] ?? 'open') === 'open');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan: <?php echo htmlspecialchars($vendor['store_name'], ENT_QUOTES, 'UTF-8'); ?> — Kedai WA Kamek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="m-0 p-0" style="background-color: #f4f5ef; color: #13261d;">

    <!-- Exact Screen-Height Scrollable Wrapper (100dvh) -->
    <div class="container-fluid p-0 overflow-hidden" style="height: 100vh; height: 100dvh;">
        <div class="d-flex h-100">

            <!-- Static Screen-Height Sidenav -->
            <?php require_once __DIR__ . '/sidenav.php'; ?>

            <!-- Independently Scrollable Main Content -->
            <main class="flex-grow-1 h-100 overflow-y-auto p-3 p-sm-4 p-lg-5" style="background-color: #f4f5ef;">
                <div class="container-fluid px-0">

                    <!-- Top Header Row -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel peniaga</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Ringkasan: <?php echo htmlspecialchars($vendor['store_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h1>
                        </div>
                        <div>
                            <a href="../index.php?token=<?php echo urlencode($vendor['token']); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                               style="border-color: #dce2da !important; color: #0e1f17;">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>Lihat kedai</span>
                            </a>
                        </div>
                    </div>

                    <!-- Feedback Alerts -->
                    <?php if ($flashSuccess !== ''): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 py-2 px-3 small mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashError !== ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2 px-3 small mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 4 Summary Metric Cards -->
                    <div class="row g-3 mb-4">

                        <!-- Card 1: Status kedai -->
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white h-100 p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-medium" style="color: #6b7c73;">Status kedai</span>
                                    <i class="bi <?php echo $isOpen ? 'bi-power text-success' : 'bi-x-circle text-danger'; ?>"></i>
                                </div>
                                <div class="h4 fw-bold mb-0 <?php echo $isOpen ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $isOpen ? 'Open' : 'Closed'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Jumlah produk -->
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white h-100 p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-medium" style="color: #6b7c73;">Jumlah produk</span>
                                    <i class="bi bi-box-seam-fill" style="color: #26382f;"></i>
                                </div>
                                <div class="h4 fw-bold mb-0" style="color: #0e1f17;">
                                    <?php echo (int) $totalProducts; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Tersedia -->
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white h-100 p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-medium" style="color: #6b7c73;">Tersedia</span>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                </div>
                                <div class="h4 fw-bold mb-0" style="color: #0e1f17;">
                                    <?php echo (int) $availableCount; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Habis -->
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white h-100 p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-medium" style="color: #6b7c73;">Habis</span>
                                    <i class="bi bi-x-circle-fill text-danger"></i>
                                </div>
                                <div class="h4 fw-bold mb-0" style="color: #0e1f17;">
                                    <?php echo (int) $soldOutCount; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Store Link & QR Code Card -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                        <h2 class="h6 fw-bold mb-1" style="color: #0e1f17;">
                            <i class="bi bi-link-45deg me-1"></i>
                            Pautan kedai &amp; QR Kod khas
                        </h2>
                        <p class="small mb-3" style="color: #6b7c73;">
                            Kongsi pautan ini atau muat turun QR kod untuk diletakkan di meja atau gerai fizikal anda.
                        </p>

                        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3">
                            <div class="input-group flex-grow-1" style="max-width: 780px;">
                                <input
                                    type="text"
                                    id="storeShareLinkInput"
                                    class="form-control bg-white border rounded-3 py-2 px-3 small"
                                    value="<?php echo htmlspecialchars($storeShareUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                    readonly
                                    aria-label="Pautan kedai khas"
                                    style="border-color: #dce2da !important; color: #1f3329;"
                                >
                                <button
                                    type="button"
                                    id="copyStoreLinkBtn"
                                    class="btn btn-sm border rounded-3 ms-2 px-3 d-inline-flex align-items-center justify-content-center"
                                    title="Salin pautan"
                                    aria-label="Salin pautan"
                                    style="background-color: #ffffff; border-color: #dce2da !important; color: #0e1f17;">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </div>

                            <a href="<?php echo htmlspecialchars($qrCodePlaceholderUrl, ENT_QUOTES, 'UTF-8'); ?>"
                               download="qr-<?php echo htmlspecialchars($vendor['token'], ENT_QUOTES, 'UTF-8'); ?>.png"
                               class="btn btn-dark rounded-3 px-3 py-2 small fw-semibold d-inline-flex align-items-center justify-content-center gap-2 flex-shrink-0"
                               style="background-color: #0e1f17; border-color: #0e1f17;">
                                <i class="bi bi-download"></i>
                                <span>Muat Turun QR Kod</span>
                            </a>
                        </div>
                    </div>

                    <!-- Sub-Navigation Tabs: Menu Items | Store Profile -->
                    <ul class="nav nav-tabs border-bottom mb-4" id="vendorDashboardTabs" role="tablist" style="border-color: #dce2da !important;">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link <?php echo ($activeTab === 'menu') ? 'active fw-semibold' : 'fw-medium'; ?> border-0 rounded-top-3 px-3 py-2 d-inline-flex align-items-center gap-2"
                                id="tab-menu-items"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-menu-items"
                                type="button"
                                role="tab"
                                aria-controls="pane-menu-items"
                                aria-selected="<?php echo ($activeTab === 'menu') ? 'true' : 'false'; ?>"
                                style="<?php echo ($activeTab === 'menu')
                                    ? 'background-color: #ffffff; color: #0e1f17; border-bottom: 2px solid #0e1f17 !important;'
                                    : 'background-color: transparent; color: #6b7c73;'; ?>">
                                <i class="bi bi-cup-hot"></i>
                                <span>Menu Items</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link <?php echo ($activeTab === 'profile') ? 'active fw-semibold' : 'fw-medium'; ?> border-0 rounded-top-3 px-3 py-2 d-inline-flex align-items-center gap-2"
                                id="tab-store-profile"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-store-profile"
                                type="button"
                                role="tab"
                                aria-controls="pane-store-profile"
                                aria-selected="<?php echo ($activeTab === 'profile') ? 'true' : 'false'; ?>"
                                style="<?php echo ($activeTab === 'profile')
                                    ? 'background-color: #ffffff; color: #0e1f17; border-bottom: 2px solid #0e1f17 !important;'
                                    : 'background-color: transparent; color: #6b7c73;'; ?>">
                                <i class="bi bi-gear"></i>
                                <span>Store Profile</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Panes -->
                    <div class="tab-content" id="vendorDashboardTabContent">

                        <!-- Tab 1: Menu Items -->
                        <div
                            class="tab-pane fade <?php echo ($activeTab === 'menu') ? 'show active' : ''; ?>"
                            id="pane-menu-items"
                            role="tabpanel"
                            aria-labelledby="tab-menu-items">

                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                                <h2 class="h5 fw-bold mb-0" style="color: #0e1f17;">Manage Menu Items</h2>
                                <button
                                    type="button"
                                    class="btn btn-dark btn-sm rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 align-self-start align-self-sm-auto"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addMenuItemModal"
                                    style="background-color: #0e1f17; border-color: #0e1f17;">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Add New Item</span>
                                </button>
                            </div>

                            <div class="card border rounded-4 bg-white overflow-hidden" style="border-color: #e4e9e5 !important;">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead style="background-color: #fcfdfb;">
                                            <tr class="small" style="color: #6b7c73; border-color: #e6ebe7;">
                                                <th scope="col" class="py-3 ps-4">Image</th>
                                                <th scope="col" class="py-3">Item Name</th>
                                                <th scope="col" class="py-3">Price / Stock</th>
                                                <th scope="col" class="py-3">Status</th>
                                                <th scope="col" class="py-3 pe-4 text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($menuItems)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 small" style="color: #6b7c73;">
                                                        Tiada item menu ditemui. Klik <strong>+ Add New Item</strong> untuk menambah produk.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($menuItems as $item): ?>
                                                    <?php
                                                    $itemAvail = !empty($item['is_available']) && ((int) ($item['stock'] ?? 0) > 0);
                                                    $itemImageSrc = resolveMenuItemImage((string) ($item['image'] ?? ''), (string) ($item['name'] ?? ''));
                                                    ?>
                                                    <tr style="border-color: #eef2ef;">
                                                        <td class="ps-4 py-3" style="width: 76px;">
                                                            <img
                                                                src="<?php echo htmlspecialchars($itemImageSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                                                alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                class="rounded-3 object-fit-cover border"
                                                                style="width: 44px; height: 44px; border-color: #e4e9e5 !important;"
                                                            >
                                                        </td>
                                                        <td class="py-3">
                                                            <div class="fw-bold small" style="color: #0e1f17;">
                                                                <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                            <div class="small text-truncate" style="max-width: 240px; color: #6b7c73;">
                                                                <?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        </td>
                                                        <td class="py-3">
                                                            <div class="fw-bold small text-success">
                                                                RM <?php echo number_format((float) $item['price'], 2); ?>
                                                            </div>
                                                            <div class="small" style="color: #6b7c73;">
                                                                Stock: <strong class="text-dark"><?php echo (int) ($item['stock'] ?? 0); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td class="py-3">
                                                            <span class="badge rounded-pill px-3 py-1 fw-semibold"
                                                                  style="<?php echo $itemAvail
                                                                      ? 'background-color: #dcfce7; color: #15803d;'
                                                                      : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                                                <?php echo $itemAvail ? 'Available' : 'Unavailable'; ?>
                                                            </span>
                                                        </td>
                                                        <td class="pe-4 py-3 text-end">
                                                            <div class="d-inline-flex align-items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-link text-primary p-1 edit-menu-btn"
                                                                    title="Sunting item"
                                                                    aria-label="Sunting item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editMenuItemModal"
                                                                    data-id="<?php echo (int) $item['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-description="<?php echo htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-price="<?php echo htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-stock="<?php echo (int) ($item['stock'] ?? 0); ?>"
                                                                    data-available="<?php echo !empty($item['is_available']) ? '1' : '0'; ?>">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </button>

                                                                <form method="POST" action="dashboard.php" class="d-inline m-0"
                                                                      onsubmit="return confirm('Padam item ini daripada menu?');">
                                                                    <input type="hidden" name="action" value="delete_item">
                                                                    <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>">
                                                                    <button
                                                                        type="submit"
                                                                        class="btn btn-sm btn-link text-danger p-1"
                                                                        title="Padam item"
                                                                        aria-label="Padam item">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <!-- Tab 2: Store Profile -->
                        <div
                            class="tab-pane fade <?php echo ($activeTab === 'profile') ? 'show active' : ''; ?>"
                            id="pane-store-profile"
                            role="tabpanel"
                            aria-labelledby="tab-store-profile">

                            <div class="card border rounded-4 bg-white p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Store Profile</h2>
                                <p class="small mb-4" style="color: #6b7c73;">
                                    Kemas kini maklumat kedai, nombor WhatsApp pesanan, dan status operasi kedai.
                                </p>

                                <form method="POST" action="dashboard.php" class="row g-3">
                                    <input type="hidden" name="action" value="update_store">

                                    <div class="col-12 col-md-6">
                                        <label for="store_name" class="form-label small fw-semibold">Nama Kedai</label>
                                        <input type="text" class="form-control rounded-3" id="store_name" name="store_name"
                                               value="<?php echo htmlspecialchars($vendor['store_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="status" class="form-label small fw-semibold">Status Operasi</label>
                                        <select class="form-select rounded-3" id="status" name="status">
                                            <option value="open" <?php echo ($vendor['status'] === 'open') ? 'selected' : ''; ?>>Open (Dibuka)</option>
                                            <option value="closed" <?php echo ($vendor['status'] === 'closed') ? 'selected' : ''; ?>>Closed (Ditutup)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="whatsapp_number" class="form-label small fw-semibold">Nombor WhatsApp</label>
                                        <input type="text" class="form-control rounded-3" id="whatsapp_number" name="whatsapp_number"
                                               value="<?php echo htmlspecialchars($vendor['whatsapp_number'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="contact_number" class="form-label small fw-semibold">Nombor Hubungi</label>
                                        <input type="text" class="form-control rounded-3" id="contact_number" name="contact_number"
                                               value="<?php echo htmlspecialchars($vendor['contact_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label for="address" class="form-label small fw-semibold">Alamat / Lokasi</label>
                                        <input type="text" class="form-control rounded-3" id="address" name="address"
                                               value="<?php echo htmlspecialchars($vendor['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>

                                    <div class="col-12">
                                        <label for="description" class="form-label small fw-semibold">Penerangan Kedai</label>
                                        <textarea class="form-control rounded-3" id="description" name="description" rows="2"><?php echo htmlspecialchars($vendor['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>

                                    <div class="col-12 pt-2">
                                        <button type="submit" class="btn btn-dark rounded-3 px-4 py-2 fw-semibold"
                                                style="background-color: #0e1f17; border-color: #0e1f17;">
                                            Simpan Profil Kedai
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>

                    </div>

                    <!-- Footer Note -->
                    <footer class="text-center small py-4 mt-4" style="color: #6b7c73;">
                        Kedai WA Kamek Vendor Panel
                    </footer>

                </div>
            </main>

        </div>
    </div>

    <!-- Modal: Add New Menu Item -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="add_item">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addMenuItemModalLabel">Add New Menu Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_name" class="form-label small fw-semibold">Item Name</label>
                            <input type="text" class="form-control rounded-3" id="add_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_description" class="form-label small fw-semibold">Description</label>
                            <textarea class="form-control rounded-3" id="add_description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="add_price" class="form-label small fw-semibold">Price (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="add_price" name="price" required>
                            </div>
                            <div class="col-6">
                                <label for="add_stock" class="form-label small fw-semibold">Stock</label>
                                <input type="number" min="0" class="form-control rounded-3" id="add_stock" name="stock" value="10" required>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_is_available" name="is_available" value="1" checked>
                            <label class="form-check-label small" for="add_is_available">Available for ordering</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark rounded-3 fw-semibold" style="background-color: #0e1f17;">Simpan Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Menu Item -->
    <div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="edit_item">
                    <input type="hidden" name="item_id" id="edit_item_id" value="">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editMenuItemModalLabel">Edit Menu Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label small fw-semibold">Item Name</label>
                            <input type="text" class="form-control rounded-3" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label small fw-semibold">Description</label>
                            <textarea class="form-control rounded-3" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_price" class="form-label small fw-semibold">Price (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="edit_price" name="price" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_stock" class="form-label small fw-semibold">Stock</label>
                                <input type="number" min="0" class="form-control rounded-3" id="edit_stock" name="stock" required>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available" value="1">
                            <label class="form-check-label small" for="edit_is_available">Available for ordering</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-dark rounded-3 fw-semibold" style="background-color: #0e1f17;">Kemas Kini</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyBtn = document.getElementById('copyStoreLinkBtn');
        const linkInput = document.getElementById('storeShareLinkInput');

        if (copyBtn && linkInput) {
            copyBtn.addEventListener('click', function () {
                linkInput.select();
                linkInput.setSelectionRange(0, 99999);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(linkInput.value);
                } else {
                    document.execCommand('copy');
                }
                const icon = copyBtn.querySelector('i');
                if (icon) {
                    icon.className = 'bi bi-check2 text-success';
                    setTimeout(function () {
                        icon.className = 'bi bi-copy';
                    }, 1500);
                }
            });
        }

        document.querySelectorAll('.edit-menu-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_item_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
                document.getElementById('edit_price').value = btn.getAttribute('data-price') || '0.00';
                document.getElementById('edit_stock').value = btn.getAttribute('data-stock') || '0';
                document.getElementById('edit_is_available').checked = (btn.getAttribute('data-available') === '1');
            });
        });
    });
    </script>
</body>
</html>