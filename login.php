<?php
if (file_exists(__DIR__ . '/config/db.php')) {
    require_once __DIR__ . '/config/db.php';
}
session_start();

$error = '';
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameValue = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameValue !== '' && $password !== '') {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.password, u.role,
                       v.id AS vendor_id, v.token AS vendor_token, v.store_name, v.logo
                FROM users u
                LEFT JOIN vendors v ON v.user_id = u.id
                WHERE u.username = :username
                LIMIT 1
            ");
            $stmt->execute([':username' => $usernameValue]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if (!empty($user['vendor_id'])) {
                    $_SESSION['vendor_id'] = (int) $user['vendor_id'];
                    $_SESSION['vendor_token'] = $user['vendor_token'];
                    $_SESSION['vendor_store_name'] = $user['store_name'];
                    $_SESSION['vendor_logo'] = $user['logo'];
                }

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit;
                } elseif ($user['role'] === 'vendor') {
                    header('Location: vendor/dashboard.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'ID/slug kedai atau kata laluan tidak sah.';
            }
        } else {
            $error = 'Sambungan pangkalan data belum dikonfigurasi.';
        }
    } else {
        $error = 'Sila lengkapkan semua medan.';
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk Peniaga — Kedai WA Kamek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100" style="background-color: #f3f4ee; color: #13261d;">

    <main class="container-fluid flex-grow-1 d-flex align-items-center justify-content-center py-4 py-md-5 px-3 px-sm-4">
        <div class="w-100" style="max-width: 460px;">

            <div class="mb-3">
                <a href="index.php"
                   class="d-inline-flex align-items-center gap-2 text-decoration-none small fw-semibold py-1"
                   style="color: #6b7c73;">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke laman utama</span>
                </a>
            </div>

            <div class="card border rounded-4 shadow-sm bg-white p-3 p-sm-4 p-md-5" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-0">

                    <div class="mb-4">
                        <img
                            src="https://placehold.co/175x48/ffffff/0b291a?text=Kedai+WA+Kamek&font=montserrat"
                            alt="Kedai WA Kamek Logo Placeholder"
                            class="img-fluid object-fit-contain"
                            style="height: 46px;"
                        >
                    </div>

                    <h1 class="h3 fw-bold mb-1" style="color: #0e1f17; letter-spacing: -0.02em;">
                        Log masuk peniaga
                    </h1>
                    <p class="small mb-4" style="color: #6b7c73;">
                        Urus kedai digital dan produk anda.
                    </p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-4" role="alert">
                            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-bold mb-2" style="color: #13261d;">
                                Nama pengguna / ID kedai
                            </label>
                            <input
                                type="text"
                                class="form-control rounded-pill py-3 px-4"
                                id="username"
                                name="username"
                                placeholder="cth: vendor1"
                                value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8'); ?>"
                                autocomplete="username"
                                required
                                style="border-color: #cbd5e1; font-size: 0.95rem;"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label small fw-bold mb-2" style="color: #13261d;">
                                PIN / Kata Laluan
                            </label>
                            <input
                                type="password"
                                class="form-control rounded-pill py-3 px-4"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                style="border-color: #cbd5e1; font-size: 0.95rem;"
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn w-100 rounded-pill py-3 fw-bold shadow-sm border-0"
                            style="background-color: #0b291a; color: #ffffff; font-size: 0.95rem;">
                            Log masuk
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>