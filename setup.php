<?php
require_once 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];

    if (!empty($admin_user) && !empty($admin_pass)) {
        $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$admin_user, $hashed]);
            $message = '<div class="alert alert-success">Admin account created successfully! You can now <a href="login.php">Login</a>.</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error: Admin username might already exist.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">All fields are required.</div>';
    }
}
?>
<?php require_once 'includes/header.php'; ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-3">System Setup</h3>
                <p class="text-muted">Use this page if you need to create an additional administrator account.</p>
                <?= $message ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Admin Username</label>
                        <input type="text" name="admin_user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Password</label>
                        <input type="password" name="admin_pass" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold">Create Admin Account</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
