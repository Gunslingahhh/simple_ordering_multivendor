</main> <!-- Closes main container opened in header.php -->

    <!-- FOOTER SECTION MATCHING REFERENCE DESIGN -->
    <footer class="text-white pt-5 pb-4 mt-auto" style="background-color: #0b291a;">
        <div class="container py-4">
            <div class="row g-4 justify-content-between">
                
                <!-- Column 1: Logo & Tagline -->
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="bg-white p-3 rounded-4 d-inline-block shadow-sm mb-3">
                        <img src="/simple_ordering_multivendor/assets/logo.png" alt="Kedai WA Kamek Logo" height="40" class="d-block">
                    </div>
                    <p class="text-white-50 small mb-2" style="max-width: 320px; line-height: 1.6;">
                        Your own digital store. Orders straight to WhatsApp. Built for Malaysian small businesses, from Sarawak.
                    </p>
                    <p class="fw-bold fst-italic mb-0" style="color: #b2ff81; font-size: 1.15rem; font-family: cursive;">
                        Bisnes Makin Mudah
                    </p>
                </div>

                <!-- Column 2: Explore Links -->
                <div class="col-6 col-lg-3">
                    <h6 class="fw-bold text-uppercase tracking-wider mb-3" style="color: #b2ff81; font-size: 0.85rem; letter-spacing: 1px;">Explore</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><a href="/simple_ordering_multivendor/index.php" class="text-white-50 text-decoration-none small hover-text-white">Home</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#how-it-works" class="text-white-50 text-decoration-none small hover-text-white">Explore Kedai</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#how-it-works" class="text-white-50 text-decoration-none small hover-text-white">How It Works</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#categories" class="text-white-50 text-decoration-none small hover-text-white">Categories</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#features" class="text-white-50 text-decoration-none small hover-text-white">Features</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#faq" class="text-white-50 text-decoration-none small hover-text-white">FAQ</a></li>
                        <li><a href="/simple_ordering_multivendor/index.php#contact" class="text-white-50 text-decoration-none small hover-text-white">Contact</a></li>
                    </ul>
                </div>

                <!-- Column 3: Contact & Portal Logins -->
                <div class="col-6 col-lg-4">
                    <h6 class="fw-bold text-uppercase tracking-wider mb-3" style="color: #b2ff81; font-size: 0.85rem; letter-spacing: 1px;">Contact</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-4">
                        <li>
                            <a href="https://wa.me/601169893389" target="_blank" class="text-white-50 text-decoration-none small d-flex align-items-center">
                                <i class="fa-brands fa-whatsapp text-success me-2 fs-6"></i> +6011-69893389
                            </a>
                        </li>
                        <li>
                            <a href="https://wa.me/60103055330" target="_blank" class="text-white-50 text-decoration-none small d-flex align-items-center">
                                <i class="fa-brands fa-whatsapp text-success me-2 fs-6"></i> +6010-3055330
                            </a>
                        </li>
                        <li class="text-white-50 small d-flex align-items-center">
                            <i class="fa-regular fa-envelope text-success me-2 fs-6"></i> WhatsApp is the fastest way to reach us
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-3 pt-2 border-top border-secondary border-opacity-25">
                        <a href="/simple_ordering_multivendor/login.php" class="text-white-50 text-decoration-none small hover-text-white">Vendor login</a>
                        <span class="text-secondary">•</span>
                        <a href="/simple_ordering_multivendor/login.php" class="text-white-50 text-decoration-none small hover-text-white">Admin login</a>
                    </div>
                </div>

            </div>

            <hr class="border-secondary border-opacity-25 my-4">

            <!-- Copyright Bar -->
            <div class="row align-items-center">
                <div class="col text-center">
                    <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">
                        &copy; <?= date('Y') ?> Kedai WA Kamek. Kedai WA Kamek does not process customer payments.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .hover-text-white:hover {
            color: #ffffff !important;
        }
    </style>
</body>
</html>