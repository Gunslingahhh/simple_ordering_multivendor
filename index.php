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

    <!-- 1. HERO BANNER SECTION (Inspired by reference layout) -->
    <div class="hero-banner p-4 p-md-5 mb-5 shadow-sm">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold mb-3 small">
                    <i class="fa-solid fa-seedling me-1"></i> FRESHLY MADE, JUST FOR YOU
                </span>
                <h1 class="display-4 fw-bold text-dark mb-3" style="line-height: 1.1;">
                    <?= htmlspecialchars($vendor['store_name']) ?>.
                </h1>
                <p class="fs-5 text-muted mb-4">
                    <?= htmlspecialchars($vendor['description']) ?> Order directly via WhatsApp in a few taps.
                </p>
                <div class="d-flex align-items-center text-muted small gap-3 flex-wrap">
                    <span><i class="fa-solid fa-clock text-success me-1"></i> Made fresh when you order</span>
                    <span><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp Checkout</span>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="position-relative d-inline-block">
                    <img src="uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" 
                         class="rounded-4 shadow-sm object-fit-cover" 
                         width="100%" height="280" style="max-width: 450px;" alt="Store Banner">
                    <div class="position-absolute bottom-0 start-0 m-3 bg-white p-2 px-3 rounded-pill shadow-sm small fw-bold text-dark">
                        <?php if ($vendor['status'] === 'open'): ?>
                            <span class="text-success"><i class="fa-solid fa-circle me-1 small"></i> Store Open</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fa-solid fa-circle me-1 small"></i> Store Closed</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MENU HEADER, SEARCH & CATEGORIES -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold text-dark mb-0">Our menu.</h2>
            <span class="text-muted small">Fresh picks. Happy plates.</span>
        </div>

        <!-- Search Bar -->
        <div class="mb-3">
            <div class="input-group search-bar-pill bg-white shadow-sm">
                <span class="input-group-text bg-transparent border-0 pe-2"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" id="menuSearchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="Search your next favourite...">
                <span class="input-group-text bg-transparent border-0 text-muted small"><i class="fa-solid fa-sliders me-1"></i> <?= count($menu_items) ?> Items</span>
            </div>
        </div>

        <!-- Filter Chips (Static examples matching reference design style) -->
        <div class="d-flex gap-2 overflow-auto pb-2" style="white-space: nowrap;">
            <button type="button" class="btn category-pill active px-4 py-2 fw-semibold small">
                <i class="fa-solid fa-utensils me-1"></i> All items
            </button>
            <button type="button" class="btn category-pill px-4 py-2 fw-semibold small">
                <i class="fa-solid fa-bowl-food me-1"></i> Signature Dishes
            </button>
            <button type="button" class="btn category-pill px-4 py-2 fw-semibold small">
                <i class="fa-solid fa-mug-hot me-1"></i> Drinks
            </button>
            <button type="button" class="btn category-pill px-4 py-2 fw-semibold small">
                <i class="fa-solid fa-cookie-bite me-1"></i> Sweet treats
            </button>
        </div>
    </div>

    <!-- 3. MAIN LAYOUT: MENU GRID & ORDER CART -->
    <div class="row pb-5 mb-5">
        <!-- Menu Grid Column -->
        <div class="col-lg-8 mb-4">
            <?php if (empty($menu_items)): ?>
                <div class="alert bg-white text-center py-5 shadow-sm rounded-4 border-0">
                    <i class="fa-solid fa-plate-wheat fs-1 text-muted mb-3"></i>
                    <h5>No menu items available right now.</h5>
                    <p class="text-muted mb-0">Please check back later!</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 g-4" id="menuItemsGrid">
                    <?php foreach ($menu_items as $item): ?>
                        <div class="col menu-item-card" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>">
                            <div class="card food-card-modern h-100 d-flex flex-column">
                                <div class="position-relative">
                                    <img src="uploads/menu/<?= htmlspecialchars($item['image'] ?: 'default_food.png') ?>" 
                                         class="card-img-top object-fit-cover" height="200" alt="<?= htmlspecialchars($item['name']) ?>">
                                </div>
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="text-muted small mb-1">Available Stock: <?= $item['stock'] ?></div>
                                    <h5 class="card-title fw-bold text-dark mb-2"><?= htmlspecialchars($item['name']) ?></h5>
                                    <p class="card-text text-muted small flex-grow-1 mb-4"><?= htmlspecialchars($item['description']) ?></p>
                                    
                                    <div class="d-flex align-items-center justify-content-between mt-auto">
                                        <span class="fw-bold text-dark fs-5">RM<?= number_format($item['price'], 2) ?></span>
                                        
                                        <?php if ($vendor['status'] === 'open'): ?>
                                            <!-- Stepper / Add Button Box matching reference style -->
                                            <button type="button" class="btn btn-dark btn-sm px-4 rounded-pill fw-bold add-to-cart shadow-sm" 
                                                data-id="<?= $item['id'] ?>" 
                                                data-name="<?= htmlspecialchars($item['name']) ?>" 
                                                data-price="<?= $item['price'] ?>"
                                                data-stock="<?= $item['stock'] ?>"
                                                style="background-color: #111; color: #fff;">
                                                + Add
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-2">Closed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Desktop Sidebar Order Summary -->
        <div class="col-lg-4 d-none d-lg-block">
            <div class="card shadow-sm sticky-top border-0 rounded-4 overflow-hidden" style="top: 2rem;">
                <div class="card-header bg-dark text-white py-3 px-4" style="background-color: #1e392a !important;">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-receipt me-2 text-warning"></i>Your Order</h5>
                </div>
                <div class="card-body p-4 bg-white">
                    <div class="cart-items-container mb-3" style="max-height: 280px; overflow-y: auto;">
                        <p class="text-muted text-center py-4">Your order is empty.</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                        <span>Total:</span>
                        <span class="cart-total-display text-success">RM0.00</span>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Your Name *</label>
                        <input type="text" class="form-control customer-name-input rounded-3 shadow-none" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">Phone Number *</label>
                        <input type="tel" class="form-control customer-phone-input rounded-3 shadow-none" placeholder="e.g. +60123456789" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Order Type *</label>
                        <select class="form-select order-type-select rounded-3 shadow-none">
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Self Pickup</option>
                        </select>
                    </div>

                    <div class="delivery-address-section mb-3">
                        <label class="form-label fw-semibold small text-muted">Delivery Address *</label>
                        <textarea class="form-control customer-address-input rounded-3 shadow-none" rows="2" placeholder="Street name, house number..."></textarea>
                        
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-dark btn-sm w-100 rounded-pill get-location-btn fw-semibold">
                                <i class="fa-solid fa-map-pin me-1 text-danger"></i> Use Google Maps Pin Location
                            </button>
                            <input type="hidden" class="maps-pin-url-input" value="">
                        </div>
                    </div>

                    <?php if ($vendor['status'] === 'open'): ?>
                        <button type="button" class="btn btn-success w-100 fw-bold py-3 rounded-pill shadow-sm whatsapp-order-btn" style="background-color: #1e392a; border-color: #1e392a;" disabled>
                            <i class="fa-brands fa-whatsapp me-2 fs-5"></i> Submit Order via WhatsApp
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary w-100 fw-bold py-3 rounded-pill" disabled>Vendor is Closed</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mobile Floating Cart Bar (Matches bottom pill bar in reference) -->
        <?php if ($vendor['status'] === 'open'): ?>
            <div class="d-lg-none fixed-bottom p-3 bg-transparent text-center" style="z-index: 1040; pointer-events: none;">
                <button type="button" class="btn floating-order-bar py-3 px-4 shadow-lg fw-bold d-inline-flex justify-content-between align-items-center w-100" data-bs-toggle="modal" data-bs-target="#mobileCartModal" style="pointer-events: auto; max-width: 500px;">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold small" style="width: 28px; height: 28px;">
                            <span id="mobile-cart-count">0</span>
                        </div>
                        <span>View your order</span>
                    </div>
                    <div>
                        <span id="mobile-cart-total" class="fw-bold me-2">RM0.00</span>
                        <i class="fa-solid fa-chevron-right small"></i>
                    </div>
                </button>
            </div>
        <?php endif; ?>

        <!-- MOBILE CART MODAL -->
        <div class="modal fade" id="mobileCartModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-dark text-white py-3 px-4" style="background-color: #1e392a !important;">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-receipt me-2 text-warning"></i>Your Order Summary
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-light p-4">
                        <div class="cart-items-container mb-3 bg-white p-3 rounded-4 shadow-sm">
                            <p class="text-muted text-center py-3">Your order is empty.</p>
                        </div>
                        
                        <div class="bg-white p-4 rounded-4 shadow-sm mb-3">
                            <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                                <span>Total:</span>
                                <span class="cart-total-display text-success">RM0.00</span>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label fw-semibold small text-muted">Your Name *</label>
                                <input type="text" class="form-control customer-name-input rounded-3 shadow-none" placeholder="e.g. John Doe" required>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold small text-muted">Phone Number *</label>
                                <input type="tel" class="form-control customer-phone-input rounded-3 shadow-none" placeholder="e.g. +60123456789" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Order Type *</label>
                                <select class="form-select order-type-select rounded-3 shadow-none">
                                    <option value="delivery">Delivery</option>
                                    <option value="pickup">Self Pickup</option>
                                </select>
                            </div>

                            <div class="delivery-address-section mb-3">
                                <label class="form-label fw-semibold small text-muted">Delivery Address *</label>
                                <textarea class="form-control customer-address-input rounded-3 shadow-none" rows="2" placeholder="Street name, house number..."></textarea>
                                
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-dark btn-sm w-100 rounded-pill get-location-btn fw-semibold">
                                        <i class="fa-solid fa-map-pin me-1 text-danger"></i> Use Google Maps Pin Location
                                    </button>
                                    <input type="hidden" class="maps-pin-url-input" value="">
                                </div>
                            </div>
                        </div>

                        <?php if ($vendor['status'] === 'open'): ?>
                            <button type="button" class="btn btn-success w-100 fw-bold py-3 rounded-pill shadow-sm whatsapp-order-btn" style="background-color: #1e392a; border-color: #1e392a;" disabled>
                                <i class="fa-brands fa-whatsapp me-2 fs-5"></i> Submit Order via WhatsApp
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100 fw-bold py-3 rounded-pill" disabled>Vendor is Closed</button>
                        <?php endif; ?>
                    </div>
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
                cart.push({ id, name, price, quantity: 1, maxStock, remark: '' });
            }
            updateCartUI();
        });
    });

    function updateCartUI() {
        const totalItemsCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        const mobileCountEl = document.getElementById('mobile-cart-count');
        const mobileTotalEl = document.getElementById('mobile-cart-total');
        
        if (mobileCountEl) mobileCountEl.textContent = totalItemsCount;

        if (cart.length === 0) {
            document.querySelectorAll('.cart-items-container').forEach(el => {
                el.innerHTML = '<p class="text-muted text-center py-3">Your order is empty.</p>';
            });
            document.querySelectorAll('.cart-total-display').forEach(el => {
                el.textContent = '$0.00';
            });
            if (mobileTotalEl) mobileTotalEl.textContent = '$0.00';
            
            document.querySelectorAll('.whatsapp-order-btn').forEach(btn => btn.disabled = true);
            return;
        }

        let html = '<ul class="list-group list-group-flush">';
        let total = 0;

        cart.forEach((item, index) => {
            let itemTotal = item.price * item.quantity;
            total += itemTotal;
            const remarkVal = item.remark ? item.remark.replace(/"/g, '&quot;') : '';

            html += `
                <li class="list-group-item px-0 py-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <h6 class="mb-0 fw-bold">${item.name}</h6>
                            <small class="text-muted">$${item.price.toFixed(2)} x ${item.quantity}</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQty(${index}, -1)">-</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQty(${index}, 1)">+</button>
                            <button type="button" class="btn btn-outline-danger" onclick="removeItem(${index})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="mt-1">
                        <input type="text" 
                            class="form-control form-control-sm bg-light" 
                            placeholder="Add remarks (e.g. Less spicy)" 
                            value="${remarkVal}"
                            oninput="updateRemark(${index}, this.value)">
                    </div>
                </li>
            `;
        });

        html += '</ul>';

        document.querySelectorAll('.cart-items-container').forEach(el => {
            el.innerHTML = html;
        });
        document.querySelectorAll('.cart-total-display').forEach(el => {
            el.textContent = '$' + total.toFixed(2);
        });
        if (mobileTotalEl) mobileTotalEl.textContent = '$' + total.toFixed(2);

        document.querySelectorAll('.whatsapp-order-btn').forEach(btn => btn.disabled = false);
    }

    function updateRemark(index, text) {
        if (cart[index]) {
            cart[index].remark = text.trim();
        }
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

    // Handle Order Type Toggle for both Desktop and Mobile views
    document.querySelectorAll('.order-type-select').forEach(selectElem => {
        selectElem.addEventListener('change', function() {
            const orderTypeVal = this.value;
            document.querySelectorAll('.delivery-address-section').forEach(section => {
                section.style.display = (orderTypeVal === 'pickup') ? 'none' : 'block';
            });
        });
    });

    // Geolocation button handlers
    document.querySelectorAll('.get-location-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const button = this;
            
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Getting Location...';

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const mapsUrl = `https://www.maps.google.com/?q=${lat},${lng}`;
                    
                    document.querySelectorAll('.customer-address-input').forEach(textarea => {
                        textarea.value = `Google Maps Pin Location:\n${mapsUrl}`;
                    });
                    document.querySelectorAll('.maps-pin-url-input').forEach(input => {
                        input.value = mapsUrl;
                    });
                    
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i> Location Pinned Successfully!';
                },
                function(error) {
                    alert('Unable to retrieve your location. Please enter your address manually.');
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-map-pin me-1 text-success"></i> Use Google Maps Pin Location';
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
        });
    });

    // WhatsApp Order Submit Handlers
    document.querySelectorAll('.whatsapp-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (cart.length === 0) return;

            // Grab values from whichever form context is currently active (Desktop sidebar or Mobile modal)
            const activeContainer = this.closest('.card-body') || this.closest('.modal-body');
            const customerName = activeContainer.querySelector('.customer-name-input').value.trim();
            const customerPhone = activeContainer.querySelector('.customer-phone-input').value.trim();
            const orderType = activeContainer.querySelector('.order-type-select').value;
            const customerAddress = activeContainer.querySelector('.customer-address-input').value.trim();

            if (!customerName || !customerPhone) {
                alert('Please enter your name and phone number.');
                activeContainer.querySelector('.customer-name-input').focus();
                return;
            }

            if (orderType === 'delivery' && !customerAddress) {
                alert('Please provide a delivery address or use the Google Maps pin feature.');
                activeContainer.querySelector('.customer-address-input').focus();
                return;
            }

            // Close mobile modal if open
            const mobileModalEl = document.getElementById('mobileCartModal');
            if (mobileModalEl) {
                const modalInstance = bootstrap.Modal.getInstance(mobileModalEl);
                if (modalInstance) modalInstance.hide();
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

                let itemLine = `• ${item.name} x ${item.quantity} - $${subtotal.toFixed(2)}`;
                if (item.remark) {
                    itemLine += `\n  _(Note: ${item.remark})_`;
                }
                message += `${itemLine}\n`;
                
                receiptItemsHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <div>
                            <h6 class="mb-0 fw-bold">${item.name}</h6>
                            <small class="text-muted">$${item.price.toFixed(2)} x ${item.quantity}</small>
                            ${item.remark ? `<br><small class="text-danger"><em>Note: ${item.remark}</em></small>` : ''}
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
                                <p class="mb-0 small">Your order has5 been submitted to <strong>${vendorName}</strong> via WhatsApp.</p>
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
    });
    </script>

<?php 
} else {
    // ----------------------------------------------------
    // LANDING PAGE VIEW (Rows 1 to 8 + remaining placeholders)
    // ----------------------------------------------------
    ?>

    <style>
        body {
            background-color: #f8f9fa;
        }
        /* Row 1: Dark Forest Green Hero Section */
        .landing-hero-green {
            background-color: #0b291a;
            color: #ffffff;
            padding: 5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        /* Row 2: Light Cream Section */
        .landing-row-cream {
            background-color: #f3f4ee;
            padding: 4rem 2rem;
        }
        /* Row 3: Light Clean Section */
        .landing-row-white {
            background-color: #ffffff;
            padding: 4rem 2rem;
            border: 1px solid #eaeaea;
        }
        /* Row 4: How Does It Work Section */
        .landing-row-how-it-works {
            background-color: #ffffff;
            padding: 5rem 2rem;
            border: 1px solid #eaeaea;
        }
        /* Row 5: Your Store, Your Products Section */
        .landing-row-your-store {
            background-color: #f3f4ee;
            padding: 5rem 2rem;
        }
        /* Row 6: Dark Business Rules Section */
        .landing-row-rules {
            background-color: #0b291a;
            color: #ffffff;
            padding: 5rem 2rem;
        }
        /* Row 7: Delivery & Direct Payments Info Section */
        .landing-row-payments {
            background-color: #ffffff;
            padding: 5rem 2rem;
            border: 1px solid #eaeaea;
        }
        /* Row 8: Explore Kedai Directory Section */
        .landing-row-explore {
            background-color: #f3f4ee;
            padding: 5rem 2rem;
        }
        .generic-row {
            background-color: #f8f9fa;
            padding: 3rem 2rem;
            border: 1px dashed #cbd5e1;
        }
        .custom-orange-btn {
            background: linear-gradient(135deg, #ffa726, #fb8c00);
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.6rem 1.5rem;
            font-weight: 700;
            border: none;
        }
        .step-icon-circle {
            width: 45px;
            height: 45px;
            background-color: #1e392a;
            color: #b2ff81;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }
        .rule-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
        }
        .info-card-cream {
            background-color: #f3f4ee;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2.5rem;
            height: 100%;
        }
        .category-chip {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 50rem;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .category-chip.active, .category-chip:hover {
            background-color: #1e392a;
            color: #ffffff;
            border-color: #1e392a;
        }
        .vendor-preview-card {
            background-color: #ffffff;
            border-radius: 1rem;
            border: none;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.04);
            text-align: left;
        }
        /* Row 9: Supported By Local Partners */
        .landing-row-partners {
            background-color: #f3f4ee;
            padding: 5rem 2rem;
        }
        .sponsor-slot-card {
            background-color: #ffffff;
            border: 1.5px dashed #cbd5e1;
            border-radius: 0.85rem;
            padding: 1.5rem 1.25rem;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .slot-pill-label {
            position: absolute;
            top: 10px;
            left: 12px;
            background-color: #f3f4ee;
            color: #64748b;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.55rem;
            border-radius: 50rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .sponsor-cta-banner {
            background-color: #0b291a;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
        }
        .btn-lime-pill {
            background-color: #b2ff81;
            color: #0b291a !important;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 50rem;
            padding: 0.65rem 1.5rem;
            border: none;
            transition: transform 0.2s ease, background-color 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-lime-pill:hover {
            background-color: #9be667;
            transform: translateY(-1px);
        }

        /* Row 10: Ready To Take Your Business Digital */
        .landing-row-digital-cta {
            background: linear-gradient(rgba(11, 41, 26, 0.92), rgba(11, 41, 26, 0.95)),
                        url('/simple_ordering_multivendor/assets/cta-bg.jpg') center/cover no-repeat;
            background-color: #0b291a;
            color: #ffffff;
            padding: 5.5rem 2rem;
        }
        .wa-number-pill {
            background-color: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(178, 255, 129, 0.35);
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.5rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        .wa-number-pill:hover {
            background-color: rgba(178, 255, 129, 0.15);
            border-color: #b2ff81;
        }
        /* Row 11: Frequently Asked Questions */
        .landing-row-faq {
            background-color: #ffffff;
            padding: 5rem 2rem;
            border: 1px solid #eaeaea;
        }
        .faq-accordion .accordion-item {
            background-color: #f3f4ee !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 50rem !important;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .faq-accordion .accordion-button {
            background-color: #f3f4ee !important;
            color: #0b291a !important;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 50rem !important;
            box-shadow: none !important;
            padding: 1.2rem 1.75rem;
        }
        .faq-accordion .accordion-button:not(.collapsed) {
            background-color: #ebf0e6 !important;
            color: #0b291a !important;
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .faq-accordion .accordion-body {
            background-color: #f3f4ee;
            color: #475569;
            padding: 0.5rem 1.75rem 1.5rem 1.75rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .faq-accordion .accordion-collapse {
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
        }
    </style>

    <!-- ==================================================== -->
    <!-- ROW 1: DARK FOREST GREEN HERO -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-hero-green shadow-sm">
        <div class="landing-content-inner container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 text-center text-lg-start">
                    <p class="fw-bold fst-italic mb-2" style="color: #b2ff81; font-size: 1.1rem; font-family: cursive;">
                        Bisnes Makin Mudah
                    </p>
                    <h1 class="display-3 fw-bold text-white mb-3" style="line-height: 1.1; letter-spacing: -1px;">
                        YOUR OWN DIGITAL STORE.<br>
                        ORDERS STRAIGHT TO WHATSAPP.
                    </h1>
                    <p class="text-white-50 mb-4 col-lg-10">
                        Your own digital store. Orders straight to WhatsApp. Built for Malaysian small businesses, from Sarawak.
                    </p>
                    <div class="d-flex justify-content-center justify-content-lg-start gap-3 flex-wrap">
                        <a href="#how-it-works" class="btn custom-orange-btn px-4 shadow-sm">
                            GET YOUR DIGITAL STORE
                        </a>
                        <a href="#explore" class="btn btn-outline-light px-4 rounded-pill fw-bold">
                            LIST MY STORE
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center">
                    <div class="p-3 bg-black bg-opacity-25 rounded-4 border border-secondary border-opacity-25 d-inline-block">
                        <i class="fa-solid fa-mobile-screen-button display-1 mb-2" style="color: #b2ff81 !important;"></i>
                        <h6 class="text-white fw-bold mb-0">Live WhatsApp Order Preview</h6>
                        <small class="text-white-50">Instant cart & checkout flow</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 2: LIGHT CREAM CATEGORY GRID -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-cream shadow-sm text-center">
        <div class="landing-content-inner container py-3">
            <small class="text-muted text-uppercase fw-bold tracking-wider" style="letter-spacing: 1px;">Fresh picks for home, happy for customer</small>
            <h2 class="fw-bold text-dark display-6 mb-4" style="letter-spacing: -0.5px;">ONE PLATFORM.<br>EVERY KIND OF KEDAI.</h2>
            
            <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center">
                <div class="col">
                    <div class="card bg-white border-0 shadow-sm rounded-4 p-3 h-100">
                        <div class="bg-light rounded-3 p-4 mb-3 text-muted">
                            <i class="fa-solid fa-utensils fs-1 text-success mb-2"></i>
                            <h5 class="fw-bold text-dark mb-0">Food & Beverages</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-white border-0 shadow-sm rounded-4 p-3 h-100">
                        <div class="bg-light rounded-3 p-4 mb-3 text-muted">
                            <i class="fa-solid fa-shirt fs-1 text-success mb-2"></i>
                            <h5 class="fw-bold text-dark mb-0">Fashion & Apparel</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-white border-0 shadow-sm rounded-4 p-3 h-100">
                        <div class="bg-light rounded-3 p-4 mb-3 text-muted">
                            <i class="fa-solid fa-carrot fs-1 text-success mb-2"></i>
                            <h5 class="fw-bold text-dark mb-0">Fresh Produce</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 3: FEATURE HIGHLIGHTS -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-white shadow-sm text-center">
        <div class="landing-content-inner container py-3">
            <small class="text-muted text-uppercase fw-bold tracking-wider" style="letter-spacing: 1px;">Made for our Malaysian business</small>
            <h3 class="fw-bold text-dark display-6 mb-5">LESS CHAT. MORE ORDERS.</h3>
            
            <div class="row row-cols-1 row-cols-md-4 g-4">
                <div class="col">
                    <div class="p-4 bg-light rounded-4 border h-100">
                        <i class="fa-solid fa-bolt text-success fs-3 mb-2"></i>
                        <h6 class="fw-bold text-dark">ONE TIME SETUP</h6>
                        <p class="text-muted small mb-0">Quick configuration to get your menu live instantly.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-light rounded-4 border h-100">
                        <i class="fa-solid fa-qrcode text-success fs-3 mb-2"></i>
                        <h6 class="fw-bold text-dark">INSTANT SCAN QR</h6>
                        <p class="text-muted small mb-0">Tabletop QR codes for fast customer scanning.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-light rounded-4 border h-100">
                        <i class="fa-solid fa-comments text-success fs-3 mb-2"></i>
                        <h6 class="fw-bold text-dark">NO CUSTOMER LOG-IN</h6>
                        <p class="text-muted small mb-0">Zero friction ordering straight to WhatsApp.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-light rounded-4 border h-100">
                        <i class="fa-solid fa-shield-halved text-success fs-3 mb-2"></i>
                        <h6 class="fw-bold text-dark">SIMPLE CUSTODIAN</h6>
                        <p class="text-muted small mb-0">Maintain total control of your business revenues.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 4: HOW DOES IT WORK -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-how-it-works shadow-sm">
        <div class="landing-content-inner container py-3">
            <div class="text-center mb-5">
                <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px;">Simple Steps. Easy Ordering.</small>
                <h2 class="fw-bold display-5 text-dark" style="letter-spacing: -1px;">HOW DOES IT WORK?</h2>
            </div>
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="d-flex align-items-start mb-4">
                        <div class="step-icon-circle me-3"><i class="fa-solid fa-store"></i></div>
                        <div>
                            <small class="text-success fw-bold text-uppercase">Step 01</small>
                            <h5 class="fw-bold text-dark mb-1">YOUR DIGITAL STORE</h5>
                            <p class="text-muted small mb-0">We set up your personalised digital store with your business details, operating hours, product photos and product list.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-4">
                        <div class="step-icon-circle me-3"><i class="fa-solid fa-basket-shopping"></i></div>
                        <div>
                            <small class="text-success fw-bold text-uppercase">Step 02</small>
                            <h5 class="fw-bold text-dark mb-1">CUSTOMERS BROWSE</h5>
                            <p class="text-muted small mb-0">Customers open your digital store through a link or QR code. No downloads or sign-ins required.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-4">
                        <div class="step-icon-circle me-3"><i class="fa-solid fa-list-check"></i></div>
                        <div>
                            <small class="text-success fw-bold text-uppercase">Step 03</small>
                            <h5 class="fw-bold text-dark mb-1">CUSTOMERS PLACE ORDERS</h5>
                            <p class="text-muted small mb-0">They select their favourite items, choose quantities, add special notes and enter their name for order identification.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="step-icon-circle me-3"><i class="fa-brands fa-whatsapp"></i></div>
                        <div>
                            <small class="text-success fw-bold text-uppercase">Step 04</small>
                            <h5 class="fw-bold text-dark mb-1">ORDER SENT TO WHATSAPP</h5>
                            <p class="text-muted small mb-0">The complete order summary goes directly to your WhatsApp. You can confirm the order and arrange payment with your customer.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="p-3 bg-light rounded-4 border shadow-sm d-inline-block">
                        <i class="fa-solid fa-mobile-screen display-2 text-success mb-2"></i>
                        <h6 class="fw-bold text-dark mb-0">Interactive Mobile Checkout Flow</h6>
                        <small class="text-muted">Real-time WhatsApp payload generator</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 5: YOUR STORE. YOUR PRODUCTS. YOUR WAY. -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-your-store shadow-sm text-center">
        <div class="landing-content-inner container py-3">
            <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px;">See it in action</small>
            <h2 class="fw-bold display-5 text-dark mb-5" style="letter-spacing: -1px;">YOUR STORE. YOUR PRODUCTS. YOUR WAY.</h2>
            
            <div class="row row-cols-1 row-cols-md-3 g-4 justify-content-center mb-5">
                <div class="col">
                    <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                        <span class="badge bg-success mb-2 px-3 py-1 rounded-pill">01</span>
                        <h5 class="fw-bold text-dark">BROWSE THE STORE</h5>
                        <p class="text-muted small mb-0">Easy, intuitive layout with categories, descriptions and photos.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                        <span class="badge bg-success mb-2 px-3 py-1 rounded-pill">02</span>
                        <h5 class="fw-bold text-dark">CUSTOMISE YOUR ORDER</h5>
                        <p class="text-muted small mb-0">Select quantities, add notes and review the order list.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                        <span class="badge bg-success mb-2 px-3 py-1 rounded-pill">03</span>
                        <h5 class="fw-bold text-dark">SEND TO WHATSAPP</h5>
                        <p class="text-muted small mb-0">Instant summary with customer terms, items and total.</p>
                    </div>
                </div>
            </div>
            
            <p class="text-muted fst-italic mb-0" style="font-size: 1.1rem;">
                Your customers don't need to download anything or create an account. Just open the link and order.
            </p>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 6: YOUR BUSINESS. YOUR SALES. YOUR RULES. -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-rules shadow-sm text-center">
        <div class="landing-content-inner container py-3">
            <small class="text-uppercase fw-bold" style="color: #b2ff81; letter-spacing: 1px;">Keep things simple</small>
            <h2 class="fw-bold display-5 text-white mb-5" style="letter-spacing: -1px;">YOUR BUSINESS. YOUR SALES. YOUR RULES.</h2>
            
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-percent text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">100% OF YOUR SALES</h6>
                        </div>
                        <p class="text-white-50 small mb-0">We don't charge commission on your orders. Your sales belong to you.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-screwdriver-wrench text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">ONE-TIME SETUP FEE</h6>
                        </div>
                        <p class="text-white-50 small mb-0">Pay a once-off setup fee for much store. No per-order commission.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-user-xmark text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">NO CUSTOMER REGISTRATION</h6>
                        </div>
                        <p class="text-white-50 small mb-0">Customers can browse and order without creating accounts.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-bolt text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">EASY TO USE</h6>
                        </div>
                        <p class="text-white-50 small mb-0">A straightforward store designed for everyday customers and business owners.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-globe text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">NO APP DOWNLOAD</h6>
                        </div>
                        <p class="text-white-50 small mb-0">Works through a web browser. Just share your store link or QR code.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="rule-card text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-shield text-success fs-4 me-2" style="color: #b2ff81 !important;"></i>
                            <h6 class="fw-bold text-white mb-0">CANCEL ANYTIME</h6>
                        </div>
                        <p class="text-white-50 small mb-0">Enjoy flexibility without complicated commitments.</p>
                    </div>
                </div>
            </div>
            
            <p class="text-white-50 small fst-italic mb-0 pt-3 border-top border-secondary border-opacity-25">
                Kedai WA Kamek is a store builder, not a marketplace. Your customers order from you, directly.
            </p>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 7: WHAT ABOUT DELIVERY? & HOW DO CUSTOMERS PAY? (Matching Image 4, Top) -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-payments shadow-sm text-center">
        <div class="landing-content-inner container py-3">
            <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px;">No middleman</small>
            <h2 class="fw-bold display-5 text-dark mb-5" style="letter-spacing: -1px;">ORDERS MADE SIMPLE.<br>PAYMENTS MADE DIRECT.</h2>
            
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <!-- Delivery Info Card -->
                <div class="col">
                    <div class="info-card-cream text-start h-100">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-flex mb-3">
                            <i class="fa-solid fa-motorcycle fs-4"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">WHAT ABOUT DELIVERY?</h4>
                        <p class="text-muted small fst-italic mb-3">Want your customers to enjoy delivery?</p>
                        <p class="text-muted small mb-0">
                            Customers discuss directly with the vendor to arrange a third-party courier or delivery service. Customers pay the actual product price, while delivery charges are arranged and paid separately.
                        </p>
                    </div>
                </div>
                
                <!-- Payment Info Card -->
                <div class="col">
                    <div class="info-card-cream text-start h-100">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-flex mb-3">
                            <i class="fa-solid fa-wallet fs-4"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">HOW DO CUSTOMERS PAY?</h4>
                        <p class="text-muted small fst-italic mb-3">Simple. Direct. Between you and your customer.</p>
                        <p class="text-muted small mb-3">
                            When the order summary reaches your WhatsApp, you provide your payment details directly.
                        </p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm small fw-semibold"><i class="fa-solid fa-qrcode text-success me-1"></i> DuitNow QR</span>
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm small fw-semibold"><i class="fa-solid fa-building-columns text-success me-1"></i> Online bank transfer</span>
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm small fw-semibold"><i class="fa-solid fa-handshake text-success me-1"></i> Other agreed arrangements</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Disclaimer Notice Box -->
            <div class="p-3 bg-white rounded-3 border border-secondary border-opacity-25 d-inline-block text-start" style="max-width: 800px;">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-circle-info text-muted fs-5 me-3"></i>
                    <p class="text-muted small mb-0">
                        <strong>Kedai WA Kamek does not collect customer payments or process transactions.</strong> Payments are handled directly between customers and vendors.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 8: EXPLORE KEDAI DIRECTORY (Matching Image 4, Bottom) -->
    <!-- ==================================================== -->
    <section class="landing-full-row landing-row-explore shadow-sm">
        <div class="landing-content-inner container py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <small class="text-muted text-uppercase fw-bold" style="letter-spacing: 1px;">Discover local businesses</small>
                    <h2 class="fw-bold display-6 text-dark mb-1" style="letter-spacing: -0.5px;">EXPLORE KEDAI</h2>
                    <p class="text-muted small mb-0">Browse local food, fashion, produce, crafts and more from businesses across Sarawak and Malaysia.</p>
                </div>
                <div>
                    <a href="#explore" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" style="background-color: #1e392a;">
                        EXPLORE ALL KEDAI <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Category Filter Chips -->
            <div class="d-flex gap-2 overflow-auto pb-3 mb-4" style="white-space: nowrap;">
                <a href="#" class="category-chip active">Food & Beverages</a>
                <a href="#" class="category-chip">Fashion & Apparel</a>
                <a href="#" class="category-chip">Fresh Produce</a>
                <a href="#" class="category-chip">Handmade Crafts</a>
                <a href="#" class="category-chip">Others</a>
            </div>

            <!-- Vendor Cards Preview Grid -->
            <div class="row row-cols-1 row-cols-md-4 g-4">
                <!-- Vendor Card 1 -->
                <div class="col">
                    <div class="card vendor-preview-card h-100">
                        <div class="p-3 pb-0">
                            <span class="badge bg-success bg-opacity-10 text-success small fw-bold px-2 py-1">FOOD & BEVERAGES</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary small fw-bold px-2 py-1 float-end">SAMPLE LISTING</span>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-dark mb-1">DAPUR MAK LONG</h5>
                            <p class="text-muted small mb-2" style="font-size: 0.8rem;">Masakan kampung, rasa macam rumah sendiri</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot text-success me-1"></i> Gedong, Sarawak</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-clock text-success me-1"></i> 7:00 am - 2:00 pm</p>
                            <p class="text-muted small mb-3" style="font-size: 0.75rem;"><i class="fa-solid fa-motorcycle text-success me-1"></i> Delivery arranged with vendor</p>
                            <button class="btn btn-dark btn-sm w-100 rounded-pill fw-bold" style="background-color: #1e392a;">VIEW STORE</button>
                        </div>
                    </div>
                </div>
                <!-- Vendor Card 2 -->
                <div class="col">
                    <div class="card vendor-preview-card h-100">
                        <div class="p-3 pb-0">
                            <span class="badge bg-success bg-opacity-10 text-success small fw-bold px-2 py-1">FOOD & BEVERAGES</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary small fw-bold px-2 py-1 float-end">SAMPLE LISTING</span>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-dark mb-1">KOPITIAM AH SENG</h5>
                            <p class="text-muted small mb-2" style="font-size: 0.8rem;">Kopi tarik pagi, roti bakar kaya sejak 1988</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot text-success me-1"></i> Kuching, Sarawak</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-clock text-success me-1"></i> 6:30 am - 3:00 pm</p>
                            <p class="text-muted small mb-3" style="font-size: 0.75rem;"><i class="fa-solid fa-motorcycle text-success me-1"></i> Delivery arranged with vendor</p>
                            <button class="btn btn-dark btn-sm w-100 rounded-pill fw-bold" style="background-color: #1e392a;">VIEW STORE</button>
                        </div>
                    </div>
                </div>
                <!-- Vendor Card 3 -->
                <div class="col">
                    <div class="card vendor-preview-card h-100">
                        <div class="p-3 pb-0">
                            <span class="badge bg-success bg-opacity-10 text-success small fw-bold px-2 py-1">FOOD & BEVERAGES</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary small fw-bold px-2 py-1 float-end">SAMPLE LISTING</span>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-dark mb-1">KEK LAPIS MAMA ROS</h5>
                            <p class="text-muted small mb-2" style="font-size: 0.8rem;">Kek lapis Sarawak buatan tangan</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot text-success me-1"></i> Kuching, Sarawak</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-clock text-success me-1"></i> 9:00 am - 6:00 pm</p>
                            <p class="text-muted small mb-3" style="font-size: 0.75rem;"><i class="fa-solid fa-motorcycle text-success me-1"></i> Delivery arranged with vendor</p>
                            <button class="btn btn-dark btn-sm w-100 rounded-pill fw-bold" style="background-color: #1e392a;">VIEW STORE</button>
                        </div>
                    </div>
                </div>
                <!-- Vendor Card 4 -->
                <div class="col">
                    <div class="card vendor-preview-card h-100">
                        <div class="p-3 pb-0">
                            <span class="badge bg-primary bg-opacity-10 text-primary small fw-bold px-2 py-1">FASHION & APPAREL</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary small fw-bold px-2 py-1 float-end">SAMPLE LISTING</span>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-dark mb-1">SAMPLE BATIK & TUDUNG</h5>
                            <p class="text-muted small mb-2" style="font-size: 0.8rem;">Batik, baju kurung and everyday tudung</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot text-success me-1"></i> Kuching, Sarawak</p>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="fa-solid fa-clock text-success me-1"></i> 10:00 am - 8:00 pm</p>
                            <p class="text-muted small mb-3" style="font-size: 0.75rem;"><i class="fa-solid fa-motorcycle text-success me-1"></i> Delivery arranged with vendor</p>
                            <button class="btn btn-dark btn-sm w-100 rounded-pill fw-bold" style="background-color: #1e392a;">VIEW STORE</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 9: SUPPORTED BY LOCAL PARTNERS -->
    <!-- ==================================================== -->
    <section id="partners" class="landing-full-row landing-row-partners shadow-sm">
        <div class="landing-content-inner container py-2" style="max-width: 980px;">
            
            <!-- Section Header -->
            <div class="text-center mb-5">
                <p class="fw-bold fst-italic mb-1" style="color: #5b8c3e; font-family: cursive; font-size: 1.1rem;">
                    Growing local together
                </p>
                <h2 class="fw-bold display-5 text-dark mb-2" style="color: #0b291a !important; letter-spacing: -1px;">
                    SUPPORTED BY LOCAL PARTNERS
                </h2>
                <p class="text-muted small mx-auto mb-0" style="max-width: 520px;">
                    Kedai WA Kamek is made possible with the support of partners who believe in helping local businesses grow digitally.
                </p>
            </div>

            <!-- Sub-grid 1: Our Sponsors (4 Columns) -->
            <div class="mb-4">
                <p class="text-muted fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 1px;">
                    OUR SPONSORS
                </p>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">SPONSORED</span>
                            <span class="text-muted small mt-2">Sponsor logo here</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">SPONSORED</span>
                            <span class="text-muted small mt-2">Sponsor logo here</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">SPONSORED</span>
                            <span class="text-muted small mt-2">Sponsor logo here</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">SPONSORED</span>
                            <span class="text-muted small mt-2">Sponsor logo here</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub-grid 2: Powered By (3 Columns) -->
            <div class="mb-5">
                <p class="text-muted fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 1px;">
                    POWERED BY
                </p>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">PARTNER</span>
                            <span class="text-muted small mt-2">Partner logo here</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">PARTNER</span>
                            <span class="text-muted small mt-2">Partner logo here</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="sponsor-slot-card">
                            <span class="slot-pill-label">PARTNER</span>
                            <span class="text-muted small mt-2">Partner logo here</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Dark Green Sponsor Banner -->
            <div class="sponsor-cta-banner d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 shadow-sm">
                <div class="d-flex align-items-center gap-3 text-center text-md-start flex-column flex-md-row">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background-color: #b2ff81; color: #0b291a;">
                        <i class="fa-regular fa-handshake fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-white mb-0" style="letter-spacing: 0.3px;">INTERESTED IN BECOMING A SPONSOR?</h5>
                        <p class="text-white-50 small mb-0">Support local Malaysian businesses and grow alongside them.</p>
                    </div>
                </div>
                <a href="https://wa.me/601169893389?text=Hi%20Kedai%20WA%20Kamek%2C%20I%20am%20interested%20in%20becoming%20a%20sponsor." target="_blank" class="btn-lime-pill flex-shrink-0">
                    <i class="fa-brands fa-whatsapp me-2 fs-6"></i> CONTACT US ON WHATSAPP
                </a>
            </div>

        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 10: READY TO TAKE YOUR BUSINESS DIGITAL? -->
    <!-- ==================================================== -->
    <section id="become-vendor" class="landing-full-row landing-row-digital-cta shadow-sm text-center">
        <div class="landing-content-inner container py-2" style="max-width: 760px;">
            
            <p class="fw-bold fst-italic mb-2" style="color: #b2ff81; font-family: cursive; font-size: 1.25rem;">
                Siap untuk berniaga online?
            </p>
            <h2 class="display-4 fw-bold text-white mb-3" style="line-height: 1.05; letter-spacing: -1px;">
                READY TO TAKE YOUR<br>BUSINESS DIGITAL?
            </h2>
            <p class="text-white-50 mb-4" style="font-size: 0.95rem;">
                Let your customers order easily while you focus on growing your business.
            </p>

            <!-- Primary Action Buttons -->
            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mb-4">
                <a href="https://wa.me/601169893389?text=Hi%20Kedai%20WA%20Kamek%2C%20I%20want%20to%20get%20my%20store%20set%20up!" target="_blank" class="btn custom-orange-btn px-4 py-2 shadow-sm" style="font-size: 0.85rem;">
                    GET YOUR STORE SET UP
                </a>
                <a href="/simple_ordering_multivendor/login.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold" style="font-size: 0.85rem; border-color: rgba(255,255,255,0.4);">
                    LIST MY STORE
                </a>
            </div>

            <!-- Direct WhatsApp Number Pills -->
            <div class="pt-2">
                <p class="text-white-50 fw-bold text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 1.2px;">
                    CHAT WITH US ON WHATSAPP
                </p>
                <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                    <a href="https://wa.me/601169893389" target="_blank" class="wa-number-pill">
                        <i class="fa-solid fa-phone me-2" style="color: #b2ff81; font-size: 0.75rem;"></i> +6011-69893389
                    </a>
                    <a href="https://wa.me/60103055330" target="_blank" class="wa-number-pill">
                        <i class="fa-solid fa-phone me-2" style="color: #b2ff81; font-size: 0.75rem;"></i> +6010-3055330
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 11: FREQUENTLY ASKED QUESTIONS (Matching Image) -->
    <!-- ==================================================== -->
    <section id="faq" class="landing-full-row landing-row-faq shadow-sm">
        <div class="landing-content-inner container py-2" style="max-width: 820px;">
            
            <!-- Section Header -->
            <div class="text-center mb-5">
                <p class="fw-bold fst-italic mb-1" style="color: #5b8c3e; font-family: cursive; font-size: 1.15rem;">
                    Got questions?
                </p>
                <h2 class="fw-bold display-5 text-dark mb-0" style="color: #0b291a !important; letter-spacing: -1px;">
                    FREQUENTLY ASKED
                </h2>
            </div>

            <!-- FAQ Accordion List -->
            <div class="accordion faq-accordion" id="kedaiFaqAccordion">
                
                <!-- FAQ Item 1 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading1">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">
                            Does it work for fashion, produce and handmade crafts?
                        </button>
                    </h2>
                    <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Yes! Kedai WA Kamek is built for any small business in Malaysia[cite: 3]. Whether you sell local food & beverages, apparel, fresh produce, or handmade crafts, you can set up your custom catalog[cite: 6, 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                            Do my customers need an account?
                        </button>
                    </h2>
                    <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            No account or password creation is needed for your customers[cite: 6, 7]. They simply open your store link or scan your QR code[cite: 6, 7], pick their items[cite: 6], add remarks[cite: 1], and submit directly to your WhatsApp[cite: 3, 6].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                            Do I need to download an app?
                        </button>
                    </h2>
                    <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            No app download is required[cite: 6, 7]. Everything runs smoothly through any standard web browser on mobile phones, tablets, or computers[cite: 6, 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                            Do you charge commission?
                        </button>
                    </h2>
                    <div id="faqHeading4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            We don't take any commission on your sales[cite: 6, 7]. All revenue from your orders belongs 100% to you[cite: 6, 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading5">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse5" aria-expanded="false" aria-controls="faqCollapse5">
                            How do customers pay?
                        </button>
                    </h2>
                    <div id="faqCollapse5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Payments are handled directly between you and your customer[cite: 7]. When the order arrives via WhatsApp, you can share your DuitNow QR, online bank details, or agree on cash/transfer arrangements[cite: 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading6">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse6" aria-expanded="false" aria-controls="faqCollapse6">
                            What about delivery?
                        </button>
                    </h2>
                    <div id="faqCollapse6" class="accordion-collapse collapse" aria-labelledby="faqHeading6" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            You coordinate delivery directly with your customer[cite: 7]. You can arrange third-party riders, self-pickups, or postal delivery[cite: 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 7 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading7">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse7" aria-expanded="false" aria-controls="faqCollapse7">
                            Can I cancel anytime?
                        </button>
                    </h2>
                    <div id="faqHeading7" class="accordion-collapse collapse" aria-labelledby="faqHeading7" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Yes, you enjoy complete flexibility without complicated contracts or long-term lock-ins[cite: 6, 7].
                        </div>
                    </div>
                </div>

                <!-- FAQ Item 8 -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading8">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse8" aria-expanded="false" aria-controls="faqCollapse8">
                            How do I get listed in Explore Kedai?
                        </button>
                    </h2>
                    <div id="faqHeading8" class="accordion-collapse collapse" aria-labelledby="faqHeading8" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Simply click the "Get Your Store Set Up" button or message our team via WhatsApp to get your store onboarded into the directory[cite: 7].
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
<?php 
}
require_once 'includes/footer.php'; 
?>