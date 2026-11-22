<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Kedai WA Kamek'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons (for the leaf icon in "Explore Kedai") -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100" style="--bs-body-bg: #fdfaf2; --bs-body-color: #143326;">

    <!-- Sticky Bootstrap Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top py-3 border-bottom shadow-sm" style="background-color: #fdfaf2;">
        <div class="container-fluid px-4 px-lg-5">

            <!-- Brand Logo Placeholder (replace src with your logo path) -->
            <a class="navbar-brand d-flex align-items-center py-0" href="index.php">
                <img
                    src="https://placehold.co/155x46/ffffff/143326?text=Kedai+WA+Kamek&font=montserrat"
                    alt="Kedai WA Kamek Logo"
                    class="img-fluid object-fit-contain"
                    style="height: 46px;"
                >
            </a>

            <!-- Mobile Toggler -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav Links & Right CTA -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto align-items-lg-center gap-2 gap-lg-3 my-3 my-lg-0 fw-medium">
                    <li class="nav-item">
                        <a class="nav-link px-2" href="index.php" style="color: #143326;">Home</a>
                    </li>

                    <!-- Highlighted Pill Item: Explore Kedai + NEW badge -->
                    <li class="nav-item">
                        <a class="nav-link d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2"
                           href="#section-8"
                           style="background-color: #e8f5e3; color: #143326;">
                            <i class="bi bi-leaf text-success"></i>
                            <span>Explore Kedai</span>
                            <span class="badge rounded-pill text-uppercase fw-bold"
                                  style="background-color: #0b2b1d; color: #ffffff; font-size: 0.65rem; letter-spacing: 0.04em;">
                                NEW
                            </span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link px-2" href="#section-4" style="color: #143326;">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2" href="#section-2" style="color: #143326;">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2" href="#section-6" style="color: #143326;">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2" href="#section-11" style="color: #143326;">FAQ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2" href="#section-10" style="color: #143326;">Contact</a>
                    </li>
                </ul>

                <!-- Right Pill CTA Button -->
                <div class="d-flex">
                    <a href="#section-10"
                       class="btn rounded-pill px-4 py-2 fw-bold text-uppercase border-0 w-100 w-auto"
                       style="background-color: #f59322; color: #143326; font-size: 0.8rem; letter-spacing: 0.04em;">
                        GET YOUR DIGITAL STORE
                    </a>
                </div>
            </div>

        </div>
    </nav>