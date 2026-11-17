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
    <title>Kedai-WA-Kamek - Multi-Vendor Food Ordering</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/simple_ordering_multivendor/assets/css/style.css">
    <style>
        /* Custom Header Styling matching the reference layout */
        .custom-navbar {
            background-color: #e1dbd0 !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .nav-link-custom {
            color: #2c3e35 !important;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }
        .nav-link-custom:hover {
            color: #1e392a !important;
        }
        .explore-pill-btn {
            background-color: #dce5d9;
            color: #1e392a;
            border-radius: 50rem;
            padding: 0.4rem 1.1rem;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            border: none;
            text-decoration: none;
        }
        .new-badge {
            background-color: #1e392a;
            color: #ffffff;
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            border-radius: 50rem;
            margin-left: 0.5rem;
            letter-spacing: 0.5px;
        }
        .cta-orange-btn {
            background: linear-gradient(135deg, #ffa726, #fb8c00);
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.5rem 1.4rem;
            font-weight: 700;
            font-size: 0.9rem;
            border: none;
            box-shadow: 0 4px 10px rgba(251, 140, 0, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
        }
        .cta-orange-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(251, 140, 0, 0.35);
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- NAVIGATION HEADER -->
    <nav class="navbar navbar-expand-lg custom-navbar sticky-top shadow-sm">
        <div class="container-fluid px-4">
            
            <!-- 1. Logo on the Left -->
            <a class="navbar-brand py-0 me-4" href="/simple_ordering_multivendor/index.php">
                <img src="/simple_ordering_multivendor/assets/logo.png" alt="Kedai-WA-Kamek Logo" height="45" class="d-inline-block align-text-top">
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- 2. Centered Navigation Links & Right CTA -->
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                
                <!-- Center Links -->
                <ul class="navbar-nav mx-auto align-items-center gap-lg-3 my-2 my-lg-0">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#categories">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a href="/simple_ordering_multivendor/index.php#explore" class="explore-pill-btn shadow-sm">
                            <i class="fa-solid fa-seedling text-success me-1"></i> Explore Kedai <span class="new-badge">NEW</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#become-vendor">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="/simple_ordering_multivendor/index.php#faq">FAQ</a>
                    </li>
                </ul>

                <!-- 3. Right CTA Button -->
                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="/simple_ordering_multivendor/admin/dashboard.php" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold">Admin Dashboard</a>
                        <?php elseif ($_SESSION['role'] === 'vendor'): ?>
                            <a href="/simple_ordering_multivendor/vendor/dashboard.php" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold">Vendor Dashboard</a>
                        <?php endif; ?>
                        <a href="/simple_ordering_multivendor/logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">Logout</a>
                    <?php else: ?>
                        <a href="https://api.whatsapp.com/send/?phone=601169893389&text=Hi+Kedai+WA+Kamek%2C+I%27d+like+to+get+my+digital+store+set+up.&type=phone_number&app_absent=0" target="_blank" class="cta-orange-btn">
                            GET YOUR DIGITAL STORE
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </nav>

    <main class="container flex-grow-1">