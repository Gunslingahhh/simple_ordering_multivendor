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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Pembayaran langganan berjaya! 30 hari telah ditambahkan ke akaun anda.';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langganan — Panel Peniaga</title>
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
        .subscription-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .payment-method-option {
            border: 1.5px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }
        .payment-method-option.selected {
            border-color: #0b291a;
            background-color: #fcfcfc;
            box-shadow: 0 0 0 1px #0b291a;
        }
        .progress-bar-custom {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 50rem;
            overflow: hidden;
            margin: 1rem 0;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #0b291a;
            width: 80%;
            border-radius: 50rem;
        }
        .btn-bayar {
            background-color: #0b291a;
            color: #ffffff;
            border-radius: 50rem;
            padding: 0.85rem;
            font-weight: 700;
            border: none;
            width: 100%;
            transition: background 0.2s ease;
        }
        .btn-bayar:hover {
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
    
    <?php include 'sidenav.php'; ?>

    <div class="dash-main">
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Panel peniaga</span>
                <h5 class="fw-bold mb-0 text-dark">Langganan</h5>
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

            <!-- Pelan Bulanan Status Card -->
            <div class="subscription-card">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Pelan bulanan</span>
                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-3 py-1.5 rounded-pill" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> Langganan aktif
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-1 mb-2">
                    <h2 class="fw-bold text-dark mb-0" style="font-size: 2rem;">RM 15.00</h2>
                    <span class="text-muted small">/ 30 hari</span>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar-custom">
                    <div class="progress-bar-fill"></div>
                </div>

                <div class="text-muted small">
                    <i class="fa-regular fa-calendar me-1"></i> 24 hari lagi &bull; tamat 12 Dis 2026
                </div>
            </div>

            <!-- Buat Bayaran Card -->
            <div class="subscription-card">
                <h5 class="fw-bold text-dark mb-1">Buat bayaran</h5>
                <p class="text-muted small mb-4">Setiap bayaran menambah 30 hari pada langganan anda.</p>

                <form method="POST">
                    <!-- Payment Options -->
                    <div class="payment-method-option selected" onclick="selectPayment(this, 'fpx')">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-building-columns fs-5 text-success"></i>
                            <span class="fw-semibold text-dark">FPX Online Banking</span>
                        </div>
                        <input class="form-check-input" type="radio" name="payment_method" value="fpx" checked>
                    </div>

                    <div class="payment-method-option" onclick="selectPayment(this, 'card')">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-regular fa-credit-card fs-5 text-muted"></i>
                            <span class="fw-semibold text-dark">Kad debit / kredit</span>
                        </div>
                        <input class="form-check-input" type="radio" name="payment_method" value="card">
                    </div>

                    <div class="payment-method-option mb-4" onclick="selectPayment(this, 'wallet')">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa-solid fa-wallet fs-5 text-muted"></i>
                            <span class="fw-semibold text-dark">e-Wallet</span>
                        </div>
                        <input class="form-check-input" type="radio" name="payment_method" value="wallet">
                    </div>

                    <button type="submit" class="btn btn-bayar shadow-sm">
                        Bayar RM 15.00
                    </button>
                </form>

                <div class="text-muted small text-center mt-3" style="font-size: 0.8rem;">
                    Prototip: bayaran disimulasikan dan tiada caj sebenar dibuat.
                </div>
            </div>

            <!-- Sejarah Bayaran -->
            <div class="subscription-card">
                <h5 class="fw-bold text-dark mb-3">Sejarah bayaran</h5>
                <div class="text-muted small py-3">
                    Tiada bayaran direkodkan lagi.
                </div>
            </div>

            <div class="text-muted text-center small pt-3">
                Kedai WA Kamek Vendor Panel
            </div>

        </div>
    </div>

</div>

<script>
function selectPayment(element, method) {
    document.querySelectorAll('.payment-method-option').forEach(opt => {
        opt.classList.remove('selected');
        opt.querySelector('input[type="radio"]').checked = false;
    });
    element.classList.add('selected');
    element.querySelector('input[type="radio"]').checked = true;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>