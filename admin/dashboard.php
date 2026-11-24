<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminPage = 'dashboard.php';
$adminRoleLabel = 'Panel admin';

if (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: ../vendor/dashboard.php');
    exit;
}

$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'toggle_vendor_active') {
            $vendorId = (int) ($_POST['vendor_id'] ?? 0);
            $nextActive = ((string) ($_POST['next_active'] ?? '0') === '1') ? 1 : 0;

            if ($vendorId > 0) {
                $stmt = $pdo->prepare("UPDATE vendors SET is_active = :is_active WHERE id = :id");
                $stmt->execute([
                    ':is_active' => $nextActive,
                    ':id' => $vendorId,
                ]);
                $flashSuccess = $nextActive === 1
                    ? 'Status kedai diaktifkan.'
                    : 'Status kedai dinyahaktifkan.';
            }
        } elseif ($action === 'add_activation_code') {
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $description = trim((string) ($_POST['description'] ?? ''));
            $freeMonths = max(1, (int) ($_POST['free_months'] ?? 1));
            $maxUses = max(1, (int) ($_POST['max_uses'] ?? 10));
            $status = in_array(($_POST['status'] ?? 'active'), ['active', 'inactive'], true)
                ? (string) $_POST['status']
                : 'active';

            if ($code === '') {
                $flashError = 'Sila masukkan kod pengaktifan.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO activation_codes (code, description, free_months, max_uses, used_count, status)
                    VALUES (:code, :description, :free_months, :max_uses, 0, :status)
                ");
                $stmt->execute([
                    ':code' => $code,
                    ':description' => $description,
                    ':free_months' => $freeMonths,
                    ':max_uses' => $maxUses,
                    ':status' => $status,
                ]);
                $flashSuccess = 'Kod pengaktifan baharu berjaya ditambah.';
            }
        } elseif ($action === 'save_platform_settings') {
            $platformName = trim((string) ($_POST['platform_name'] ?? 'Kedai WA Kamek'));
            $showSponsor = isset($_POST['show_sponsor']) ? 1 : 0;
            $sponsorText = trim((string) ($_POST['sponsor_text'] ?? 'Ditaja oleh'));
            $sponsorName = trim((string) ($_POST['sponsor_name'] ?? ''));

            if ($platformName === '') {
                $flashError = 'Nama platform wajib diisi.';
            } else {
                $chk = $pdo->query("SELECT id FROM platform_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($chk) {
                    $stmt = $pdo->prepare("
                        UPDATE platform_settings
                        SET platform_name = :platform_name,
                            show_sponsor = :show_sponsor,
                            sponsor_text = :sponsor_text,
                            sponsor_name = :sponsor_name
                        WHERE id = 1
                    ");
                    $stmt->execute([
                        ':platform_name' => $platformName,
                        ':show_sponsor' => $showSponsor,
                        ':sponsor_text' => $sponsorText,
                        ':sponsor_name' => $sponsorName,
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO platform_settings (id, platform_name, show_sponsor, sponsor_text, sponsor_name)
                        VALUES (1, :platform_name, :show_sponsor, :sponsor_text, :sponsor_name)
                    ");
                    $stmt->execute([
                        ':platform_name' => $platformName,
                        ':show_sponsor' => $showSponsor,
                        ':sponsor_text' => $sponsorText,
                        ':sponsor_name' => $sponsorName,
                    ]);
                }
                $flashSuccess = 'Tetapan platform berjaya disimpan.';
            }
        }
    } catch (PDOException $e) {
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

$totalVendors = 0;
$activeVendorsCount = 0;
$totalMenuItems = 0;
$totalOrders = 0;
$totalOrderRevenue = 0.00;
$vendorsList = [];
$codesList = [];
$platformSettings = [
    'platform_name' => 'Kedai WA Kamek',
    'show_sponsor' => 1,
    'sponsor_text' => 'Ditaja oleh',
    'sponsor_name' => 'N.26 Gedong',
];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vRows = $pdo->query("
            SELECT id, token, user_id, store_name, description, address, whatsapp_number, status, is_active, created_at
            FROM vendors
            ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $vendorsList = $vRows;
        $totalVendors = count($vRows);
        foreach ($vRows as $vr) {
            if ((int) ($vr['is_active'] ?? 0) === 1) {
                $activeVendorsCount++;
            }
        }

        $mCountRow = $pdo->query("SELECT COUNT(*) AS cnt FROM menu_items")->fetch(PDO::FETCH_ASSOC);
        $totalMenuItems = (int) ($mCountRow['cnt'] ?? 0);

        $oStatsRow = $pdo->query("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS total_rev
            FROM orders
        ")->fetch(PDO::FETCH_ASSOC);
        $totalOrders = (int) ($oStatsRow['cnt'] ?? 0);
        $totalOrderRevenue = (float) ($oStatsRow['total_rev'] ?? 0);

        $codesList = $pdo->query("
            SELECT id, code, description, free_months, max_uses, used_count, status, created_at
            FROM activation_codes
            ORDER BY id DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $psRow = $pdo->query("
            SELECT platform_name, show_sponsor, sponsor_text, sponsor_name
            FROM platform_settings
            WHERE id = 1
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        if ($psRow) {
            $platformSettings = array_merge($platformSettings, $psRow);
        }
    } catch (PDOException $e) {
        // Keep defaults if query fails
    }
}

$adminDisplayName = (string) ($_SESSION['username'] ?? 'Admin');
$adminCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $adminDisplayName), 0, 2) ?: 'AD');
$adminAvatarUrl = 'https://placehold.co/64x64/14261d/9df557?text=' . urlencode($adminCode);
$brandLogoPlaceholder = 'https://placehold.co/160x44/ffffff/143326?text=' . urlencode((string) ($platformSettings['platform_name'] ?? 'Kedai WA Kamek'));
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Admin — <?php echo htmlspecialchars((string) ($platformSettings['platform_name'] ?? 'Kedai WA Kamek'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="m-0 p-0" style="background-color: #f4f5ef; color: #13261d;">

    <!-- Exact Screen-Height Scrollable Wrapper (`100dvh`) -->
    <div class="container-fluid p-0 overflow-hidden" style="height: 100vh; height: 100dvh;">
        <div class="d-flex h-100">

            <!-- Static Screen-Height Admin Sidenav -->
            <?php require_once __DIR__ . '/sidenav.php'; ?>

            <!-- Independently Scrollable Main Content -->
            <main class="flex-grow-1 h-100 overflow-y-auto p-3 p-sm-4 p-lg-5" style="background-color: #f4f5ef;">
                <div class="container-fluid px-0">

                    <!-- Top Bar -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel admin</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Ringkasan
                            </h1>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="../index.php"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                               style="border-color: #dce2da !important; color: #0e1f17;">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>Lihat laman utama</span>
                            </a>
                            <button type="button"
                                    class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addActivationCodeModal"
                                    style="background-color: #143326; color: #ffffff;">
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambah kod aktif</span>
                            </button>
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
                    <div class="row g-3 g-lg-4 mb-4">
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="small fw-medium" style="color: #6b7c73;">Jumlah kedai</span>
                                    <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                                          style="width: 34px; height: 34px; background-color: #e9f3ec; color: #143326;">
                                        <i class="bi bi-shop"></i>
                                    </span>
                                </div>
                                <div class="h2 fw-bold mb-1" style="color: #0e1f17;"><?php echo (int) $totalVendors; ?></div>
                                <div class="small" style="color: #6b7c73;">
                                    <?php echo (int) $activeVendorsCount; ?> kedai aktif
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="small fw-medium" style="color: #6b7c73;">Jumlah menu</span>
                                    <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                                          style="width: 34px; height: 34px; background-color: #e8f5e3; color: #15803d;">
                                        <i class="bi bi-box-seam"></i>
                                    </span>
                                </div>
                                <div class="h2 fw-bold mb-1" style="color: #0e1f17;"><?php echo (int) $totalMenuItems; ?></div>
                                <div class="small" style="color: #6b7c73;">Item merentas semua kedai</div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="small fw-medium" style="color: #6b7c73;">Jumlah pesanan</span>
                                    <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                                          style="width: 34px; height: 34px; background-color: #fdfaf2; color: #b45309;">
                                        <i class="bi bi-receipt"></i>
                                    </span>
                                </div>
                                <div class="h2 fw-bold mb-1" style="color: #0e1f17;"><?php echo (int) $totalOrders; ?></div>
                                <div class="small" style="color: #6b7c73;">Pesanan direkodkan</div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="small fw-medium" style="color: #6b7c73;">Nilai pesanan</span>
                                    <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                                          style="width: 34px; height: 34px; background-color: #e9f3ec; color: #143326;">
                                        <i class="bi bi-cash-stack"></i>
                                    </span>
                                </div>
                                <div class="h2 fw-bold mb-1" style="color: #0e1f17;">
                                    RM <?php echo number_format($totalOrderRevenue, 2); ?>
                                </div>
                                <div class="small" style="color: #6b7c73;">Jumlah keseluruhan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Vendors List Card (`#senarai-kedai`) -->
                    <div id="senarai-kedai" class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Senarai kedai</h2>
                                <div class="small" style="color: #6b7c73;"><?php echo (int) $totalVendors; ?> kedai berdaftar</div>
                            </div>
                            <div style="max-width: 280px; width: 100%;">
                                <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                                     style="border-color: #dce2da !important;">
                                    <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                                    <input type="search"
                                           id="adminVendorSearch"
                                           class="form-control border-0 shadow-none bg-transparent p-0 small"
                                           placeholder="Cari kedai...">
                                </div>
                            </div>
                        </div>

                        <?php if (empty($vendorsList)): ?>
                            <div class="small py-3" style="color: #6b7c73;">Tiada kedai dijumpai dalam pangkalan data.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 small" id="adminVendorsTable">
                                    <thead>
                                        <tr style="color: #6b7c73; border-color: #e6ebe7;">
                                            <th scope="col" class="py-2">Kedai</th>
                                            <th scope="col" class="py-2">WhatsApp</th>
                                            <th scope="col" class="py-2">Status kedai</th>
                                            <th scope="col" class="py-2">Akaun aktif</th>
                                            <th scope="col" class="py-2 text-end">Pautan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vendorsList as $vRow): ?>
                                            <?php
                                            $vId = (int) $vRow['id'];
                                            $vName = (string) $vRow['store_name'];
                                            $vToken = (string) ($vRow['token'] ?? '');
                                            $vOpen = (($vRow['status'] ?? 'open') === 'open');
                                            $vActive = ((int) ($vRow['is_active'] ?? 1) === 1);
                                            $vCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vName), 0, 2) ?: 'KD');
                                            $vThumb = 'https://placehold.co/48x48/e9f3ec/143326?text=' . urlencode($vCode);
                                            ?>
                                            <tr class="admin-vendor-row"
                                                data-search="<?php echo htmlspecialchars(strtolower($vName . ' ' . ($vRow['address'] ?? '') . ' ' . ($vRow['whatsapp_number'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                                                style="border-color: #eef2ef;">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?php echo htmlspecialchars($vThumb, ENT_QUOTES, 'UTF-8'); ?>"
                                                             alt="<?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                             class="rounded-3 object-fit-cover flex-shrink-0 border"
                                                             style="width: 40px; height: 40px; border-color: #e4e9e5 !important;">
                                                        <div>
                                                            <div class="fw-bold" style="color: #0e1f17;">
                                                                <?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                            <div style="color: #6b7c73; font-size: 0.78rem;">
                                                                <?php echo htmlspecialchars((string) ($vRow['address'] ?? 'Sarawak'), ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3" style="color: #1f3329;">
                                                    <?php echo htmlspecialchars((string) ($vRow['whatsapp_number'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge rounded-pill px-2 py-1"
                                                          style="<?php echo $vOpen
                                                              ? 'background-color: #dcfce7; color: #15803d;'
                                                              : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                                        <?php echo $vOpen ? 'Buka' : 'Tutup'; ?>
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <form method="POST" action="dashboard.php" class="d-inline m-0">
                                                        <input type="hidden" name="action" value="toggle_vendor_active">
                                                        <input type="hidden" name="vendor_id" value="<?php echo $vId; ?>">
                                                        <input type="hidden" name="next_active" value="<?php echo $vActive ? '0' : '1'; ?>">
                                                        <div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   role="switch"
                                                                   <?php echo $vActive ? 'checked' : ''; ?>
                                                                   onchange="this.form.submit()"
                                                                   style="cursor: pointer; <?php echo $vActive ? 'background-color: #143326; border-color: #143326;' : ''; ?>">
                                                            <span style="color: #6b7c73;"><?php echo $vActive ? 'Aktif' : 'Nyahaktif'; ?></span>
                                                        </div>
                                                    </form>
                                                </td>
                                                <td class="py-3 text-end">
                                                    <?php if ($vToken !== ''): ?>
                                                        <a href="../index.php?token=<?php echo urlencode($vToken); ?>"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           class="btn btn-sm rounded-3 border bg-white px-2 py-1 fw-semibold"
                                                           style="border-color: #dce2da !important; color: #0e1f17;">
                                                            Buka
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Two-Column Row: Activation Codes (`#kod-pengaktifan`) & Platform Settings (`#tetapan-platform`) -->
                    <div class="row g-3 g-lg-4 mb-4">

                        <div id="kod-pengaktifan" class="col-12 col-xl-6">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Kod pengaktifan</h2>
                                        <div class="small" style="color: #6b7c73;"><?php echo count($codesList); ?> kod direkodkan</div>
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#addActivationCodeModal"
                                            style="border-color: #dce2da !important; color: #0e1f17;">
                                        + Kod
                                    </button>
                                </div>

                                <?php if (empty($codesList)): ?>
                                    <div class="small" style="color: #6b7c73;">Belum ada kod pengaktifan.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0 small">
                                            <thead>
                                                <tr style="color: #6b7c73; border-color: #e6ebe7;">
                                                    <th scope="col" class="py-2">Kod</th>
                                                    <th scope="col" class="py-2">Bulan</th>
                                                    <th scope="col" class="py-2">Digunakan</th>
                                                    <th scope="col" class="py-2 text-end">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($codesList as $cRow): ?>
                                                    <?php $cActive = (strtolower((string) ($cRow['status'] ?? 'active')) === 'active'); ?>
                                                    <tr style="border-color: #eef2ef;">
                                                        <td class="py-2 fw-bold" style="color: #0e1f17;">
                                                            <?php echo htmlspecialchars((string) $cRow['code'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </td>
                                                        <td class="py-2" style="color: #6b7c73;">
                                                            +<?php echo (int) ($cRow['free_months'] ?? 1); ?> bln
                                                        </td>
                                                        <td class="py-2" style="color: #1f3329;">
                                                            <?php echo (int) ($cRow['used_count'] ?? 0); ?> / <?php echo (int) ($cRow['max_uses'] ?? 1); ?>
                                                        </td>
                                                        <td class="py-2 text-end">
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                  style="<?php echo $cActive
                                                                      ? 'background-color: #dcfce7; color: #15803d;'
                                                                      : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                                                <?php echo htmlspecialchars((string) ($cRow['status'] ?? 'active'), ENT_QUOTES, 'UTF-8'); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="tetapan-platform" class="col-12 col-xl-6">
                            <div class="card border rounded-4 bg-white p-3 p-sm-4 h-100" style="border-color: #e4e9e5 !important;">
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Tetapan platform</h2>
                                <p class="small mb-3" style="color: #6b7c73;">Kemas kini maklumat jenama dan penaja platform.</p>

                                <form method="POST" action="dashboard.php">
                                    <input type="hidden" name="action" value="save_platform_settings">

                                    <div class="mb-3">
                                        <label for="plat_name" class="form-label small fw-semibold">Nama platform</label>
                                        <input type="text"
                                               class="form-control rounded-3"
                                               id="plat_name"
                                               name="platform_name"
                                               value="<?php echo htmlspecialchars((string) ($platformSettings['platform_name'] ?? 'Kedai WA Kamek'), ENT_QUOTES, 'UTF-8'); ?>"
                                               required>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-sm-5">
                                            <label for="plat_sponsor_text" class="form-label small fw-semibold">Teks penaja</label>
                                            <input type="text"
                                                   class="form-control rounded-3"
                                                   id="plat_sponsor_text"
                                                   name="sponsor_text"
                                                   value="<?php echo htmlspecialchars((string) ($platformSettings['sponsor_text'] ?? 'Ditaja oleh'), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="col-12 col-sm-7">
                                            <label for="plat_sponsor_name" class="form-label small fw-semibold">Nama penaja</label>
                                            <input type="text"
                                                   class="form-control rounded-3"
                                                   id="plat_sponsor_name"
                                                   name="sponsor_name"
                                                   value="<?php echo htmlspecialchars((string) ($platformSettings['sponsor_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>

                                    <div class="form-check form-switch mb-4">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               role="switch"
                                               id="plat_show_sponsor"
                                               name="show_sponsor"
                                               value="1"
                                               <?php echo !empty($platformSettings['show_sponsor']) ? 'checked' : ''; ?>
                                               style="cursor: pointer; background-color: #143326; border-color: #143326;">
                                        <label class="form-check-label small" for="plat_show_sponsor">Papar lencana penaja</label>
                                    </div>

                                    <button type="submit"
                                            class="btn rounded-3 px-4 py-2 fw-semibold border-0"
                                            style="background-color: #143326; color: #ffffff;">
                                        Simpan tetapan
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>

                </div>
            </main>

        </div>
    </div>

    <!-- Modal: Tambah Kod Pengaktifan -->
    <div class="modal fade" id="addActivationCodeModal" tabindex="-1" aria-labelledby="addActivationCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="add_activation_code">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addActivationCodeModalLabel">Tambah Kod Pengaktifan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="code_input" class="form-label small fw-semibold">Kod</label>
                            <input type="text" class="form-control rounded-3 text-uppercase" id="code_input" name="code" placeholder="cth: GEDONG30" required>
                        </div>
                        <div class="mb-3">
                            <label for="code_desc" class="form-label small fw-semibold">Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="code_desc" name="description" placeholder="Promosi komuniti">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="code_months" class="form-label small fw-semibold">Bulan percuma</label>
                                <input type="number" min="1" class="form-control rounded-3" id="code_months" name="free_months" value="1" required>
                            </div>
                            <div class="col-6">
                                <label for="code_max_uses" class="form-label small fw-semibold">Had penggunaan</label>
                                <input type="number" min="1" class="form-control rounded-3" id="code_max_uses" name="max_uses" value="10" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="code_status" class="form-label small fw-semibold">Status</label>
                            <select class="form-select rounded-3" id="code_status" name="status">
                                <option value="active" selected>active</option>
                                <option value="inactive">inactive</option>
                            </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('adminVendorSearch');
        const rows = document.querySelectorAll('.admin-vendor-row');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    const hay = row.getAttribute('data-search') || '';
                    row.classList.toggle('d-none', q !== '' && !hay.includes(q));
                });
            });
        }
    });
    </script>
</body>
</html>