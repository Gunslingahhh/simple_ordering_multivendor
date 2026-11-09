<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Check if a secure vendor token is provided in the URL
$vendor_token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!empty($vendor_token)) {
    // ----------------------------------------------------
    // VENDOR STORE & MENU VIEW (When ?token=XYZ is present)
    // ----------------------------------------------------
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE token = ? AND is_active = 1");
    $stmt->execute([$vendor_token]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        echo '<div class="alert alert-danger mt-5 text-center shadow-sm py-4">
                <h4>Store not found or link is invalid.</h4>
                <p class="text-muted mb-3">Please check the store link and try again.</p>
                <a href="index.php" class="btn btn-dark fw-bold">Back to Home</a>
              </div>';
        require_once 'includes/footer.php';
        exit;
    }

    $vendor_id = $vendor['id'];

    // Fetch menu items for this vendor (available and in stock)
    $stmtMenu = $pdo->prepare("SELECT * FROM menu_items WHERE vendor_id = ? AND is_available = 1 AND stock > 0 ORDER BY name ASC");
    $stmtMenu->execute([$vendor_id]);
    $menu_items = $stmtMenu->fetchAll();
    ?>

    <!-- Vendor Header Info -->
    <div class="row mb-4 bg-white p-4 rounded shadow-sm align-items-center border-start border-4 border-success">
        <div class="col-md-3 text-center">
            <img src="uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" class="img-fluid rounded-circle shadow-sm" width="120" height="120" style="object-fit: cover;" alt="...">
        </div>
        <div class="col-md-9">
            <div class="d-flex align-items-center mb-2">
                <h1 class="fw-bold mb-0 me-3"><?= htmlspecialchars($vendor['store_name']) ?></h1>
                <?php if ($vendor['status'] === 'open'): ?>
                    <span class="badge bg-success fs-6">Open</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">Closed</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-1"><?= htmlspecialchars($vendor['description']) ?></p>
            <p class="text-muted mb-0 small"><i class="fa-solid fa-location-dot me-1 text-success"></i><?= htmlspecialchars($vendor['address']) ?> | <i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($vendor['contact_number']) ?></p>
        </div>
    </div>

    <div class="row">
        <!-- Menu Items Column -->
        <div class="col-lg-8 mb-4">
            <h3 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-book-open me-2 text-success"></i>Menu Items</h3>
            <?php if (empty($menu_items)): ?>
                <div class="alert alert-info">No menu items available for this vendor right now.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0">
                                <img src="uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" class="card-img-top object-fit-cover" height="160" alt="<?= htmlspecialchars($item['name']) ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold"><?= htmlspecialchars($item['name']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($item['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="fw-bold text-success fs-5">$<?= number_format($item['price'], 2) ?></span>
                                            <br><small class="text-muted">Stock: <span class="badge bg-secondary"><?= $item['stock'] ?></span></small>
                                        </div>
                                        <?php if ($vendor['status'] === 'open'): ?>
                                            <button class="btn btn-sm btn-main add-to-cart" 
                                                data-id="<?= $item['id'] ?>" 
                                                data-name="<?= htmlspecialchars($item['name']) ?>" 
                                                data-price="<?= $item['price'] ?>"
                                                data-stock="<?= $item['stock'] ?>">
                                                <i class="fa-solid fa-cart-plus me-1"></i> Add to Order
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Summary & Delivery Column -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top border-0" style="top: 2rem;">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-receipt me-2" style="color: #b2ff81;"></i>Order Summary</h5>
                </div>
                <div class="card-body bg-white border border-top-0 rounded-bottom">
                    <div id="cart-items" class="mb-3">
                        <p class="text-muted text-center py-3">Your order is empty.</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                        <span>Total:</span>
                        <span id="cart-total" class="text-success">$0.00</span>
                    </div>
                    
                    <div class="mb-2">
                        <label for="customer-name" class="form-label fw-semibold small">Your Name *</label>
                        <input type="text" id="customer-name" class="form-control form-control-sm" placeholder="e.g., John Doe" required>
                    </div>

                    <div class="mb-2">
                        <label for="customer-phone" class="form-label fw-semibold small">Phone Number *</label>
                        <input type="tel" id="customer-phone" class="form-control form-control-sm" placeholder="e.g., +1234567890" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Order Type *</label>
                        <select id="order-type" class="form-select form-select-sm">
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Self Pickup</option>
                        </select>
                    </div>

                    <div id="delivery-address-section" class="mb-3">
                        <label for="customer-address" class="form-label fw-semibold small">Delivery Address *</label>
                        <textarea id="customer-address" class="form-control form-control-sm" rows="2" placeholder="Street address, apartment, etc."></textarea>
                        
                        <div class="mt-2">
                            <button type="button" id="get-location-btn" class="btn btn-outline-dark btn-sm w-100">
                                <i class="fa-solid fa-map-pin me-1 text-success"></i> Use Google Maps Pin Location
                            </button>
                            <input type="hidden" id="maps-pin-url" value="">
                        </div>
                    </div>

                    <?php if ($vendor['status'] === 'open'): ?>
                        <button id="whatsapp-order-btn" class="btn btn-success w-100 fw-bold py-2 shadow-sm" disabled>
                            <i class="fa-brands fa-whatsapp me-2 fs-5"></i> Submit Order via WhatsApp
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 fw-bold py-2" disabled>Vendor is Closed</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const vendorName = "<?= addslashes($vendor['store_name']) ?>";
    const vendorWhatsApp = "<?= preg_replace('/[^0-9]/', '', $vendor['whatsapp_number']) ?>";

    let cart = [];

    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = parseFloat(this.getAttribute('data-price'));
            const maxStock = parseInt(this.getAttribute('data-stock'));

            const existing = cart.find(item => item.id === id);
            const currentQty = existing ? existing.quantity : 0;

            if (currentQty + 1 > maxStock) {
                alert(`Sorry, only ${maxStock} units of "${name}" are available in stock.`);
                return;
            }

            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({ id, name, price, quantity: 1, maxStock });
            }
            updateCartUI();
        });
    });

    function updateCartUI() {
        const cartContainer = document.getElementById('cart-items');
        const cartTotalEl = document.getElementById('cart-total');
        const orderBtn = document.getElementById('whatsapp-order-btn');

        if (cart.length === 0) {
            cartContainer.innerHTML = '<p class="text-muted text-center py-3">Your order is empty.</p>';
            cartTotalEl.textContent = '$0.00';
            if (orderBtn) orderBtn.disabled = true;
            return;
        }

        let html = '<ul class="list-group list-group-flush">';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.quantity;
            total += itemTotal;
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div>
                        <h6 class="mb-0">${item.name}</h6>
                        <small class="text-muted">$${item.price.toFixed(2)} x ${item.quantity}</small>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="changeQty(${index}, -1)">-</button>
                        <button class="btn btn-outline-secondary" onclick="changeQty(${index}, 1)">+</button>
                        <button class="btn btn-outline-danger" onclick="removeItem(${index})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </li>
            `;
        });

        html += '</ul>';
        cartContainer.innerHTML = html;
        cartTotalEl.textContent = '$' + total.toFixed(2);
        if (orderBtn) orderBtn.disabled = false;
    }

    function changeQty(index, delta) {
        cart[index].quantity += delta;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        updateCartUI();
    }

    function removeItem(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    document.getElementById('order-type').addEventListener('change', function() {
        const addressSection = document.getElementById('delivery-address-section');
        if (this.value === 'pickup') {
            addressSection.style.display = 'none';
        } else {
            addressSection.style.display = 'block';
        }
    });

    document.getElementById('get-location-btn').addEventListener('click', function() {
        const btn = this;
        const addressInput = document.getElementById('customer-address');
        
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Getting Location...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const mapsUrl = `https://www.maps.google.com/?q=${lat},${lng}`;
                
                addressInput.value = `Google Maps Pin Location:\n${mapsUrl}`;
                document.getElementById('maps-pin-url').value = mapsUrl;
                
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i> Location Pinned Successfully!';
            },
            function(error) {
                alert('Unable to retrieve your location. Please enter your address manually.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-map-pin me-1"></i> Use Google Maps Pin Location';
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    });

    const orderBtn = document.getElementById('whatsapp-order-btn');
    if (orderBtn) {
        orderBtn.addEventListener('click', function() {
            if (cart.length === 0) return;

            const customerName = document.getElementById('customer-name').value.trim();
            const customerPhone = document.getElementById('customer-phone').value.trim();
            const orderType = document.getElementById('order-type').value;
            const customerAddress = document.getElementById('customer-address').value.trim();

            if (!customerName || !customerPhone) {
                alert('Please enter your name and phone number.');
                document.getElementById('customer-name').focus();
                return;
            }

            if (orderType === 'delivery' && !customerAddress) {
                alert('Please provide a delivery address or use the Google Maps pin feature.');
                document.getElementById('customer-address').focus();
                return;
            }

            let message = `Hello *${vendorName}*, I would like to place an *${orderType.toUpperCase()}* order:\n\n`;
            message += `*Customer Name:* ${customerName}\n`;
            message += `*Phone Number:* ${customerPhone}\n`;
            message += `*Order Type:* ${orderType.toUpperCase()}\n`;
            
            if (orderType === 'delivery') {
                message += `*Delivery Address:* ${customerAddress}\n`;
            }
            message += `--------------------------------\n`;

            let total = 0;
            let receiptItemsHtml = '';
            
            cart.forEach(item => {
                let subtotal = item.price * item.quantity;
                total += subtotal;
                message += `• ${item.name} x ${item.quantity} - $${subtotal.toFixed(2)}\n`;
                
                receiptItemsHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <h6 class="mb-0">${item.name}</h6>
                            <small class="text-muted">$${item.price.toFixed(2)} x ${item.quantity}</small>
                        </div>
                        <span class="fw-bold">$${subtotal.toFixed(2)}</span>
                    </li>
                `;
            });

            message += `--------------------------------\n`;
            message += `*Total Amount:* $${total.toFixed(2)}\n\n`;
            message += `Please confirm my order. Thank you!`;

            const formData = new FormData();
            formData.append('vendor_id', <?= $vendor_id ?>);
            formData.append('customer_name', `${customerName} (${customerPhone}) - ${orderType.toUpperCase()}`);
            formData.append('total_amount', total);
            formData.append('cart_items', JSON.stringify(cart));

            fetch('log_order.php', {
                method: 'POST',
                body: formData
            });

            const encodedMessage = encodeURIComponent(message);
            const whatsappURL = `https://wa.me/${vendorWhatsApp}?text=${encodedMessage}`;
            window.open(whatsappURL, '_blank');

            const mainContainer = document.querySelector('main.container');
            mainContainer.innerHTML = `
                <div class="row justify-content-center">
                    <div class="col-md-7">
                        <div class="card shadow-sm border-success">
                            <div class="card-header bg-success text-white text-center py-3">
                                <i class="fa-solid fa-circle-check fs-1 mb-2"></i>
                                <h3 class="fw-bold mb-0">Order Sent Successfully!</h3>
                                <p class="mb-0 small">Your order has been submitted to <strong>${vendorName}</strong> via WhatsApp.</p>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-receipt me-2"></i>Order Receipt</h5>
                                <p class="mb-1 text-muted"><strong>Customer Name:</strong> ${customerName}</p>
                                <p class="mb-1 text-muted"><strong>Phone:</strong> ${customerPhone}</p>
                                <p class="mb-1 text-muted"><strong>Order Type:</strong> ${orderType.toUpperCase()}</p>
                                ${orderType === 'delivery' ? `<p class="mb-1 text-muted"><strong>Address:</strong> ${customerAddress}</p>` : ''}
                                <p class="mb-3 text-muted"><strong>Vendor:</strong> ${vendorName}</p>
                                <hr>
                                <ul class="list-group list-group-flush mb-3">
                                    ${receiptItemsHtml}
                                </ul>
                                <hr>
                                <div class="d-flex justify-content-between mb-4 fw-bold fs-4">
                                    <span>Total Paid:</span>
                                    <span class="text-success">$${total.toFixed(2)}</span>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="index.php?token=<?= $vendor_token ?>" class="btn btn-dark fw-bold">
                                        <i class="fa-solid fa-arrow-left me-2"></i> Back to Store Menu
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        Browse Other Vendors
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    </script>

<?php 
} else {
    // ----------------------------------------------------
    // LANDING PAGE VIEW (When no ?token is present)
    // ----------------------------------------------------
    ?>

    <!-- 1. HERO SECTION -->
    <div class="p-5 mb-5 bg-white rounded-3 shadow-sm border text-center position-relative overflow-hidden border-top border-4" style="border-color: #b2ff81 !important;">
        <div class="container-fluid py-4">
            <span class="badge px-3 py-2 rounded-pill fw-bold mb-3 fs-6 shadow-sm" style="background-color: #b2ff81; color: #1a1a1a;">
                <i class="fa-solid fa-bolt me-1"></i> Fast, Simple & Direct
            </span>
            <h1 class="display-4 fw-bold text-dark mb-3">Delicious Food, Straight to WhatsApp</h1>
            <p class="col-md-8 fs-5 mx-auto text-muted mb-4">
                Order your favorite meals directly from local food vendors with zero registration hassle. Just pick your items, drop your location, and send your order straight to the store's WhatsApp!
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#how-it-works" class="btn btn-dark btn-lg px-4 fw-bold">
                    <i class="fa-solid fa-circle-info me-2 text-success"></i> How It Works
                </a>
                <a href="#become-vendor" class="btn btn-main btn-lg px-4 fw-bold shadow-sm">
                    <i class="fa-solid fa-store me-2"></i> Join as a Vendor
                </a>
            </div>
        </div>
    </div>

    <!-- 2. PLATFORM FEATURES FOR CUSTOMERS -->
    <div id="how-it-works" class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark">Why Order With Kedai-WA-Kamek?</h2>
            <p class="text-muted">Designed for convenience and speed</p>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <div class="col">
                <div class="card h-100 shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <div class="rounded-circle d-inline-flex p-3 mb-3 shadow-sm" style="background-color: rgba(178, 255, 129, 0.3); color: #2e7d32;">
                            <i class="fa-solid fa-user-slash fs-2"></i>
                        </div>
                        <h5 class="fw-bold">No Registration Needed</h5>
                        <p class="text-muted small mb-0">Skip the lengthy signup forms and password resets. Order immediately without creating an account.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex p-3 mb-3 shadow-sm">
                            <i class="fa-brands fa-whatsapp fs-2"></i>
                        </div>
                        <h5 class="fw-bold">Direct WhatsApp Checkout</h5>
                        <p class="text-muted small mb-0">Your order is formatted instantly into a clear WhatsApp message sent directly to the vendor's phone.</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100 shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <div class="rounded-circle d-inline-flex p-3 mb-3 shadow-sm" style="background-color: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                            <i class="fa-solid fa-location-dot fs-2"></i>
                        </div>
                        <h5 class="fw-bold">1-Click Map Location</h5>
                        <p class="text-muted small mb-0">Share your live delivery location pin with Google Maps integration so your food arrives accurately every time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. VENDOR RECRUITMENT & VALUE PROPOSITION SECTION -->
    <div id="become-vendor" class="bg-dark text-white rounded-3 p-5 shadow-sm mb-5 border-bottom border-4" style="border-color: #b2ff81 !important;">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge px-3 py-1 rounded-pill fw-bold mb-2 shadow-sm" style="background-color: #b2ff81; color: #1a1a1a;">For Restaurant & Food Stall Owners</span>
                <h2 class="fw-bold display-6 mb-3">Grow Your Food Business with Zero Commissions</h2>
                <p class="text-white-50 mb-4">
                    Stop paying heavy commission fees to third-party delivery apps. Get your own dedicated store link, manage your menu items, track real-time stock, and receive orders directly on your WhatsApp line!
                </p>
                <div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
                    <div class="col d-flex align-items-center">
                        <i class="fa-solid fa-check-circle fs-5 me-2" style="color: #b2ff81;"></i>
                        <span>100% Direct Customer Relation</span>
                    </div>
                    <div class="col d-flex align-items-center">
                        <i class="fa-solid fa-check-circle fs-5 me-2" style="color: #b2ff81;"></i>
                        <span>Real-Time Inventory Control</span>
                    </div>
                    <div class="col d-flex align-items-center">
                        <i class="fa-solid fa-check-circle fs-5 me-2" style="color: #b2ff81;"></i>
                        <span>Unique Private Store Link</span>
                    </div>
                    <div class="col d-flex align-items-center">
                        <i class="fa-solid fa-check-circle fs-5 me-2" style="color: #b2ff81;"></i>
                        <span>Instant Order Notifications</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <div class="card bg-light text-dark p-4 shadow border-0">
                    <div class="card-body">
                        <h4 class="fw-bold mb-2">Want to Partner With Us?</h4>
                        <p class="text-muted small mb-3">Contact our admin team or sign in to set up your digital menu in minutes.</p>
                        <div class="d-grid gap-2">
                            <a href="login.php" class="btn btn-main fw-bold py-2 shadow-sm">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                            </a>
                            <a href="https://wa.me/1234567890?text=Hi!%20I%20would%20like%20to%20register%20my%20store%20on%20Kedai-WA-Kamek" target="_blank" class="btn btn-outline-dark fw-bold py-2">
                                <i class="fa-brands fa-whatsapp me-1 text-success"></i> Contact Admin to Register
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php 
}
require_once 'includes/footer.php'; 
?>