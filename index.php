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