<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminPage = 'menu.php';
$adminRoleLabel = 'Panel admin';

if (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: ../vendor/dashboard.php');
    exit;
}

function resolveAdminVendorBadge(string $logoValue, string $storeName): string
{
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 2) ?: 'KD');
    $fallback = 'https://placehold.co/80x80/143326/ffffff?text=' . urlencode($code);
    $trimmed = trim($logoValue);

    if ($trimmed === '' || preg_match('#^https?://#i', $trimmed)) {
        return $trimmed !== '' ? $trimmed : $fallback;
    }

    $cleanRel = ltrim(str_replace('\\', '/', $trimmed), '/');
    $candidates = [
        '../uploads/stores/' . basename($cleanRel),
        '../uploads/' . basename($cleanRel),
        '../' . $cleanRel,
    ];

    foreach ($candidates as $relPath) {
        if (file_exists(__DIR__ . '/' . $relPath) && is_file(__DIR__ . '/' . $relPath)) {
            return $relPath;
        }
    }

    return $fallback;
}

function resolveAdminMenuThumb(string $imageValue, string $itemName): string
{
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $itemName), 0, 2) ?: 'MN');
    $fallback = 'https://placehold.co/96x96/eef2ef/143326?text=' . urlencode($code);
    $trimmed = trim($imageValue);

    if ($trimmed === '' || preg_match('#^https?://#i', $trimmed)) {
        return $trimmed !== '' ? $trimmed : $fallback;
    }

    $cleanRel = ltrim(str_replace('\\', '/', $trimmed), '/');
    $candidates = [
        '../uploads/menu/' . basename($cleanRel),
        '../uploads/' . basename($cleanRel),
        '../' . $cleanRel,
    ];

    foreach ($candidates as $relPath) {
        if (file_exists(__DIR__ . '/' . $relPath) && is_file(__DIR__ . '/' . $relPath)) {
            return $relPath;
        }
    }

    return $fallback;
}

function saveUploadedMenuImage(array $fileField, string $fallbackName): string
{
    if (empty($fileField['name']) || ($fileField['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }
    $tmpName = (string) ($fileField['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return '';
    }
    $imgInfo = @getimagesize($tmpName);
    if ($imgInfo === false) {
        return '';
    }
    $mime = (string) ($imgInfo['mime'] ?? '');
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $ext = $extMap[$mime] ?? 'jpg';
    $targetDir = __DIR__ . '/../uploads/menu';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($fallbackName));
    $slug = trim((string) $slug, '-') ?: 'menu';
    $filename = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($tmpName, $targetDir . '/' . $filename)) {
        return 'uploads/menu/' . $filename;
    }
    return '';
}

$flashSuccess = '';
$flashError = '';
$selectedVendorId = (int) ($_GET['vendor_id'] ?? ($_POST['vendor_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_menu_item') {
            $vId = (int) ($_POST['vendor_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? 'Makanan'));
            $price = max(0.00, (float) ($_POST['price'] ?? 0));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;
            $uploadedImage = saveUploadedMenuImage($_FILES['image'] ?? [], $name);
            $imageVal = $uploadedImage !== '' ? $uploadedImage : trim((string) ($_POST['image_url'] ?? ''));

            if ($vId > 0 && $name !== '') {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_items (vendor_id, name, category, price, description, image, is_available)
                    VALUES (:vendor_id, :name, :category, :price, :description, :image, :is_available)
                ");
                $stmt->execute([
                    ':vendor_id' => $vId,
                    ':name' => $name,
                    ':category' => $category !== '' ? $category : 'Makanan',
                    ':price' => $price,
                    ':description' => $description,
                    ':image' => $imageVal,
                    ':is_available' => $isAvailable,
                ]);
                $selectedVendorId = $vId;
                $flashSuccess = 'Produk menu baharu berjaya ditambah.';
            } else {
                $flashError = 'Nama produk dan peniaga wajib diisi.';
            }
        } elseif ($action === 'edit_menu_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $vId = (int) ($_POST['vendor_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? 'Makanan'));
            $price = max(0.00, (float) ($_POST['price'] ?? 0));
            $description = trim((string) ($_POST['description'] ?? ''));
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;

            if ($itemId > 0 && $name !== '') {
                $uploadedImage = saveUploadedMenuImage($_FILES['image'] ?? [], $name);
                if ($uploadedImage !== '') {
                    $stmt = $pdo->prepare("
                        UPDATE menu_items
                        SET name = :name, category = :category, price = :price,
                            description = :description, image = :image, is_available = :is_available
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':category' => $category !== '' ? $category : 'Makanan',
                        ':price' => $price,
                        ':description' => $description,
                        ':image' => $uploadedImage,
                        ':is_available' => $isAvailable,
                        ':id' => $itemId,
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE menu_items
                        SET name = :name, category = :category, price = :price,
                            description = :description, is_available = :is_available
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':category' => $category !== '' ? $category : 'Makanan',
                        ':price' => $price,
                        ':description' => $description,
                        ':is_available' => $isAvailable,
                        ':id' => $itemId,
                    ]);
                }
                if ($vId > 0) {
                    $selectedVendorId = $vId;
                }
                $flashSuccess = 'Produk menu berjaya dikemas kini.';
            } else {
                $flashError = 'Maklumat produk tidak sah.';
            }
        } elseif ($action === 'toggle_menu_availability') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $vId = (int) ($_POST['vendor_id'] ?? 0);
            $nextAvail = ((string) ($_POST['next_available'] ?? '0') === '1') ? 1 : 0;

            if ($itemId > 0) {
                $stmt = $pdo->prepare("UPDATE menu_items SET is_available = :is_available WHERE id = :id");
                $stmt->execute([
                    ':is_available' => $nextAvail,
                    ':id' => $itemId,
                ]);
                if ($vId > 0) {
                    $selectedVendorId = $vId;
                }
                $flashSuccess = $nextAvail === 1 ? 'Produk ditandakan Tersedia.' : 'Produk ditandakan Habis.';
            }
        } elseif ($action === 'delete_menu_item') {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $vId = (int) ($_POST['vendor_id'] ?? 0);

            if ($itemId > 0) {
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = :id");
                $stmt->execute([':id' => $itemId]);
                if ($vId > 0) {
                    $selectedVendorId = $vId;
                }
                $flashSuccess = 'Produk menu berjaya dipadam.';
            }
        }
    } catch (PDOException $e) {
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

$vendors = [];
$menuItems = [];
$platformName = 'Kedai WA Kamek';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $psRow = $pdo->query("SELECT platform_name FROM platform_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($psRow && !empty($psRow['platform_name'])) {
            $platformName = (string) $psRow['platform_name'];
        }

        $vendors = $pdo->query("
            SELECT id, token, store_name, logo, status, is_active
            FROM vendors
            ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        if ($selectedVendorId <= 0 && !empty($vendors)) {
            $selectedVendorId = (int) $vendors[0]['id'];
        }

        if ($selectedVendorId > 0) {
            $mStmt = $pdo->prepare("
                SELECT id, vendor_id, name, category, price, description, image, is_available
                FROM menu_items
                WHERE vendor_id = :vendor_id
                ORDER BY id ASC
            ");
            $mStmt->execute([':vendor_id' => $selectedVendorId]);
            $menuItems = $mStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan data menu.';
    }
}

$activeVendor = null;
foreach ($vendors as $vCandidate) {
    if ((int) $vCandidate['id'] === $selectedVendorId) {
        $activeVendor = $vCandidate;
        break;
    }
}
if ($activeVendor === null && !empty($vendors)) {
    $activeVendor = $vendors[0];
    $selectedVendorId = (int) $activeVendor['id'];
}

$activeVendorName = (string) ($activeVendor['store_name'] ?? 'Kedai');
$activeVendorToken = (string) ($activeVendor['token'] ?? '');
$activeStorefrontUrl = $activeVendorToken !== ''
    ? ('../index.php?token=' . urlencode($activeVendorToken))
    : '../index.php';
$productCount = count($menuItems);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — <?php echo htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="m-0 p-0" style="background-color: #f4f5ef; color: #13261d;">

    <!-- Exact Screen-Height Scrollable Wrapper (`100dvh`) -->
    <div class="container-fluid p-0 overflow-hidden" style="height: 100vh; height: 100dvh;">
        <div class="d-flex h-100">

            <!-- Admin Sidenav -->
            <?php require_once __DIR__ . '/sidenav.php'; ?>

            <!-- Independently Scrollable Main Content -->
            <main class="flex-grow-1 h-100 overflow-y-auto p-3 p-sm-4 p-lg-5" style="background-color: #f4f5ef;">
                <div class="container-fluid px-0">

                    <!-- Top Page Bar (`Admin platform` / `Menu`) -->
                    <div class="mb-4">
                        <div class="small mb-1" style="color: #6b7c73;">Admin platform</div>
                        <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                            Menu
                        </h1>
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

                    <!-- Main Two-Column Layout (`Pilih peniaga` Sidebar + `Menu untuk ...` / `Produk` Card) -->
                    <div class="row g-4 align-items-start">

                        <!-- Left Column: Pilih peniaga -->
                        <div class="col-12 col-lg-4 col-xl-3">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4" style="border-color: #e4e9e5 !important;">
                                <h2 class="h6 fw-bold mb-3" style="color: #0e1f17;">Pilih peniaga</h2>

                                <div class="mb-3">
                                    <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                                         style="border-color: #dce2da !important;">
                                        <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                                        <input type="search"
                                               id="pickerVendorSearch"
                                               class="form-control border-0 shadow-none bg-transparent p-0 small"
                                               placeholder="Cari peniaga"
                                               aria-label="Cari peniaga"
                                               style="color: #13261d;">
                                    </div>
                                </div>

                                <?php if (empty($vendors)): ?>
                                    <div class="small" style="color: #6b7c73;">Tiada peniaga dijumpai.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-1" id="vendorPickerList">
                                        <?php foreach ($vendors as $vItem): ?>
                                            <?php
                                            $vItemId = (int) $vItem['id'];
                                            $vItemName = (string) $vItem['store_name'];
                                            $isSelectedVendor = ($vItemId === $selectedVendorId);
                                            $vBadgeImg = resolveAdminVendorBadge((string) ($vItem['logo'] ?? ''), $vItemName);
                                            ?>
                                            <a href="menu.php?vendor_id=<?php echo $vItemId; ?>"
                                               data-vendor-name="<?php echo htmlspecialchars(strtolower($vItemName), ENT_QUOTES, 'UTF-8'); ?>"
                                               class="vendor-picker-item d-flex align-items-center gap-3 p-2 rounded-3 text-decoration-none small <?php echo $isSelectedVendor ? 'fw-semibold' : 'fw-medium'; ?>"
                                               style="<?php echo $isSelectedVendor
                                                   ? 'background-color: #eef2ef; color: #0e1f17;'
                                                   : 'background-color: transparent; color: #1f3329;'; ?>">
                                                <img src="<?php echo htmlspecialchars($vBadgeImg, ENT_QUOTES, 'UTF-8'); ?>"
                                                     alt="<?php echo htmlspecialchars($vItemName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                     class="rounded-circle object-fit-cover flex-shrink-0 border"
                                                     style="width: 34px; height: 34px; border-color: #e4e9e5 !important;">
                                                <span class="text-truncate"><?php echo htmlspecialchars($vItemName, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column: `Menu untuk ...` Link + `Produk` Card -->
                        <div class="col-12 col-lg-8 col-xl-9">

                            <!-- Context Header (`Menu untuk ...` + `Lihat kedai`) -->
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div class="small" style="color: #6b7c73;">
                                    Menu untuk <strong style="color: #0e1f17;"><?php echo htmlspecialchars($activeVendorName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <a href="<?php echo htmlspecialchars($activeStorefrontUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="small fw-medium text-decoration-none d-inline-flex align-items-center gap-1"
                                   style="color: #6b7c73;">
                                    <span>Lihat kedai</span>
                                    <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
                                </a>
                            </div>

                            <!-- `Produk` Card -->
                            <div class="card border rounded-4 bg-white p-3 p-sm-4" style="border-color: #e4e9e5 !important;">

                                <!-- Card Top (`Produk` + count + `+ Tambah produk`) -->
                                <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Produk</h2>
                                        <div class="small" style="color: #6b7c73;">
                                            <?php echo (int) $productCount; ?> produk
                                        </div>
                                    </div>

                                    <button type="button"
                                            class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2 align-self-start"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addMenuItemModal"
                                            style="background-color: #143326; color: #ffffff;">
                                        <i class="bi bi-plus-lg"></i>
                                        <span>Tambah produk</span>
                                    </button>
                                </div>

                                <!-- Search Input (`Cari produk`) -->
                                <div class="mb-4" style="max-width: 320px;">
                                    <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                                         style="border-color: #dce2da !important;">
                                        <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                                        <input type="search"
                                               id="menuProductSearch"
                                               class="form-control border-0 shadow-none bg-transparent p-0 small"
                                               placeholder="Cari produk"
                                               aria-label="Cari produk"
                                               style="color: #13261d;">
                                    </div>
                                </div>

                                <!-- Menu Items List -->
                                <?php if (empty($menuItems)): ?>
                                    <div class="text-center py-5 small" style="color: #6b7c73;">
                                        Tiada produk menu untuk kedai ini. Klik <strong>+ Tambah produk</strong> untuk menambah item.
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column" id="menuItemsList">
                                        <?php foreach ($menuItems as $mItem): ?>
                                            <?php
                                            $mId = (int) $mItem['id'];
                                            $mName = (string) $mItem['name'];
                                            $mCat = (string) ($mItem['category'] ?? 'Makanan');
                                            $mPrice = (float) ($mItem['price'] ?? 0);
                                            $mDesc = (string) ($mItem['description'] ?? '');
                                            $mAvail = ((int) ($mItem['is_available'] ?? 1) === 1);
                                            $nextAvail = $mAvail ? '0' : '1';
                                            $mThumb = resolveAdminMenuThumb((string) ($mItem['image'] ?? ''), $mName);
                                            ?>
                                            <div class="menu-product-row d-flex align-items-center justify-content-between gap-3 py-3 border-top"
                                                 data-search="<?php echo htmlspecialchars(strtolower($mName . ' ' . $mCat . ' ' . $mDesc), ENT_QUOTES, 'UTF-8'); ?>"
                                                 style="border-color: #eef2ef !important;">

                                                <!-- Left: Thumbnail + Product Name + Price & Category -->
                                                <div class="d-flex align-items-center gap-3 min-w-0">
                                                    <img src="<?php echo htmlspecialchars($mThumb, ENT_QUOTES, 'UTF-8'); ?>"
                                                         alt="<?php echo htmlspecialchars($mName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                         class="rounded-3 object-fit-cover flex-shrink-0 border"
                                                         style="width: 44px; height: 44px; border-color: #e4e9e5 !important;">
                                                    <div class="min-w-0">
                                                        <div class="fw-bold small text-truncate" style="color: #0e1f17;">
                                                            <?php echo htmlspecialchars($mName, ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                        <div class="small text-truncate" style="color: #6b7c73;">
                                                            <span>RM <?php echo number_format($mPrice, 2); ?></span>
                                                            <span class="mx-1">&middot;</span>
                                                            <span><?php echo htmlspecialchars($mCat, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right: Availability Badge + Switch + Edit + Delete -->
                                                <div class="d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0">
                                                    <span class="fw-medium d-inline-flex align-items-center gap-1"
                                                          style="font-size: 0.78rem; color: <?php echo $mAvail ? '#15803d' : '#dc2626'; ?>;">
                                                        <i class="bi bi-circle-fill" style="font-size: 0.42rem;"></i>
                                                        <span><?php echo $mAvail ? 'Tersedia' : 'Habis'; ?></span>
                                                    </span>

                                                    <form method="POST" action="menu.php?vendor_id=<?php echo $selectedVendorId; ?>" class="d-inline m-0">
                                                        <input type="hidden" name="action" value="toggle_menu_availability">
                                                        <input type="hidden" name="item_id" value="<?php echo $mId; ?>">
                                                        <input type="hidden" name="vendor_id" value="<?php echo $selectedVendorId; ?>">
                                                        <input type="hidden" name="next_available" value="<?php echo $nextAvail; ?>">
                                                        <div class="form-check form-switch m-0">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   role="switch"
                                                                   aria-label="Ketersediaan produk"
                                                                   <?php echo $mAvail ? 'checked' : ''; ?>
                                                                   onchange="this.form.submit()"
                                                                   style="width: 2.35em; height: 1.2em; cursor: pointer; <?php echo $mAvail ? 'background-color: #143326; border-color: #143326;' : ''; ?>">
                                                        </div>
                                                    </form>

                                                    <button type="button"
                                                            class="btn btn-sm border-0 bg-transparent p-1 edit-menu-btn"
                                                            title="Sunting produk"
                                                            aria-label="Sunting produk"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editMenuItemModal"
                                                            data-id="<?php echo $mId; ?>"
                                                            data-name="<?php echo htmlspecialchars($mName, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-category="<?php echo htmlspecialchars($mCat, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-price="<?php echo number_format($mPrice, 2, '.', ''); ?>"
                                                            data-description="<?php echo htmlspecialchars($mDesc, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-available="<?php echo $mAvail ? '1' : '0'; ?>"
                                                            style="color: #52635a;">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>

                                                    <form method="POST" action="menu.php?vendor_id=<?php echo $selectedVendorId; ?>" class="d-inline m-0"
                                                          onsubmit="return confirm('Padam produk ini?');">
                                                        <input type="hidden" name="action" value="delete_menu_item">
                                                        <input type="hidden" name="item_id" value="<?php echo $mId; ?>">
                                                        <input type="hidden" name="vendor_id" value="<?php echo $selectedVendorId; ?>">
                                                        <button type="submit"
                                                                class="btn btn-sm border-0 bg-transparent p-1"
                                                                title="Padam produk"
                                                                aria-label="Padam produk"
                                                                style="color: #52635a;">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>
            </main>

        </div>
    </div>

    <!-- Modal: Tambah Produk -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" aria-labelledby="addMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="menu.php?vendor_id=<?php echo $selectedVendorId; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_menu_item">
                    <input type="hidden" name="vendor_id" value="<?php echo $selectedVendorId; ?>">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addMenuItemModalLabel">
                            Tambah Produk — <?php echo htmlspecialchars($activeVendorName, ENT_QUOTES, 'UTF-8'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_menu_name" class="form-label small fw-semibold">Nama Produk</label>
                            <input type="text" class="form-control rounded-3" id="add_menu_name" name="name" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="add_menu_category" class="form-label small fw-semibold">Kategori</label>
                                <input type="text" class="form-control rounded-3" id="add_menu_category" name="category" value="Makanan" required>
                            </div>
                            <div class="col-6">
                                <label for="add_menu_price" class="form-label small fw-semibold">Harga (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="add_menu_price" name="price" value="5.00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_menu_desc" class="form-label small fw-semibold">Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="add_menu_desc" name="description">
                        </div>
                        <div class="mb-3">
                            <label for="add_menu_image" class="form-label small fw-semibold">Gambar Produk</label>
                            <input type="file" class="form-control rounded-3" id="add_menu_image" name="image" accept="image/*">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_menu_avail" name="is_available" value="1" checked>
                            <label class="form-check-label small" for="add_menu_avail">Tersedia</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn rounded-3 fw-semibold border-0" style="background-color: #143326; color: #ffffff;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Sunting Produk -->
    <div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="menu.php?vendor_id=<?php echo $selectedVendorId; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_menu_item">
                    <input type="hidden" name="vendor_id" value="<?php echo $selectedVendorId; ?>">
                    <input type="hidden" name="item_id" id="edit_menu_item_id" value="">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editMenuItemModalLabel">Sunting Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_menu_name" class="form-label small fw-semibold">Nama Produk</label>
                            <input type="text" class="form-control rounded-3" id="edit_menu_name" name="name" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_menu_category" class="form-label small fw-semibold">Kategori</label>
                                <input type="text" class="form-control rounded-3" id="edit_menu_category" name="category" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_menu_price" class="form-label small fw-semibold">Harga (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="edit_menu_price" name="price" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_menu_desc" class="form-label small fw-semibold">Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="edit_menu_desc" name="description">
                        </div>
                        <div class="mb-3">
                            <label for="edit_menu_image" class="form-label small fw-semibold">Tukar Gambar (Pilihan)</label>
                            <input type="file" class="form-control rounded-3" id="edit_menu_image" name="image" accept="image/*">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_menu_avail" name="is_available" value="1">
                            <label class="form-check-label small" for="edit_menu_avail">Tersedia</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn rounded-3 fw-semibold border-0" style="background-color: #143326; color: #ffffff;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const vendorSearch = document.getElementById('pickerVendorSearch');
        const vendorItems = document.querySelectorAll('.vendor-picker-item');
        if (vendorSearch && vendorItems.length > 0) {
            vendorSearch.addEventListener('input', function () {
                const q = vendorSearch.value.trim().toLowerCase();
                vendorItems.forEach(function (item) {
                    const name = item.getAttribute('data-vendor-name') || '';
                    item.classList.toggle('d-none', q !== '' && !name.includes(q));
                });
            });
        }

        const productSearch = document.getElementById('menuProductSearch');
        const productRows = document.querySelectorAll('.menu-product-row');
        if (productSearch && productRows.length > 0) {
            productSearch.addEventListener('input', function () {
                const q = productSearch.value.trim().toLowerCase();
                productRows.forEach(function (row) {
                    const hay = row.getAttribute('data-search') || '';
                    row.classList.toggle('d-none', q !== '' && !hay.includes(q));
                });
            });
        }

        document.querySelectorAll('.edit-menu-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_menu_item_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_menu_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('edit_menu_category').value = btn.getAttribute('data-category') || 'Makanan';
                document.getElementById('edit_menu_price').value = btn.getAttribute('data-price') || '0.00';
                document.getElementById('edit_menu_desc').value = btn.getAttribute('data-description') || '';
                document.getElementById('edit_menu_avail').checked = (btn.getAttribute('data-available') === '1');
            });
        });
    });
    </script>
</body>
</html>