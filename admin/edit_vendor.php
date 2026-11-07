<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT v.*, u.username FROM vendors v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$stmt->execute([$id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $status = $_POST['status'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];

    if (!empty($store_name) && !empty($whatsapp_number)) {
        $logo = $vendor['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];
            $fileSize = $_FILES['logo']['size'];
            $fileType = $_FILES['logo']['type'];
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (in_array($fileType, $allowedTypes) && $fileSize <= 5 * 1024 * 1024) {
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../uploads/vendors/';
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $logo = $newFileName;
                }
            } else {
                $error = 'Invalid image type or file exceeds 5MB.';
            }
        }

        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                // Update vendor info
                $stmtUpdate = $pdo->prepare("UPDATE vendors SET store_name = ?, description = ?, address = ?, contact_number = ?, whatsapp_number = ?, logo = ?, status = ?, is_active = ? WHERE id = ?");
                $stmtUpdate->execute([$store_name, $description, $address, $contact_number, $whatsapp_number, $logo, $status, $is_active, $id]);

                // Update password if provided
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmtPass->execute([$hashed, $vendor['user_id']]);
                }

                $pdo->commit();
                header('Location: dashboard.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error updating vendor: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Store name and WhatsApp number are required.';
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Edit Vendor: <?= htmlspecialchars($vendor['store_name']) ?></h3>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Username (Read-only)</label>
                        type="text" class="form-control" value="<?= htmlspecialchars($vendor['username']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Store Name *</label>
                        <input type="text" name="store_name" class="form-control" value="<?= htmlspecialchars($vendor['store_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($vendor['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($vendor['address']) ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($vendor['contact_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Number *</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($vendor['whatsapp_number']) ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Store Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?= $vendor['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="closed" <?= $vendor['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Logo</label><br>
                            <img src="../uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" width="50" height="50" class="rounded-circle object-fit-cover" alt="">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Logo</label>
                        <input type="file" name="logo" class="form-control">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $vendor['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Active Account</label>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-bold">Update Vendor</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
