<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmtV = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmtV->execute([$user_id]);
$vendor = $stmtV->fetch();
$vendor_id = $vendor['id'];

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmtItem = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND vendor_id = ?");
$stmtItem->execute([$item_id, $vendor_id]);
$item = $stmtItem->fetch();

if (!$item) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $stock = (int)$_POST['stock']; // Added stock capture
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!empty($name) && !empty($price)) {
        $image = $item['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];

            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (in_array($fileType, $allowedTypes) && $fileSize <= 5 * 1024 * 1024) {
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path = '../uploads/menu/' . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $image = $newFileName;
                }
            } else {
                $error = 'Invalid image format or exceeds 5MB.';
            }
        }

        if (empty($error)) {
            $stmtUp = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, stock = ?, image = ?, is_available = ? WHERE id = ? AND vendor_id = ?");
            $stmtUp->execute([$name, $description, $price, $stock, $image, $is_available, $item_id, $vendor_id]);
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Name and price are required.';
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Edit Menu Item</h3>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                </div>

                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Item Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($item['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?= $item['price'] ?>" required>
                    </div>
                    <!-- Inside form in vendor/edit_item.php -->
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity *</label>
                        <input type="number" name="stock" class="form-control" value="<?= (int)$item['stock'] ?>" min="0" required>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label">Current Image</label><br>
                            <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" width="60" height="60" class="rounded object-fit-cover shadow-sm" alt="">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Change Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_available" class="form-check-input" id="isAvail" <?= $item['is_available'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isAvail">Available for Order</label>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Update Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
