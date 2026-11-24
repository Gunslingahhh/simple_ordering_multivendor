<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentVendorPage = 'subscription.php';
$vendorRoleLabel = 'Panel peniaga';

// Resolve active vendor ID from session or database
$activeVendorId = (int) ($_SESSION['vendor_id'] ?? 0);
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
        // Keep current vendor ID
    }
}
if ($activeVendorId <= 0 && isset($pdo) && $pdo instanceof PDO) {
    try {
        $firstVendorStmt = $pdo->query("SELECT id FROM vendors ORDER BY id ASC LIMIT 1");
        $firstRow = $firstVendorStmt->fetch(PDO::FETCH_ASSOC);
        if ($firstRow) {
            $activeVendorId = (int) $firstRow['id'];
        }
    } catch (PDOException $e) {
        $activeVendorId = 1;
    }
}

$monthsMalay = [
    1 => 'Jan',
    2 => 'Feb',
    3 => 'Mac',
    4 => 'Apr',
    5 => 'Mei',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ogo',
    9 => 'Sep',
    10 => 'Okt',
    11 => 'Nov',
    12 => 'Dis',
];

function formatDateMalay(string $isoDate, array $monthsMalay): string
{
    $ts = strtotime($isoDate);
    if ($ts === false) {
        return $isoDate;
    }
    $day = (int) date('j', $ts);
    $month = $monthsMalay[(int) date('n', $ts)] ?? date('M', $ts);
    $year = date('Y', $ts);
    return $day . ' ' . $month . ' ' . $year;
}

$flashSuccess = '';
$flashError = '';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vendor_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vendor_id INT NOT NULL,
                plan_name VARCHAR(100) NOT NULL DEFAULT 'PELAN BULANAN',
                amount DECIMAL(10,2) NOT NULL DEFAULT 15.00,
                payment_method VARCHAR(100) NOT NULL DEFAULT 'FPX Online Banking',
                days_added INT NOT NULL DEFAULT 30,
                expiry_date DATE NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'Berjaya',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_vendor_id (vendor_id)
            )
        ");
    } catch (PDOException $e) {
        // Ignore if table creation is not permitted
    }
}

// Load real vendor record from `food_ordering_db.vendors`
$vendor = [
    'id' => $activeVendorId,
    'token' => '',
    'store_name' => 'Kedai',
    'status' => 'open',
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s'),
];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vStmt = $pdo->prepare("
            SELECT id, token, store_name, status, is_active, created_at
            FROM vendors
            WHERE id = :id
            LIMIT 1
        ");
        $vStmt->execute([':id' => $activeVendorId]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $vendor = array_merge($vendor, $vRow);
        }
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan maklumat kedai dari pangkalan data.';
    }
}

$monthlyPrice = 15.00;
$cycleDays = 30;
$allowedMethods = [
    'FPX Online Banking',
    'Kad debit / kredit',
    'e-Wallet',
];

function getLatestSubscriptionState(PDO $pdo, int $vendorId, string $vendorCreatedAt, int $cycleDays): array
{
    $history = [];
    $latestExpiry = null;

    try {
        $hStmt = $pdo->prepare("
            SELECT id, plan_name, amount, payment_method, days_added, expiry_date, status, created_at
            FROM vendor_subscriptions
            WHERE vendor_id = :vendor_id
            ORDER BY id DESC
        ");
        $hStmt->execute([':vendor_id' => $vendorId]);
        $history = $hStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($history) && !empty($history[0]['expiry_date'])) {
            $latestExpiry = (string) $history[0]['expiry_date'];
        }
    } catch (PDOException $e) {
        $history = [];
    }

    if ($latestExpiry === null) {
        $createdTs = strtotime($vendorCreatedAt);
        $baseTs = ($createdTs !== false) ? $createdTs : strtotime(date('Y-m-d'));
        $latestExpiry = date('Y-m-d', strtotime('+' . $cycleDays . ' days', $baseTs));
    }

    return [
        'expiry_date' => $latestExpiry,
        'history' => $history,
    ];
}

// Handle real payment (`Bayar RM 15.00`) or real `activation_codes` redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    if ($action === 'pay_subscription') {
        $method = trim((string) ($_POST['payment_method'] ?? 'FPX Online Banking'));
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'FPX Online Banking';
        }

        try {
            $state = getLatestSubscriptionState($pdo, $activeVendorId, (string) $vendor['created_at'], $cycleDays);
            $todayTs = strtotime(date('Y-m-d'));
            $currExpTs = strtotime((string) $state['expiry_date']);
            $baseTs = ($currExpTs !== false && $currExpTs >= $todayTs) ? $currExpTs : $todayTs;
            $newExpiryDate = date('Y-m-d', strtotime('+' . $cycleDays . ' days', $baseTs));

            $insStmt = $pdo->prepare("
                INSERT INTO vendor_subscriptions (
                    vendor_id, plan_name, amount, payment_method, days_added, expiry_date, status
                ) VALUES (
                    :vendor_id, :plan_name, :amount, :payment_method, :days_added, :expiry_date, :status
                )
            ");
            $insStmt->execute([
                ':vendor_id' => $activeVendorId,
                ':plan_name' => 'PELAN BULANAN',
                ':amount' => $monthlyPrice,
                ':payment_method' => $method,
                ':days_added' => $cycleDays,
                ':expiry_date' => $newExpiryDate,
                ':status' => 'Berjaya',
            ]);

            $flashSuccess = 'Bayaran direkodkan (' . $method . '). Tempoh langganan ditambah ' . $cycleDays . ' hari sehingga ' . formatDateMalay($newExpiryDate, $monthsMalay) . '.';
        } catch (PDOException $e) {
            $flashError = 'Ralat merekodkan bayaran: ' . $e->getMessage();
        }
    } elseif ($action === 'redeem_activation_code') {
        $codeInput = strtoupper(trim((string) ($_POST['activation_code'] ?? '')));

        if ($codeInput === '') {
            $flashError = 'Sila masukkan kod pengaktifan.';
        } else {
            try {
                $pdo->beginTransaction();

                $cStmt = $pdo->prepare("
                    SELECT id, code, description, free_months, max_uses, used_count, status
                    FROM activation_codes
                    WHERE UPPER(code) = :code
                    LIMIT 1
                    FOR UPDATE
                ");
                $cStmt->execute([':code' => $codeInput]);
                $codeRow = $cStmt->fetch(PDO::FETCH_ASSOC);

                if (!$codeRow) {
                    $pdo->rollBack();
                    $flashError = 'Kod pengaktifan tidak dijumpai dalam pangkalan data.';
                } elseif (strtolower((string) ($codeRow['status'] ?? '')) !== 'active') {
                    $pdo->rollBack();
                    $flashError = 'Kod pengaktifan tidak aktif.';
                } elseif ((int) ($codeRow['used_count'] ?? 0) >= (int) ($codeRow['max_uses'] ?? 1)) {
                    $pdo->rollBack();
                    $flashError = 'Kod pengaktifan telah mencapai had penggunaan maksimum.';
                } else {
                    $freeMonths = max(1, (int) ($codeRow['free_months'] ?? 1));
                    $addedDays = $freeMonths * $cycleDays;

                    $state = getLatestSubscriptionState($pdo, $activeVendorId, (string) $vendor['created_at'], $cycleDays);
                    $todayTs = strtotime(date('Y-m-d'));
                    $currExpTs = strtotime((string) $state['expiry_date']);
                    $baseTs = ($currExpTs !== false && $currExpTs >= $todayTs) ? $currExpTs : $todayTs;
                    $newExpiryDate = date('Y-m-d', strtotime('+' . $addedDays . ' days', $baseTs));

                    $updCode = $pdo->prepare("
                        UPDATE activation_codes
                        SET used_count = used_count + 1
                        WHERE id = :id
                    ");
                    $updCode->execute([':id' => (int) $codeRow['id']]);

                    $insSub = $pdo->prepare("
                        INSERT INTO vendor_subscriptions (
                            vendor_id, plan_name, amount, payment_method, days_added, expiry_date, status
                        ) VALUES (
                            :vendor_id, :plan_name, :amount, :payment_method, :days_added, :expiry_date, :status
                        )
                    ");
                    $insSub->execute([
                        ':vendor_id' => $activeVendorId,
                        ':plan_name' => 'PELAN BULANAN',
                        ':amount' => 0.00,
                        ':payment_method' => 'Kod: ' . $codeRow['code'],
                        ':days_added' => $addedDays,
                        ':expiry_date' => $newExpiryDate,
                        ':status' => 'Aktif (Kod)',
                    ]);

                    $pdo->commit();
                    $flashSuccess = 'Kod ' . htmlspecialchars((string) $codeRow['code'], ENT_QUOTES, 'UTF-8') . ' berjaya ditebus (+' . $addedDays . ' hari sehingga ' . formatDateMalay($newExpiryDate, $monthsMalay) . ').';
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $flashError = 'Ralat menebus kod pengaktifan: ' . $e->getMessage();
            }
        }
    }
}

// Fetch real subscription state & history from database
$subState = [
    'expiry_date' => date('Y-m-d'),
    'history' => [],
];

if (isset($pdo) && $pdo instanceof PDO) {
    $subState = getLatestSubscriptionState($pdo, $activeVendorId, (string) $vendor['created_at'], $cycleDays);
}

$expiryDateStr = (string) ($subState['expiry_date'] ?? date('Y-m-d'));
$todayMidnight = strtotime(date('Y-m-d'));
$expiryMidnight = strtotime($expiryDateStr);
$daysRemaining = ($expiryMidnight !== false)
    ? max(0, (int) round(($expiryMidnight - $todayMidnight) / 86400))
    : 0;
$isSubActive = ((int) ($vendor['is_active'] ?? 1) === 1) && ($daysRemaining > 0);
$progressPercent = (int) max(5, min(100, round(($daysRemaining / $cycleDays) * 100)));
$formattedExpiry = formatDateMalay($expiryDateStr, $monthsMalay);
$paymentHistory = is_array($subState['history'] ?? null) ? $subState['history'] : [];

$selectedMethod = trim((string) ($_POST['payment_method'] ?? 'FPX Online Banking'));
if (!in_array($selectedMethod, $allowedMethods, true)) {
    $selectedMethod = 'FPX Online Banking';
}

$vendorStoreName = (string) $vendor['store_name'];
$storeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vendorStoreName), 0, 2) ?: 'KW');
$vendorLogoUrl = 'https://placehold.co/64x64/14261d/9df557?text=' . urlencode($storeCode);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langganan — <?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?> | Kedai WA Kamek</title>
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

                    <!-- Top Header (`Panel peniaga` / `Langganan` + `Lihat kedai`) -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel peniaga</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Langganan
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

                    <!-- Card 1: PELAN BULANAN Overview & Progress (Real DB Data) -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <span class="small fw-semibold text-uppercase" style="color: #6b7c73; letter-spacing: 0.04em;">
                                PELAN BULANAN
                            </span>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1"
                                  style="<?php echo $isSubActive
                                      ? 'background-color: #dcfce7; color: #15803d;'
                                      : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i>
                                <span><?php echo $isSubActive ? 'Langganan aktif' : 'Langganan tamat'; ?></span>
                            </span>
                        </div>

                        <div class="d-flex align-items-baseline gap-2 mb-3">
                            <span class="h2 fw-bold mb-0" style="color: #0e1f17;">
                                RM <?php echo number_format($monthlyPrice, 2); ?>
                            </span>
                            <span class="small" style="color: #6b7c73;">/ <?php echo (int) $cycleDays; ?> hari</span>
                        </div>

                        <div class="progress rounded-pill mb-3"
                             style="height: 8px; background-color: #eef2ef;"
                             role="progressbar"
                             aria-label="Baki hari langganan"
                             aria-valuenow="<?php echo $progressPercent; ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <div class="progress-bar rounded-pill"
                                 style="width: <?php echo $progressPercent; ?>%; background-color: #143326;"></div>
                        </div>

                        <div class="small d-flex align-items-center gap-2" style="color: #6b7c73;">
                            <i class="bi bi-calendar3"></i>
                            <span>
                                <?php echo (int) $daysRemaining; ?> hari lagi &middot; tamat <?php echo htmlspecialchars($formattedExpiry, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Card 2: Buat bayaran & Kod Pengaktifan -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                        <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Buat bayaran</h2>
                        <p class="small mb-4" style="color: #6b7c73;">
                            Setiap bayaran menambah <?php echo (int) $cycleDays; ?> hari pada langganan anda.
                        </p>

                        <form method="POST" action="subscription.php" id="subscriptionPaymentForm">
                            <input type="hidden" name="action" value="pay_subscription">
                            <input type="hidden" name="payment_method" id="selectedPaymentMethodInput"
                                   value="<?php echo htmlspecialchars($selectedMethod, ENT_QUOTES, 'UTF-8'); ?>">

                            <?php
                            $methodsConfig = [
                                ['label' => 'FPX Online Banking / DuitNow', 'icon' => 'bi-bank'],
                            ];
                            ?>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <?php foreach ($methodsConfig as $mItem): ?>
                                    <?php $isChecked = ($selectedMethod === $mItem['label']); ?>
                                    <button
                                        type="button"
                                        class="w-100 rounded-4 border p-3 px-sm-4 d-flex align-items-center justify-content-between text-start payment-method-option"
                                        data-method="<?php echo htmlspecialchars($mItem['label'], ENT_QUOTES, 'UTF-8'); ?>"
                                        style="background-color: #ffffff; cursor: pointer; <?php echo $isChecked
                                            ? 'border-color: #143326 !important;'
                                            : 'border-color: #dce2da !important;'; ?>">
                                        <span class="d-inline-flex align-items-center gap-3 fw-medium small" style="color: #0e1f17;">
                                            <i class="bi <?php echo htmlspecialchars($mItem['icon'], ENT_QUOTES, 'UTF-8'); ?> fs-5" style="color: #13261d;"></i>
                                            <span><?php echo htmlspecialchars($mItem['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle border payment-radio-ring"
                                              style="width: 18px; height: 18px; <?php echo $isChecked
                                                  ? 'border-color: #143326 !important;'
                                                  : 'border-color: #b8c2bc !important;'; ?>">
                                            <span class="rounded-circle payment-radio-dot <?php echo $isChecked ? '' : 'd-none'; ?>"
                                                  style="width: 10px; height: 10px; background-color: #143326;"></span>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <button
                                type="submit"
                                class="btn w-100 rounded-3 py-3 fw-semibold border-0 mb-4"
                                style="background-color: #143326; color: #ffffff;">
                                Bayar RM <?php echo number_format($monthlyPrice, 2); ?>
                            </button>
                        </form>

                        <!-- Real `activation_codes` Redemption Form -->
                        <form method="POST" action="subscription.php" class="border-top pt-3" style="border-color: #eef2ef !important;">
                            <input type="hidden" name="action" value="redeem_activation_code">
                            <label for="activation_code" class="form-label small fw-semibold" style="color: #13261d;">
                                Kod pengaktifan
                            </label>
                            <div class="d-flex gap-2">
                                <input type="text"
                                       class="form-control rounded-3 bg-white text-uppercase"
                                       id="activation_code"
                                       name="activation_code"
                                       placeholder="Masukkan kod pengaktifan"
                                       style="border-color: #dce2da !important;">
                                <button type="submit"
                                        class="btn btn-sm rounded-3 border bg-white px-3 fw-semibold flex-shrink-0"
                                        style="border-color: #dce2da !important; color: #0e1f17;">
                                    Tebus kod
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Card 3: Sejarah bayaran (Real DB Rows) -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">
                        <h2 class="h5 fw-bold mb-3" style="color: #0e1f17;">Sejarah bayaran</h2>

                        <?php if (empty($paymentHistory)): ?>
                            <div class="small" style="color: #6b7c73;">
                                Tiada bayaran direkodkan lagi.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0 small">
                                    <thead>
                                        <tr style="color: #6b7c73; border-color: #e6ebe7;">
                                            <th scope="col" class="py-2">Tarikh</th>
                                            <th scope="col" class="py-2">Kaedah</th>
                                            <th scope="col" class="py-2">Tempoh</th>
                                            <th scope="col" class="py-2">Amaun</th>
                                            <th scope="col" class="py-2 text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentHistory as $rec): ?>
                                            <tr style="border-color: #eef2ef;">
                                                <td class="py-2" style="color: #0e1f17;">
                                                    <?php echo htmlspecialchars(formatDateMalay((string) ($rec['created_at'] ?? date('Y-m-d')), $monthsMalay), ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td class="py-2" style="color: #1f3329;">
                                                    <?php echo htmlspecialchars((string) ($rec['payment_method'] ?? 'FPX Online Banking'), ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td class="py-2" style="color: #6b7c73;">
                                                    +<?php echo (int) ($rec['days_added'] ?? 30); ?> hari
                                                </td>
                                                <td class="py-2 fw-bold" style="color: #0e1f17;">
                                                    RM <?php echo number_format((float) ($rec['amount'] ?? 15.00), 2); ?>
                                                </td>
                                                <td class="py-2 text-end">
                                                    <span class="badge rounded-pill px-2 py-1" style="background-color: #dcfce7; color: #15803d;">
                                                        <?php echo htmlspecialchars((string) ($rec['status'] ?? 'Berjaya'), ENT_QUOTES, 'UTF-8'); ?>
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
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const options = document.querySelectorAll('.payment-method-option');
        const hiddenInput = document.getElementById('selectedPaymentMethodInput');

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                const chosen = opt.getAttribute('data-method') || 'FPX Online Banking';
                if (hiddenInput) {
                    hiddenInput.value = chosen;
                }

                options.forEach(function (item) {
                    const active = item === opt;
                    item.style.borderColor = active ? '#143326' : '#dce2da';
                    const ring = item.querySelector('.payment-radio-ring');
                    const dot = item.querySelector('.payment-radio-dot');
                    if (ring) {
                        ring.style.borderColor = active ? '#143326' : '#b8c2bc';
                    }
                    if (dot) {
                        dot.classList.toggle('d-none', !active);
                    }
                });
            });
        });
    });
    </script>
</body>
</html>