<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentAdminPage = 'codes.php';
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
        if ($action === 'add_activation_code') {
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
        } elseif ($action === 'edit_activation_code') {
            $codeId = (int) ($_POST['code_id'] ?? 0);
            $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
            $description = trim((string) ($_POST['description'] ?? ''));
            $freeMonths = max(1, (int) ($_POST['free_months'] ?? 1));
            $maxUses = max(1, (int) ($_POST['max_uses'] ?? 1));
            $status = in_array(($_POST['status'] ?? 'active'), ['active', 'inactive'], true)
                ? (string) $_POST['status']
                : 'active';

            if ($codeId > 0 && $code !== '') {
                $stmt = $pdo->prepare("
                    UPDATE activation_codes
                    SET code = :code,
                        description = :description,
                        free_months = :free_months,
                        max_uses = :max_uses,
                        status = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':code' => $code,
                    ':description' => $description,
                    ':free_months' => $freeMonths,
                    ':max_uses' => $maxUses,
                    ':status' => $status,
                    ':id' => $codeId,
                ]);
                $flashSuccess = 'Kod pengaktifan berjaya dikemas kini.';
            } else {
                $flashError = 'Maklumat kod tidak sah.';
            }
        } elseif ($action === 'toggle_activation_code') {
            $codeId = (int) ($_POST['code_id'] ?? 0);
            $nextStatus = ((string) ($_POST['next_status'] ?? 'inactive') === 'active') ? 'active' : 'inactive';

            if ($codeId > 0) {
                $stmt = $pdo->prepare("UPDATE activation_codes SET status = :status WHERE id = :id");
                $stmt->execute([
                    ':status' => $nextStatus,
                    ':id' => $codeId,
                ]);
                $flashSuccess = $nextStatus === 'active'
                    ? 'Kod pengaktifan diaktifkan.'
                    : 'Kod pengaktifan dinyahaktifkan.';
            }
        } elseif ($action === 'delete_activation_code') {
            $codeId = (int) ($_POST['code_id'] ?? 0);
            if ($codeId > 0) {
                $stmt = $pdo->prepare("DELETE FROM activation_codes WHERE id = :id");
                $stmt->execute([':id' => $codeId]);
                $flashSuccess = 'Kod pengaktifan berjaya dipadam.';
            }
        }
    } catch (PDOException $e) {
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

$codes = [];
$platformName = 'Kedai WA Kamek';

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $psRow = $pdo->query("SELECT platform_name FROM platform_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($psRow && !empty($psRow['platform_name'])) {
            $platformName = (string) $psRow['platform_name'];
        }

        $codes = $pdo->query("
            SELECT id, code, description, free_months, max_uses, used_count, status, created_at
            FROM activation_codes
            ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan senarai kod pengaktifan.';
    }
}

$codeIconPlaceholder = 'https://placehold.co/80x80/f4f5ef/143326?text=' . urlencode('KD');
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kod pengaktifan — <?php echo htmlspecialchars($platformName, ENT_QUOTES, 'UTF-8'); ?></title>
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

                    <!-- Top Page Bar (`Admin platform` / `Kod pengaktifan`) -->
                    <div class="mb-4">
                        <div class="small mb-1" style="color: #6b7c73;">Admin platform</div>
                        <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">
                            Kod pengaktifan
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

                    <!-- Main Card: Kod pengaktifan -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4" style="border-color: #e4e9e5 !important;">

                        <!-- Card Header (`Kod pengaktifan` + subtitle + `+ Kod baharu`) -->
                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3 mb-4">
                            <div>
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Kod pengaktifan</h2>
                                <p class="small mb-0" style="color: #6b7c73;">
                                    Kod penaja untuk pendaftaran kedai percuma. Setiap kod memberi tempoh percuma dan nama penaja kepada kedai.
                                </p>
                            </div>

                            <button type="button"
                                    class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2 align-self-start flex-shrink-0"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addCodeModal"
                                    style="background-color: #143326; color: #ffffff;">
                                <i class="bi bi-plus-lg"></i>
                                <span>Kod baharu</span>
                            </button>
                        </div>

                        <!-- Activation Codes List -->
                        <?php if (empty($codes)): ?>
                            <div class="text-center py-5 small" style="color: #6b7c73;">
                                Tiada kod pengaktifan direkodkan lagi. Klik <strong>+ Kod baharu</strong> untuk menambah kod.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column">
                                <?php foreach ($codes as $row): ?>
                                    <?php
                                    $cId = (int) $row['id'];
                                    $cCode = (string) $row['code'];
                                    $cDesc = trim((string) ($row['description'] ?? ''));
                                    $cMonths = max(1, (int) ($row['free_months'] ?? 1));
                                    $cUsed = (int) ($row['used_count'] ?? 0);
                                    $cMax = max(1, (int) ($row['max_uses'] ?? 1));
                                    $cStatus = strtolower((string) ($row['status'] ?? 'active'));
                                    $isCodeActive = ($cStatus === 'active');
                                    $nextStatus = $isCodeActive ? 'inactive' : 'active';
                                    $rowIconClass = ($cMonths >= 3) ? 'bi-ticket-perforated' : 'bi-gift';
                                    ?>
                                    <div class="d-flex align-items-center justify-content-between gap-3 py-3 border-top"
                                         style="border-color: #eef2ef !important;">

                                        <!-- Left: Square Icon Badge + Code + `Aktif`/`Tidak aktif` Badge + Meta Line -->
                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                            <div class="rounded-3 border d-flex align-items-center justify-content-center flex-shrink-0"
                                                 data-placeholder-src="<?php echo htmlspecialchars($codeIconPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"
                                                 style="width: 42px; height: 42px; background-color: #f8faf9; border-color: #e4e9e5 !important; color: #37483f;">
                                                <i class="bi <?php echo htmlspecialchars($rowIconClass, ENT_QUOTES, 'UTF-8'); ?> fs-6"></i>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="fw-bold small" style="color: #0e1f17;">
                                                        <?php echo htmlspecialchars($cCode, ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <span class="badge rounded-pill px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                                          style="font-size: 0.72rem; <?php echo $isCodeActive
                                                              ? 'background-color: #dcfce7; color: #15803d;'
                                                              : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                                        <i class="bi bi-circle-fill" style="font-size: 0.38rem;"></i>
                                                        <span><?php echo $isCodeActive ? 'Aktif' : 'Tidak aktif'; ?></span>
                                                    </span>
                                                </div>

                                                <div class="small text-truncate mt-1" style="color: #6b7c73;">
                                                    <?php if ($cDesc !== ''): ?>
                                                        <span><?php echo htmlspecialchars($cDesc, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span class="mx-1">&middot;</span>
                                                    <?php endif; ?>
                                                    <span><?php echo (int) $cMonths; ?> bulan percuma</span>
                                                    <span class="mx-1">&middot;</span>
                                                    <span><?php echo (int) $cUsed; ?>/<?php echo (int) $cMax; ?> digunakan</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Toggle Switch + Edit + Delete -->
                                        <div class="d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0">
                                            <form method="POST" action="activation.php" class="d-inline m-0">
                                                <input type="hidden" name="action" value="toggle_activation_code">
                                                <input type="hidden" name="code_id" value="<?php echo $cId; ?>">
                                                <input type="hidden" name="next_status" value="<?php echo htmlspecialchars($nextStatus, ENT_QUOTES, 'UTF-8'); ?>">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           role="switch"
                                                           aria-label="Status kod pengaktifan"
                                                           <?php echo $isCodeActive ? 'checked' : ''; ?>
                                                           onchange="this.form.submit()"
                                                           style="width: 2.35em; height: 1.2em; cursor: pointer; <?php echo $isCodeActive ? 'background-color: #143326; border-color: #143326;' : ''; ?>">
                                                </div>
                                            </form>

                                            <button type="button"
                                                    class="btn btn-sm border-0 bg-transparent p-1 edit-code-btn"
                                                    title="Sunting kod"
                                                    aria-label="Sunting kod"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editCodeModal"
                                                    data-id="<?php echo $cId; ?>"
                                                    data-code="<?php echo htmlspecialchars($cCode, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-description="<?php echo htmlspecialchars($cDesc, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-months="<?php echo (int) $cMonths; ?>"
                                                    data-max-uses="<?php echo (int) $cMax; ?>"
                                                    data-status="<?php echo htmlspecialchars($cStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                                    style="color: #52635a;">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <form method="POST" action="activation.php" class="d-inline m-0"
                                                  onsubmit="return confirm('Padam kod pengaktifan ini?');">
                                                <input type="hidden" name="action" value="delete_activation_code">
                                                <input type="hidden" name="code_id" value="<?php echo $cId; ?>">
                                                <button type="submit"
                                                        class="btn btn-sm border-0 bg-transparent p-1"
                                                        title="Padam kod"
                                                        aria-label="Padam kod"
                                                        style="color: #52635a;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>
            </main>

        </div>
    </div>

    <!-- Modal: Kod Baharu -->
    <div class="modal fade" id="addCodeModal" tabindex="-1" aria-labelledby="addCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="activation.php">
                    <input type="hidden" name="action" value="add_activation_code">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addCodeModalLabel">Tambah Kod Pengaktifan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_code_val" class="form-label small fw-semibold">Kod Penaja</label>
                            <input type="text" class="form-control rounded-3 text-uppercase" id="add_code_val" name="code" placeholder="cth: GEDONG2026" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_code_desc" class="form-label small fw-semibold">Nama Penaja / Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="add_code_desc" name="description" placeholder="cth: N.26 Gedong">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="add_code_months" class="form-label small fw-semibold">Bulan Percuma</label>
                                <input type="number" min="1" class="form-control rounded-3" id="add_code_months" name="free_months" value="3" required>
                            </div>
                            <div class="col-6">
                                <label for="add_code_max_uses" class="form-label small fw-semibold">Had Penggunaan</label>
                                <input type="number" min="1" class="form-control rounded-3" id="add_code_max_uses" name="max_uses" value="50" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="add_code_status" class="form-label small fw-semibold">Status</label>
                            <select class="form-select rounded-3" id="add_code_status" name="status">
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

    <!-- Modal: Sunting Kod -->
    <div class="modal fade" id="editCodeModal" tabindex="-1" aria-labelledby="editCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="activation.php">
                    <input type="hidden" name="action" value="edit_activation_code">
                    <input type="hidden" name="code_id" id="edit_code_id" value="">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editCodeModalLabel">Sunting Kod Pengaktifan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_code_val" class="form-label small fw-semibold">Kod Penaja</label>
                            <input type="text" class="form-control rounded-3 text-uppercase" id="edit_code_val" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_code_desc" class="form-label small fw-semibold">Nama Penaja / Penerangan</label>
                            <input type="text" class="form-control rounded-3" id="edit_code_desc" name="description">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_code_months" class="form-label small fw-semibold">Bulan Percuma</label>
                                <input type="number" min="1" class="form-control rounded-3" id="edit_code_months" name="free_months" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_code_max_uses" class="form-label small fw-semibold">Had Penggunaan</label>
                                <input type="number" min="1" class="form-control rounded-3" id="edit_code_max_uses" name="max_uses" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="edit_code_status" class="form-label small fw-semibold">Status</label>
                            <select class="form-select rounded-3" id="edit_code_status" name="status">
                                <option value="active">active</option>
                                <option value="inactive">inactive</option>
                            </select>
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
        document.querySelectorAll('.edit-code-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_code_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_code_val').value = btn.getAttribute('data-code') || '';
                document.getElementById('edit_code_desc').value = btn.getAttribute('data-description') || '';
                document.getElementById('edit_code_months').value = btn.getAttribute('data-months') || '1';
                document.getElementById('edit_code_max_uses').value = btn.getAttribute('data-max-uses') || '10';
                document.getElementById('edit_code_status').value = btn.getAttribute('data-status') || 'active';
            });
        });
    });
    </script>
</body>
</html>