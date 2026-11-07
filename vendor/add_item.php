<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->execute([$user_id]);
$vendor = $stmt->fetch();
$vendor_id = $vendor['id'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $stock = (int)$_POST['stock']; // Added stock capture
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!empty($name) && !empty($price)) {
        $image = 'default_food.png';
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
            $stmtIns = $pdo->prepare("INSERT INTO menu_items (vendor_id, name, description, price, stock, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$vendor_id, $name, $description, $price, $stock, $image, $is_available]);
            header('Location: dashboard.php');
            exit;
        }
    } else {
        $error = 'Item name and price are required.';
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Add Menu Item</h3>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                </div>

                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Item Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <!-- Inside form in vendor/add_item.php -->
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity *</label>
                        <input type="number" name="stock" class="form-control" value="10" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Image</label>
                        <input type="file" name="image" class="form-control">
                        <small class="text-muted">Supports JPG, JPEG, PNG, WEBP. Max 5MB.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_available" class="form-check-input" id="isAvail" checked>
                        <label class="form-check-label" for="isAvail">Available for Order</label>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Add Item</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
