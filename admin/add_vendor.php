<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $store_name = trim($_POST['store_name']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Generate a unique unguessable secure token
    $token = bin2hex(random_bytes(16)); // e.g. "4a6f9c2d1e... "

    if (!empty($username) && !empty($password) && !empty($store_name) && !empty($whatsapp_number)) {
        // Handle logo upload
        $logo = 'default_logo.png';
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

                // Insert into users
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'vendor')");
                $stmtUser->execute([$username, $hashed]);
                $userId = $pdo->lastInsertId();

                // Update your insert statement for the vendors table
                $stmtVendor = $pdo->prepare("INSERT INTO vendors (token, user_id, store_name, description, address, contact_number, whatsapp_number, logo, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtVendor->execute([$token, $userId, $store_name, $description, $address, $contact_number, $whatsapp_number, $logo, $is_active]);

                $pdo->commit();
                header('Location: dashboard.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error creating vendor: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<?php require_once '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Add New Vendor</h3>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Back to Dashboard</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <h5 class="text-secondary mb-3">Account Credentials</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <h5 class="text-secondary mb-3 mt-4">Store Details</h5>
                    <div class="mb-3">
                        <label class="form-label">Store Name *</label>
                        <input type="text" name="store_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Number * (with country code, e.g. 1234567890)</label>
                            <input type="text" name="whatsapp_number" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Store Logo</label>
                        <input type="file" name="logo" class="form-control">
                        <small class="text-muted">Supports JPG, JPEG, PNG, WEBP. Max 5MB.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                        <label class="form-check-label" for="isActive">Active Account</label>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-bold">Create Vendor</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
