<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if a secure vendor token is provided in the URL
$vendor_token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($vendor_token)) {
} else{
    ?>
    <main class="flex-grow-1">
        <!-- Section 01: Hero -->
        <section id="section-1" class="container-fluid text-white py-5" style="background: #083b36;">
            <div class="container py-4 py-lg-5">
                <div class="row align-items-center g-5">

                    <!-- Left Column: Eyebrow, Headlines, Copy & Pill CTAs -->
                    <div class="col-12 col-lg-7">
                        <p class="fst-italic text-white-50 mb-2">Bisnes Makin Mudah</p>

                        <h1 class="display-5 fw-bold text-uppercase lh-1 mb-2">
                            YOUR OWN DIGITAL STORE.
                        </h1>
                        <h2 class="display-5 fw-bold text-uppercase lh-1 mb-4" style="color: #84f56b;">
                            ORDERS STRAIGHT TO WHATSAPP.
                        </h2>

                        <p class="fs-6 mb-4" style="color: rgba(255, 255, 255, 0.85); max-width: 540px;">
                            Food, fashion, fresh produce, handmade crafts and more. Turn your products into a simple digital ordering experience. Let customers browse, choose, order and send their orders directly to your WhatsApp.
                        </p>

                        <!-- Row 1: Two side-by-side pill buttons on sm+, stacked on mobile -->
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 mb-3">
                            <a href="#section-10"
                            class="btn rounded-pill px-4 py-2 fw-bold text-uppercase border-0 w-100 w-auto text-center"
                            style="background-color: #f59322; color: #143326; font-size: 0.78rem; letter-spacing: 0.04em;">
                                GET YOUR STORE SET UP
                            </a>

                            <a href="#section-5"
                            class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold text-uppercase w-100 w-auto text-center"
                            style="font-size: 0.78rem; letter-spacing: 0.04em;">
                                SEE HOW IT WORKS
                            </a>
                        </div>

                        <!-- Row 2: Third pill button positioned below on its own line -->
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                            <a href="#section-2"
                            class="btn rounded-pill px-4 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center justify-content-center gap-2 w-100 w-auto"
                            style="background-color: #a8f878; color: #143326; font-size: 0.78rem; letter-spacing: 0.04em;">
                                <span>EXPLORE KEDAI</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Live Storefront Preview Card -->
                    <div class="col-12 col-lg-5 d-flex justify-content-center">
                        <a href="#section-6"
                        class="card text-decoration-none text-center border-0 rounded-4 shadow-lg w-100 p-4 p-sm-5"
                        style="max-width: 340px; background-color: #050706;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-2">
                                <i class="bi bi-phone fs-1 mb-3" style="color: #84f56b;"></i>
                                <div class="fw-semibold text-white fs-6 mb-1">Live Storefront Preview</div>
                                <small class="text-white-50">Seamless WhatsApp Cart Integration</small>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- Section 02: One Platform. Every Kind of Kedai. -->
        <section id="section-2" class="container-fluid py-5" style="background-color: #f4f5ef; color: #14261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="fst-italic fw-medium mb-1" style="color: #3f7d32;">From kuih to kain, sayur to sulaman.</p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0e1f17;">
                        ONE PLATFORM.<br>
                        EVERY KIND OF KEDAI.
                    </h2>
                </div>

                <!-- Top Row: 2 Featured Cards -->
                <div class="row g-4 mb-4">

                    <!-- Card 1: Food & Beverages -->
                    <div class="col-12 col-lg-6">
                        <div class="card position-relative overflow-hidden border-0 rounded-4 shadow-sm h-100" style="min-height: 310px; background-color: #163325;">
                            <img
                                src="https://placehold.co/800x500/b7c2ba/163325?text=Food+%26+Beverages+Photo"
                                alt="Food and Beverages Placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(22, 51, 37, 0.12) 0%, rgba(16, 40, 29, 0.78) 68%, rgba(12, 31, 22, 0.94) 100%);"></div>

                            <div class="card-body position-relative d-flex flex-column justify-content-between p-4 p-sm-5 text-white">
                                <div>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background-color: #9df557; color: #132c1e;">
                                        <i class="bi bi-cup-hot-fill fs-5"></i>
                                    </span>
                                </div>

                                <div class="pt-5 mt-4">
                                    <h3 class="h4 fw-bold text-uppercase mb-2">FOOD &amp; BEVERAGES</h3>
                                    <p class="small mb-4" style="color: rgba(255, 255, 255, 0.85);">
                                        Hawkers, kuih sellers, home bakers, cafés, restaurants and catering.
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="#section-10"
                                        class="btn rounded-pill px-3 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center gap-1"
                                        style="background-color: #9df557; color: #132c1e; font-size: 0.7rem; letter-spacing: 0.04em;">
                                            <span>SEE SAMPLE STORE</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a href="#section-6"
                                        class="btn rounded-pill px-3 py-2 fw-semibold text-uppercase text-white"
                                        style="background-color: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 0.7rem; letter-spacing: 0.04em;">
                                            SAMPLE STORE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Fashion & Apparel -->
                    <div class="col-12 col-lg-6">
                        <div class="card position-relative overflow-hidden border-0 rounded-4 shadow-sm h-100" style="min-height: 310px; background-color: #163325;">
                            <img
                                src="https://placehold.co/800x500/b7c2ba/163325?text=Fashion+%26+Apparel+Photo"
                                alt="Fashion and Apparel Placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(22, 51, 37, 0.12) 0%, rgba(16, 40, 29, 0.78) 68%, rgba(12, 31, 22, 0.94) 100%);"></div>

                            <div class="card-body position-relative d-flex flex-column justify-content-between p-4 p-sm-5 text-white">
                                <div>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background-color: #9df557; color: #132c1e;">
                                        <i class="bi bi-tag-fill fs-5"></i>
                                    </span>
                                </div>

                                <div class="pt-5 mt-4">
                                    <h3 class="h4 fw-bold text-uppercase mb-2">FASHION &amp; APPAREL</h3>
                                    <p class="small mb-4" style="color: rgba(255, 255, 255, 0.85);">
                                        Baju kurung, batik, tudung, modest wear, streetwear and accessories. Sizes, colours and requests go in order notes.
                                    </p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="#section-10"
                                        class="btn rounded-pill px-3 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center gap-1"
                                        style="background-color: #9df557; color: #132c1e; font-size: 0.7rem; letter-spacing: 0.04em;">
                                            <span>SEE SAMPLE STORE</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a href="#section-6"
                                        class="btn rounded-pill px-3 py-2 fw-semibold text-uppercase text-white"
                                        style="background-color: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 0.7rem; letter-spacing: 0.04em;">
                                            SAMPLE STORE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Row: 3 Category Cards -->
                <div class="row g-4">

                    <!-- Card 3: Fresh Produce -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card position-relative overflow-hidden border-0 rounded-4 shadow-sm h-100" style="min-height: 320px; background-color: #163325;">
                            <img
                                src="https://placehold.co/600x520/b7c2ba/163325?text=Fresh+Produce+Photo"
                                alt="Fresh Produce Placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(22, 51, 37, 0.12) 0%, rgba(16, 40, 29, 0.78) 65%, rgba(12, 31, 22, 0.94) 100%);"></div>

                            <div class="card-body position-relative d-flex flex-column justify-content-between p-4 text-white">
                                <div>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background-color: #9df557; color: #132c1e;">
                                        <i class="bi bi-basket2-fill fs-5"></i>
                                    </span>
                                </div>

                                <div class="pt-5 mt-3">
                                    <h3 class="h5 fw-bold text-uppercase mb-2">FRESH PRODUCE</h3>
                                    <p class="small mb-3" style="color: rgba(255, 255, 255, 0.85);">
                                        Vegetables, fruits, seafood, eggs, local farm and pasar goods.
                                    </p>
                                    <div class="d-flex flex-column align-items-start gap-2">
                                        <a href="#section-10"
                                        class="btn rounded-pill px-3 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center gap-1"
                                        style="background-color: #9df557; color: #132c1e; font-size: 0.7rem; letter-spacing: 0.04em;">
                                            <span>SEE SAMPLE STORE</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a href="#section-6"
                                        class="btn rounded-pill px-3 py-2 fw-semibold text-uppercase text-white"
                                        style="background-color: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 0.7rem; letter-spacing: 0.04em;">
                                            SAMPLE STORE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Handmade Crafts -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card position-relative overflow-hidden border-0 rounded-4 shadow-sm h-100" style="min-height: 320px; background-color: #163325;">
                            <img
                                src="https://placehold.co/600x520/b7c2ba/163325?text=Handmade+Crafts+Photo"
                                alt="Handmade Crafts Placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(22, 51, 37, 0.12) 0%, rgba(16, 40, 29, 0.78) 65%, rgba(12, 31, 22, 0.94) 100%);"></div>

                            <div class="card-body position-relative d-flex flex-column justify-content-between p-4 text-white">
                                <div>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background-color: #9df557; color: #132c1e;">
                                        <i class="bi bi-gem fs-5"></i>
                                    </span>
                                </div>

                                <div class="pt-5 mt-3">
                                    <h3 class="h5 fw-bold text-uppercase mb-2">HANDMADE CRAFTS</h3>
                                    <p class="small mb-3" style="color: rgba(255, 255, 255, 0.85);">
                                        Weaving, beadwork, songket, batik, candles, soap, ceramics and personalised gifts.
                                    </p>
                                    <div class="d-flex flex-column align-items-start gap-2">
                                        <a href="#section-10"
                                        class="btn rounded-pill px-3 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center gap-1"
                                        style="background-color: #9df557; color: #132c1e; font-size: 0.7rem; letter-spacing: 0.04em;">
                                            <span>SEE SAMPLE STORE</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a href="#section-6"
                                        class="btn rounded-pill px-3 py-2 fw-semibold text-uppercase text-white"
                                        style="background-color: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 0.7rem; letter-spacing: 0.04em;">
                                            SAMPLE STORE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: More Businesses -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card position-relative overflow-hidden border-0 rounded-4 shadow-sm h-100" style="min-height: 320px; background-color: #163325;">
                            <img
                                src="https://placehold.co/600x520/b7c2ba/163325?text=More+Businesses+Photo"
                                alt="More Businesses Placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(22, 51, 37, 0.12) 0%, rgba(16, 40, 29, 0.78) 65%, rgba(12, 31, 22, 0.94) 100%);"></div>

                            <div class="card-body position-relative d-flex flex-column justify-content-between p-4 text-white">
                                <div>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 40px; height: 40px; background-color: #9df557; color: #132c1e;">
                                        <i class="bi bi-shop fs-5"></i>
                                    </span>
                                </div>

                                <div class="pt-5 mt-3">
                                    <h3 class="h5 fw-bold text-uppercase mb-2">MORE BUSINESSES</h3>
                                    <p class="small mb-3" style="color: rgba(255, 255, 255, 0.85);">
                                        Beauty and wellness, home goods, gifts and hampers, plants, pre-loved items and other small sellers.
                                    </p>
                                    <div class="d-flex flex-column align-items-start gap-2">
                                        <a href="#section-10"
                                        class="btn rounded-pill px-3 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center gap-1"
                                        style="background-color: #9df557; color: #132c1e; font-size: 0.7rem; letter-spacing: 0.04em;">
                                            <span>SEE SAMPLE STORE</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <a href="#section-6"
                                        class="btn rounded-pill px-3 py-2 fw-semibold text-uppercase text-white"
                                        style="background-color: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 0.7rem; letter-spacing: 0.04em;">
                                            SAMPLE STORE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Tagline -->
                <p class="fst-italic text-center small mt-4 mb-0" style="color: #4c5d54;">
                    If you sell it, you can list it.
                </p>

            </div>
        </section>

        <!-- Section 03: Made For Our Malaysian Business -->
        <section id="section-3" class="container-fluid py-5 border-top border-bottom" style="background-color: #fbfcfa; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="small fw-semibold text-uppercase mb-2" style="color: #4f6659; letter-spacing: 0.14em;">
                        MADE FOR OUR MALAYSIAN BUSINESS
                    </p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0f2018;">
                        LESS CHAT. MORE ORDERS.
                    </h2>
                </div>

                <!-- 4-Card Feature Grid -->
                <div class="row g-4">

                    <!-- Card 1: One Time Setup -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 text-center rounded-4 border" style="background-color: #f8f9f8; border-color: #e4e8e5 !important;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                <i class="bi bi-lightning-charge-fill fs-3 mb-3" style="color: #189a5a;"></i>
                                <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #12241b; letter-spacing: 0.03em;">
                                    ONE TIME SETUP
                                </h3>
                                <p class="small mb-0" style="color: #56685e;">
                                    Quick configuration to get your menu live instantly.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Instant Scan QR -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 text-center rounded-4 border" style="background-color: #f8f9f8; border-color: #e4e8e5 !important;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                <i class="bi bi-qr-code fs-3 mb-3" style="color: #189a5a;"></i>
                                <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #12241b; letter-spacing: 0.03em;">
                                    INSTANT SCAN QR
                                </h3>
                                <p class="small mb-0" style="color: #56685e;">
                                    Tabletop QR codes for fast customer scanning.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: No Customer Log-In -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 text-center rounded-4 border" style="background-color: #f8f9f8; border-color: #e4e8e5 !important;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                <i class="bi bi-chat-dots-fill fs-3 mb-3" style="color: #189a5a;"></i>
                                <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #12241b; letter-spacing: 0.03em;">
                                    NO CUSTOMER LOG-IN
                                </h3>
                                <p class="small mb-0" style="color: #56685e;">
                                    Zero friction ordering straight to WhatsApp.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Simple Custodian -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card h-100 text-center rounded-4 border" style="background-color: #f8f9f8; border-color: #e4e8e5 !important;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                <i class="bi bi-shield-fill-check fs-3 mb-3" style="color: #189a5a;"></i>
                                <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #12241b; letter-spacing: 0.03em;">
                                    SIMPLE CUSTODIAN
                                </h3>
                                <p class="small mb-0" style="color: #56685e;">
                                    Maintain total control of your business revenues.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Section 04: How Does It Work? -->
        <section id="section-4" class="container-fluid py-5 border-top border-bottom" style="background-color: #ffffff; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="small fw-semibold text-uppercase mb-2" style="color: #4f6659; letter-spacing: 0.14em;">
                        SIMPLE STEPS. EASY ORDERING.
                    </p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0f2018;">
                        HOW DOES IT WORK?
                    </h2>
                </div>

                <!-- Two-Column Layout: 4 Steps on Left, Checkout Flow Card on Right -->
                <div class="row align-items-center g-5">

                    <!-- Left Column: Steps 01 - 04 -->
                    <div class="col-12 col-lg-7">
                        <div class="d-flex flex-column gap-4">

                            <!-- Step 01 -->
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-shop fs-5 mt-1 flex-shrink-0" style="color: #152b20;"></i>
                                <div>
                                    <div class="small fw-bold text-uppercase mb-1" style="color: #198754; letter-spacing: 0.05em;">
                                        STEP 01
                                    </div>
                                    <h3 class="h6 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                        YOUR DIGITAL STORE
                                    </h3>
                                    <p class="small mb-0" style="color: #56685e; max-width: 520px;">
                                        We set up your personalised digital store with your business details, operating hours, product photos and product list.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 02 -->
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-basket2-fill fs-5 mt-1 flex-shrink-0" style="color: #152b20;"></i>
                                <div>
                                    <div class="small fw-bold text-uppercase mb-1" style="color: #198754; letter-spacing: 0.05em;">
                                        STEP 02
                                    </div>
                                    <h3 class="h6 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                        CUSTOMERS BROWSE
                                    </h3>
                                    <p class="small mb-0" style="color: #56685e; max-width: 520px;">
                                        Customers open your digital store through a link or QR code. No downloads or sign-ins required.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 03 -->
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-list-check fs-5 mt-1 flex-shrink-0" style="color: #152b20;"></i>
                                <div>
                                    <div class="small fw-bold text-uppercase mb-1" style="color: #198754; letter-spacing: 0.05em;">
                                        STEP 03
                                    </div>
                                    <h3 class="h6 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                        CUSTOMERS PLACE ORDERS
                                    </h3>
                                    <p class="small mb-0" style="color: #56685e; max-width: 520px;">
                                        They select their favourite items, choose quantities, add special notes and enter their name for order identification.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 04 -->
                            <div class="d-flex align-items-start gap-3">
                                <i class="bi bi-whatsapp fs-5 mt-1 flex-shrink-0" style="color: #152b20;"></i>
                                <div>
                                    <div class="small fw-bold text-uppercase mb-1" style="color: #198754; letter-spacing: 0.05em;">
                                        STEP 04
                                    </div>
                                    <h3 class="h6 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                        ORDER SENT TO WHATSAPP
                                    </h3>
                                    <p class="small mb-0" style="color: #56685e; max-width: 520px;">
                                        The complete order summary goes directly to your WhatsApp. You can confirm the order and arrange payment with your customer.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Right Column: Interactive Mobile Checkout Flow Card -->
                    <div class="col-12 col-lg-5 d-flex justify-content-center">
                        <div class="card w-100 text-center rounded-4 border shadow-sm p-4" style="max-width: 340px; background-color: #f6f7f6; border-color: #e3e7e4 !important;">
                            <div class="card-body d-flex flex-column align-items-center justify-content-center p-2">
                                <img
                                    src="https://placehold.co/120x120/f6f7f6/189a5a?text=Mobile+UI"
                                    alt="Interactive Mobile Checkout Flow Placeholder"
                                    class="img-fluid rounded-3 mb-3 object-fit-contain"
                                    style="width: 72px; height: 72px;"
                                >
                                <h3 class="h6 fw-bold mb-1" style="color: #0f2018;">
                                    Interactive Mobile Checkout Flow
                                </h3>
                                <p class="small mb-0" style="color: #6b7c73;">
                                    Real-time WhatsApp payload generator
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Section 05: See It In Action -->
        <section id="section-5" class="container-fluid py-5 border-top border-bottom" style="background-color: #f4f5ef; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="fst-italic fw-medium mb-1" style="color: #4f7d2f;">See it in action</p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0e1f17;">
                        YOUR STORE. YOUR<br>
                        PRODUCTS. YOUR WAY.
                    </h2>
                </div>

                <!-- 3-Column Showcase Grid -->
                <div class="row g-4 align-items-end text-center">

                    <!-- Step 01: Browse the Store -->
                    <div class="col-12 col-md-4">
                        <div class="card border-0 rounded-4 shadow-sm p-3 p-sm-4 mx-auto bg-white" style="max-width: 300px; transform: rotate(-2deg);">
                            <div class="card-body p-2">
                                <img
                                    src="https://placehold.co/120x120/ffffff/15803d?text=Store+UI"
                                    alt="Browse the Store Screen Placeholder"
                                    class="img-fluid rounded-3 mb-2 object-fit-contain"
                                    style="width: 56px; height: 56px;"
                                >
                                <div class="fw-bold small mb-1" style="color: #0f2018;">Browse the Store Screen</div>
                                <div class="small" style="color: #6b7c73; font-size: 0.78rem;">Interactive catalog &amp; pricing</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-1">
                            <div class="fs-5 fw-bold mb-1" style="color: #169a5a;">01</div>
                            <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #0f2018;">BROWSE THE STORE</h3>
                            <p class="small mx-auto mb-0" style="color: #56685e; max-width: 260px;">
                                Beautiful product photography, descriptions and prices.
                            </p>
                        </div>
                    </div>

                    <!-- Step 02: Customise Your Order -->
                    <div class="col-12 col-md-4">
                        <div class="card border-0 rounded-4 shadow-sm p-3 p-sm-4 mx-auto bg-white" style="max-width: 300px;">
                            <div class="card-body p-2">
                                <img
                                    src="https://placehold.co/120x120/ffffff/15803d?text=Order+UI"
                                    alt="Customise Your Order Screen Placeholder"
                                    class="img-fluid rounded-3 mb-2 object-fit-contain"
                                    style="width: 56px; height: 56px;"
                                >
                                <div class="fw-bold small mb-1" style="color: #0f2018;">Customise Your Order Screen</div>
                                <div class="small" style="color: #6b7c73; font-size: 0.78rem;">Quantities, notes &amp; review list</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-1">
                            <div class="fs-5 fw-bold mb-1" style="color: #169a5a;">02</div>
                            <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #0f2018;">CUSTOMISE YOUR ORDER</h3>
                            <p class="small mx-auto mb-0" style="color: #56685e; max-width: 260px;">
                                Select quantities, add notes and review the order list.
                            </p>
                        </div>
                    </div>

                    <!-- Step 03: Send to WhatsApp -->
                    <div class="col-12 col-md-4">
                        <div class="card border-0 rounded-4 shadow-sm p-3 p-sm-4 mx-auto bg-white" style="max-width: 300px; transform: rotate(2deg);">
                            <div class="card-body p-2">
                                <img
                                    src="https://placehold.co/120x120/ffffff/15803d?text=WA+UI"
                                    alt="Send to WhatsApp Screen Placeholder"
                                    class="img-fluid rounded-3 mb-2 object-fit-contain"
                                    style="width: 56px; height: 56px;"
                                >
                                <div class="fw-bold small mb-1" style="color: #0f2018;">Send to WhatsApp Screen</div>
                                <div class="small" style="color: #6b7c73; font-size: 0.78rem;">Clean summary &amp; total payload</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-1">
                            <div class="fs-5 fw-bold mb-1" style="color: #169a5a;">03</div>
                            <h3 class="h6 fw-bold text-uppercase mb-2" style="color: #0f2018;">SEND TO WHATSAPP</h3>
                            <p class="small mx-auto mb-0" style="color: #56685e; max-width: 260px;">
                                A clean summary with name, items, notes and total.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Bottom Italic Note -->
                <p class="fst-italic text-center small mt-5 mb-0" style="color: #4c5d54;">
                    Your customers don’t need to download anything or create an account. Just open the link and order.
                </p>

            </div>
        </section>

        <!-- Section 06: Keep Things Simple -->
        <section id="section-6" class="container-fluid py-5 text-white" style="background-color: #0b2618;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="small fw-semibold text-uppercase mb-2" style="color: #84f56b; letter-spacing: 0.14em;">
                        KEEP THINGS SIMPLE
                    </p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0 text-white">
                        YOUR BUSINESS. YOUR SALES. YOUR RULES.
                    </h2>
                </div>

                <!-- 6-Item Feature Grid -->
                <div class="row g-4 g-lg-5">

                    <!-- Item 1: 100% Of Your Sales -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-percent fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                100% OF YOUR SALES
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            We don’t charge commission on your orders. Your sales belong to you.
                        </p>
                    </div>

                    <!-- Item 2: One-Time Setup Fee -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-tools fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                ONE-TIME SETUP FEE
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            Pay a once-off setup fee for your store. No per-order commission.
                        </p>
                    </div>

                    <!-- Item 3: No Customer Registration -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-x-fill fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                NO CUSTOMER REGISTRATION
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            Customers can browse and order without creating accounts.
                        </p>
                    </div>

                    <!-- Item 4: Easy To Use -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-lightning-charge-fill fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                EASY TO USE
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            A straightforward store designed for everyday customers and business owners.
                        </p>
                    </div>

                    <!-- Item 5: No App Download -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-globe2 fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                NO APP DOWNLOAD
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            Works through a web browser. Just share your store link or QR code.
                        </p>
                    </div>

                    <!-- Item 6: Cancel Anytime -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-shield-check fs-5 flex-shrink-0" style="color: #84f56b;"></i>
                            <h3 class="h6 fw-bold text-uppercase mb-0 text-white" style="letter-spacing: 0.03em;">
                                CANCEL ANYTIME
                            </h3>
                        </div>
                        <p class="small mb-0" style="color: rgba(255, 255, 255, 0.72);">
                            Enjoy flexibility without complicated commitments.
                        </p>
                    </div>

                </div>

                <!-- Divider & Bottom Italic Note -->
                <hr class="mt-5 mb-4 border-secondary border-opacity-50">
                <p class="fst-italic text-center small mb-0" style="color: rgba(255, 255, 255, 0.68);">
                    Kedai WA Kamek is a store builder, not a marketplace. Your customers order from you, directly.
                </p>

            </div>
        </section>

        <!-- Section 07: No Middleman -->
        <section id="section-7" class="container-fluid py-5 border-top border-bottom" style="background-color: #fbfcfa; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="small fw-semibold text-uppercase mb-2" style="color: #4f6659; letter-spacing: 0.14em;">
                        NO MIDDLEMAN
                    </p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0f2018;">
                        ORDERS MADE SIMPLE.<br>
                        PAYMENTS MADE DIRECT.
                    </h2>
                </div>

                <!-- Two-Column Cards -->
                <div class="row g-4">

                    <!-- Left Card: What About Delivery? -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 rounded-4 border p-3 p-sm-4 p-lg-5" style="background-color: #f2f4ee; border-color: #e2e6de !important;">
                            <div class="card-body p-0">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                                    style="width: 46px; height: 46px; background-color: #dcebe0; color: #15803d;">
                                    <i class="bi bi-bicycle fs-5"></i>
                                </span>

                                <h3 class="h5 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                    WHAT ABOUT DELIVERY?
                                </h3>
                                <p class="fst-italic small mb-3" style="color: #56685e;">
                                    Want your customers to enjoy delivery?
                                </p>
                                <p class="small mb-0" style="color: #4c5d54; line-height: 1.65;">
                                    Customers discuss directly with the vendor to arrange a third-party courier or delivery service. Customers pay the actual product price, while delivery charges are arranged and paid separately.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Card: How Do Customers Pay? -->
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 rounded-4 border p-3 p-sm-4 p-lg-5" style="background-color: #f2f4ee; border-color: #e2e6de !important;">
                            <div class="card-body p-0">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                                    style="width: 46px; height: 46px; background-color: #dcebe0; color: #15803d;">
                                    <i class="bi bi-wallet2 fs-5"></i>
                                </span>

                                <h3 class="h5 fw-bold text-uppercase mb-1" style="color: #0f2018;">
                                    HOW DO CUSTOMERS PAY?
                                </h3>
                                <p class="fst-italic small mb-3" style="color: #56685e;">
                                    Simple. Direct. Between you and your customer.
                                </p>
                                <p class="small mb-4" style="color: #4c5d54; line-height: 1.65;">
                                    When the order summary reaches your WhatsApp, you provide your payment details directly.
                                </p>

                                <!-- Payment Method Pills -->
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge rounded-pill bg-white border px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                        style="color: #143326; border-color: #dce2da !important; font-size: 0.78rem;">
                                        <i class="bi bi-qr-code text-success"></i>
                                        <span>DuitNow QR</span>
                                    </span>

                                    <span class="badge rounded-pill bg-white border px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                        style="color: #143326; border-color: #dce2da !important; font-size: 0.78rem;">
                                        <i class="bi bi-bank text-success"></i>
                                        <span>Online bank transfer</span>
                                    </span>

                                    <span class="badge rounded-pill bg-white border px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                                        style="color: #143326; border-color: #dce2da !important; font-size: 0.78rem;">
                                        <i class="bi bi-hand-thumbs-up text-success"></i>
                                        <span>Other agreed arrangements</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Bottom Disclaimer Pill/Banner -->
                <div class="d-flex justify-content-center mt-4 pt-1">
                    <div class="d-flex align-items-center gap-3 rounded-4 border bg-white px-4 py-3 shadow-2xs"
                        style="max-width: 720px; border-color: #dce2da !important;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                            style="width: 22px; height: 22px; background-color: #4b5d54; color: #ffffff; font-size: 0.75rem;">
                            <i class="bi bi-info"></i>
                        </span>
                        <p class="small mb-0 text-start" style="color: #4c5d54;">
                            <strong class="fw-bold" style="color: #0f2018;">Kedai WA Kamek does not collect customer payments or process transactions.</strong>
                            Payments are handled directly between customers and vendors.
                        </p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 09: Supported By Local Partners -->
        <section id="section-9" class="container-fluid py-5 border-top border-bottom" style="background-color: #f4f5ef; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Header -->
                <div class="text-center mb-5">
                    <p class="fst-italic fw-medium mb-1" style="color: #4f7d2f;">Growing local together</p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-2" style="color: #0e1f17;">
                        SUPPORTED BY LOCAL PARTNERS
                    </h2>
                    <p class="small mx-auto mb-0" style="color: #56685e; max-width: 520px;">
                        Kedai WA Kamek is made possible with the support of partners who believe in helping local businesses grow digitally.
                    </p>
                </div>

                <!-- Subsection 1: Our Sponsors (4 Slots) -->
                <div class="mb-5">
                    <p class="small fw-semibold text-uppercase mb-3" style="color: #4f6659; letter-spacing: 0.14em;">
                        OUR SPONSORS
                    </p>
                    <div class="row g-3 g-md-4">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card h-100 rounded-4 bg-white p-3" style="border: 1px dashed #c8d2c9 !important;">
                                    <div class="card-body d-flex flex-column justify-content-between p-2" style="min-height: 118px;">
                                        <div>
                                            <span class="badge rounded-pill px-3 py-1 fw-semibold text-uppercase"
                                                style="background-color: #eef0eb; color: #6c7d73; font-size: 0.65rem; letter-spacing: 0.05em;">
                                                SPONSORED
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column align-items-center justify-content-center my-auto py-2 text-center">
                                            <img
                                                src="https://placehold.co/180x44/ffffff/8a9990?text=Sponsor+Logo+<?php echo $i; ?>"
                                                alt="Sponsor Logo Placeholder <?php echo $i; ?>"
                                                class="img-fluid object-fit-contain"
                                                style="max-height: 36px;"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Subsection 2: Powered By (3 Slots) -->
                <div class="mb-5">
                    <p class="small fw-semibold text-uppercase mb-3" style="color: #4f6659; letter-spacing: 0.14em;">
                        POWERED BY
                    </p>
                    <div class="row g-3 g-md-4">
                        <?php for ($j = 1; $j <= 3; $j++): ?>
                            <div class="col-12 col-md-4">
                                <div class="card h-100 rounded-4 bg-white p-3" style="border: 1px dashed #c8d2c9 !important;">
                                    <div class="card-body d-flex flex-column justify-content-between p-2" style="min-height: 118px;">
                                        <div>
                                            <span class="badge rounded-pill px-3 py-1 fw-semibold text-uppercase"
                                                style="background-color: #eef0eb; color: #6c7d73; font-size: 0.65rem; letter-spacing: 0.05em;">
                                                PARTNER
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column align-items-center justify-content-center my-auto py-2 text-center">
                                            <img
                                                src="https://placehold.co/200x44/ffffff/8a9990?text=Partner+Logo+<?php echo $j; ?>"
                                                alt="Partner Logo Placeholder <?php echo $j; ?>"
                                                class="img-fluid object-fit-contain"
                                                style="max-height: 36px;"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Bottom Dark Sponsor Callout Banner -->
                <div class="card border-0 rounded-4 shadow-sm p-4" style="background-color: #0b2618; color: #ffffff;">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                style="width: 48px; height: 48px; background-color: #9df557; color: #0b2618;">
                                <i class="bi bi-handshake fs-4"></i>
                            </span>
                            <div>
                                <h3 class="h6 fw-bold text-uppercase mb-1 text-white" style="letter-spacing: 0.03em;">
                                    INTERESTED IN BECOMING A SPONSOR?
                                </h3>
                                <p class="small mb-0" style="color: rgba(255, 255, 255, 0.75);">
                                    Support local Malaysian businesses and grow alongside them.
                                </p>
                            </div>
                        </div>

                        <a href="#section-11"
                        class="btn rounded-pill px-4 py-2 fw-bold text-uppercase border-0 d-inline-flex align-items-center justify-content-center gap-2 w-100 w-auto flex-shrink-0"
                        style="background-color: #9df557; color: #0b2618; font-size: 0.75rem; letter-spacing: 0.04em;">
                            <i class="bi bi-whatsapp fs-6"></i>
                            <span>CONTACT US ON WHATSAPP</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 10: Ready To Take Your Business Digital? -->
        <section id="section-10" class="container-fluid py-5 text-white" style="background-color: #0b2618;">
            <div class="container py-4 py-lg-5 text-center">

                <!-- Eyebrow, Headline & Subtitle -->
                <p class="fst-italic fw-medium mb-2" style="color: #9df557;">Siap untuk berniaga online?</p>
                <h2 class="display-5 fw-bold text-uppercase lh-1 mb-3 text-white">
                    READY TO TAKE YOUR<br>
                    BUSINESS DIGITAL?
                </h2>
                <p class="small mx-auto mb-4 mb-md-5" style="color: rgba(255, 255, 255, 0.78); max-width: 540px;">
                    Let your customers order easily while you focus on growing your business.
                </p>

                <!-- Primary & Secondary Pill Buttons -->
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-center gap-3 mb-5">
                    <a href="#section-11"
                    class="btn rounded-pill px-4 py-3 fw-bold text-uppercase border-0 w-100 w-auto"
                    style="background-color: #f59322; color: #143326; font-size: 0.78rem; letter-spacing: 0.04em; box-shadow: 0 0 24px rgba(245, 147, 34, 0.35);">
                        GET YOUR STORE SET UP
                    </a>

                    <a href="#section-8"
                    class="btn rounded-pill px-4 py-3 fw-bold text-uppercase w-100 w-auto"
                    style="background-color: rgba(255, 255, 255, 0.06); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.25); font-size: 0.78rem; letter-spacing: 0.04em;">
                        LIST MY STORE
                    </a>
                </div>

                <!-- WhatsApp Direct Chat Label & Pill Buttons -->
                <p class="small fw-semibold text-uppercase mb-3" style="color: #84f56b; letter-spacing: 0.14em; font-size: 0.72rem;">
                    CHAT WITH US ON WHATSAPP
                </p>

                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-center gap-3">
                    <a href="https://wa.me/601169893389"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 w-100 w-auto"
                    style="background-color: rgba(255, 255, 255, 0.04); color: #e9f2ec; border: 1px solid rgba(255, 255, 255, 0.18); font-size: 0.85rem;">
                        <i class="bi bi-whatsapp" style="color: #4ade80;"></i>
                        <span>+6011-69893389</span>
                    </a>

                    <a href="https://wa.me/60103055330"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 w-100 w-auto"
                    style="background-color: rgba(255, 255, 255, 0.04); color: #e9f2ec; border: 1px solid rgba(255, 255, 255, 0.18); font-size: 0.85rem;">
                        <i class="bi bi-whatsapp" style="color: #4ade80;"></i>
                        <span>+6010-3055330</span>
                    </a>
                </div>

            </div>
        </section>

        <?php
            $faqList = [
                [
                    'q' => 'Does it work for fashion, produce and handmade crafts?',
                    'a' => 'Yes. Kedai WA Kamek works for any business that sells products. Customers can add sizes, colours, weights, quantities or personalisation requests in the order notes.',
                ],
                [
                    'q' => 'Do my customers need an account?',
                    'a' => 'No. Customers can browse and order without signing in.',
                ],
                [
                    'q' => 'Do I need to download an app?',
                    'a' => 'No. Your store opens in any web browser through a link or QR code.',
                ],
                [
                    'q' => 'Do you charge commission?',
                    'a' => 'No. We charge a one-time setup fee per store. Your sales belong to you.',
                ],
                [
                    'q' => 'How do customers pay?',
                    'a' => 'You share your payment details on WhatsApp, such as DuitNow QR or online bank transfer.',
                ],
                [
                    'q' => 'What about delivery?',
                    'a' => 'You arrange delivery directly with the customer or a third-party courier. Delivery charges are separate.',
                ],
                [
                    'q' => 'Can I cancel anytime?',
                    'a' => 'Yes.',
                ],
                [
                    'q' => 'How do I get listed in Explore Kedai?',
                    'a' => 'Every store we set up can be listed in Explore Kedai. Contact us on WhatsApp and we will add you.',
                ],
            ];
        ?>

        <!-- Section 11: Frequently Asked Questions -->
        <section id="section-11" class="container-fluid py-5 border-top border-bottom" style="background-color: #f4f5ef; color: #13261d;">
            <div class="container py-3 py-lg-4">

                <!-- Section Heading -->
                <div class="text-center mb-4 mb-md-5">
                    <p class="fst-italic fw-medium mb-1" style="color: #4f7d2f;">Got questions?</p>
                    <h2 class="display-6 fw-bold text-uppercase lh-1 mb-0" style="color: #0e1f17;">
                        FREQUENTLY ASKED
                    </h2>
                </div>

                <!-- Mobile-Friendly Bootstrap Accordion -->
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-10 col-xl-9">
                        <div class="accordion d-flex flex-column gap-3" id="section11FaqAccordion">
                            <?php foreach ($faqList as $idx => $faq): ?>
                                <?php
                                    $itemNum = $idx + 1;
                                    $headingId = "s11FaqHeading{$itemNum}";
                                    $collapseId = "s11FaqCollapse{$itemNum}";
                                ?>
                                <div class="accordion-item rounded-4 overflow-hidden border bg-white" style="border-color: #e2e6de !important;">
                                    <h3 class="accordion-header" id="<?php echo $headingId; ?>">
                                        <button class="accordion-button collapsed fw-semibold py-3 px-3 px-sm-4 text-start"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?php echo $collapseId; ?>"
                                                aria-expanded="false"
                                                aria-controls="<?php echo $collapseId; ?>"
                                                style="background-color: #f8f9f5; color: #0f2018; box-shadow: none;">
                                            <?php echo htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                    </h3>
                                    <div id="<?php echo $collapseId; ?>"
                                        class="accordion-collapse collapse"
                                        aria-labelledby="<?php echo $headingId; ?>"
                                        data-bs-parent="#section11FaqAccordion">
                                        <div class="accordion-body px-3 px-sm-4 pb-3 pb-sm-4 pt-2 small" style="color: #56685e; line-height: 1.65;">
                                            <?php echo htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>
    <?php
}
require_once 'includes/footer.php'; 
?>