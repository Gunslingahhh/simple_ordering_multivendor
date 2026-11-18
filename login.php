<?php
require_once 'config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } elseif ($user['role'] === 'vendor') {
                header('Location: vendor/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<style>
    body {
        background-color: #f3f4ee;
    }
    .login-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem 1rem;
    }
    .login-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        width: 100%;
        max-width: 460px;
        padding: 2.5rem;
    }
    .form-control-rounded {
        border-radius: 50rem;
        padding: 0.75rem 1.25rem;
        border: 1px solid #cbd5e1;
        font-size: 0.95rem;
    }
    .form-control-rounded:focus {
        border-color: #0b291a;
        box-shadow: 0 0 0 0.2rem rgba(11, 41, 26, 0.15);
    }
    .btn-login-green {
        background-color: #0b291a;
        color: #ffffff;
        border-radius: 50rem;
        padding: 0.75rem;
        font-weight: 700;
        border: none;
        width: 100%;
        transition: background-color 0.2s ease;
    }
    .btn-login-green:hover {
        background-color: #16422b;
        color: #ffffff;
    }
</style>

<div class="login-wrapper">
    <div style="width: 100%; max-width: 460px;" class="mb-3">
        <a href="index.php" class="text-decoration-none text-muted small fw-semibold">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke laman utama
        </a>
    </div>

    <div class="login-card">
        <!-- Logo -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #0b291a !important;">
                    <i class="fa-solid fa-bag-shopping fs-5"></i>
                </div>
                <span class="fw-bold fs-5" style="color: #0b291a;">Kedai <span style="color: #198754;">WA Kamek</span></span>
            </div>
        </div>

        <h3 class="fw-bold text-dark mb-1" style="font-size: 1.6rem; letter-spacing: -0.5px;">
            Log masuk peniaga
        </h3>
        <p class="text-muted small mb-4">
            Urus kedai digital dan produk anda.
        </p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label small fw-bold text-dark">ID atau slug kedai</label>
                <input type="text" class="form-control form-control-rounded" id="username" name="username" placeholder="cth: dapur-mak-long" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label small fw-bold text-dark">PIN / Kata Laluan</label>
                <input type="password" class="form-control form-control-rounded" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login-green shadow-sm">Log masuk</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>