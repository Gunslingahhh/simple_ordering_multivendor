<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminPage = 'branding.php';
$adminRoleLabel = 'Panel admin';

if (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: ../vendor/dashboard.php');
    exit;
}

function resolvePlatformAssetPath(string $storedPath, string $fallbackUrl): string
{
    $trimmed = trim($storedPath);
    if ($trimmed === '') {
        return $fallbackUrl;
    }
    if (preg_match('#^https?://#i', $trimmed)) {
        return $trimmed;
    }
    $cleanRel = ltrim(str_replace('\\', '/', $trimmed), '/');
    $candidates = [
        '../uploads/branding/' . basename($cleanRel),
        '../uploads/' . basename($cleanRel),
        '../' . $cleanRel,
    ];
    foreach ($candidates as $rel) {
        if (file_exists(__DIR__ . '/' . $rel) && is_file(__DIR__ . '/' . $rel)) {
            return $rel;
        }
    }
    return $fallbackUrl;
}

function saveBrandingUpload(array $fileField, string $prefix): string
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
    $ext = $extMap[$mime] ?? 'png';
    $targetDir = __DIR__ . '/../uploads/branding';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }
    $filename = $prefix . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($tmpName, $targetDir . '/' . $filename)) {
        return 'uploads/branding/' . $filename;
    }
    return '';
}

$flashSuccess = '';
$flashError = '';

$settings = [
    'platform_name' => 'Kedai WA Kamek',
    'logo' => '',
    'show_sponsor' => 1,
    'sponsor_text' => 'Ditaja oleh',
    'sponsor_name' => 'N.26 Gedong',
    'sponsor_logo' => '',
    'contact_email' => 'hello@kedaiwakamek.my',
    'contact_phone' => '60128889900',
    'address' => 'Pusat Komuniti N.26 Gedong, Sarawak',
    'admin_pin' => '0000',
];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM platform_settings")->fetchAll(PDO::FETCH_COLUMN);
        $colSet = array_flip(is_array($cols) ? $cols : []);

        $optionalCols = [
            'logo' => "VARCHAR(255) DEFAULT ''",
            'sponsor_logo' => "VARCHAR(255) DEFAULT ''",
            'contact_email' => "VARCHAR(120) DEFAULT 'hello@kedaiwakamek.my'",
            'contact_phone' => "VARCHAR(40) DEFAULT '60128889900'",
            'address' => "VARCHAR(255) DEFAULT 'Pusat Komuniti N.26 Gedong, Sarawak'",
            'admin_pin' => "VARCHAR(20) DEFAULT '0000'",
        ];
        foreach ($optionalCols as $colName => $colDef) {
            if (!isset($colSet[$colName])) {
                $pdo->exec("ALTER TABLE platform_settings ADD COLUMN {$colName} {$colDef}");
            }
        }
    } catch (PDOException $e) {
        // Ignore if schema check/alter is restricted
    }

    try {
        $psRow = $pdo->query("SELECT * FROM platform_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($psRow) {
            $settings = array_merge($settings, $psRow);
        }
    } catch (PDOException $e) {
        // Keep defaults
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_branding') {
    $platformName = trim((string) ($_POST['platform_name'] ?? 'Kedai WA Kamek'));
    $showSponsor = isset($_POST['show_sponsor']) ? 1 : 0;
    $sponsorName = trim((string) ($_POST['sponsor_name'] ?? ''));
    $sponsorText = trim((string) ($_POST['sponsor_text'] ?? 'Ditaja oleh'));
    $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
    $contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $adminPin = trim((string) ($_POST['admin_pin'] ?? '0000'));

    $removePlatformLogo = !empty($_POST['remove_platform_logo']);
    $removeSponsorLogo = !empty($_POST['remove_sponsor_logo']);

    $logoValue = $removePlatformLogo ? '' : (string) ($settings['logo'] ?? '');
    $sponsorLogoValue = $removeSponsorLogo ? '' : (string) ($settings['sponsor_logo'] ?? '');

    $uploadedPlatformLogo = saveBrandingUpload($_FILES['platform_logo'] ?? [], 'platform-logo');
    if ($uploadedPlatformLogo !== '') {
        $logoValue = $uploadedPlatformLogo;
    }

    $uploadedSponsorLogo = saveBrandingUpload($_FILES['sponsor_logo'] ?? [], 'sponsor-logo');
    if ($uploadedSponsorLogo !== '') {
        $sponsorLogoValue = $uploadedSponsorLogo;
    }

    if ($platformName === '') {
        $flashError = 'Nama platform wajib diisi.';
    } else {
        $settings['platform_name'] = $platformName;
        $settings['logo'] = $logoValue;
        $settings['show_sponsor'] = $showSponsor;
        $settings['sponsor_text'] = $sponsorText !== '' ? $sponsorText : 'Ditaja oleh';
        $settings['sponsor_name'] = $sponsorName;
        $settings['sponsor_logo'] = $sponsorLogoValue;
        $settings['contact_email'] = $contactEmail;
        $settings['contact_phone'] = $contactPhone;
        $settings['address'] = $address;
        $settings['admin_pin'] = $adminPin !== '' ? $adminPin : '0000';

        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $exists = $pdo->query("SELECT id FROM platform_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($exists) {
                    $stmt = $pdo->prepare("
                        UPDATE platform_settings
                        SET platform_name = :platform_name,
                            logo = :logo,
                            show_sponsor = :show_sponsor,
                            sponsor_text = :sponsor_text,
                            sponsor_name = :sponsor_name,
                            sponsor_logo = :sponsor_logo,
                            contact_email = :contact_email,
                            contact_phone = :contact_phone,
                            address = :address,
                            admin_pin = :admin_pin
                        WHERE id = 1
                    ");
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO platform_settings (
                            id, platform_name, logo, show_sponsor, sponsor_text, sponsor_name,
                            sponsor_logo, contact_email, contact_phone, address, admin_pin
                        ) VALUES (
                            1, :platform_name, :logo, :show_sponsor, :sponsor_text, :sponsor_name,
                            :sponsor_logo, :contact_email, :contact_phone, :address, :admin_pin
                        )
                    ");
                }
                $stmt->execute([
                    ':platform_name' => $settings['platform_name'],
                    ':logo' => $settings['logo'],
                    ':show_sponsor' => $settings['show_sponsor'],
                    ':sponsor_text' => $settings['sponsor_text'],
                    ':sponsor_name' => $settings['sponsor_name'],
                    ':sponsor_logo' => $settings['sponsor_logo'],
                    ':contact_email' => $settings['contact_email'],
                    ':contact_phone' => $settings['contact_phone'],
                    ':address' => $settings['address'],
                    ':admin_pin' => $settings['admin_pin'],
                ]);
                $flashSuccess = 'Jenama dan tetapan platform berjaya disimpan.';
            } catch (PDOException $e) {
                $flashError = 'Ralat menyimpan tetapan: ' . $e->getMessage();
            }
        } else {
            $flashSuccess = 'Jenama dan tetapan platform berjaya disimpan.';
        }
    }
}

$defaultPlatformLogoUrl = 'https://placehold.co/180x56/169a5a/ffffff?text=' . urlencode((string) ($settings['platform_name'] ?? 'Kedai WA Kamek'));
$platformLogoPreview = resolvePlatformAssetPath((string) ($settings['logo'] ?? ''), $defaultPlatformLogoUrl);

$sponsorCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($settings['sponsor_name'] ?? 'N26')), 0, 2) ?: 'PJ');
$defaultSponsorLogoUrl = 'https://placehold.co/64x64/e9f3ec/143326?text=' . urlencode($sponsorCode);
$sponsorLogoPreview = resolvePlatformAssetPath((string) ($settings['sponsor_logo'] ?? ''), $defaultSponsorLogoUrl);

$showSponsorChecked = !empty($settings['show_sponsor']);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenama &amp; penaja — <?php echo htmlspecialchars((string) $settings['platform_name'], ENT_QUOTES, 'UTF-8'); ?></title>
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

                    <!-- Top Page Bar (`Admin platform` / `Jenama & penaja`) -->
                    <div class="mb-4">
                        <div class="small mb-1" style="color: #6b7c73;">Admin platform</div>
                        <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                            Jenama &amp; penaja
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

                    <form method="POST" action="settings.php" enctype="multipart/form-data" id="brandingSettingsForm">
                        <input type="hidden" name="action" value="save_branding">
                        <input type="hidden" name="remove_platform_logo" id="removePlatformLogoInput" value="0">
                        <input type="hidden" name="remove_sponsor_logo" id="removeSponsorLogoInput" value="0">

                        <!-- Card 1: Platform -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-4" style="color: #0e1f17;">Platform</h2>

                            <div class="row g-3 g-lg-4 align-items-start">
                                <div class="col-12 col-lg-6">
                                    <label for="platform_name" class="form-label small fw-semibold" style="color: #13261d;">
                                        Nama platform
                                    </label>
                                    <input type="text"
                                           class="form-control rounded-3 bg-white"
                                           id="platform_name"
                                           name="platform_name"
                                           value="<?php echo htmlspecialchars((string) $settings['platform_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                           required
                                           style="border-color: #dce2da !important;">
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label small fw-semibold d-block mb-2" style="color: #13261d;">
                                        Logo platform
                                    </label>

                                    <div class="border rounded-4 p-3 d-flex flex-wrap align-items-center gap-3 bg-white"
                                         style="border-color: #dce2da !important;">
                                        <img id="platformLogoPreview"
                                             src="<?php echo htmlspecialchars($platformLogoPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                             data-default="<?php echo htmlspecialchars($defaultPlatformLogoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="Logo platform Placeholder"
                                             class="rounded-3 object-fit-contain flex-shrink-0"
                                             style="height: 44px; max-width: 130px;">

                                        <input type="file"
                                               id="platformLogoFile"
                                               name="platform_logo"
                                               accept="image/*"
                                               class="d-none">

                                        <button type="button"
                                                id="swapPlatformLogoBtn"
                                                class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                                style="border-color: #dce2da !important; color: #0e1f17;">
                                            <i class="bi bi-image"></i>
                                            <span>Tukar</span>
                                        </button>

                                        <button type="button"
                                                id="removePlatformLogoBtn"
                                                class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-trash"></i>
                                            <span>Buang</span>
                                        </button>
                                    </div>

                                    <div class="form-text small mt-2" style="color: #6b7c73;">
                                        Kosongkan untuk logo lalai
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Penaja -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Penaja</h2>
                            <p class="small mb-4" style="color: #6b7c73;">
                                Dipaparkan di sebelah logo utama dalam pengepala.
                            </p>

                            <!-- Switch Row: Papar jenama penaja -->
                            <div class="border rounded-4 p-3 px-sm-4 mb-4 d-flex align-items-center justify-content-between gap-3 bg-white"
                                 style="border-color: #e4e9e5 !important;">
                                <div>
                                    <div class="fw-semibold small" style="color: #0e1f17;">Papar jenama penaja</div>
                                    <div class="small" style="color: #6b7c73;">Kelihatan kepada semua pengunjung</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           role="switch"
                                           id="show_sponsor"
                                           name="show_sponsor"
                                           value="1"
                                           <?php echo $showSponsorChecked ? 'checked' : ''; ?>
                                           style="width: 2.5em; height: 1.3em; cursor: pointer; <?php echo $showSponsorChecked ? 'background-color: #143326; border-color: #143326;' : ''; ?>">
                                </div>
                            </div>

                            <!-- Nama penaja & Teks penaja -->
                            <div class="row g-3 g-lg-4 mb-4">
                                <div class="col-12 col-lg-6">
                                    <label for="sponsor_name" class="form-label small fw-semibold" style="color: #13261d;">
                                        Nama penaja
                                    </label>
                                    <input type="text"
                                           class="form-control rounded-3 bg-white"
                                           id="sponsor_name"
                                           name="sponsor_name"
                                           value="<?php echo htmlspecialchars((string) $settings['sponsor_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                           style="border-color: #dce2da !important;">
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label for="sponsor_text" class="form-label small fw-semibold" style="color: #13261d;">
                                        Teks penaja
                                    </label>
                                    <input type="text"
                                           class="form-control rounded-3 bg-white"
                                           id="sponsor_text"
                                           name="sponsor_text"
                                           value="<?php echo htmlspecialchars((string) $settings['sponsor_text'], ENT_QUOTES, 'UTF-8'); ?>"
                                           style="border-color: #dce2da !important;">
                                </div>
                            </div>

                            <!-- Logo penaja -->
                            <div class="mb-4">
                                <label class="form-label small fw-semibold d-block mb-2" style="color: #13261d;">
                                    Logo penaja
                                </label>

                                <div class="border rounded-4 p-3 d-flex flex-wrap align-items-center gap-3 bg-white"
                                     style="border-color: #dce2da !important;">
                                    <img id="sponsorLogoPreview"
                                         src="<?php echo htmlspecialchars($sponsorLogoPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                         data-default="<?php echo htmlspecialchars($defaultSponsorLogoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Logo penaja Placeholder"
                                         class="rounded-3 object-fit-cover flex-shrink-0 border"
                                         style="width: 36px; height: 36px; border-color: #e4e9e5 !important;">

                                    <input type="file"
                                           id="sponsorLogoFile"
                                           name="sponsor_logo"
                                           accept="image/*"
                                           class="d-none">

                                    <button type="button"
                                            id="swapSponsorLogoBtn"
                                            class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                            style="border-color: #dce2da !important; color: #0e1f17;">
                                        <i class="bi bi-image"></i>
                                        <span>Tukar</span>
                                    </button>

                                    <button type="button"
                                            id="removeSponsorLogoBtn"
                                            class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-trash"></i>
                                        <span>Buang</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Pratonton (Live Header Preview) -->
                            <div>
                                <div class="small mb-2" style="color: #6b7c73;">Pratonton</div>
                                <div class="border rounded-4 p-3 px-sm-4 d-flex flex-wrap align-items-center gap-3"
                                     style="background-color: #f8faf9; border-color: #e4e9e5 !important;">
                                    <img id="previewPlatformImg"
                                         src="<?php echo htmlspecialchars($platformLogoPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Pratonton Logo Platform Placeholder"
                                         class="rounded-2 object-fit-contain"
                                         style="height: 36px;">

                                    <div id="previewSponsorBlock"
                                         class="d-flex align-items-center gap-2 border-start ps-3 <?php echo $showSponsorChecked ? '' : 'd-none'; ?>"
                                         style="border-color: #dce2da !important;">
                                        <img id="previewSponsorImg"
                                             src="<?php echo htmlspecialchars($sponsorLogoPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="Pratonton Logo Penaja Placeholder"
                                             class="rounded-2 object-fit-cover"
                                             style="width: 28px; height: 28px;">
                                        <div style="line-height: 1.2;">
                                            <div id="previewSponsorText" style="font-size: 0.72rem; color: #6b7c73;">
                                                <?php echo htmlspecialchars((string) $settings['sponsor_text'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div id="previewSponsorName" class="fw-bold small" style="color: #0e1f17;">
                                                <?php echo htmlspecialchars((string) $settings['sponsor_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Hubungi & keselamatan -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <h2 class="h5 fw-bold mb-4" style="color: #0e1f17;">Hubungi &amp; keselamatan</h2>

                            <div class="row g-3 g-lg-4 mb-4">
                                <div class="col-12 col-lg-6">
                                    <label for="contact_email" class="form-label small fw-semibold" style="color: #13261d;">
                                        E-mel
                                    </label>
                                    <input type="email"
                                           class="form-control rounded-3 bg-white"
                                           id="contact_email"
                                           name="contact_email"
                                           value="<?php echo htmlspecialchars((string) $settings['contact_email'], ENT_QUOTES, 'UTF-8'); ?>"
                                           style="border-color: #dce2da !important;">
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label for="contact_phone" class="form-label small fw-semibold" style="color: #13261d;">
                                        No. telefon / WhatsApp
                                    </label>
                                    <input type="text"
                                           class="form-control rounded-3 bg-white"
                                           id="contact_phone"
                                           name="contact_phone"
                                           value="<?php echo htmlspecialchars((string) $settings['contact_phone'], ENT_QUOTES, 'UTF-8'); ?>"
                                           style="border-color: #dce2da !important;">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="address" class="form-label small fw-semibold" style="color: #13261d;">
                                    Alamat
                                </label>
                                <input type="text"
                                       class="form-control rounded-3 bg-white"
                                       id="address"
                                       name="address"
                                       value="<?php echo htmlspecialchars((string) $settings['address'], ENT_QUOTES, 'UTF-8'); ?>"
                                       style="border-color: #dce2da !important;">
                            </div>

                            <div style="max-width: 520px;">
                                <label for="admin_pin" class="form-label small fw-semibold" style="color: #13261d;">
                                    PIN admin
                                </label>
                                <input type="text"
                                       class="form-control rounded-3 bg-white"
                                       id="admin_pin"
                                       name="admin_pin"
                                       value="<?php echo htmlspecialchars((string) $settings['admin_pin'], ENT_QUOTES, 'UTF-8'); ?>"
                                       style="border-color: #dce2da !important;">
                                <div class="form-text small mt-2" style="color: #6b7c73;">
                                    Prototaip sahaja
                                </div>
                            </div>
                        </div>

                        <!-- Save Button (`Simpan jenama`) -->
                        <div class="d-flex justify-content-end mb-4">
                            <button type="submit"
                                    class="btn rounded-3 px-4 py-2 fw-semibold border-0"
                                    style="background-color: #143326; color: #ffffff;">
                                Simpan jenama
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
        const platformLogoFile = document.getElementById('platformLogoFile');
        const swapPlatformLogoBtn = document.getElementById('swapPlatformLogoBtn');
        const removePlatformLogoBtn = document.getElementById('removePlatformLogoBtn');
        const platformLogoPreview = document.getElementById('platformLogoPreview');
        const previewPlatformImg = document.getElementById('previewPlatformImg');
        const removePlatformLogoInput = document.getElementById('removePlatformLogoInput');

        const sponsorLogoFile = document.getElementById('sponsorLogoFile');
        const swapSponsorLogoBtn = document.getElementById('swapSponsorLogoBtn');
        const removeSponsorLogoBtn = document.getElementById('removeSponsorLogoBtn');
        const sponsorLogoPreview = document.getElementById('sponsorLogoPreview');
        const previewSponsorImg = document.getElementById('previewSponsorImg');
        const removeSponsorLogoInput = document.getElementById('removeSponsorLogoInput');

        const showSponsorSwitch = document.getElementById('show_sponsor');
        const sponsorNameInput = document.getElementById('sponsor_name');
        const sponsorTextInput = document.getElementById('sponsor_text');
        const previewSponsorBlock = document.getElementById('previewSponsorBlock');
        const previewSponsorText = document.getElementById('previewSponsorText');
        const previewSponsorName = document.getElementById('previewSponsorName');

        if (swapPlatformLogoBtn && platformLogoFile) {
            swapPlatformLogoBtn.addEventListener('click', function () {
                platformLogoFile.click();
            });
            platformLogoFile.addEventListener('change', function () {
                if (platformLogoFile.files && platformLogoFile.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (platformLogoPreview) platformLogoPreview.src = e.target.result;
                        if (previewPlatformImg) previewPlatformImg.src = e.target.result;
                        if (removePlatformLogoInput) removePlatformLogoInput.value = '0';
                    };
                    reader.readAsDataURL(platformLogoFile.files[0]);
                }
            });
        }

        if (removePlatformLogoBtn && platformLogoPreview) {
            removePlatformLogoBtn.addEventListener('click', function () {
                const defSrc = platformLogoPreview.getAttribute('data-default') || platformLogoPreview.src;
                if (platformLogoFile) platformLogoFile.value = '';
                if (removePlatformLogoInput) removePlatformLogoInput.value = '1';
                platformLogoPreview.src = defSrc;
                if (previewPlatformImg) previewPlatformImg.src = defSrc;
            });
        }

        if (swapSponsorLogoBtn && sponsorLogoFile) {
            swapSponsorLogoBtn.addEventListener('click', function () {
                sponsorLogoFile.click();
            });
            sponsorLogoFile.addEventListener('change', function () {
                if (sponsorLogoFile.files && sponsorLogoFile.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (sponsorLogoPreview) sponsorLogoPreview.src = e.target.result;
                        if (previewSponsorImg) previewSponsorImg.src = e.target.result;
                        if (removeSponsorLogoInput) removeSponsorLogoInput.value = '0';
                    };
                    reader.readAsDataURL(sponsorLogoFile.files[0]);
                }
            });
        }

        if (removeSponsorLogoBtn && sponsorLogoPreview) {
            removeSponsorLogoBtn.addEventListener('click', function () {
                const defSrc = sponsorLogoPreview.getAttribute('data-default') || sponsorLogoPreview.src;
                if (sponsorLogoFile) sponsorLogoFile.value = '';
                if (removeSponsorLogoInput) removeSponsorLogoInput.value = '1';
                sponsorLogoPreview.src = defSrc;
                if (previewSponsorImg) previewSponsorImg.src = defSrc;
            });
        }

        function updateSponsorPreview() {
            if (previewSponsorBlock && showSponsorSwitch) {
                previewSponsorBlock.classList.toggle('d-none', !showSponsorSwitch.checked);
                showSponsorSwitch.style.backgroundColor = showSponsorSwitch.checked ? '#143326' : '';
                showSponsorSwitch.style.borderColor = showSponsorSwitch.checked ? '#143326' : '';
            }
            if (previewSponsorText && sponsorTextInput) {
                previewSponsorText.textContent = sponsorTextInput.value.trim() || 'Ditaja oleh';
            }
            if (previewSponsorName && sponsorNameInput) {
                previewSponsorName.textContent = sponsorNameInput.value.trim() || '';
            }
        }

        if (showSponsorSwitch) showSponsorSwitch.addEventListener('change', updateSponsorPreview);
        if (sponsorTextInput) sponsorTextInput.addEventListener('input', updateSponsorPreview);
        if (sponsorNameInput) sponsorNameInput.addEventListener('input', updateSponsorPreview);
    });
    </script>
</body>
</html>