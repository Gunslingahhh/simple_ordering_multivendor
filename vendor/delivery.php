<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentVendorPage = 'delivery.php';
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

$defaultDeliveryConfig = [
    'delivery_enabled' => 1,
    'free_delivery_enabled' => 1,
    'free_delivery_min_amount' => 50,
    'zones' => [
        [
            'name' => 'Zon 1 - Kawasan berdekatan',
            'fee' => 3,
            'min_order' => 10,
            'active' => 1,
            'out_of_coverage' => 0,
            'areas' => ['Padungan', 'Pending', 'Jalan Song'],
        ],
        [
            'name' => 'Zon 2 - Jarak sederhana',
            'fee' => 5,
            'min_order' => 15,
            'active' => 1,
            'out_of_coverage' => 0,
            'areas' => ['Tabuan Jaya', 'Stutong', 'BDC'],
        ],
        [
            'name' => 'Zon 3 - Kawasan jauh',
            'fee' => 8,
            'min_order' => 25,
            'active' => 1,
            'out_of_coverage' => 0,
            'areas' => ['Petra Jaya', 'Batu Kawa', 'Matang'],
        ],
        [
            'name' => 'Zon 4 - Luar liputan',
            'fee' => 0,
            'min_order' => 0,
            'active' => 1,
            'out_of_coverage' => 1,
            'areas' => ['Kota Samarahan', 'Bau', 'Siburan'],
        ],
    ],
];

$flashSuccess = '';
$flashError = '';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendor_delivery_settings (
                vendor_id INT PRIMARY KEY,
                delivery_enabled TINYINT(1) NOT NULL DEFAULT 1,
                free_delivery_enabled TINYINT(1) NOT NULL DEFAULT 1,
                free_delivery_min_amount DECIMAL(10,2) NOT NULL DEFAULT 50.00,
                zones_json LONGTEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    } catch (PDOException $e) {
        // Ignore DDL error if permissions restrict table creation
    }
}

// Handle form submission (`Simpan tetapan penghantaran`)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_delivery') {
    $deliveryEnabled = !empty($_POST['delivery_enabled']) ? 1 : 0;
    $freeDeliveryEnabled = !empty($_POST['free_delivery_enabled']) ? 1 : 0;
    $freeDeliveryMin = max(0, (float) ($_POST['free_delivery_min_amount'] ?? 50));

    $zonesJsonRaw = (string) ($_POST['zones_json'] ?? '');
    $decodedZones = json_decode($zonesJsonRaw, true);
    $cleanZones = [];

    if (is_array($decodedZones)) {
        foreach ($decodedZones as $z) {
            if (!is_array($z)) {
                continue;
            }
            $zName = trim((string) ($z['name'] ?? 'Zon Penghantaran'));
            if ($zName === '') {
                $zName = 'Zon Penghantaran';
            }
            $outCov = !empty($z['out_of_coverage']) ? 1 : 0;
            $feeVal = $outCov ? 0.0 : max(0, (float) ($z['fee'] ?? 0));
            $minOrd = max(0, (float) ($z['min_order'] ?? 0));
            $zAct = !empty($z['active']) ? 1 : 0;

            $rawAreas = is_array($z['areas'] ?? null) ? $z['areas'] : [];
            $cleanAreas = [];
            foreach ($rawAreas as $ar) {
                $arStr = trim((string) $ar);
                if ($arStr !== '' && !in_array($arStr, $cleanAreas, true)) {
                    $cleanAreas[] = $arStr;
                }
            }

            $cleanZones[] = [
                'name' => $zName,
                'fee' => $feeVal,
                'min_order' => $minOrd,
                'active' => $zAct,
                'out_of_coverage' => $outCov,
                'areas' => $cleanAreas,
            ];
        }
    }

    if (empty($cleanZones)) {
        $cleanZones = $defaultDeliveryConfig['zones'];
    }

    $savedConfig = [
        'delivery_enabled' => $deliveryEnabled,
        'free_delivery_enabled' => $freeDeliveryEnabled,
        'free_delivery_min_amount' => $freeDeliveryMin,
        'zones' => $cleanZones,
    ];

    $_SESSION['vendor_delivery_config_' . $activeVendorId] = $savedConfig;

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $saveStmt = $pdo->prepare("
                INSERT INTO vendor_delivery_settings (
                    vendor_id, delivery_enabled, free_delivery_enabled, free_delivery_min_amount, zones_json
                ) VALUES (
                    :vendor_id, :delivery_enabled, :free_delivery_enabled, :free_delivery_min_amount, :zones_json
                )
                ON DUPLICATE KEY UPDATE
                    delivery_enabled = VALUES(delivery_enabled),
                    free_delivery_enabled = VALUES(free_delivery_enabled),
                    free_delivery_min_amount = VALUES(free_delivery_min_amount),
                    zones_json = VALUES(zones_json)
            ");
            $saveStmt->execute([
                ':vendor_id' => $activeVendorId,
                ':delivery_enabled' => $deliveryEnabled,
                ':free_delivery_enabled' => $freeDeliveryEnabled,
                ':free_delivery_min_amount' => $freeDeliveryMin,
                ':zones_json' => json_encode($cleanZones, JSON_UNESCAPED_UNICODE),
            ]);
            $flashSuccess = 'Tetapan penghantaran berjaya disimpan.';
        } catch (PDOException $e) {
            $flashSuccess = 'Tetapan penghantaran disimpan untuk sesi semasa.';
        }
    } else {
        $flashSuccess = 'Tetapan penghantaran disimpan.';
    }
}

// Fetch vendor info & delivery settings
$vendor = [
    'id' => $activeVendorId,
    'token' => '9226f954b98b2ae5bae55d8501d8d716',
    'store_name' => $_SESSION['vendor_store_name'] ?? 'Roti Berkaus',
];

$deliveryConfig = $_SESSION['vendor_delivery_config_' . $activeVendorId] ?? $defaultDeliveryConfig;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vStmt = $pdo->prepare("SELECT id, token, store_name FROM vendors WHERE id = :id LIMIT 1");
        $vStmt->execute([':id' => $activeVendorId]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $vendor = array_merge($vendor, $vRow);
            $_SESSION['vendor_store_name'] = $vendor['store_name'];
        }

        $dStmt = $pdo->prepare("
            SELECT delivery_enabled, free_delivery_enabled, free_delivery_min_amount, zones_json
            FROM vendor_delivery_settings
            WHERE vendor_id = :vendor_id
            LIMIT 1
        ");
        $dStmt->execute([':vendor_id' => $activeVendorId]);
        $dRow = $dStmt->fetch(PDO::FETCH_ASSOC);
        if ($dRow) {
            $dbZones = json_decode((string) ($dRow['zones_json'] ?? ''), true);
            $deliveryConfig = [
                'delivery_enabled' => (int) ($dRow['delivery_enabled'] ?? 1),
                'free_delivery_enabled' => (int) ($dRow['free_delivery_enabled'] ?? 1),
                'free_delivery_min_amount' => (float) ($dRow['free_delivery_min_amount'] ?? 50),
                'zones' => (is_array($dbZones) && !empty($dbZones)) ? $dbZones : $defaultDeliveryConfig['zones'],
            ];
        }
    } catch (PDOException $e) {
        // Keep session/default config
    }
}

$vendorStoreName = (string) $vendor['store_name'];
$storeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vendorStoreName), 0, 2) ?: 'RB');
$vendorLogoUrl = 'https://placehold.co/64x64/14261d/9df557?text=' . urlencode($storeCode);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Settings — <?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?> | Kedai WA Kamek</title>
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

                    <!-- Top Header (`Panel peniaga` / `Delivery Settings` + `Lihat kedai`) -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel peniaga</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Delivery Settings
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

                    <form method="POST" action="delivery.php" id="deliverySettingsForm">
                        <input type="hidden" name="action" value="save_delivery">
                        <input type="hidden" name="zones_json" id="zonesJsonField" value="">

                        <!-- Card 1: Penghantaran & Penghantaran Percuma -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                                <div>
                                    <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Penghantaran</h2>
                                    <p class="small mb-0" style="color: #6b7c73;">
                                        Pelanggan boleh memilih Penghantaran atau COD apabila ini diaktifkan.
                                    </p>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="deliveryEnabledSwitch"
                                        name="delivery_enabled"
                                        value="1"
                                        <?php echo !empty($deliveryConfig['delivery_enabled']) ? 'checked' : ''; ?>
                                        style="width: 2.6em; height: 1.35em; cursor: pointer; background-color: #143326; border-color: #143326;"
                                    >
                                </div>
                            </div>

                            <!-- Inner Free Delivery Box -->
                            <div class="border rounded-4 p-3 p-sm-4" style="background-color: #fcfdfb; border-color: #e6ebe7 !important;">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" style="color: #0e1f17;">Penghantaran percuma</h3>
                                        <p class="small mb-0" style="color: #6b7c73;">
                                            Percuma apabila jumlah pesanan melebihi amaun ini.
                                        </p>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="freeDeliverySwitch"
                                            name="free_delivery_enabled"
                                            value="1"
                                            <?php echo !empty($deliveryConfig['free_delivery_enabled']) ? 'checked' : ''; ?>
                                            style="width: 2.6em; height: 1.35em; cursor: pointer; background-color: #143326; border-color: #143326;"
                                        >
                                    </div>
                                </div>

                                <div style="max-width: 300px;">
                                    <label for="freeDeliveryMinAmount" class="form-label small fw-semibold" style="color: #13261d;">
                                        Percuma untuk pesanan melebihi (RM)
                                    </label>
                                    <input
                                        type="number"
                                        step="1"
                                        min="0"
                                        class="form-control rounded-3 bg-white"
                                        id="freeDeliveryMinAmount"
                                        name="free_delivery_min_amount"
                                        value="<?php echo htmlspecialchars((string) (int) ($deliveryConfig['free_delivery_min_amount'] ?? 50), ENT_QUOTES, 'UTF-8'); ?>"
                                        style="border-color: #dce2da !important;"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Zon Penghantaran -->
                        <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3 mb-4">
                                <div>
                                    <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Zon penghantaran</h2>
                                    <p class="small mb-0" style="color: #6b7c73;">
                                        Tetapkan kawasan, caj dan minimum pesanan untuk setiap zon. Tambah seberapa banyak zon yang perlu.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    id="addDeliveryZoneBtn"
                                    class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 align-self-start"
                                    style="border-color: #dce2da !important; color: #0e1f17;">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Tambah zon</span>
                                </button>
                            </div>

                            <!-- Zones Container Rendered by JS from Real PHP Data -->
                            <div id="deliveryZonesContainer" class="d-flex flex-column gap-4"></div>
                        </div>

                        <!-- Bottom Save Button Row -->
                        <div class="d-flex justify-content-end mb-4">
                            <button
                                type="submit"
                                class="btn rounded-3 px-4 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2"
                                style="background-color: #143326; color: #ffffff;">
                                <span>Simpan tetapan penghantaran</span>
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
        const initialZones = <?php echo json_encode($deliveryConfig['zones'], JSON_UNESCAPED_UNICODE); ?>;
        let zones = Array.isArray(initialZones) && initialZones.length > 0 ? initialZones : [];

        const suggestedAreas = [
            '+ Pusat bandar',
            '+ Taman perumahan berdekatan',
            '+ Kawasan industri',
            '+ Pinggir bandar'
        ];

        const container = document.getElementById('deliveryZonesContainer');
        const zonesJsonField = document.getElementById('zonesJsonField');
        const addZoneBtn = document.getElementById('addDeliveryZoneBtn');
        const form = document.getElementById('deliverySettingsForm');

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function syncHiddenJson() {
            if (zonesJsonField) {
                zonesJsonField.value = JSON.stringify(zones);
            }
        }

        function renderZones() {
            if (!container) return;
            syncHiddenJson();

            container.innerHTML = zones.map(function (zone, idx) {
                const num = idx + 1;
                const isOut = Boolean(Number(zone.out_of_coverage || 0));
                const isActive = Boolean(Number(zone.active ?? 1));
                const feeVal = Number(zone.fee || 0);
                const minOrderVal = Number(zone.min_order || 0);
                const areas = Array.isArray(zone.areas) ? zone.areas : [];

                const chipsHtml = areas.map(function (area, aIdx) {
                    return (
                        '<span class="badge rounded-pill px-3 py-2 fw-medium d-inline-flex align-items-center gap-1" ' +
                        'style="background-color: #e9f3ec; color: #143326;">' +
                            '<i class="bi bi-geo-alt"></i>' +
                            '<span>' + escapeHtml(area) + '</span>' +
                            '<button type="button" class="btn btn-sm p-0 ms-1 border-0 bg-transparent remove-area-btn" ' +
                            'data-zidx="' + idx + '" data-aidx="' + aIdx + '" aria-label="Buang kawasan" ' +
                            'style="color: #143326; line-height: 1;">&times;</button>' +
                        '</span>'
                    );
                }).join('');

                const suggestionsHtml = suggestedAreas.map(function (sug) {
                    const cleanSug = sug.replace(/^\+\s*/, '');
                    return (
                        '<button type="button" class="btn btn-sm rounded-pill border bg-white px-2 py-1 small suggest-area-btn" ' +
                        'data-zidx="' + idx + '" data-area="' + escapeHtml(cleanSug) + '" ' +
                        'style="border-color: #e4e9e5 !important; color: #6b7c73; font-size: 0.78rem;">' +
                            escapeHtml(sug) +
                        '</button>'
                    );
                }).join('');

                const footerNoteHtml = isOut
                    ? '<div class="small mt-2" style="color: #6b7c73;">Pelanggan di zon ini tidak boleh membuat pesanan penghantaran.</div>'
                    : '<div class="small mt-2" style="color: #6b7c73;">Caj RM ' + feeVal.toFixed(2) + ' &middot; minimum pesanan RM ' + minOrderVal.toFixed(2) + '</div>';

                return (
                    '<div class="border rounded-4 p-3 p-sm-4" style="background-color: #fcfdfb; border-color: #e6ebe7 !important;">' +
                        '<div class="d-flex align-items-center justify-content-between gap-2 mb-3">' +
                            '<div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">' +
                                '<span class="badge rounded-circle d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" ' +
                                'style="width: 26px; height: 26px; background-color: #e9f3ec; color: #143326;">' + num + '</span>' +
                                '<div class="flex-grow-1 min-w-0">' +
                                    '<label class="form-label small fw-semibold mb-1" style="color: #13261d;">Nama zon</label>' +
                                    '<input type="text" class="form-control rounded-3 bg-white zone-name-input" ' +
                                    'data-zidx="' + idx + '" value="' + escapeHtml(zone.name || '') + '" ' +
                                    'style="border-color: #dce2da !important;">' +
                                '</div>' +
                            '</div>' +

                            '<div class="d-flex align-items-center gap-2 flex-shrink-0 pt-3">' +
                                '<div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2">' +
                                    '<input class="form-check-input zone-active-switch" type="checkbox" role="switch" ' +
                                    'data-zidx="' + idx + '" ' + (isActive ? 'checked' : '') + ' ' +
                                    'style="width: 2.3em; height: 1.2em; cursor: pointer; background-color: #143326; border-color: #143326;">' +
                                    '<span class="small fw-medium" style="color: #13261d;">Aktif</span>' +
                                '</div>' +
                                '<button type="button" class="btn btn-sm btn-link p-1 delete-zone-btn" ' +
                                'data-zidx="' + idx + '" title="Padam zon" aria-label="Padam zon" style="color: #6b7c73;">' +
                                    '<i class="bi bi-trash"></i>' +
                                '</button>' +
                            '</div>' +
                        '</div>' +

                        '<div class="row g-3 align-items-end mb-3">' +
                            '<div class="col-12 col-md-5">' +
                                '<label class="form-label small fw-semibold" style="color: #13261d;">Caj penghantaran (RM)</label>' +
                                (isOut
                                    ? '<input type="text" class="form-control rounded-3 bg-light" value="Tidak tersedia" disabled style="border-color: #dce2da !important;">'
                                    : '<input type="number" step="1" min="0" class="form-control rounded-3 bg-white zone-fee-input" ' +
                                      'data-zidx="' + idx + '" value="' + escapeHtml(String(feeVal)) + '" style="border-color: #dce2da !important;">') +
                            '</div>' +

                            '<div class="col-12 col-md-5">' +
                                '<label class="form-label small fw-semibold" style="color: #13261d;">Minimum pesanan (RM)</label>' +
                                '<input type="number" step="1" min="0" class="form-control rounded-3 bg-white zone-min-input" ' +
                                'data-zidx="' + idx + '" value="' + escapeHtml(String(minOrderVal)) + '" style="border-color: #dce2da !important;">' +
                            '</div>' +

                            '<div class="col-12 col-md-2 d-flex align-items-center justify-content-md-end pb-1">' +
                                '<div class="form-check form-switch m-0 d-inline-flex align-items-center gap-2">' +
                                    '<input class="form-check-input zone-out-switch" type="checkbox" role="switch" ' +
                                    'data-zidx="' + idx + '" ' + (isOut ? 'checked' : '') + ' ' +
                                    'style="width: 2.3em; height: 1.2em; cursor: pointer; ' + (isOut ? 'background-color: #143326; border-color: #143326;' : '') + '">' +
                                    '<span class="small fw-medium text-nowrap" style="color: #13261d;">Luar liputan</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +

                        '<div class="mb-2">' +
                            '<label class="form-label small fw-semibold d-block" style="color: #13261d;">Kawasan diliputi</label>' +
                            '<div class="d-flex flex-wrap gap-2 mb-2">' + chipsHtml + '</div>' +
                            '<div class="d-flex gap-2">' +
                                '<input type="text" class="form-control rounded-3 bg-white zone-area-input" ' +
                                'data-zidx="' + idx + '" placeholder="Taip nama kawasan" style="border-color: #dce2da !important;">' +
                                '<button type="button" class="btn btn-sm rounded-3 border bg-white px-3 fw-semibold add-area-btn flex-shrink-0" ' +
                                'data-zidx="' + idx + '" style="border-color: #dce2da !important; color: #13261d;">Tambah</button>' +
                            '</div>' +
                        '</div>' +

                        '<div class="d-flex flex-wrap align-items-center gap-1 mt-2">' +
                            '<span class="small me-1" style="color: #6b7c73;">Cadangan:</span>' +
                            suggestionsHtml +
                        '</div>' +

                        footerNoteHtml +
                    '</div>'
                );
            }).join('');

            bindZoneEvents();
        }

        function addAreaToZone(zIdx, areaName) {
            const clean = String(areaName || '').trim();
            if (!clean || !zones[zIdx]) return;
            if (!Array.isArray(zones[zIdx].areas)) zones[zIdx].areas = [];
            if (!zones[zIdx].areas.includes(clean)) {
                zones[zIdx].areas.push(clean);
            }
            renderZones();
        }

        function bindZoneEvents() {
            container.querySelectorAll('.zone-name-input').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    const idx = Number(inp.getAttribute('data-zidx'));
                    if (zones[idx]) {
                        zones[idx].name = inp.value;
                        syncHiddenJson();
                    }
                });
            });

            container.querySelectorAll('.zone-active-switch').forEach(function (sw) {
                sw.addEventListener('change', function () {
                    const idx = Number(sw.getAttribute('data-zidx'));
                    if (zones[idx]) {
                        zones[idx].active = sw.checked ? 1 : 0;
                        syncHiddenJson();
                    }
                });
            });

            container.querySelectorAll('.zone-fee-input').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    const idx = Number(inp.getAttribute('data-zidx'));
                    if (zones[idx]) {
                        zones[idx].fee = Math.max(0, Number(inp.value || 0));
                        syncHiddenJson();
                    }
                });
            });

            container.querySelectorAll('.zone-min-input').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    const idx = Number(inp.getAttribute('data-zidx'));
                    if (zones[idx]) {
                        zones[idx].min_order = Math.max(0, Number(inp.value || 0));
                        syncHiddenJson();
                    }
                });
            });

            container.querySelectorAll('.zone-out-switch').forEach(function (sw) {
                sw.addEventListener('change', function () {
                    const idx = Number(sw.getAttribute('data-zidx'));
                    if (zones[idx]) {
                        zones[idx].out_of_coverage = sw.checked ? 1 : 0;
                        if (sw.checked) zones[idx].fee = 0;
                        renderZones();
                    }
                });
            });

            container.querySelectorAll('.delete-zone-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const idx = Number(btn.getAttribute('data-zidx'));
                    zones.splice(idx, 1);
                    renderZones();
                });
            });

            container.querySelectorAll('.remove-area-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const zIdx = Number(btn.getAttribute('data-zidx'));
                    const aIdx = Number(btn.getAttribute('data-aidx'));
                    if (zones[zIdx] && Array.isArray(zones[zIdx].areas)) {
                        zones[zIdx].areas.splice(aIdx, 1);
                        renderZones();
                    }
                });
            });

            container.querySelectorAll('.add-area-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const zIdx = Number(btn.getAttribute('data-zidx'));
                    const inp = container.querySelector('.zone-area-input[data-zidx="' + zIdx + '"]');
                    if (inp) {
                        addAreaToZone(zIdx, inp.value);
                        inp.value = '';
                    }
                });
            });

            container.querySelectorAll('.zone-area-input').forEach(function (inp) {
                inp.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const zIdx = Number(inp.getAttribute('data-zidx'));
                        addAreaToZone(zIdx, inp.value);
                        inp.value = '';
                    }
                });
            });

            container.querySelectorAll('.suggest-area-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const zIdx = Number(btn.getAttribute('data-zidx'));
                    const areaName = btn.getAttribute('data-area') || '';
                    addAreaToZone(zIdx, areaName);
                });
            });
        }

        if (addZoneBtn) {
            addZoneBtn.addEventListener('click', function () {
                const nextNum = zones.length + 1;
                zones.push({
                    name: 'Zon ' + nextNum + ' - Kawasan baharu',
                    fee: 5,
                    min_order: 15,
                    active: 1,
                    out_of_coverage: 0,
                    areas: []
                });
                renderZones();
            });
        }

        if (form) {
            form.addEventListener('submit', function () {
                syncHiddenJson();
            });
        }

        renderZones();
    });
    </script>
</body>
</html>