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
$specialLink = "http://" . $_SERVER['HTTP_HOST'] . "/simple_ordering_multivendor/index.php?token=" . $vendor['token'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $category = trim($_POST['category']);
    $tagline = trim($_POST['tagline']);
    $description = trim($_POST['description']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $operating_hours = trim($_POST['operating_hours']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $maps_link = trim($_POST['maps_link']);
    $title_size = trim($_POST['title_size'] ?? 'Sederhana');

    if (!empty($store_name) && !empty($whatsapp_number)) {
        $logo = $vendor['logo'] ?? '';
        $banner = $vendor['banner'] ?? '';

        // Handle Logo Upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newLogoName = 'logo_' . md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = '../uploads/vendors/' . $newLogoName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $logo = $newLogoName;
            }
        }

        // Handle Banner Upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['banner']['tmp_name'];
            $fileName = $_FILES['banner']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newBannerName = 'banner_' . md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = '../uploads/vendors/' . $newBannerName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $banner = $newBannerName;
            }
        }

        try {
            $stmtUp = $pdo->prepare("UPDATE vendors SET store_name = ?, category = ?, tagline = ?, description = ?, whatsapp_number = ?, operating_hours = ?, address = ?, city = ?, maps_link = ?, title_size = ?, logo = ?, banner = ? WHERE id = ?");
            $stmtUp->execute([$store_name, $category, $tagline, $description, $whatsapp_number, $operating_hours, $address, $city, $maps_link, $title_size, $logo, $banner, $vendor_id]);
        } catch (PDOException $e) {
            $stmtBasic = $pdo->prepare("UPDATE vendors SET store_name = ?, description = ?, address = ?, whatsapp_number = ? WHERE id = ?");
            $stmtBasic->execute([$store_name, $description, $address, $whatsapp_number, $vendor_id]);
        }
        
        $success = 'Tetapan kedai berjaya disimpan!';
        $stmt->execute([$user_id]);
        $vendor = $stmt->fetch();
    } else {
        $error = 'Nama kedai dan No. WhatsApp diperlukan.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetapan Kedai — Panel Peniaga</title>
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
        .settings-card-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .title-size-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .title-size-card.selected {
            border-color: #0b291a;
            background-color: #fafafa;
            box-shadow: 0 0 0 1px #0b291a;
        }
        .image-box-container {
            border: 1px dashed #cbd5e1;
            border-radius: 0.75rem;
            padding: 1.25rem;
            background: #fcfcfc;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
    
    <?php include 'sidenav.php'; ?>

    <div class="dash-main">
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Panel peniaga</span>
                <h5 class="fw-bold mb-0 text-dark">Tetapan kedai</h5>
            </div>
            <div>
                <a href="<?= htmlspecialchars($specialLink) ?>" target="_blank" class="btn-lihat-kedai">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i> Lihat kedai
                </a>
            </div>
        </div>

        <div class="dash-content-body">
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <!-- SECTION 1: Maklumat kedai -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-4">Maklumat kedai</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nama kedai</label>
                            <input type="text" class="form-control" name="store_name" value="<?= htmlspecialchars($vendor['store_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="Food & Beverages" <?= ($vendor['category'] ?? '') === 'Food & Beverages' ? 'selected' : '' ?>>Food & Beverages</option>
                                <option value="Fashion & Apparel" <?= ($vendor['category'] ?? '') === 'Fashion & Apparel' ? 'selected' : '' ?>>Fashion & Apparel</option>
                                <option value="Fresh Produce" <?= ($vendor['category'] ?? '') === 'Fresh Produce' ? 'selected' : '' ?>>Fresh Produce</option>
                                <option value="Handmade Crafts" <?= ($vendor['category'] ?? '') === 'Handmade Crafts' ? 'selected' : '' ?>>Handmade Crafts</option>
                                <option value="Others" <?= ($vendor['category'] ?? '') === 'Others' ? 'selected' : '' ?>>Others</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Saiz tajuk kedai</label>
                        <input type="hidden" name="title_size" id="titleSizeInput" value="<?= htmlspecialchars($vendor['title_size'] ?? 'Sederhana') ?>">
                        <div class="row row-cols-3 g-3">
                            <div class="col">
                                <div class="title-size-card <?= ($vendor['title_size'] ?? '') === 'Kecil' ? 'selected' : '' ?>" onclick="selectTitleSize('Kecil', this)">
                                    <div class="fw-bold fs-6">Aa</div>
                                    <div class="text-muted small">Kecil</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="title-size-card <?= ($vendor['title_size'] ?? 'Sederhana') === 'Sederhana' ? 'selected' : '' ?>" onclick="selectTitleSize('Sederhana', this)">
                                    <div class="fw-bold fs-5">Aa</div>
                                    <div class="text-muted small">Sederhana</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="title-size-card <?= ($vendor['title_size'] ?? '') === 'Besar' ? 'selected' : '' ?>" onclick="selectTitleSize('Besar', this)">
                                    <div class="fw-bold fs-4">Aa</div>
                                    <div class="text-muted small">Besar</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tagline</label>
                        <input type="text" class="form-control" name="tagline" value="<?= htmlspecialchars($vendor['tagline'] ?? 'Masakan kampung, rasa macam rumah sendiri') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Penerangan</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($vendor['description']) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">No. WhatsApp</label>
                            <input type="text" class="form-control" name="whatsapp_number" value="<?= htmlspecialchars($vendor['whatsapp_number']) ?>" required>
                            <div class="form-text small text-muted">Format: 60123456789</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Waktu operasi</label>
                            <input type="text" class="form-control" name="operating_hours" value="<?= htmlspecialchars($vendor['operating_hours'] ?? '7:00 am – 2:00 pm') ?>">
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Lokasi -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-4">Lokasi</h5>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Alamat</label>
                        <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($vendor['address']) ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Bandar</label>
                            <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($vendor['city'] ?? 'Gedong') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Pautan Google Maps</label>
                            <input type="text" class="form-control" name="maps_link" value="<?= htmlspecialchars($vendor['maps_link'] ?? 'https://maps.google.com/?q=Gedong+Sarawak') ?>">
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Imej Kedai -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-1">Imej kedai</h5>
                    <p class="text-muted small mb-4">JPG, PNG atau WebP. Imej besar akan dikecilkan secara automatik.</p>

                    <div class="row g-4">
                        <!-- Logo Kedai -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Logo kedai</label>
                            <div class="image-box-container">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" width="48" height="48" class="rounded-circle object-fit-cover border shadow-sm" alt="Logo">
                                    <input type="file" name="logo" id="logoInput" class="d-none" accept="image/*">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-light border fw-semibold px-3" onclick="document.getElementById('logoInput').click();">
                                        <i class="fa-regular fa-image me-1"></i> Tukar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold" onclick="alert('Logo dibuang');">
                                        Buang
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Kedai -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Banner kedai</label>
                            <div class="image-box-container">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="../uploads/vendors/<?= htmlspecialchars($vendor['banner'] ?: 'default_banner.png') ?>" width="75" height="45" class="rounded object-fit-cover border shadow-sm" alt="Banner">
                                    <input type="file" name="banner" id="bannerInput" class="d-none" accept="image/*">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-light border fw-semibold px-3" onclick="document.getElementById('bannerInput').click();">
                                        <i class="fa-regular fa-image me-1"></i> Tukar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold" onclick="alert('Banner dibuang');">
                                        Buang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-dark fw-bold px-5 py-2.5" style="background-color: #0b291a; border-radius: 50rem;">
                        Simpan perubahan
                    </button>
                </div>

            </form>

            <div class="text-muted text-center small pt-3">
                Prototip: data dan PIN disimpan dalam localStorage pelayar ini dan tidak selamat untuk produksi.
            </div>

        </div>
    </div>

</div>

<script>
function selectTitleSize(size, element) {
    document.querySelectorAll('.title-size-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('titleSizeInput').value = size;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>