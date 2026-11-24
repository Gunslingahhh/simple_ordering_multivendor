<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminPage = 'vendors.php';
$adminRoleLabel = 'Panel admin';

if (!empty($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: ../vendor/dashboard.php');
    exit;
}

function resolveAdminVendorAvatar(string $logoValue, string $storeName): string
{
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 2) ?: 'KD');
    $fallback = 'https://placehold.co/96x96/fef9c3/143326?text=' . urlencode($code);
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

$flashSuccess = '';
$flashError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'register_vendor') {
            $storeName = trim((string) ($_POST['store_name'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? 'vendor123');
            $description = trim((string) ($_POST['description'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? 'Kuching'));
            $contactNumber = trim((string) ($_POST['contact_number'] ?? ''));
            $whatsappNumber = trim((string) ($_POST['whatsapp_number'] ?? ''));
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($storeName === '' || $username === '') {
                $flashError = 'Nama kedai dan username peniaga wajib diisi.';
            } else {
                $pdo->beginTransaction();

                $uChk = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
                $uChk->execute([':username' => $username]);
                $existingUser = $uChk->fetch(PDO::FETCH_ASSOC);

                if ($existingUser) {
                    $vendorUserId = (int) $existingUser['id'];
                } else {
                    $hashPass = password_hash($password !== '' ? $password : 'vendor123', PASSWORD_DEFAULT);
                    $uIns = $pdo->prepare("
                        INSERT INTO users (username, password, role)
                        VALUES (:username, :password, 'vendor')
                    ");
                    $uIns->execute([
                        ':username' => $username,
                        ':password' => $hashPass,
                    ]);
                    $vendorUserId = (int) $pdo->lastInsertId();
                }

                $token = bin2hex(random_bytes(16));
                $vIns = $pdo->prepare("
                    INSERT INTO vendors (
                        token, user_id, store_name, description, address,
                        contact_number, whatsapp_number, logo, status, is_active
                    ) VALUES (
                        :token, :user_id, :store_name, :description, :address,
                        :contact_number, :whatsapp_number, '', 'open', :is_active
                    )
                ");
                $vIns->execute([
                    ':token' => $token,
                    ':user_id' => $vendorUserId,
                    ':store_name' => $storeName,
                    ':description' => $description,
                    ':address' => $address,
                    ':contact_number' => $contactNumber !== '' ? $contactNumber : $whatsappNumber,
                    ':whatsapp_number' => $whatsappNumber,
                    ':is_active' => $isActive,
                ]);

                $pdo->commit();
                $flashSuccess = 'Peniaga baharu berjaya didaftarkan.';
            }
        } elseif ($action === 'edit_vendor') {
            $vendorId = (int) ($_POST['vendor_id'] ?? 0);
            $storeName = trim((string) ($_POST['store_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $whatsappNumber = trim((string) ($_POST['whatsapp_number'] ?? ''));
            $status = in_array(($_POST['status'] ?? 'open'), ['open', 'closed'], true) ? (string) $_POST['status'] : 'open';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($vendorId > 0 && $storeName !== '') {
                $stmt = $pdo->prepare("
                    UPDATE vendors
                    SET store_name = :store_name,
                        description = :description,
                        address = :address,
                        whatsapp_number = :whatsapp_number,
                        status = :status,
                        is_active = :is_active
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':store_name' => $storeName,
                    ':description' => $description,
                    ':address' => $address,
                    ':whatsapp_number' => $whatsappNumber,
                    ':status' => $status,
                    ':is_active' => $isActive,
                    ':id' => $vendorId,
                ]);
                $flashSuccess = 'Maklumat peniaga berjaya dikemas kini.';
            } else {
                $flashError = 'Maklumat peniaga tidak sah.';
            }
        } elseif ($action === 'toggle_vendor_active') {
            $vendorId = (int) ($_POST['vendor_id'] ?? 0);
            $nextActive = ((string) ($_POST['next_active'] ?? '0') === '1') ? 1 : 0;

            if ($vendorId > 0) {
                $stmt = $pdo->prepare("UPDATE vendors SET is_active = :is_active WHERE id = :id");
                $stmt->execute([
                    ':is_active' => $nextActive,
                    ':id' => $vendorId,
                ]);
                $flashSuccess = $nextActive === 1 ? 'Peniaga diaktifkan.' : 'Peniaga dijeda (tidak aktif).';
            }
        } elseif ($action === 'impersonate_vendor') {
            $vendorId = (int) ($_POST['vendor_id'] ?? 0);
            if ($vendorId > 0) {
                $vStmt = $pdo->prepare("SELECT id, user_id, store_name FROM vendors WHERE id = :id LIMIT 1");
                $vStmt->execute([':id' => $vendorId]);
                $vFound = $vStmt->fetch(PDO::FETCH_ASSOC);
                if ($vFound) {
                    $_SESSION['admin_return_role'] = $_SESSION['role'] ?? 'admin';
                    $_SESSION['vendor_id'] = (int) $vFound['id'];
                    $_SESSION['vendor_store_name'] = (string) $vFound['store_name'];
                    header('Location: ../vendor/dashboard.php');
                    exit;
                }
            }
        } elseif ($action === 'delete_vendor') {
            $vendorId = (int) ($_POST['vendor_id'] ?? 0);
            if ($vendorId > 0) {
                $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = :id");
                $stmt->execute([':id' => $vendorId]);
                $flashSuccess = 'Peniaga berjaya dipadam.';
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

$vendors = [];
$platformName = 'Kedai WA Kamek';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $psStmt = $pdo->query("SELECT platform_name FROM platform_settings WHERE id = 1 LIMIT 1");
        $psRow = $psStmt->fetch(PDO::FETCH_ASSOC);
        if ($psRow && !empty($psRow['platform_name'])) {
            $platformName = (string) $psRow['platform_name'];
        }

        $vStmt = $pdo->query("
            SELECT
                v.id,
                v.token,
                v.user_id,
                v.store_name,
                v.description,
                v.address,
                v.contact_number,
                v.whatsapp_number,
                v.logo,
                v.status,
                v.is_active,
                v.created_at,
                u.username,
                (SELECT COUNT(*) FROM menu_items m WHERE m.vendor_id = v.id) AS product_count
            FROM vendors v
            LEFT JOIN users u ON u.id = v.user_id
            ORDER BY v.id ASC
        ");
        $vendors = $vStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan senarai peniaga.';
    }
}

$vendorCount = count($vendors);
$firstVendorToken = !empty($vendors[0]['token']) ? (string) $vendors[0]['token'] : '';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peniaga — <?php echo htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8'); ?></title>
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
            <main class="flex-grow-1 h-100 overflow-y-auto p-3 p-sm-4 p-lg-5 d-flex flex-column justify-content-between" style="background-color: #f4f5ef;">
                <div class="container-fluid px-0">

                    <!-- Top Page Bar (`Admin platform` / `Peniaga` + `Lihat kedai`) -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Admin platform</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                                Peniaga
                            </h1>
                        </div>
                        <div>
                            <a href="<?php echo $firstVendorToken !== '' ? '../index.php?token=' . urlencode($firstVendorToken) : '../index.php'; ?>"
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

                    <!-- Main Card: Pengurusan peniaga -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4 mb-4" style="border-color: #e4e9e5 !important;">

                        <!-- Header (`Pengurusan peniaga` + count + `+ Daftar peniaga`) -->
                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Pengurusan peniaga</h2>
                                <div class="small" style="color: #6b7c73;">
                                    <?php echo (int) $vendorCount; ?> peniaga
                                </div>
                            </div>

                            <button type="button"
                                    class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2 align-self-start"
                                    data-bs-toggle="modal"
                                    data-bs-target="#registerVendorModal"
                                    style="background-color: #143326; color: #ffffff;">
                                <i class="bi bi-plus-lg"></i>
                                <span>Daftar peniaga</span>
                            </button>
                        </div>

                        <!-- Search Input (`Cari nama, slug atau bandar`) -->
                        <div class="mb-4" style="max-width: 340px;">
                            <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                                 style="border-color: #dce2da !important;">
                                <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                                <input type="search"
                                       id="vendorSearchInput"
                                       class="form-control border-0 shadow-none bg-transparent p-0 small"
                                       placeholder="Cari nama, slug atau bandar"
                                       aria-label="Cari nama, slug atau bandar"
                                       style="color: #13261d;">
                            </div>
                        </div>

                        <!-- Vendors List -->
                        <?php if (empty($vendors)): ?>
                            <div class="text-center py-5 small" style="color: #6b7c73;">
                                Belum ada peniaga berdaftar dalam pangkalan data.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column" id="vendorManagementList">
                                <?php foreach ($vendors as $v): ?>
                                    <?php
                                    $vId = (int) $v['id'];
                                    $vName = (string) $v['store_name'];
                                    $vSlug = 'v' . str_pad((string) $vId, 3, '0', STR_PAD_LEFT);
                                    $vUserHandle = (string) ($v['username'] ?? ('vendor' . $vId));
                                    $vCity = (string) ($v['address'] ?? 'Kuching');
                                    $vProdCount = (int) ($v['product_count'] ?? 0);
                                    $vToken = (string) ($v['token'] ?? '');
                                    $vActive = ((int) ($v['is_active'] ?? 1) === 1);
                                    $nextActive = $vActive ? '0' : '1';
                                    $avatarSrc = resolveAdminVendorAvatar((string) ($v['logo'] ?? ''), $vName);
                                    $storeLink = $vToken !== '' ? ('../index.php?token=' . urlencode($vToken)) : '../index.php';

                                    $createdTs = strtotime((string) ($v['created_at'] ?? date('Y-m-d')));
                                    $daysLeft = 24;
                                    if ($createdTs !== false) {
                                        $expiryTs = strtotime('+30 days', $createdTs);
                                        $daysLeft = max(0, (int) round(($expiryTs - time()) / 86400));
                                    }
                                    ?>
                                    <div class="vendor-manage-row d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 py-3 border-top"
                                         data-search="<?php echo htmlspecialchars(strtolower($vName . ' ' . $vSlug . ' ' . $vUserHandle . ' ' . $vCity), ENT_QUOTES, 'UTF-8'); ?>"
                                         style="border-color: #eef2ef !important;">

                                        <!-- Left: Avatar + Name/Code + Link/City/Products + Active Subscription Status -->
                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                            <img src="<?php echo htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                                 alt="<?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                 class="rounded-circle object-fit-cover flex-shrink-0 border"
                                                 style="width: 48px; height: 48px; border-color: #e4e9e5 !important;">
                                            <div class="min-w-0">
                                                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                    <span class="fw-bold small" style="color: #0e1f17;">
                                                        <?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <span style="color: #8c9c93; font-size: 0.78rem;">
                                                        <?php echo htmlspecialchars($vSlug, ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </div>

                                                <div class="small text-truncate" style="color: #6b7c73;">
                                                    <span>/index.php?token=<?php echo htmlspecialchars(substr($vToken, 0, 8), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="mx-1">&middot;</span>
                                                    <span><?php echo htmlspecialchars($vCity, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="mx-1">&middot;</span>
                                                    <span><?php echo (int) $vProdCount; ?> produk</span>
                                                </div>

                                                <div class="fw-medium d-flex align-items-center gap-1 mt-1"
                                                     style="font-size: 0.78rem; color: <?php echo $vActive ? '#15803d' : '#b91c1c'; ?>;">
                                                    <i class="bi bi-circle-fill" style="font-size: 0.42rem;"></i>
                                                    <span>
                                                        <?php echo $vActive
                                                            ? ('Langganan aktif &middot; ' . (int) $daysLeft . ' hari lagi')
                                                            : 'Langganan dijeda'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: `Jeda` + Switch + `Aktif` + Copy/Open/Edit/Delete -->
                                        <div class="d-flex align-items-center justify-content-between justify-content-xl-end gap-3 flex-shrink-0">

                                            <div class="d-inline-flex align-items-center gap-2">
                                                <span class="small" style="color: #6b7c73;">Jeda</span>
                                                <form method="POST" action="vendors.php" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="toggle_vendor_active">
                                                    <input type="hidden" name="vendor_id" value="<?php echo $vId; ?>">
                                                    <input type="hidden" name="next_active" value="<?php echo $nextActive; ?>">
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               role="switch"
                                                               aria-label="Status aktif peniaga"
                                                               <?php echo $vActive ? 'checked' : ''; ?>
                                                               onchange="this.form.submit()"
                                                               style="width: 2.35em; height: 1.2em; cursor: pointer; <?php echo $vActive ? 'background-color: #143326; border-color: #143326;' : ''; ?>">
                                                    </div>
                                                </form>
                                                <span class="small fw-semibold"
                                                      style="color: <?php echo $vActive ? '#15803d' : '#6b7c73'; ?>;">
                                                    <?php echo $vActive ? 'Aktif' : 'Jeda'; ?>
                                                </span>
                                            </div>

                                            <div class="d-inline-flex align-items-center gap-1">
                                                <!-- Copy Store Link -->
                                                <button type="button"
                                                        class="btn btn-sm border rounded-3 bg-white px-2 py-1 copy-vendor-link-btn"
                                                        data-link="<?php echo htmlspecialchars('index.php?token=' . $vToken, ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="Salin pautan kedai"
                                                        aria-label="Salin pautan kedai"
                                                        style="border-color: #e4e9e5 !important; color: #37483f;">
                                                    <i class="bi bi-copy"></i>
                                                </button>

                                                <!-- Enter Vendor Panel -->
                                                <form method="POST" action="vendors.php" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="impersonate_vendor">
                                                    <input type="hidden" name="vendor_id" value="<?php echo $vId; ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm border rounded-3 bg-white px-2 py-1"
                                                            title="Buka panel peniaga"
                                                            aria-label="Buka panel peniaga"
                                                            style="border-color: #e4e9e5 !important; color: #37483f;">
                                                        <i class="bi bi-box-arrow-in-right"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit Vendor Modal Trigger -->
                                                <button type="button"
                                                        class="btn btn-sm border rounded-3 bg-white px-2 py-1 edit-vendor-btn"
                                                        title="Sunting peniaga"
                                                        aria-label="Sunting peniaga"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editVendorModal"
                                                        data-id="<?php echo $vId; ?>"
                                                        data-name="<?php echo htmlspecialchars($vName, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-description="<?php echo htmlspecialchars((string) ($v['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-address="<?php echo htmlspecialchars($vCity, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-whatsapp="<?php echo htmlspecialchars((string) ($v['whatsapp_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-status="<?php echo htmlspecialchars((string) ($v['status'] ?? 'open'), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-active="<?php echo $vActive ? '1' : '0'; ?>"
                                                        style="border-color: #e4e9e5 !important; color: #37483f;">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- Delete Vendor -->
                                                <form method="POST" action="vendors.php" class="d-inline m-0"
                                                      onsubmit="return confirm('Padam peniaga ini?');">
                                                    <input type="hidden" name="action" value="delete_vendor">
                                                    <input type="hidden" name="vendor_id" value="<?php echo $vId; ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm border rounded-3 bg-white px-2 py-1 text-danger"
                                                            title="Padam peniaga"
                                                            aria-label="Padam peniaga"
                                                            style="border-color: #e4e9e5 !important;">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>

                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <!-- Footer Caption -->
                <footer class="text-center small py-2" style="color: #8c9c93;">
                    <?php echo htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8'); ?> Admin Platform
                </footer>
            </main>

        </div>
    </div>

    <!-- Modal: Daftar Peniaga -->
    <div class="modal fade" id="registerVendorModal" tabindex="-1" aria-labelledby="registerVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="vendors.php">
                    <input type="hidden" name="action" value="register_vendor">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="registerVendorModalLabel">Daftar Peniaga Baharu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reg_store_name" class="form-label small fw-semibold">Nama Kedai</label>
                            <input type="text" class="form-control rounded-3" id="reg_store_name" name="store_name" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="reg_username" class="form-label small fw-semibold">Username Log Masuk</label>
                                <input type="text" class="form-control rounded-3" id="reg_username" name="username" required>
                            </div>
                            <div class="col-6">
                                <label for="reg_password" class="form-label small fw-semibold">Kata Laluan</label>
                                <input type="password" class="form-control rounded-3" id="reg_password" name="password" value="vendor123" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="reg_whatsapp" class="form-label small fw-semibold">No. WhatsApp</label>
                                <input type="text" class="form-control rounded-3" id="reg_whatsapp" name="whatsapp_number" placeholder="60123456789" required>
                            </div>
                            <div class="col-6">
                                <label for="reg_address" class="form-label small fw-semibold">Bandar / Lokasi</label>
                                <input type="text" class="form-control rounded-3" id="reg_address" name="address" placeholder="Kuching">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reg_desc" class="form-label small fw-semibold">Penerangan Ringkas</label>
                            <input type="text" class="form-control rounded-3" id="reg_desc" name="description">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="reg_active" name="is_active" value="1" checked>
                            <label class="form-check-label small" for="reg_active">Aktifkan kedai serta-merta</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn rounded-3 fw-semibold border-0" style="background-color: #143326; color: #ffffff;">Daftar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Sunting Peniaga -->
    <div class="modal fade" id="editVendorModal" tabindex="-1" aria-labelledby="editVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="vendors.php">
                    <input type="hidden" name="action" value="edit_vendor">
                    <input type="hidden" name="vendor_id" id="edit_vendor_id" value="">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editVendorModalLabel">Sunting Peniaga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_vendor_name" class="form-label small fw-semibold">Nama Kedai</label>
                            <input type="text" class="form-control rounded-3" id="edit_vendor_name" name="store_name" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_vendor_whatsapp" class="form-label small fw-semibold">No. WhatsApp</label>
                                <input type="text" class="form-control rounded-3" id="edit_vendor_whatsapp" name="whatsapp_number">
                            </div>
                            <div class="col-6">
                                <label for="edit_vendor_address" class="form-label small fw-semibold">Bandar / Lokasi</label>
                                <input type="text" class="form-control rounded-3" id="edit_vendor_address" name="address">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_vendor_desc" class="form-label small fw-semibold">Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="edit_vendor_desc" name="description">
                        </div>
                        <div class="mb-3">
                            <label for="edit_vendor_status" class="form-label small fw-semibold">Status Operasi</label>
                            <select class="form-select rounded-3" id="edit_vendor_status" name="status">
                                <option value="open">open</option>
                                <option value="closed">closed</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_vendor_active" name="is_active" value="1">
                            <label class="form-check-label small" for="edit_vendor_active">Akaun aktif</label>
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
        const searchInput = document.getElementById('vendorSearchInput');
        const rows = document.querySelectorAll('.vendor-manage-row');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    const hay = row.getAttribute('data-search') || '';
                    row.classList.toggle('d-none', q !== '' && !hay.includes(q));
                });
            });
        }

        document.querySelectorAll('.copy-vendor-link-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const relUrl = btn.getAttribute('data-link') || '';
                const fullUrl = new URL(relUrl, window.location.origin + window.location.pathname).href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(fullUrl);
                }
            });
        });

        document.querySelectorAll('.edit-vendor-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_vendor_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_vendor_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('edit_vendor_whatsapp').value = btn.getAttribute('data-whatsapp') || '';
                document.getElementById('edit_vendor_address').value = btn.getAttribute('data-address') || '';
                document.getElementById('edit_vendor_desc').value = btn.getAttribute('data-description') || '';
                document.getElementById('edit_vendor_status').value = btn.getAttribute('data-status') || 'open';
                document.getElementById('edit_vendor_active').checked = (btn.getAttribute('data-active') === '1');
            });
        });
    });
    </script>
</body>
</html>