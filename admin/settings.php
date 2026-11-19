<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission to update platform settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platform_name = trim($_POST['platform_name']);
    $show_sponsor = isset($_POST['show_sponsor']) ? 1 : 0;
    $sponsor_name = trim($_POST['sponsor_name']);
    $sponsor_text = trim($_POST['sponsor_text']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $admin_pin = trim($_POST['admin_pin']);

    // Fetch existing settings
    $stmtCur = $pdo->query("SELECT * FROM platform_settings WHERE id = 1");
    $current = $stmtCur->fetch();

    $platform_logo = $current['platform_logo'] ?? '';
    $sponsor_logo = $current['sponsor_logo'] ?? '';

    // Handle Platform Logo Upload
    if (isset($_FILES['platform_logo']) && $_FILES['platform_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['platform_logo']['tmp_name'];
        $fileName = $_FILES['platform_logo']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'plogo_' . md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = '../uploads/platform/' . $newFileName;
        if (!is_dir('../uploads/platform/')) {
            mkdir('../uploads/platform/', 0777, true);
        }
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $platform_logo = $newFileName;
        }
    }

    // Handle Sponsor Logo Upload
    if (isset($_FILES['sponsor_logo']) && $_FILES['sponsor_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['sponsor_logo']['tmp_name'];
        $fileName = $_FILES['sponsor_logo']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'slogo_' . md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = '../uploads/platform/' . $newFileName;
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $sponsor_logo = $newFileName;
        }
    }

    try {
        // Attempt update with extended columns (or fallback if columns are missing)
        $stmtUp = $pdo->prepare("UPDATE platform_settings SET platform_name = ?, platform_logo = ?, show_sponsor = ?, sponsor_name = ?, sponsor_text = ?, sponsor_logo = ?, email = ?, phone = ?, address = ?, admin_pin = ? WHERE id = 1");
        $stmtUp->execute([$platform_name, $platform_logo, $show_sponsor, $sponsor_name, $sponsor_text, $sponsor_logo, $email, $phone, $address, $admin_pin]);
        $success = 'Tetapan jenama & penaja berjaya disimpan!';
    } catch (PDOException $e) {
        // Fallback update if contact columns haven't been migrated yet
        $stmtBasic = $pdo->prepare("UPDATE platform_settings SET platform_name = ?, show_sponsor = ?, sponsor_name = ?, sponsor_text = ? WHERE id = 1");
        $stmtBasic->execute([$platform_name, $show_sponsor, $sponsor_name, $sponsor_text]);
        $success = 'Tetapan asas berjaya disimpan!';
    }
}

// Fetch current platform settings from database
$stmtSettings = $pdo->query("SELECT * FROM platform_settings WHERE id = 1");
$settings = $stmtSettings->fetch();

if (!$settings) {
    $settings = [
        'platform_name' => 'Kedai WA Kamek',
        'platform_logo' => '',
        'show_sponsor' => 1,
        'sponsor_name' => 'N.26 Gedong',
        'sponsor_text' => 'Ditaja oleh',
        'sponsor_logo' => '',
        'email' => 'hello@kedaiwakamek.my',
        'phone' => '60128889900',
        'address' => 'Pusat Komuniti N.26 Gedong, Sarawak',
        'admin_pin' => '0000'
    ];
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenama & Penaja — Admin Platform</title>
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
        .image-box-container {
            border: 1px dashed #cbd5e1;
            border-radius: 0.75rem;
            padding: 1.25rem;
            background: #fcfcfc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
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
                <h5 class="fw-bold mb-0 text-dark">Jenama & penaja</h5>
            </div>
            <div>
                <a href="../index.php" target="_blank" class="btn-lihat-kedai">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i> Lihat kedai
                </a>
            </div>
        </div>

        <!-- Content Body -->
        <div class="dash-content-body">
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <!-- SECTION 1: Platform -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-4">Platform</h5>

                    <div class="row g-4 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nama platform</label>
                            <input type="text" class="form-control" name="platform_name" value="<?= htmlspecialchars($settings['platform_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Logo platform</label>
                            <div class="image-box-container">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="text-white rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: #0b291a;">
                                        <i class="fa-solid fa-bag-shopping fs-6"></i>
                                    </div>
                                    <span class="fw-bold text-dark">Kedai WA Kamek</span>
                                    <input type="file" name="platform_logo" id="platformLogoInput" class="d-none" accept="image/*">
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-light border fw-semibold px-3" onclick="document.getElementById('platformLogoInput').click();">
                                        <i class="fa-regular fa-image me-1"></i> Tukar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold" onclick="alert('Logo platform dibuang');">
                                        Buang
                                    </button>
                                </div>
                            </div>
                            <div class="form-text small text-muted mt-1">Kosongkan untuk logo lalai</div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: Penaja -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-1">Penaja</h5>
                    <p class="text-muted small mb-4">Dipaparkan di sebelah logo utama dalam pengepala.</p>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 border mb-4">
                        <div>
                            <div class="fw-bold text-dark small">Papar jenama penaja</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Kelihatan kepada semua pengunjung</div>
                        </div>
                        <div class="form-check form-switch fs-4 m-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="show_sponsor" value="1" <?= ($settings['show_sponsor'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nama penaja</label>
                            <input type="text" class="form-control" name="sponsor_name" value="<?= htmlspecialchars($settings['sponsor_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Teks penaja</label>
                            <input type="text" class="form-control" name="sponsor_text" value="<?= htmlspecialchars($settings['sponsor_text'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Logo penaja</label>
                        <div class="image-box-container" style="max-width: 600px;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-dark text-white rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fa-solid fa-landmark small"></i>
                                </div>
                                <input type="file" name="sponsor_logo" id="sponsorLogoInput" class="d-none" accept="image/*">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-light border fw-semibold px-3" onclick="document.getElementById('sponsorLogoInput').click();">
                                    <i class="fa-regular fa-image me-1"></i> Tukar
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold" onclick="alert('Logo penaja dibuang');">
                                    Buang
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pratonton (Live Preview Box) -->
                    <div>
                        <label class="form-label small fw-bold text-muted mb-2">Pratonton</label>
                        <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="text-white rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background-color: #0b291a;">
                                    <i class="fa-solid fa-bag-shopping" style="font-size: 0.75rem;"></i>
                                </div>
                                <span class="fw-bold text-dark small"><?= htmlspecialchars($settings['platform_name']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 border-start ps-3">
                                <div class="bg-dark text-white rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                    <i class="fa-solid fa-landmark" style="font-size: 0.6rem;"></i>
                                </div>
                                <div style="line-height: 1.1;">
                                    <div class="text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars($settings['sponsor_text']) ?></div>
                                    <div class="fw-bold text-dark" style="font-size: 0.75rem;"><?= htmlspecialchars($settings['sponsor_name']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: Hubungi & keselamatan -->
                <div class="settings-card-section">
                    <h5 class="fw-bold text-dark mb-4">Hubungi & keselamatan</h5>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">E-mel</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($settings['email'] ?? 'hello@kedaiwakamek.my') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">No. telefon / WhatsApp</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '60128889900') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Alamat</label>
                        <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($settings['address'] ?? 'Pusat Komuniti N.26 Gedong, Sarawak') ?>">
                    </div>

                    <div class="mb-2" style="max-width: 400px;">
                        <label class="form-label small fw-bold text-muted">PIN admin</label>
                        <input type="text" class="form-control" name="admin_pin" value="<?= htmlspecialchars($settings['admin_pin'] ?? '0000') ?>">
                        <div class="form-text small text-muted">Prototip sahaja</div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-dark fw-bold px-5 py-2.5" style="background-color: #0b291a; border-radius: 50rem;">
                        Simpan jenama
                    </button>
                </div>

            </form>

            <div class="text-muted text-center small pt-3">
                Kedai WA Kamek Admin Platform
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>