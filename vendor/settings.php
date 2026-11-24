<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentVendorPage = 'settings.php';
$vendorRoleLabel = 'Panel peniaga';

// Resolve active vendor ID
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
        // Keep fallback vendor ID
    }
}

$flashSuccess = '';
$flashError = '';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendor_store_settings (
                vendor_id INT PRIMARY KEY,
                category VARCHAR(100) NOT NULL DEFAULT 'Food & Beverages',
                title_size VARCHAR(20) NOT NULL DEFAULT 'medium',
                tagline VARCHAR(255) NOT NULL DEFAULT '',
                operating_hours VARCHAR(100) NOT NULL DEFAULT '7:00 am - 2:00 pm',
                city VARCHAR(100) NOT NULL DEFAULT '',
                maps_url VARCHAR(255) NOT NULL DEFAULT '',
                banner_image VARCHAR(255) NOT NULL DEFAULT '',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    } catch (PDOException $e) {
        // Ignore DDL error if permissions restrict table creation
    }
}

// Fetch existing vendor row from `food_ordering_db.vendors`
$vendor = [
    'id' => $activeVendorId,
    'token' => '9226f954b98b2ae5bae55d8501d8d716',
    'user_id' => 2,
    'store_name' => 'Roti Berkaus',
    'description' => 'Bukan Steam Bun Tapi Roti Berkaus',
    'address' => 'Bumi',
    'contact_number' => '01298765432',
    'whatsapp_number' => '601298765432',
    'logo' => '',
    'status' => 'open',
    'is_active' => 1,
];

$extraSettings = [
    'category' => 'Food & Beverages',
    'title_size' => 'medium',
    'tagline' => '',
    'operating_hours' => '7:00 am - 2:00 pm',
    'city' => '',
    'maps_url' => '',
    'banner_image' => '',
];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vStmt = $pdo->prepare("SELECT * FROM vendors WHERE id = :id LIMIT 1");
        $vStmt->execute([':id' => $activeVendorId]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $vendor = array_merge($vendor, $vRow);
        }

        $eStmt = $pdo->prepare("
            SELECT category, title_size, tagline, operating_hours, city, maps_url, banner_image
            FROM vendor_store_settings
            WHERE vendor_id = :vendor_id
            LIMIT 1
        ");
        $eStmt->execute([':vendor_id' => $activeVendorId]);
        $eRow = $eStmt->fetch(PDO::FETCH_ASSOC);
        if ($eRow) {
            $extraSettings = array_merge($extraSettings, $eRow);
        }
    } catch (PDOException $e) {
        // Keep defaults
    }
}

// Handle POST submission (`Simpan perubahan`)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_store_settings') {
    $storeName = trim((string) ($_POST['store_name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? 'Food & Beverages'));
    $titleSize = in_array(($_POST['title_size'] ?? 'medium'), ['small', 'medium', 'large'], true)
        ? (string) $_POST['title_size']
        : 'medium';
    $tagline = trim((string) ($_POST['tagline'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $whatsappNumber = trim((string) ($_POST['whatsapp_number'] ?? ''));
    $operatingHours = trim((string) ($_POST['operating_hours'] ?? '7:00 am - 2:00 pm'));
    $address = trim((string) ($_POST['address'] ?? ''));
    $city = trim((string) ($_POST['city'] ?? ''));
    $mapsUrl = trim((string) ($_POST['maps_url'] ?? ''));

    $logoValue = trim((string) ($_POST['current_logo'] ?? ($vendor['logo'] ?? '')));
    $bannerValue = trim((string) ($_POST['current_banner'] ?? ($extraSettings['banner_image'] ?? '')));

    if (!empty($_POST['remove_logo'])) {
        $logoValue = '__removed__';
    }
    if (!empty($_POST['remove_banner'])) {
        $bannerValue = '__removed__';
    }

    // Handle optional uploaded logo file
    if (!empty($_FILES['logo_file']['name']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../uploads/stores';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $ext = strtolower(pathinfo((string) $_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $fileName = 'logo_' . $activeVendorId . '_' . time() . '.' . $ext;
            if (@move_uploaded_file($_FILES['logo_file']['tmp_name'], $uploadDir . '/' . $fileName)) {
                $logoValue = 'uploads/stores/' . $fileName;
            }
        }
    }

    // Handle optional uploaded banner file
    if (!empty($_FILES['banner_file']['name']) && is_uploaded_file($_FILES['banner_file']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../uploads/stores';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $ext = strtolower(pathinfo((string) $_FILES['banner_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $fileName = 'banner_' . $activeVendorId . '_' . time() . '.' . $ext;
            if (@move_uploaded_file($_FILES['banner_file']['tmp_name'], $uploadDir . '/' . $fileName)) {
                $bannerValue = 'uploads/stores/' . $fileName;
            }
        }
    }

    if ($storeName === '' || $whatsappNumber === '') {
        $flashError = 'Nama kedai dan No. WhatsApp wajib diisi.';
    } else {
        $vendor['store_name'] = $storeName;
        $vendor['description'] = $description;
        $vendor['whatsapp_number'] = $whatsappNumber;
        $vendor['address'] = $address;
        $vendor['logo'] = ($logoValue === '__removed__') ? '' : $logoValue;

        $extraSettings['category'] = $category;
        $extraSettings['title_size'] = $titleSize;
        $extraSettings['tagline'] = $tagline;
        $extraSettings['operating_hours'] = $operatingHours;
        $extraSettings['city'] = $city;
        $extraSettings['maps_url'] = $mapsUrl;
        $extraSettings['banner_image'] = ($bannerValue === '__removed__') ? '' : $bannerValue;

        $_SESSION['vendor_store_name'] = $storeName;

        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $uStmt = $pdo->prepare("
                    UPDATE vendors
                    SET store_name = :store_name,
                        description = :description,
                        address = :address,
                        whatsapp_number = :whatsapp_number,
                        logo = :logo
                    WHERE id = :id
                ");
                $uStmt->execute([
                    ':store_name' => $storeName,
                    ':description' => $description,
                    ':address' => $address,
                    ':whatsapp_number' => $whatsappNumber,
                    ':logo' => $vendor['logo'],
                    ':id' => $activeVendorId,
                ]);

                $eUpsert = $pdo->prepare("
                    INSERT INTO vendor_store_settings (
                        vendor_id, category, title_size, tagline, operating_hours, city, maps_url, banner_image
                    ) VALUES (
                        :vendor_id, :category, :title_size, :tagline, :operating_hours, :city, :maps_url, :banner_image
                    )
                    ON DUPLICATE KEY UPDATE
                        category = VALUES(category),
                        title_size = VALUES(title_size),
                        tagline = VALUES(tagline),
                        operating_hours = VALUES(operating_hours),
                        city = VALUES(city),
                        maps_url = VALUES(maps_url),
                        banner_image = VALUES(banner_image)
                ");
                $eUpsert->execute([
                    ':vendor_id' => $activeVendorId,
                    ':category' => $category,
                    ':title_size' => $titleSize,
                    ':tagline' => $tagline,
                    ':operating_hours' => $operatingHours,
                    ':city' => $city,
                    ':maps_url' => $mapsUrl,
                    ':banner_image' => $extraSettings['banner_image'],
                ]);

                $flashSuccess = 'Tetapan kedai berjaya disimpan.';
            } catch (PDOException $e) {
                $flashError = 'Ralat menyimpan tetapan: ' . $e->getMessage();
            }
        } else {
            $flashSuccess = 'Tetapan kedai berjaya disimpan.';
        }
    }
}

$vendorStoreName = (string) $vendor['store_name'];
$storeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vendorStoreName), 0, 2) ?: 'KW');
$defaultLogoPlaceholder = 'https://placehold.co/96x96/0b291a/9df557?text=' . urlencode($storeCode);
$defaultBannerPlaceholder = 'https://placehold.co/320x120/7c1d18/f8e5b4?text=' . urlencode($vendorStoreName);

$logoPreviewSrc = $defaultLogoPlaceholder;
if (!empty($vendor['logo']) && file_exists(__DIR__ . '/../' . ltrim((string) $vendor['logo'], '/'))) {
    $logoPreviewSrc = '../' . ltrim((string) $vendor['logo'], '/');
}

$bannerPreviewSrc = $defaultBannerPlaceholder;
if (!empty($extraSettings['banner_image']) && file_exists(__DIR__ . '/../' . ltrim((string) $extraSettings['banner_image'], '/'))) {
    $bannerPreviewSrc = '../' . ltrim((string) $extraSettings['banner_image'], '/');
}

$vendorLogoUrl = $logoPreviewSrc;
$categories = ['Food & Beverages', 'Bakery & Kuih', 'Cafe & Drinks', 'Groceries & Mart', 'Fashion & Craft'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetapan kedai — <?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?> | Kedai WA Kamek</title>
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

                    <!-- Top Bar (`Panel peniaga` / `Tetapan kedai` + `Lihat kedai`) -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel peniaga</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Tetapan kedai
                            </h1>
                        </div>
                        <div>
                            <a href="../index.php?token=<?php echo urlencode((string) $vendor['token']); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                               style="border-color: #dce2da !important; color: #0e1f17;">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>Lihat kedai</span>
                            </a>
                        </div>
                    </div>

                    <!-- Alerts -->
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

                    <form method="POST" action="settings.php" enctype="multipart/form-data" id="storeSettingsForm">
                        <input type="hidden" name="action" value="save_store_settings">
                        <input type="hidden" name="current_logo" id="currentLogoField" value="<?php echo htmlspecialchars((string) ($vendor['logo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="current_banner" id="currentBannerField" value="<?php echo htmlspecialchars((string) ($extraSettings['banner_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="remove_logo" id="removeLogoField" value="0">
                        <input type="hidden" name="remove_banner" id="removeBannerField" value="0">

                        <!-- Section 1: Maklumat kedai -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-4" style="color: #0e1f17;">Maklumat kedai</h2>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label for="store_name" class="form-label small fw-semibold" style="color: #13261d;">Nama kedai</label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 bg-white"
                                        id="store_name"
                                        name="store_name"
                                        value="<?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                        style="border-color: #dce2da !important;"
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="category" class="form-label small fw-semibold" style="color: #13261d;">Kategori</label>
                                    <select class="form-select rounded-3 bg-white"
                                            id="category"
                                            name="category"
                                            style="border-color: #dce2da !important;">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                                                <?php echo (($extraSettings['category'] ?? 'Food & Beverages') === $cat) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Saiz tajuk kedai (Kecil / Sederhana / Besar) -->
                            <div class="mb-4">
                                <label class="form-label small fw-semibold d-block mb-2" style="color: #13261d;">Saiz tajuk kedai</label>
                                <div class="row g-2 g-sm-3">
                                    <?php
                                    $sizeOptions = [
                                        'small' => ['title' => 'Aa', 'sub' => 'Kecil'],
                                        'medium' => ['title' => 'Aa', 'sub' => 'Sederhana'],
                                        'large' => ['title' => 'Aa', 'sub' => 'Besar'],
                                    ];
                                    $currentSize = $extraSettings['title_size'] ?? 'medium';
                                    ?>
                                    <?php foreach ($sizeOptions as $sKey => $sMeta): ?>
                                        <?php $isSelectedSize = ($currentSize === $sKey); ?>
                                        <div class="col-4">
                                            <label class="w-100 rounded-3 border p-3 text-center d-block title-size-card"
                                                   style="cursor: pointer; <?php echo $isSelectedSize
                                                       ? 'background-color: #eef2ef; border-color: #143326 !important;'
                                                       : 'background-color: #ffffff; border-color: #dce2da !important;'; ?>">
                                                <input type="radio"
                                                       class="btn-check title-size-radio"
                                                       name="title_size"
                                                       value="<?php echo htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8'); ?>"
                                                       <?php echo $isSelectedSize ? 'checked' : ''; ?>>
                                                <div class="fw-bold <?php echo ($sKey === 'large') ? 'fs-5' : (($sKey === 'small') ? 'fs-6' : 'fs-6'); ?>"
                                                     style="color: #0e1f17;">
                                                    <?php echo htmlspecialchars($sMeta['title'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="small" style="color: #6b7c73;">
                                                    <?php echo htmlspecialchars($sMeta['sub'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Tagline -->
                            <div class="mb-4">
                                <label for="tagline" class="form-label small fw-semibold" style="color: #13261d;">Tagline</label>
                                <input
                                    type="text"
                                    class="form-control rounded-3 bg-white"
                                    id="tagline"
                                    name="tagline"
                                    placeholder="Masakan kampung, rasa macam rumah sendiri"
                                    value="<?php echo htmlspecialchars((string) ($extraSettings['tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    style="border-color: #dce2da !important;"
                                >
                            </div>

                            <!-- Penerangan -->
                            <div class="mb-4">
                                <label for="description" class="form-label small fw-semibold" style="color: #13261d;">Penerangan</label>
                                <textarea
                                    class="form-control rounded-3 bg-white"
                                    id="description"
                                    name="description"
                                    rows="3"
                                    placeholder="Nasi lemak, mee kolok dan laksa Sarawak dimasak segar setiap pagi."
                                    style="border-color: #dce2da !important;"
                                ><?php echo htmlspecialchars((string) ($vendor['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>

                            <!-- No. WhatsApp & Waktu operasi -->
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="whatsapp_number" class="form-label small fw-semibold" style="color: #13261d;">No. WhatsApp</label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 bg-white"
                                        id="whatsapp_number"
                                        name="whatsapp_number"
                                        value="<?php echo htmlspecialchars((string) ($vendor['whatsapp_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        required
                                        style="border-color: #dce2da !important;"
                                    >
                                    <div class="form-text small" style="color: #6b7c73;">Format: 60123456789</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="operating_hours" class="form-label small fw-semibold" style="color: #13261d;">Waktu operasi</label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 bg-white"
                                        id="operating_hours"
                                        name="operating_hours"
                                        value="<?php echo htmlspecialchars((string) ($extraSettings['operating_hours'] ?? '7:00 am - 2:00 pm'), ENT_QUOTES, 'UTF-8'); ?>"
                                        style="border-color: #dce2da !important;"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Lokasi -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-4" style="color: #0e1f17;">Lokasi</h2>

                            <div class="mb-3">
                                <label for="address" class="form-label small fw-semibold" style="color: #13261d;">Alamat</label>
                                <input
                                    type="text"
                                    class="form-control rounded-3 bg-white"
                                    id="address"
                                    name="address"
                                    value="<?php echo htmlspecialchars((string) ($vendor['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    style="border-color: #dce2da !important;"
                                >
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="city" class="form-label small fw-semibold" style="color: #13261d;">Bandar</label>
                                    <input
                                        type="text"
                                        class="form-control rounded-3 bg-white"
                                        id="city"
                                        name="city"
                                        value="<?php echo htmlspecialchars((string) ($extraSettings['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        style="border-color: #dce2da !important;"
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="maps_url" class="form-label small fw-semibold" style="color: #13261d;">Pautan Google Maps</label>
                                    <input
                                        type="url"
                                        class="form-control rounded-3 bg-white"
                                        id="maps_url"
                                        name="maps_url"
                                        placeholder="https://maps.google.com/?q=..."
                                        value="<?php echo htmlspecialchars((string) ($extraSettings['maps_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        style="border-color: #dce2da !important;"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Imej kedai -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Imej kedai</h2>
                            <p class="small mb-4" style="color: #6b7c73;">
                                JPG, PNG atau WebP. Imej besar akan dikecilkan secara automatik.
                            </p>

                            <div class="row g-3 g-lg-4">

                                <!-- Logo kedai -->
                                <div class="col-12 col-lg-6">
                                    <label class="form-label small fw-semibold d-block mb-2" style="color: #13261d;">Logo kedai</label>
                                    <div class="border rounded-4 p-3 d-flex flex-wrap align-items-center gap-3"
                                         style="border-color: #dce2da !important; background-color: #ffffff;">
                                        <img
                                            id="logoPreviewImg"
                                            src="<?php echo htmlspecialchars($logoPreviewSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-default="<?php echo htmlspecialchars($defaultLogoPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Logo kedai Placeholder"
                                            class="rounded-3 object-fit-cover flex-shrink-0 border"
                                            style="width: 56px; height: 56px; border-color: #e4e9e5 !important;"
                                        >

                                        <input type="file"
                                               id="logoFileInput"
                                               name="logo_file"
                                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                               class="d-none">

                                        <button type="button"
                                                id="swapLogoBtn"
                                                class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                                style="border-color: #dce2da !important; color: #0e1f17;">
                                            <i class="bi bi-image"></i>
                                            <span>Tukar</span>
                                        </button>

                                        <button type="button"
                                                id="removeLogoBtn"
                                                class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-trash"></i>
                                            <span>Buang</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Banner kedai -->
                                <div class="col-12 col-lg-6">
                                    <label class="form-label small fw-semibold d-block mb-2" style="color: #13261d;">Banner kedai</label>
                                    <div class="border rounded-4 p-3 d-flex flex-wrap align-items-center gap-3"
                                         style="border-color: #dce2da !important; background-color: #ffffff;">
                                        <img
                                            id="bannerPreviewImg"
                                            src="<?php echo htmlspecialchars($bannerPreviewSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                            data-default="<?php echo htmlspecialchars($defaultBannerPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Banner kedai Placeholder"
                                            class="rounded-3 object-fit-cover flex-shrink-0 border"
                                            style="width: 92px; height: 56px; border-color: #e4e9e5 !important;"
                                        >

                                        <input type="file"
                                               id="bannerFileInput"
                                               name="banner_file"
                                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                               class="d-none">

                                        <button type="button"
                                                id="swapBannerBtn"
                                                class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                                style="border-color: #dce2da !important; color: #0e1f17;">
                                            <i class="bi bi-image"></i>
                                            <span>Tukar</span>
                                        </button>

                                        <button type="button"
                                                id="removeBannerBtn"
                                                class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-trash"></i>
                                            <span>Buang</span>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Save Button Row -->
                        <div class="d-flex justify-content-end mb-4">
                            <button
                                type="submit"
                                class="btn rounded-3 px-4 py-2 fw-semibold border-0"
                                style="background-color: #143326; color: #ffffff;">
                                Simpan perubahan
                            </button>
                        </div>

                    </form>

                </div>
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sizeCards = document.querySelectorAll('.title-size-card');
        sizeCards.forEach(function (card) {
            const radio = card.querySelector('.title-size-radio');
            if (!radio) return;
            radio.addEventListener('change', function () {
                sizeCards.forEach(function (c) {
                    const r = c.querySelector('.title-size-radio');
                    const active = r && r.checked;
                    c.style.backgroundColor = active ? '#eef2ef' : '#ffffff';
                    c.style.borderColor = active ? '#143326' : '#dce2da';
                });
            });
        });

        const swapLogoBtn = document.getElementById('swapLogoBtn');
        const removeLogoBtn = document.getElementById('removeLogoBtn');
        const logoFileInput = document.getElementById('logoFileInput');
        const logoPreviewImg = document.getElementById('logoPreviewImg');
        const removeLogoField = document.getElementById('removeLogoField');

        if (swapLogoBtn && logoFileInput) {
            swapLogoBtn.addEventListener('click', function () {
                logoFileInput.click();
            });
            logoFileInput.addEventListener('change', function () {
                if (logoFileInput.files && logoFileInput.files[0] && logoPreviewImg) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        logoPreviewImg.src = ev.target.result;
                        if (removeLogoField) removeLogoField.value = '0';
                    };
                    reader.readAsDataURL(logoFileInput.files[0]);
                }
            });
        }

        if (removeLogoBtn && logoPreviewImg) {
            removeLogoBtn.addEventListener('click', function () {
                if (logoFileInput) logoFileInput.value = '';
                if (removeLogoField) removeLogoField.value = '1';
                logoPreviewImg.src = logoPreviewImg.getAttribute('data-default') || logoPreviewImg.src;
            });
        }

        const swapBannerBtn = document.getElementById('swapBannerBtn');
        const removeBannerBtn = document.getElementById('removeBannerBtn');
        const bannerFileInput = document.getElementById('bannerFileInput');
        const bannerPreviewImg = document.getElementById('bannerPreviewImg');
        const removeBannerField = document.getElementById('removeBannerField');

        if (swapBannerBtn && bannerFileInput) {
            swapBannerBtn.addEventListener('click', function () {
                bannerFileInput.click();
            });
            bannerFileInput.addEventListener('change', function () {
                if (bannerFileInput.files && bannerFileInput.files[0] && bannerPreviewImg) {
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        bannerPreviewImg.src = ev.target.result;
                        if (removeBannerField) removeBannerField.value = '0';
                    };
                    reader.readAsDataURL(bannerFileInput.files[0]);
                }
            });
        }

        if (removeBannerBtn && bannerPreviewImg) {
            removeBannerBtn.addEventListener('click', function () {
                if (bannerFileInput) bannerFileInput.value = '';
                if (removeBannerField) removeBannerField.value = '1';
                bannerPreviewImg.src = bannerPreviewImg.getAttribute('data-default') || bannerPreviewImg.src;
            });
        }
    });
    </script>
</body>
</html>