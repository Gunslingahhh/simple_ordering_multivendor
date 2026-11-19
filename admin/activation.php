<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle real database insertion for a new activation code
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_code'])) {
    $code = trim($_POST['code']);
    $description = trim($_POST['description']);
    $free_months = (int)$_POST['free_months'];
    $max_uses = (int)$_POST['max_uses'];

    if (!empty($code) && !empty($description)) {
        try {
            $stmtIns = $pdo->prepare("INSERT INTO activation_codes (code, description, free_months, max_uses, status) VALUES (?, ?, ?, ?, 'active')");
            $stmtIns->execute([strtoupper($code), $description, $free_months, $max_uses]);
            $success = 'Kod pengaktifan baharu berjaya dicipta!';
        } catch (PDOException $e) {
            $error = 'Ralat: Kod pengaktifan ini sudah wujud di dalam pangkalan data.';
        }
    } else {
        $error = 'Sila lengkapkan semua medan yang diperlukan.';
    }
}

// Fetch real activation codes from database
$stmtCodes = $pdo->query("SELECT * FROM activation_codes ORDER BY id DESC");
$activation_codes = $stmtCodes->fetchAll();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kod Pengaktifan — Admin Platform</title>
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
        .admin-main-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .code-row-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }
        .code-row-item:last-child {
            border-bottom: none;
        }
        .code-row-item:hover {
            background-color: #fafafa;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-kod-baharu {
            background-color: #0b291a;
            color: #ffffff;
            border-radius: 50rem;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 10px rgba(11, 41, 26, 0.2);
            transition: background 0.2s ease;
        }
        .btn-kod-baharu:hover {
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
    
    <!-- Include Admin Sidenav -->
    <?php include 'sidenav.php'; ?>

    <!-- Main Content -->
    <div class="dash-main">
        <!-- Topbar -->
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Admin platform</span>
                <h5 class="fw-bold mb-0 text-dark">Kod pengaktifan</h5>
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

            <div class="admin-main-card">
                <!-- Header Title & Add Button -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-2">
                    <div>
                        <h4 class="fw-bold text-dark mb-1 fs-5">Kod & pautan jemputan</h4>
                        <p class="text-muted small mb-0">Pendaftaran kedai hanya melalui jemputan. Salin pautan jemputan dan hantar kepada peniaga. Kod ditaja memberi bulan percuma; kod 0 bulan ialah jemputan berbayar.</p>
                    </div>
                    <div>
                        <button type="button" class="btn-kod-baharu" data-bs-toggle="modal" data-bs-target="#addCodeModal">
                            <i class="fa-solid fa-plus"></i> Kod baharu
                        </button>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- Activation Codes List -->
                <?php if (empty($activation_codes)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-ticket fs-1 opacity-50 mb-2"></i>
                        <p>Tiada kod pengaktifan dijumpai dalam pangkalan data. Sila klik "Kod baharu" untuk menambah.</p>
                    </div>
                <?php else: ?>
                    <div class="code-list-container">
                        <?php foreach ($activation_codes as $index => $ac): ?>
                            <?php 
                                $invite_link = "http://" . $_SERVER['HTTP_HOST'] . "/simple_ordering_multivendor/register.php?code=" . $ac['code'];
                                $icon_class = ($index % 2 === 0) ? 'fa-building-columns' : 'fa-gift';
                            ?>
                            <div class="code-row-item">
                                <!-- Left: Code Icon & Details -->
                                <div class="d-flex align-items-center gap-3 overflow-hidden">
                                    <div class="bg-light border text-secondary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="fa-solid <?= $icon_class ?> fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($ac['code']) ?></span>
                                            <?php if ($ac['status'] === 'active'): ?>
                                                <span class="text-success small fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="text-secondary small fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="fa-solid fa-circle me-1" style="font-size: 0.45rem;"></i> Tak aktif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small text-truncate">
                                            <?= htmlspecialchars($ac['description']) ?> &bull; <?= (int)$ac['free_months'] ?> bulan percuma &bull; <?= (int)$ac['used_count'] ?>/<?= (int)$ac['max_uses'] ?> digunakan
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Controls & Actions -->
                                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch" <?= $ac['status'] === 'active' ? 'checked' : '' ?> title="Tukar status">
                                    </div>

                                    <div class="d-flex align-items-center gap-2 ms-2">
                                        <button class="btn btn-sm btn-light border text-secondary" title="Salin pautan jemputan" onclick="navigator.clipboard.writeText('<?= $invite_link ?>'); alert('Pautan jemputan berjaya disalin!');">
                                            <i class="fa-solid fa-link" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <a href="edit_code.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-light border text-secondary" title="Sunting">
                                            <i class="fa-solid fa-pen" style="font-size: 0.75rem;"></i>
                                        </a>
                                        <a href="delete_code.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-light border text-danger" title="Padam" onclick="return confirm('Adakah anda pasti mahu memadam kod ini?');">
                                            <i class="fa-solid fa-trash" style="font-size: 0.75rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

            <div class="text-muted text-center small pt-5">
                Kedai WA Kamek Admin Platform
            </div>

        </div>
    </div>

</div>

<!-- Modal Tambah Kod Baharu -->
<div class="modal fade" id="addCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Kod Pengaktifan Baharu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kod Jemputan *</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="cth. KUCHING2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Penerangan / Kempen *</label>
                        <input type="text" name="description" class="form-control" placeholder="cth. Program Usahawan Miri" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bulan Percuma</label>
                            <input type="number" name="free_months" class="form-control" value="1" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Had Penggunaan</label>
                            <input type="number" name="max_uses" class="form-control" value="50" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="create_code" class="btn btn-dark fw-bold px-4" style="background-color: #0b291a;">Cipta Kod</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>