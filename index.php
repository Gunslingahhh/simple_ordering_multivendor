<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if a secure vendor token is provided in the URL
$vendor_token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($vendor_token)) {
    ?>
    <?php
        // Handle AJAX order logging into `food_ordering_db.orders` before opening WhatsApp
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax_action'] ?? '') === 'record_order') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');

            $orderVendorId = (int) ($_POST['vendor_id'] ?? 0);
            $customerName = trim((string) ($_POST['customer_name'] ?? ''));
            $totalAmount = (float) ($_POST['total_amount'] ?? 0);
            $orderDetails = trim((string) ($_POST['order_details'] ?? ''));

            if (isset($pdo) && $pdo instanceof PDO && $orderVendorId > 0 && $customerName !== '' && $orderDetails !== '') {
                try {
                    $insStmt = $pdo->prepare("
                        INSERT INTO orders (vendor_id, customer_name, total_amount, order_details)
                        VALUES (:vendor_id, :customer_name, :total_amount, :order_details)
                    ");
                    $insStmt->execute([
                        ':vendor_id' => $orderVendorId,
                        ':customer_name' => $customerName,
                        ':total_amount' => $totalAmount,
                        ':order_details' => $orderDetails,
                    ]);
                    echo json_encode(['ok' => true, 'order_id' => (int) $pdo->lastInsertId()]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['ok' => false]);
                    exit;
                }
            }

            echo json_encode(['ok' => false]);
            exit;
        }

        $activeVendor = null;
        $vendorMenuItems = [];

        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $vStmt = $pdo->prepare("
                    SELECT id, token, user_id, store_name, description, address, contact_number, whatsapp_number, logo, status, is_active
                    FROM vendors
                    WHERE token = :token
                    LIMIT 1
                ");
                $vStmt->execute([':token' => $vendor_token]);
                $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);

                if ($vRow && (int) ($vRow['is_active'] ?? 1) === 1) {
                    $activeVendor = $vRow;

                    $mStmt = $pdo->prepare("
                        SELECT id, vendor_id, name, description, price, stock, image, is_available
                        FROM menu_items
                        WHERE vendor_id = :vendor_id
                        ORDER BY id ASC
                    ");
                    $mStmt->execute([':vendor_id' => (int) $activeVendor['id']]);
                    $vendorMenuItems = $mStmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $activeVendor = null;
            }
        }
        ?>

        <?php if ($activeVendor === null): ?>
            <div class="container py-5">
                <div class="card border rounded-4 bg-white p-4 p-sm-5 text-center mx-auto" style="max-width: 540px; border-color: #e4e9e5 !important;">
                    <div class="mb-3 text-danger">
                        <i class="bi bi-shop-window fs-1"></i>
                    </div>
                    <h1 class="h4 fw-bold mb-2" style="color: #0e1f17;">Kedai Tidak Dijumpai</h1>
                    <p class="small mb-4" style="color: #6b7c73;">
                        Pautan atau token kedai tidak sah atau kedai sedang tidak aktif.
                    </p>
                    <a href="index.php"
                    class="btn rounded-pill px-4 py-2 fw-semibold border-0"
                    style="background-color: #143326; color: #ffffff;">
                        Kembali ke Halaman Utama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $isStoreOpen = (($activeVendor['status'] ?? 'open') === 'open');
            $cleanWa = preg_replace('/\D+/', '', (string) ($activeVendor['whatsapp_number'] ?? ''));
            $storeNameEsc = htmlspecialchars((string) $activeVendor['store_name'], ENT_QUOTES, 'UTF-8');
            $storeDescEsc = htmlspecialchars((string) ($activeVendor['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $storeAddrEsc = htmlspecialchars((string) ($activeVendor['address'] ?? 'Sarawak'), ENT_QUOTES, 'UTF-8');
            $bannerPlaceholder = 'https://placehold.co/1200x360/7c1d18/f8e5b4?text=' . urlencode((string) $activeVendor['store_name']);
            ?>

            <div class="container py-3 py-sm-4 pb-5 mb-5">

                <!-- Store Banner & Info Bar -->
                <div class="card border rounded-4 bg-white overflow-hidden mb-3 shadow-sm" style="border-color: #e6ebe7 !important;">
                    <div class="position-relative" style="height: 240px;">
                        <img
                            src="<?php echo htmlspecialchars($bannerPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo $storeNameEsc; ?> Banner Placeholder"
                            class="w-100 h-100 object-fit-cover"
                        >
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-end p-3 p-sm-4 p-lg-5"
                            style="background: linear-gradient(180deg, rgba(14,31,23,0.08) 0%, rgba(14,31,23,0.78) 100%);">
                            <h1 class="h2 fw-bold text-white mb-1" style="letter-spacing: -0.02em;">
                                <?php echo $storeNameEsc; ?>
                            </h1>
                            <p class="small text-white-50 mb-0">
                                <?php echo $storeDescEsc; ?>
                            </p>
                        </div>
                    </div>

                    <div class="px-3 px-sm-4 py-3 border-top bg-white d-flex flex-wrap align-items-center gap-3 small"
                        style="border-color: #eef2ef !important; color: #6b7c73;">
                        <span class="fw-semibold d-inline-flex align-items-center gap-1 <?php echo $isStoreOpen ? 'text-success' : 'text-danger'; ?>">
                            <i class="bi bi-circle-fill" style="font-size: 0.45rem;"></i>
                            <span><?php echo $isStoreOpen ? 'Buka Sekarang' : 'Tutup Sekarang'; ?></span>
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="bi bi-clock"></i>
                            <span>10:00 am – 8:00 pm</span>
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo $storeAddrEsc; ?></span>
                            <span>&middot;</span>
                            <a href="#peta-lokasi" class="text-decoration-none" style="color: #6b7c73;">Lihat peta</a>
                        </span>
                    </div>
                </div>

                <!-- Search Input (`Cari menu`) -->
                <div class="mb-3">
                    <div class="input-group bg-white border rounded-3 px-3 py-2 align-items-center"
                        style="border-color: #e4e9e5 !important;">
                        <i class="bi bi-search me-2" style="color: #6b7c73;"></i>
                        <input
                            type="search"
                            id="vendorMenuSearchInput"
                            class="form-control border-0 shadow-none bg-transparent p-0 small"
                            placeholder="Cari menu"
                            aria-label="Cari menu"
                            style="color: #13261d;"
                        >
                    </div>
                </div>

                <!-- Filter Pill (`Semua`) -->
                <div class="mb-4">
                    <button
                        type="button"
                        id="filterAllMenuBtn"
                        class="btn btn-sm rounded-pill px-3 py-2 fw-semibold border-0"
                        style="background-color: #143326; color: #ffffff;">
                        Semua
                    </button>
                </div>

                <!-- Menu Items List -->
                <?php if (empty($vendorMenuItems)): ?>
                    <div class="card border rounded-4 bg-white p-4 text-center small" style="border-color: #e4e9e5 !important; color: #6b7c73;">
                        Tiada item menu untuk kedai ini buat masa ini.
                    </div>
                <?php else: ?>
                    <div class="row g-3 g-lg-4" id="vendorMenuItemsGrid">
                        <?php foreach ($vendorMenuItems as $menu): ?>
                            <?php
                            $itemId = (int) $menu['id'];
                            $itemName = (string) $menu['name'];
                            $itemDesc = (string) ($menu['description'] ?? '');
                            $itemPrice = (float) $menu['price'];
                            $itemStock = (int) ($menu['stock'] ?? 0);
                            $isAvail = !empty($menu['is_available']) && ($itemStock > 0);
                            $itemCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $itemName), 0, 3) ?: 'MN');
                            $itemPlaceholder = 'https://placehold.co/120x120/fdf4f2/7c1d18?text=' . urlencode($itemCode);
                            ?>
                            <div class="col-12 col-lg-6 vendor-menu-col"
                                data-search="<?php echo htmlspecialchars(strtolower($itemName . ' ' . $itemDesc), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="card border rounded-4 bg-white p-3 h-100" style="border-color: #e4e9e5 !important;">
                                    <div class="d-flex align-items-center justify-content-between gap-3 h-100">

                                        <div class="d-flex align-items-center gap-3 min-w-0">
                                            <img
                                                src="<?php echo htmlspecialchars($itemPlaceholder, ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="<?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?> Placeholder"
                                                class="rounded-3 object-fit-cover flex-shrink-0 border"
                                                style="width: 74px; height: 74px; border-color: #e4e9e5 !important;"
                                            >
                                            <div class="min-w-0">
                                                <h2 class="h6 fw-bold mb-1 text-truncate" style="color: #0e1f17;">
                                                    <?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?>
                                                </h2>
                                                <div class="small text-truncate mb-1" style="color: #6b7c73;">
                                                    <?php echo htmlspecialchars($itemDesc, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="fw-bold small" style="color: #0e1f17;">
                                                    RM <?php echo number_format($itemPrice, 2); ?>
                                                </div>
                                                <div class="small" style="color: <?php echo $isAvail ? '#6b7c73' : '#b91c1c'; ?>;">
                                                    <?php echo $isAvail ? 'Tersedia' : 'Habis'; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0">
                                            <?php if (!$isStoreOpen || !$isAvail): ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border disabled"
                                                    disabled
                                                    style="background-color: #f4f5ef; border-color: #dce2da !important; color: #6b7c73;">
                                                    Habis
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 menu-add-btn"
                                                    data-id="<?php echo $itemId; ?>"
                                                    data-name="<?php echo htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-price="<?php echo number_format($itemPrice, 2, '.', ''); ?>"
                                                    data-max="<?php echo $itemStock; ?>"
                                                    style="background-color: #143326; color: #ffffff;">
                                                    + Tambah
                                                </button>

                                                <div class="d-none align-items-center gap-2 menu-qty-group"
                                                    data-id="<?php echo $itemId; ?>">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0 border-0 menu-dec-btn"
                                                        data-id="<?php echo $itemId; ?>"
                                                        aria-label="Kurangkan kuantiti"
                                                        style="width: 28px; height: 28px; background-color: #143326; color: #ffffff;">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <span class="fw-bold small menu-qty-value"
                                                        data-id="<?php echo $itemId; ?>"
                                                        style="min-width: 20px; text-align: center; color: #0e1f17;">
                                                        0
                                                    </span>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0 border-0 menu-inc-btn"
                                                        data-id="<?php echo $itemId; ?>"
                                                        aria-label="Tambah kuantiti"
                                                        style="width: 28px; height: 28px; background-color: #143326; color: #ffffff;">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Location Anchor -->
                <div id="peta-lokasi" class="card border rounded-4 bg-white p-3 p-sm-4 mt-4" style="border-color: #e4e9e5 !important;">
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                        <div>
                            <h2 class="h6 fw-bold mb-1" style="color: #0e1f17;">Lokasi Kedai</h2>
                            <p class="small mb-0" style="color: #6b7c73;">
                                <i class="bi bi-geo-alt me-1"></i><?php echo $storeAddrEsc; ?>
                            </p>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo urlencode($storeNameEsc . ' ' . $storeAddrEsc); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-sm rounded-3 border bg-white px-3 py-2 fw-semibold"
                        style="border-color: #dce2da !important; color: #0e1f17;">
                            <i class="bi bi-map me-1"></i>Buka Google Maps
                        </a>
                    </div>
                </div>

            </div>

            <!-- Floating Bottom Checkout Bar (`Lihat Pesanan & Checkout`) -->
            <div
                id="vendorCheckoutBar"
                class="fixed-bottom px-3 px-sm-4 pb-3"
                style="z-index: 1025; pointer-events: none;">
                <div class="container px-0 d-flex justify-content-center">
                    <button
                        type="button"
                        id="openCheckoutModalBtn"
                        data-bs-toggle="modal"
                        data-bs-target="#yourOrderModal"
                        class="btn w-100 rounded-3 px-3 px-sm-4 py-3 fw-semibold border-0 shadow-lg d-flex align-items-center justify-content-between"
                        style="max-width: 760px; pointer-events: auto; background-color: #143326; color: #ffffff;">
                        <span class="d-inline-flex align-items-center gap-2">
                            <span id="checkoutBadgeCount"
                                class="badge rounded-pill fw-bold"
                                style="background-color: #f59322; color: #143326;">
                                0
                            </span>
                            <span>Lihat Pesanan &amp; Checkout</span>
                        </span>
                        <span id="checkoutTotalAmount" class="fw-bold">RM 0.00</span>
                    </button>
                </div>
            </div>

            <!-- Modal: Your Order -->
            <div class="modal fade" id="yourOrderModal" tabindex="-1" aria-labelledby="yourOrderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 overflow-hidden">

                        <div class="modal-header border-0 px-4 py-3 text-white" style="background-color: #143326;">
                            <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="yourOrderModalLabel">
                                <i class="bi bi-receipt-cutoff"></i>
                                <span>Your Order</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-3 p-sm-4" style="background-color: #f4f5ef;">
                            <div class="card border-0 rounded-4 bg-white p-3 mb-3 shadow-sm">
                                <div id="orderModalItemsContainer" class="d-flex flex-column gap-3">
                                    <div class="text-center small py-2" style="color: #6b7c73;">
                                        Tiada item dalam pesanan lagi.
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 rounded-4 bg-white p-3 p-sm-4 shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="h5 fw-bold mb-0" style="color: #0e1f17;">Total:</span>
                                    <span id="orderModalTotalText" class="h5 fw-bold mb-0 text-success">RM 0.00</span>
                                </div>

                                <div class="mb-3">
                                    <label for="orderCustomerName" class="form-label small fw-semibold" style="color: #13261d;">
                                        Your Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        id="orderCustomerName"
                                        class="form-control rounded-3"
                                        placeholder="e.g. John Doe"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="orderCustomerPhone" class="form-label small fw-semibold" style="color: #13261d;">
                                        Phone Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel"
                                        id="orderCustomerPhone"
                                        class="form-control rounded-3"
                                        placeholder="e.g. +60123456789"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="orderTypeSelect" class="form-label small fw-semibold" style="color: #13261d;">
                                        Order Type <span class="text-danger">*</span>
                                    </label>
                                    <select id="orderTypeSelect" class="form-select rounded-3">
                                        <option value="Delivery" selected>Delivery</option>
                                        <option value="Pickup">Pickup</option>
                                    </select>
                                </div>

                                <div id="orderDeliveryAddressGroup" class="mb-3">
                                    <label for="orderDeliveryAddress" class="form-label small fw-semibold" style="color: #13261d;">
                                        Delivery Address <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="orderDeliveryAddress"
                                            class="form-control rounded-3 mb-2"
                                            rows="3"
                                            placeholder="Street name, house number..."></textarea>

                                    <button type="button"
                                            id="useGoogleMapsPinBtn"
                                            class="btn w-100 rounded-3 py-2 fw-semibold small border bg-white d-inline-flex align-items-center justify-content-center gap-2"
                                            style="border-color: #dce2da !important; color: #0e1f17;">
                                        <i class="bi bi-geo-alt-fill text-danger"></i>
                                        <span>Use Google Maps Pin Location</span>
                                    </button>
                                </div>

                                <div id="orderFormAlert" class="alert alert-warning d-none py-2 px-3 small mb-0" role="alert"></div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 bg-white px-3 px-sm-4 py-3">
                            <button type="button"
                                    id="submitOrderWhatsappBtn"
                                    data-vendor-id="<?php echo (int) $activeVendor['id']; ?>"
                                    data-wa="<?php echo htmlspecialchars($cleanWa, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-store="<?php echo $storeNameEsc; ?>"
                                    class="btn w-100 rounded-pill py-3 fw-bold border-0 d-inline-flex align-items-center justify-content-center gap-2"
                                    style="background-color: #143326; color: #ffffff;">
                                <i class="bi bi-whatsapp"></i>
                                <span>Submit Order via WhatsApp</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('vendorMenuSearchInput');
                const menuCols = document.querySelectorAll('.vendor-menu-col');

                if (searchInput && menuCols.length > 0) {
                    searchInput.addEventListener('input', function () {
                        const term = searchInput.value.trim().toLowerCase();
                        menuCols.forEach(function (col) {
                            const hay = col.getAttribute('data-search') || '';
                            col.classList.toggle('d-none', term !== '' && !hay.includes(term));
                        });
                    });
                }

                const cart = {};
                const remarks = {};
                const itemsMeta = {};

                document.querySelectorAll('.menu-add-btn').forEach(function (btn) {
                    const id = btn.getAttribute('data-id');
                    itemsMeta[id] = {
                        name: btn.getAttribute('data-name') || 'Item',
                        price: parseFloat(btn.getAttribute('data-price') || '0') || 0,
                        max: parseInt(btn.getAttribute('data-max') || '99', 10) || 99
                    };
                    cart[id] = 0;
                    remarks[id] = '';

                    btn.addEventListener('click', function () {
                        if (cart[id] < itemsMeta[id].max) {
                            cart[id] += 1;
                            syncUI();
                        }
                    });
                });

                document.querySelectorAll('.menu-inc-btn').forEach(function (btn) {
                    const id = btn.getAttribute('data-id');
                    btn.addEventListener('click', function () {
                        if (itemsMeta[id] && cart[id] < itemsMeta[id].max) {
                            cart[id] += 1;
                            syncUI();
                        }
                    });
                });

                document.querySelectorAll('.menu-dec-btn').forEach(function (btn) {
                    const id = btn.getAttribute('data-id');
                    btn.addEventListener('click', function () {
                        if (cart[id] > 0) {
                            cart[id] -= 1;
                            syncUI();
                        }
                    });
                });

                const orderTypeSelect = document.getElementById('orderTypeSelect');
                const deliveryAddrGroup = document.getElementById('orderDeliveryAddressGroup');
                if (orderTypeSelect && deliveryAddrGroup) {
                    orderTypeSelect.addEventListener('change', function () {
                        deliveryAddrGroup.classList.toggle('d-none', orderTypeSelect.value !== 'Delivery');
                    });
                }

                const pinBtn = document.getElementById('useGoogleMapsPinBtn');
                const addrInput = document.getElementById('orderDeliveryAddress');
                if (pinBtn && addrInput) {
                    pinBtn.addEventListener('click', function () {
                        if (!navigator.geolocation) {
                            addrInput.value = (addrInput.value ? addrInput.value + '\n' : '') + 'Pin Lokasi: https://maps.google.com/?q=1.4750,110.5110';
                            return;
                        }
                        pinBtn.disabled = true;
                        navigator.geolocation.getCurrentPosition(
                            function (pos) {
                                const pinUrl = 'https://maps.google.com/?q=' + pos.coords.latitude.toFixed(6) + ',' + pos.coords.longitude.toFixed(6);
                                addrInput.value = (addrInput.value.trim() ? addrInput.value.trim() + '\n' : '') + 'Pin Lokasi: ' + pinUrl;
                                pinBtn.disabled = false;
                            },
                            function () {
                                addrInput.value = (addrInput.value.trim() ? addrInput.value.trim() + '\n' : '') + 'Pin Lokasi: https://maps.google.com/?q=1.4750,110.5110';
                                pinBtn.disabled = false;
                            }
                        );
                    });
                }

                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function syncUI() {
                    let totalQty = 0;
                    let totalAmount = 0;
                    const activeIds = [];

                    Object.keys(cart).forEach(function (id) {
                        const qty = cart[id];
                        const meta = itemsMeta[id];
                        if (!meta) return;

                        const addBtn = document.querySelector('.menu-add-btn[data-id="' + id + '"]');
                        const qtyGroup = document.querySelector('.menu-qty-group[data-id="' + id + '"]');
                        const qtyVal = document.querySelector('.menu-qty-value[data-id="' + id + '"]');

                        if (qty > 0) {
                            if (addBtn) addBtn.classList.add('d-none');
                            if (qtyGroup) {
                                qtyGroup.classList.remove('d-none');
                                qtyGroup.classList.add('d-inline-flex');
                            }
                            if (qtyVal) qtyVal.textContent = String(qty);

                            totalQty += qty;
                            totalAmount += qty * meta.price;
                            activeIds.push(id);
                        } else {
                            if (addBtn) addBtn.classList.remove('d-none');
                            if (qtyGroup) {
                                qtyGroup.classList.add('d-none');
                                qtyGroup.classList.remove('d-inline-flex');
                            }
                            if (qtyVal) qtyVal.textContent = '0';
                        }
                    });

                    const badgeEl = document.getElementById('checkoutBadgeCount');
                    const totalEl = document.getElementById('checkoutTotalAmount');
                    const modalTotalEl = document.getElementById('orderModalTotalText');
                    const modalItemsEl = document.getElementById('orderModalItemsContainer');

                    if (badgeEl) badgeEl.textContent = String(totalQty);
                    if (totalEl) totalEl.textContent = 'RM ' + totalAmount.toFixed(2);
                    if (modalTotalEl) modalTotalEl.textContent = 'RM ' + totalAmount.toFixed(2);

                    if (modalItemsEl) {
                        if (activeIds.length === 0) {
                            modalItemsEl.innerHTML = '<div class="text-center small py-2" style="color: #6b7c73;">Tiada item dalam pesanan lagi.</div>';
                        } else {
                            modalItemsEl.innerHTML = activeIds.map(function (id) {
                                const meta = itemsMeta[id];
                                const qty = cart[id];
                                const remarkVal = remarks[id] || '';
                                return (
                                    '<div class="pb-3 border-bottom">' +
                                        '<div class="d-flex align-items-start justify-content-between gap-2 mb-2">' +
                                            '<div>' +
                                                '<div class="fw-bold small" style="color: #0e1f17;">' + escapeHtml(meta.name) + '</div>' +
                                                '<div class="small" style="color: #6b7c73;">RM ' + meta.price.toFixed(2) + ' x ' + qty + '</div>' +
                                            '</div>' +
                                            '<div class="d-inline-flex align-items-center gap-1">' +
                                                '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 modal-item-dec" data-id="' + id + '">-</button>' +
                                                '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 modal-item-inc" data-id="' + id + '">+</button>' +
                                                '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 modal-item-del" data-id="' + id + '" title="Buang item"><i class="bi bi-trash"></i></button>' +
                                            '</div>' +
                                        '</div>' +
                                        '<input type="text" class="form-control form-control-sm rounded-3 modal-item-remark" data-id="' + id + '" value="' + escapeHtml(remarkVal) + '" placeholder="Add remarks (e.g. Size / colour / note)">' +
                                    '</div>'
                                );
                            }).join('');

                            modalItemsEl.querySelectorAll('.modal-item-dec').forEach(function (b) {
                                b.addEventListener('click', function () {
                                    const id = b.getAttribute('data-id');
                                    if (cart[id] > 0) {
                                        cart[id] -= 1;
                                        syncUI();
                                    }
                                });
                            });

                            modalItemsEl.querySelectorAll('.modal-item-inc').forEach(function (b) {
                                b.addEventListener('click', function () {
                                    const id = b.getAttribute('data-id');
                                    if (itemsMeta[id] && cart[id] < itemsMeta[id].max) {
                                        cart[id] += 1;
                                        syncUI();
                                    }
                                });
                            });

                            modalItemsEl.querySelectorAll('.modal-item-del').forEach(function (b) {
                                b.addEventListener('click', function () {
                                    const id = b.getAttribute('data-id');
                                    cart[id] = 0;
                                    remarks[id] = '';
                                    syncUI();
                                });
                            });

                            modalItemsEl.querySelectorAll('.modal-item-remark').forEach(function (inp) {
                                inp.addEventListener('input', function () {
                                    const id = inp.getAttribute('data-id');
                                    remarks[id] = inp.value;
                                });
                            });
                        }
                    }
                }

                const submitWaBtn = document.getElementById('submitOrderWhatsappBtn');
                const alertBox = document.getElementById('orderFormAlert');

                if (submitWaBtn) {
                    submitWaBtn.addEventListener('click', function () {
                        const nameInput = document.getElementById('orderCustomerName');
                        const phoneInput = document.getElementById('orderCustomerPhone');
                        const typeInput = document.getElementById('orderTypeSelect');
                        const addrInputEl = document.getElementById('orderDeliveryAddress');

                        const custName = nameInput ? nameInput.value.trim() : '';
                        const custPhone = phoneInput ? phoneInput.value.trim() : '';
                        const orderType = typeInput ? typeInput.value : 'Delivery';
                        const delivAddr = addrInputEl ? addrInputEl.value.trim() : '';

                        const lines = [];
                        let totalAmount = 0;

                        Object.keys(cart).forEach(function (id) {
                            const qty = cart[id];
                            const meta = itemsMeta[id];
                            if (qty > 0 && meta) {
                                const sub = qty * meta.price;
                                totalAmount += sub;
                                const note = (remarks[id] || '').trim();
                                lines.push('- ' + meta.name + ' x' + qty + ' (RM ' + sub.toFixed(2) + ')' + (note ? ' [' + note + ']' : ''));
                            }
                        });

                        if (lines.length === 0) {
                            if (alertBox) {
                                alertBox.textContent = 'Sila pilih sekurang-kurangnya satu item menu.';
                                alertBox.classList.remove('d-none');
                            }
                            return;
                        }
                        if (!custName || !custPhone || (orderType === 'Delivery' && !delivAddr)) {
                            if (alertBox) {
                                alertBox.textContent = 'Sila lengkapkan ruangan wajib (*) sebelum menghantar pesanan.';
                                alertBox.classList.remove('d-none');
                            }
                            return;
                        }

                        if (alertBox) {
                            alertBox.classList.add('d-none');
                        }

                        const storeName = submitWaBtn.getAttribute('data-store') || 'Kedai';
                        const waNum = submitWaBtn.getAttribute('data-wa') || '';
                        const vendorId = submitWaBtn.getAttribute('data-vendor-id') || '';

                        const summaryText = [
                            'Pesanan Baharu — ' + storeName,
                            'Nama: ' + custName,
                            'Telefon: ' + custPhone,
                            'Jenis: ' + orderType,
                            orderType === 'Delivery' ? 'Alamat: ' + delivAddr : '',
                            'Item:',
                            lines.join('\n'),
                            'Jumlah: RM ' + totalAmount.toFixed(2)
                        ].filter(Boolean).join('\n');

                        const waUrl = waNum
                            ? 'https://wa.me/' + encodeURIComponent(waNum) + '?text=' + encodeURIComponent(summaryText)
                            : '#';

                        const formData = new URLSearchParams();
                        formData.append('ajax_action', 'record_order');
                        formData.append('vendor_id', vendorId);
                        formData.append('customer_name', custName);
                        formData.append('total_amount', totalAmount.toFixed(2));
                        formData.append('order_details', lines.join('; ') + ' | ' + orderType + (delivAddr ? ' (' + delivAddr + ')' : ''));

                        fetch('index.php?token=' + encodeURIComponent('<?php echo htmlspecialchars($vendor_token, ENT_QUOTES, 'UTF-8'); ?>'), {
                            method: 'POST',
                            body: formData
                        }).finally(function () {
                            if (waUrl !== '#') {
                                window.open(waUrl, '_blank', 'noopener,noreferrer');
                            }
                        });
                    });
                }

                syncUI();
            });
            </script>
        <?php endif; ?>
    <?php
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