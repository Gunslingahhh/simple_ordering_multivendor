<?php
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentVendorPage = 'products.php';
$vendorRoleLabel = 'Panel peniaga';

function resolveProductImage(string $imageFilename, string $productName): string
{
    $trimmed = trim($imageFilename);
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 3) ?: 'PR');
    $fallback = 'https://placehold.co/96x96/f4f5ef/0e1f17?text=' . urlencode($code);

    if ($trimmed === '' || strtolower($trimmed) === 'default_food.png') {
        return $fallback;
    }
    if (preg_match('#^https?://#i', $trimmed)) {
        return $trimmed;
    }

    $cleanRel = ltrim(str_replace('\\', '/', $trimmed), '/');
    $candidates = [
        '../uploads/menu/' . basename($cleanRel),
        '../uploads/' . basename($cleanRel),
        '../assets/images/' . basename($cleanRel),
        '../' . $cleanRel,
    ];

    foreach ($candidates as $relPath) {
        if (file_exists(__DIR__ . '/' . $relPath) && is_file(__DIR__ . '/' . $relPath)) {
            return $relPath;
        }
    }

    return $fallback;
}

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

$flashSuccess = '';
$flashError = '';

// Handle POST actions (Add, Edit, Toggle Availability, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_product') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 10);
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;

            if ($name === '' || $price < 0) {
                $flashError = 'Sila masukkan nama produk dan harga yang sah.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO menu_items (vendor_id, name, description, price, stock, image, is_available)
                    VALUES (:vendor_id, :name, :description, :price, :stock, 'default_food.png', :is_available)
                ");
                $stmt->execute([
                    ':vendor_id' => $activeVendorId,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':stock' => max(0, $stock),
                    ':is_available' => $isAvailable,
                ]);
                $flashSuccess = 'Produk berjaya ditambah.';
            }
        } elseif ($action === 'edit_product') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = (float) ($_POST['price'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;

            if ($productId > 0 && $name !== '' && $price >= 0) {
                $stmt = $pdo->prepare("
                    UPDATE menu_items
                    SET name = :name,
                        description = :description,
                        price = :price,
                        stock = :stock,
                        is_available = :is_available
                    WHERE id = :id AND vendor_id = :vendor_id
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':stock' => max(0, $stock),
                    ':is_available' => $isAvailable,
                    ':id' => $productId,
                    ':vendor_id' => $activeVendorId,
                ]);
                $flashSuccess = 'Produk berjaya dikemas kini.';
            } else {
                $flashError = 'Maklumat kemas kini produk tidak sah.';
            }
        } elseif ($action === 'toggle_availability') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $newState = ((string) ($_POST['next_available'] ?? '0') === '1') ? 1 : 0;

            if ($productId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE menu_items
                    SET is_available = :is_available
                    WHERE id = :id AND vendor_id = :vendor_id
                ");
                $stmt->execute([
                    ':is_available' => $newState,
                    ':id' => $productId,
                    ':vendor_id' => $activeVendorId,
                ]);
                $flashSuccess = $newState === 1 ? 'Status produk ditukar kepada Tersedia.' : 'Status produk ditukar kepada Habis.';
            }
        } elseif ($action === 'delete_product') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            if ($productId > 0) {
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = :id AND vendor_id = :vendor_id");
                $stmt->execute([
                    ':id' => $productId,
                    ':vendor_id' => $activeVendorId,
                ]);
                $flashSuccess = 'Produk berjaya dipadam.';
            }
        }
    } catch (PDOException $e) {
        $flashError = 'Ralat pangkalan data: ' . $e->getMessage();
    }
}

// Fetch vendor & menu_items from `food_ordering_db`
$vendor = [
    'id' => $activeVendorId,
    'token' => '9226f954b98b2ae5bae55d8501d8d716',
    'store_name' => $_SESSION['vendor_store_name'] ?? 'Roti Berkaus',
];

$products = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $vStmt = $pdo->prepare("SELECT id, token, store_name FROM vendors WHERE id = :id LIMIT 1");
        $vStmt->execute([':id' => $activeVendorId]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $vendor = array_merge($vendor, $vRow);
            $_SESSION['vendor_store_name'] = $vendor['store_name'];
        }

        $pStmt = $pdo->prepare("
            SELECT id, vendor_id, name, description, price, stock, image, is_available
            FROM menu_items
            WHERE vendor_id = :vendor_id
            ORDER BY id ASC
        ");
        $pStmt->execute([':vendor_id' => $activeVendorId]);
        $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $flashError = 'Gagal memuatkan senarai produk.';
    }
}

$vendorStoreName = (string) $vendor['store_name'];
$storeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $vendorStoreName), 0, 2) ?: 'RB');
$vendorLogoUrl = 'https://placehold.co/64x64/14261d/9df557?text=' . urlencode($storeCode);
$productCount = count($products);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk — <?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?> | Kedai WA Kamek</title>
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

                    <!-- Page Top Bar (`Panel peniaga` / `Produk` + `Lihat kedai`) -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="small mb-1" style="color: #6b7c73;">Panel peniaga</div>
                            <h1 class="h3 fw-bold mb-0" style="color: #0e1f17; letter-spacing: -0.02em;">Produk</h1>
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

                    <!-- Main Produk Card Container -->
                    <div class="card border rounded-4 bg-white p-3 p-sm-4" style="border-color: #e4e9e5 !important;">

                        <!-- Card Header (`Produk` + count + `+ Tambah produk`) -->
                        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1" style="color: #0e1f17;">Produk</h2>
                                <div class="small" style="color: #6b7c73;">
                                    <?php echo (int) $productCount; ?> produk
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 d-inline-flex align-items-center gap-2 align-self-start"
                                data-bs-toggle="modal"
                                data-bs-target="#addProductModal"
                                style="background-color: #143326; color: #ffffff;">
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambah produk</span>
                            </button>
                        </div>

                        <!-- Search Input (`Cari produk`) -->
                        <div class="mb-4" style="max-width: 340px;">
                            <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                                 style="border-color: #dce2da !important;">
                                <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                                <input
                                    type="search"
                                    id="productSearchInput"
                                    class="form-control border-0 shadow-none bg-transparent p-0 small"
                                    placeholder="Cari produk"
                                    aria-label="Cari produk"
                                    style="color: #13261d;"
                                >
                            </div>
                        </div>

                        <!-- Product Rows List -->
                        <?php if (empty($products)): ?>
                            <div class="text-center py-5 small" style="color: #6b7c73;">
                                Belum ada produk dalam menu. Klik <strong>+ Tambah produk</strong> untuk menambah item baharu.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column divide-y" id="productRowsList">
                                <?php foreach ($products as $prod): ?>
                                    <?php
                                    $pId = (int) $prod['id'];
                                    $pName = (string) $prod['name'];
                                    $pDesc = (string) ($prod['description'] ?? '');
                                    $pPrice = (float) $prod['price'];
                                    $pStock = (int) ($prod['stock'] ?? 0);
                                    $isAvail = !empty($prod['is_available']) && ($pStock > 0);
                                    $imgUrl = resolveProductImage((string) ($prod['image'] ?? ''), $pName);
                                    $nextAvail = !empty($prod['is_available']) ? '0' : '1';
                                    ?>
                                    <div class="product-row-item d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3 border-top"
                                         data-search="<?php echo htmlspecialchars(strtolower($pName . ' ' . $pDesc), ENT_QUOTES, 'UTF-8'); ?>"
                                         style="border-color: #eef2ef !important;">

                                        <!-- Left: Thumbnail + Name + Price & Description -->
                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                            <img
                                                src="<?php echo htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="<?php echo htmlspecialchars($pName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                class="rounded-3 object-fit-cover flex-shrink-0 border"
                                                style="width: 56px; height: 56px; border-color: #e4e9e5 !important;"
                                            >
                                            <div class="min-w-0">
                                                <div class="fw-bold small text-truncate" style="color: #0e1f17;">
                                                    <?php echo htmlspecialchars($pName, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="small text-truncate" style="color: #6b7c73;">
                                                    <span class="fw-semibold" style="color: #13261d;">
                                                        RM <?php echo number_format($pPrice, 2); ?>
                                                    </span>
                                                    <?php if ($pDesc !== ''): ?>
                                                        <span class="mx-1">&middot;</span>
                                                        <span><?php echo htmlspecialchars($pDesc, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right: Status Badge + Switch + Edit + Delete -->
                                        <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-3 flex-shrink-0">

                                            <span class="badge rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1"
                                                  style="<?php echo $isAvail
                                                      ? 'background-color: #dcfce7; color: #15803d;'
                                                      : 'background-color: #fee2e2; color: #b91c1c;'; ?>">
                                                <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i>
                                                <span><?php echo $isAvail ? 'Tersedia' : 'Habis'; ?></span>
                                            </span>

                                            <!-- Toggle Availability Form -->
                                            <form method="POST" action="products.php" class="d-inline m-0">
                                                <input type="hidden" name="action" value="toggle_availability">
                                                <input type="hidden" name="product_id" value="<?php echo $pId; ?>">
                                                <input type="hidden" name="next_available" value="<?php echo $nextAvail; ?>">
                                                <div class="form-check form-switch m-0">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        role="switch"
                                                        aria-label="Tukar status tersedia atau habis"
                                                        <?php echo !empty($prod['is_available']) ? 'checked' : ''; ?>
                                                        onchange="this.form.submit()"
                                                        style="width: 2.4em; height: 1.25em; cursor: pointer; <?php echo !empty($prod['is_available']) ? 'background-color: #143326; border-color: #143326;' : ''; ?>"
                                                    >
                                                </div>
                                            </form>

                                            <!-- Edit Button -->
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-link p-1 edit-product-btn"
                                                title="Sunting produk"
                                                aria-label="Sunting produk"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editProductModal"
                                                data-id="<?php echo $pId; ?>"
                                                data-name="<?php echo htmlspecialchars($pName, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-description="<?php echo htmlspecialchars($pDesc, ENT_QUOTES, 'UTF-8'); ?>"
                                                data-price="<?php echo htmlspecialchars(number_format($pPrice, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-stock="<?php echo $pStock; ?>"
                                                data-available="<?php echo !empty($prod['is_available']) ? '1' : '0'; ?>"
                                                style="color: #6b7c73;">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <form method="POST" action="products.php" class="d-inline m-0"
                                                  onsubmit="return confirm('Padam produk ini?');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?php echo $pId; ?>">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-link p-1"
                                                    title="Padam produk"
                                                    aria-label="Padam produk"
                                                    style="color: #6b7c73;">
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

    <!-- Modal: Tambah Produk -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="products.php">
                    <input type="hidden" name="action" value="add_product">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="addProductModalLabel">Tambah Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_prod_name" class="form-label small fw-semibold">Nama Produk</label>
                            <input type="text" class="form-control rounded-3" id="add_prod_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_prod_desc" class="form-label small fw-semibold">Penerangan / Kategori</label>
                            <input type="text" class="form-control rounded-3" id="add_prod_desc" name="description" placeholder="cth: Makanan / Minuman / Sampingan">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="add_prod_price" class="form-label small fw-semibold">Harga (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="add_prod_price" name="price" required>
                            </div>
                            <div class="col-6">
                                <label for="add_prod_stock" class="form-label small fw-semibold">Stok</label>
                                <input type="number" min="0" class="form-control rounded-3" id="add_prod_stock" name="stock" value="10" required>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_prod_avail" name="is_available" value="1" checked>
                            <label class="form-check-label small" for="add_prod_avail">Tersedia untuk pesanan</label>
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

    <!-- Modal: Sunting Produk -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <form method="POST" action="products.php">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="edit_prod_id" value="">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editProductModalLabel">Sunting Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_prod_name" class="form-label small fw-semibold">Nama Produk</label>
                            <input type="text" class="form-control rounded-3" id="edit_prod_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_prod_desc" class="form-label small fw-semibold">Penerangan / Kategori</label>
                            <input type="text" class="form-control rounded-3" id="edit_prod_desc" name="description">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_prod_price" class="form-label small fw-semibold">Harga (RM)</label>
                                <input type="number" step="0.01" min="0" class="form-control rounded-3" id="edit_prod_price" name="price" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_prod_stock" class="form-label small fw-semibold">Stok</label>
                                <input type="number" min="0" class="form-control rounded-3" id="edit_prod_stock" name="stock" required>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_prod_avail" name="is_available" value="1">
                            <label class="form-check-label small" for="edit_prod_avail">Tersedia untuk pesanan</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn rounded-3 fw-semibold border-0" style="background-color: #143326; color: #ffffff;">Kemas Kini</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('productSearchInput');
        const rows = document.querySelectorAll('.product-row-item');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    const hay = row.getAttribute('data-search') || '';
                    row.classList.toggle('d-none', q !== '' && !hay.includes(q));
                });
            });
        }

        document.querySelectorAll('.edit-product-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_prod_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_prod_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('edit_prod_desc').value = btn.getAttribute('data-description') || '';
                document.getElementById('edit_prod_price').value = btn.getAttribute('data-price') || '0.00';
                document.getElementById('edit_prod_stock').value = btn.getAttribute('data-stock') || '0';
                document.getElementById('edit_prod_avail').checked = (btn.getAttribute('data-available') === '1');
            });
        });
    });
    </script>
</body>
</html>