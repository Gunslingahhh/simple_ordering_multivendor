<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Vendor Food Ordering System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/simple_ordering_multivendor/assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/simple_ordering_multivendor/index.php">
                <i class="fa-solid fa-utensils text-warning me-2"></i>QuickBites
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="/simple_ordering_multivendor/admin/dashboard.php">Admin Dashboard</a></li>
                        <?php elseif ($_SESSION['role'] === 'vendor'): ?>
                            <li class="nav-item"><a class="nav-link" href="/simple_ordering_multivendor/vendor/dashboard.php">Vendor Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm ms-2 px-3 text-dark bg-warning fw-semibold" href="/simple_ordering_multivendor/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-warning btn-sm ms-2 px-3" href="/simple_ordering_multivendor/login.php"><i class="fa-solid fa-right-to-bracket me-1"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4 flex-grow-1">
