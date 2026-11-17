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
        /* ====================================================
        ROW 1: COMPLETE HERO SECTION STYLES
        ==================================================== */
        .landing-hero-green-v2 {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            padding: 6rem 1rem 5rem 1rem;
            background-color: #0b291a;
            color: #ffffff;
            overflow: hidden;
        }

        /* 4-Image Background Slideshow Layer */
        .hero-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: heroSlideFade 24s infinite ease-in-out;
        }

        .hero-slide:nth-child(1) {
            background-image: linear-gradient(135deg, rgba(41, 11, 11, 0.84), rgba(11, 41, 26, 0.92)),
                            url('/simple_ordering_multivendor/assets/hero-bg-1.jpg');
            animation-delay: 0s;
        }
        .hero-slide:nth-child(2) {
            background-image: linear-gradient(135deg, rgba(35, 133, 212, 0.84), rgba(11, 41, 26, 0.92)),
                            url('/simple_ordering_multivendor/assets/hero-bg-2.jpg');
            animation-delay: 6s;
        }
        .hero-slide:nth-child(3) {
            background-image: linear-gradient(135deg, rgba(11, 41, 26, 0.84), rgba(11, 41, 26, 0.92)),
                            url('/simple_ordering_multivendor/assets/hero-bg-3.jpg');
            animation-delay: 12s;
        }
        .hero-slide:nth-child(4) {
            background-image: linear-gradient(135deg, rgba(11, 41, 26, 0.84), rgba(11, 41, 26, 0.92)),
                            url('/simple_ordering_multivendor/assets/hero-bg-4.jpg');
            animation-delay: 18s;
        }

        @keyframes heroSlideFade {
            0% {
                opacity: 0;
                transform: scale(1);
            }
            8% {
                opacity: 1;
            }
            25% {
                opacity: 1;
                transform: scale(1.04);
            }
            33% {
                opacity: 0;
            }
            100% {
                opacity: 0;
            }
        }

        /* Foreground Content Container */
        .landing-hero-green-v2 .landing-content-inner {
            position: relative;
            z-index: 2;
            max-width: 1140px;
            margin: 0 auto;
        }

        /* Hero Typography */
        .hero-tagline {
            color: #b2ff81;
            font-family: cursive;
            font-size: 1.25rem;
            font-weight: 700;
            font-style: italic;
        }

        .hero-headline {
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            font-size: 3.5rem;
            color: #ffffff;
            text-transform: uppercase;
        }

        .hero-headline .text-lime {
            color: #b2ff81;
        }

        .hero-subtext {
            color: rgba(255, 255, 255, 0.82);
            font-size: 1rem;
            line-height: 1.65;
            max-width: 510px;
        }

        /* Hero CTA Buttons */
        .btn-orange-pill {
            background: linear-gradient(135deg, #ffa726, #fb8c00);
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.75rem 1.8rem;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            border: none;
            box-shadow: 0 4px 15px rgba(251, 140, 0, 0.32);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-orange-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(251, 140, 0, 0.45);
        }

        .btn-outline-custom {
            background: transparent;
            color: #ffffff !important;
            border: 1.5px solid rgba(255, 255, 255, 0.45);
            border-radius: 50rem;
            padding: 0.75rem 1.8rem;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn-outline-custom:hover {
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-lime-pill-solid {
            background-color: #b2ff81;
            color: #0b291a !important;
            border-radius: 50rem;
            padding: 0.65rem 1.75rem;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 14px rgba(178, 255, 129, 0.25);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .btn-lime-pill-solid:hover {
            background-color: #9be667;
            transform: translateY(-2px);
        }

        /* Right Column Phone Mockup */
        .phone-mockup-container {
            position: relative;
            max-width: 290px;
            margin: 0 auto;
        }

        /* Responsive Mobile Adjustments */
        @media (max-width: 768px) {
            .landing-hero-green-v2 {
                padding: 4rem 1rem 3.5rem 1rem;
            }
            .hero-headline {
                font-size: 2.35rem;
                letter-spacing: -1px;
            }
        }
        /* ====================================================
        ROW 2: ONE PLATFORM, EVERY KIND OF KEDAI
        ==================================================== */
        .landing-row-platform {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background-color: #f3f4ee;
            color: #1a1a1a;
            padding: 5rem 1rem;
        }

        .landing-row-platform .landing-content-inner {
            max-width: 1140px;
            margin: 0 auto;
        }

        .platform-tagline {
            color: #5b8c3e;
            font-family: cursive;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .platform-headline {
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            font-size: 3rem;
            color: #0b291a;
            text-transform: uppercase;
        }

        /* Category Feature Card Style */
        .category-feature-card {
            position: relative;
            border-radius: 1.25rem;
            overflow: hidden;
            min-height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            background-size: cover;
            background-position: center;
        }

        /* Specific card background images (replace with your assets) */
        .cat-card-food {
            background-image: linear-gradient(to top, rgba(11, 41, 26, 0.9) 15%, rgba(11, 41, 26, 0.3) 70%, rgba(11, 41, 26, 0.1)),
                            url('/simple_ordering_multivendor/assets/cat-food.jpg');
        }
        .cat-card-fashion {
            background-image: linear-gradient(to top, rgba(11, 41, 26, 0.9) 15%, rgba(11, 41, 26, 0.3) 70%, rgba(11, 41, 26, 0.1)),
                            url('/simple_ordering_multivendor/assets/cat-fashion.jpg');
        }
        .cat-card-fresh {
            background-image: linear-gradient(to top, rgba(11, 41, 26, 0.9) 15%, rgba(11, 41, 26, 0.3) 70%, rgba(11, 41, 26, 0.1)),
                            url('/simple_ordering_multivendor/assets/cat-fresh.jpg');
        }
        .cat-card-crafts {
            background-image: linear-gradient(to top, rgba(11, 41, 26, 0.9) 15%, rgba(11, 41, 26, 0.3) 70%, rgba(11, 41, 26, 0.1)),
                            url('/simple_ordering_multivendor/assets/cat-crafts.jpg');
        }
        .cat-card-more {
            background-image: linear-gradient(to top, rgba(11, 41, 26, 0.9) 15%, rgba(11, 41, 26, 0.3) 70%, rgba(11, 41, 26, 0.1)),
                            url('/simple_ordering_multivendor/assets/cat-more.jpg');
        }

        .category-icon-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            width: 48px;
            height: 48px;
            background-color: #b2ff81;
            color: #0b291a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .category-feature-card h3 {
            font-weight: 800;
            font-size: 1.65rem;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }

        .category-feature-card p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.25rem;
        }

        .card-action-btns {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-sample-action {
            background-color: #b2ff81;
            color: #0b291a !important;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .btn-sample-action:hover {
            background-color: #9be667;
            transform: translateY(-1px);
        }

        .btn-sample-outline {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(4px);
            transition: background 0.2s ease;
        }
        .btn-sample-outline:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .platform-headline {
                font-size: 2.25rem;
            }
            .category-feature-card {
                min-height: 340px;
            }
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
        /* ====================================================
        ROW 5: YOUR STORE. YOUR PRODUCTS. YOUR WAY.
        ==================================================== */
        .landing-row-showcase {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background-color: #f3f4ee;
            color: #0b291a;
            padding: 5.5rem 1rem;
            overflow: hidden;
        }

        .landing-row-showcase .landing-content-inner {
            max-width: 1040px;
            margin: 0 auto;
        }

        .showcase-tagline {
            color: #5b8c3e;
            font-family: cursive;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .showcase-headline {
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            font-size: 3.1rem;
            color: #0b291a;
            text-transform: uppercase;
        }

        /* Phone Mockup Fan Display */
        .showcase-phone-col {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .showcase-phone-img {
            width: 100%;
            max-width: 260px;
            height: auto;
            border-radius: 2.2rem;
            filter: drop-shadow(0 18px 28px rgba(11, 41, 26, 0.22));
            transition: transform 0.3s ease;
        }

        .phone-tilt-left {
            transform: rotate(-3.5deg) translateY(14px);
        }

        .phone-center-elevated {
            max-width: 280px;
            transform: translateY(-10px);
            z-index: 2;
        }

        .phone-tilt-right {
            transform: rotate(3.5deg) translateY(14px);
        }

        .showcase-phone-img:hover {
            transform: translateY(-6px) rotate(0deg);
        }

        /* Numbered Step Captions */
        .showcase-step-num {
            color: #5b8c3e;
            font-weight: 800;
            font-size: 2rem;
            line-height: 1;
            margin-top: 1.5rem;
            margin-bottom: 0.35rem;
        }

        .showcase-step-title {
            color: #0b291a;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: -0.3px;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .showcase-step-desc {
            color: #475569;
            font-size: 0.88rem;
            line-height: 1.5;
            max-width: 245px;
            margin: 0 auto;
        }

        .showcase-footer-note {
            color: #0b291a;
            font-family: cursive;
            font-size: 1.2rem;
            font-weight: 600;
            max-width: 580px;
            margin: 0 auto;
            line-height: 1.45;
        }

        /* Responsive Adjustments */
        @media (max-width: 767px) {
            .showcase-headline {
                font-size: 2.25rem;
            }
            .phone-tilt-left,
            .phone-center-elevated,
            .phone-tilt-right {
                transform: none;
                max-width: 250px;
            }
        }
        /* Row 6: Dark Business Rules Section */
        .landing-row-rules {
            background-color: #0b291a;
            color: #ffffff;
            padding: 5rem 2rem;
        }
        /* ====================================================
        ROW 7: ORDERS MADE SIMPLE. PAYMENTS MADE DIRECT.
        ==================================================== */
        .landing-row-direct {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background-color: #ffffff;
            color: #0b291a;
            padding: 5.5rem 1rem;
            border-top: 1px solid #eaeaea;
            border-bottom: 1px solid #eaeaea;
        }

        .landing-row-direct .landing-content-inner {
            max-width: 1080px;
            margin: 0 auto;
        }

        .direct-kicker {
            color: #5b6b63;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .direct-headline {
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -1.5px;
            font-size: 3rem;
            color: #0b291a;
            text-transform: uppercase;
        }

        .direct-info-card {
            background-color: #f3f4ee;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 2.5rem;
            height: 100%;
            text-align: left;
        }

        .direct-icon-circle {
            width: 56px;
            height: 56px;
            background-color: #e2ebe3;
            color: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }

        .direct-info-card h3 {
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.3px;
            color: #0b291a;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
        }

        .direct-card-subtitle {
            color: #5b6b63;
            font-style: italic;
            font-size: 0.92rem;
            margin-bottom: 1.15rem;
        }

        .direct-card-text {
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        .payment-method-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 1.35rem;
        }

        .payment-pill-badge {
            background-color: #ffffff;
            color: #0b291a;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }

        .payment-pill-badge i {
            color: #198754;
        }

        .direct-disclaimer-box {
            max-width: 720px;
            margin: 2.5rem auto 0 auto;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            padding: 1rem 1.35rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            text-align: left;
            font-size: 0.9rem;
            color: #334155;
            line-height: 1.5;
        }

        .direct-disclaimer-box i {
            color: #475569;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        @media (max-width: 767px) {
            .direct-headline {
                font-size: 2.2rem;
            }
            .direct-info-card {
                padding: 1.85rem;
            }
        }
        /* ====================================================
        ROW 8: EXPLORE KEDAI DIRECTORY
        ==================================================== */
        .landing-row-explore {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background-color: #f3f4ee;
            color: #0b291a;
            padding: 5.5rem 1rem;
        }

        .landing-row-explore .landing-content-inner {
            max-width: 1200px;
            margin: 0 auto;
        }

        .explore-tagline {
            color: #5b8c3e;
            font-family: cursive;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .explore-headline {
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            font-size: 3rem;
            color: #0b291a;
            text-transform: uppercase;
        }

        .explore-subtext {
            color: #475569;
            font-size: 1rem;
            max-width: 580px;
        }

        /* Explore All Kedai Button */
        .btn-explore-all {
            background-color: #0b291a;
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.75rem 1.75rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(11, 41, 26, 0.2);
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .btn-explore-all:hover {
            background-color: #16422b;
            transform: translateY(-2px);
        }

        /* Category Filter Chips */
        .explore-filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            justify-content: center;
            margin-bottom: 3rem;
        }

        .filter-chip {
            background-color: #ffffff;
            color: #0b291a;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 0.55rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .filter-chip.active,
        .filter-chip:hover {
            background-color: #0b291a;
            color: #ffffff;
            border-color: #0b291a;
        }

        /* Vendor Directory Card */
        .vendor-dir-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            text-align: left;
            transition: transform 0.2s ease;
        }

        .vendor-dir-card:hover {
            transform: translateY(-4px);
        }

        .vendor-card-header-img {
            position: relative;
            height: 160px;
            background-size: cover;
            background-position: center;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .vendor-cat-badge {
            background-color: rgba(11, 41, 26, 0.85);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 0.3rem 0.65rem;
            border-radius: 50rem;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }

        .vendor-sample-badge {
            background-color: #ffffff;
            color: #0b291a;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            border-radius: 50rem;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .vendor-avatar-row {
            position: relative;
            padding: 0 1.25rem;
            margin-top: -32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .vendor-avatar-circle {
            width: 64px;
            height: 64px;
            background-color: #0b291a;
            border: 3px solid #ffffff;
            border-radius: 50%;
            color: #b2ff81;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .vendor-status-pill {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .status-open {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .status-closed {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .vendor-card-body {
            padding: 1rem 1.25rem 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .vendor-store-title {
            font-weight: 800;
            font-size: 1.15rem;
            color: #0b291a;
            margin-bottom: 0.25rem;
            letter-spacing: -0.3px;
        }

        .vendor-store-desc {
            color: #475569;
            font-size: 0.85rem;
            line-height: 1.45;
            margin-bottom: 1rem;
            min-height: 2.5rem;
        }

        .vendor-meta-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.25rem 0;
            font-size: 0.8rem;
            color: #5b6b63;
        }

        .vendor-meta-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
        }

        .vendor-meta-list i {
            width: 14px;
            color: #198754;
            text-align: center;
        }

        .btn-view-store {
            background-color: #0b291a;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 50rem;
            padding: 0.6rem;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-top: auto;
            transition: background-color 0.2s ease;
        }

        .btn-view-store:hover {
            background-color: #16422b;
        }

        @media (max-width: 992px) {
            .explore-headline {
                font-size: 2.3rem;
            }
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

        /* ====================================================
        ROW 10: READY TO TAKE YOUR BUSINESS DIGITAL? (CTA)
        ==================================================== */
        .landing-row-cta-banner {
            width: 100vw;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            background-color: #0b291a;
            color: #ffffff;
            padding: 6rem 1rem;
            text-align: center;
            overflow: hidden;
        }

        .landing-row-cta-banner .landing-content-inner {
            max-width: 900px;
            margin: 0 auto;
        }

        .cta-banner-tagline {
            color: #b2ff81;
            font-family: cursive;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .cta-banner-headline {
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            font-size: 3.2rem;
            color: #ffffff;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .cta-banner-subtext {
            color: rgba(255, 255, 255, 0.82);
            font-size: 1.05rem;
            margin-bottom: 2.5rem;
        }

        /* Action Buttons */
        .btn-cta-orange {
            background: linear-gradient(135deg, #ffa726, #fb8c00);
            color: #ffffff !important;
            border-radius: 50rem;
            padding: 0.85rem 2.2rem;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            border: none;
            box-shadow: 0 4px 15px rgba(251, 140, 0, 0.35);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-cta-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(251, 140, 0, 0.45);
        }

        .btn-cta-outline {
            background: transparent;
            color: #ffffff !important;
            border: 1.5px solid rgba(255, 255, 255, 0.5);
            border-radius: 50rem;
            padding: 0.85rem 2.2rem;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .cta-whatsapp-label {
            color: #b2ff81;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .whatsapp-contact-pills {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .wa-pill-item {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 50rem;
            padding: 0.65rem 1.5rem;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .wa-pill-item i {
            color: #b2ff81;
            font-size: 1.1rem;
        }

        .wa-pill-item:hover {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: #b2ff81;
            color: #ffffff;
        }

        @media (max-width: 767px) {
            .cta-banner-headline {
                font-size: 2.3rem;
            }
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
            border-radius: 10rem !important;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .faq-accordion .accordion-button {
            background-color: #f3f4ee !important;
            color: #0b291a !important;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10rem !important;
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
    <!-- ROW 1: HERO SECTION WITH 4-IMAGE BACKGROUND SLIDESHOW -->
    <!-- ==================================================== -->
    <section id="home" class="landing-full-row landing-hero-green-v2 shadow-sm">
        
        <!-- Background Slideshow Layer -->
        <div class="hero-slideshow">
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
        </div>

        <!-- Foreground Content Layer -->
        <div class="landing-content-inner container py-3">
            <div class="row align-items-center g-5">
                
                <!-- Left Column: Copy & Actions -->
                <div class="col-lg-7 text-center text-lg-start">
                    <p class="hero-tagline fw-bold fst-italic mb-2">
                        Bisnes Makin Mudah
                    </p>
                    <h1 class="hero-headline text-white mb-3">
                        YOUR OWN DIGITAL STORE.<br>
                        <span class="text-lime">ORDERS STRAIGHT TO WHATSAPP.</span>
                    </h1>
                    <p class="hero-subtext mb-4 mx-auto mx-lg-0">
                        Food, fashion, fresh produce, handmade crafts and more. Turn your products into a simple digital ordering experience. Let customers browse, choose, order and send their orders directly to your WhatsApp.
                    </p>
                    
                    <!-- Action Buttons Row -->
                    <div class="d-flex justify-content-center justify-content-lg-start align-items-center gap-3 flex-wrap mb-3">
                        <a href="https://api.whatsapp.com/send/?phone=601169893389&text=Hi+Kedai+WA+Kamek%2C+I%27d+like+to+get+my+digital+store+set+up.&type=phone_number&app_absent=0" target="_blank" class="btn btn-orange-pill">
                            GET YOUR STORE SET UP
                        </a>
                        <a href="#how-it-works" class="btn btn-outline-custom">
                            SEE HOW IT WORKS
                        </a>
                    </div>
                    
                    <!-- Explore Kedai Lime Pill Button -->
                    <div class="text-center text-lg-start mt-3">
                        <a href="#explore" class="btn btn-lime-pill-solid">
                            EXPLORE KEDAI <i class="fa-solid fa-arrow-right ms-2 fs-6"></i>
                        </a>
                    </div>
                </div>

                <!-- Right Column: Phone Mockup Visual -->
                <div class="col-lg-5 text-center">
                    <div class="phone-mockup-container">
                        <div class="p-3 bg-black bg-opacity-40 rounded-5 border border-secondary border-opacity-50 shadow-lg text-center">
                            <i class="fa-solid fa-mobile-screen display-1 mb-3" style="color: #b2ff81;"></i>
                            <h6 class="text-white fw-bold mb-1">Live Storefront Preview</h6>
                            <p class="text-white-50 small mb-0">Seamless WhatsApp Cart Integration</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 2: ONE PLATFORM. EVERY KIND OF KEDAI. -->
    <!-- ==================================================== -->
    <section id="categories" class="landing-row-platform shadow-sm">
        <div class="landing-content-inner container py-2">
            
            <!-- Section Header -->
            <div class="text-center mb-5">
                <p class="platform-tagline fst-italic mb-1">
                    From kuih to kain, sayur to sulaman.
                </p>
                <h2 class="platform-headline mb-0">
                    ONE PLATFORM.<br>EVERY KIND OF KEDAI.
                </h2>
            </div>

            <!-- Top Row: 2 Large Cards (Food & Fashion) -->
            <div class="row g-4 mb-4">
                
                <!-- Card 1: Food & Beverages -->
                <div class="col-lg-6">
                    <div class="category-feature-card cat-card-food">
                        <div class="category-icon-badge">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div>
                            <h3>FOOD & BEVERAGES</h3>
                            <p>Hawkers, kuih sellers, home bakers, cafés, restaurants and catering.</p>
                            <div class="card-action-btns">
                                <a href="#sample-food" class="btn-sample-action">SEE SAMPLE STORE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7rem;"></i></a>
                                <a href="#sample-food" class="btn-sample-outline">SAMPLE STORE</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Fashion & Apparel -->
                <div class="col-lg-6">
                    <div class="category-feature-card cat-card-fashion">
                        <div class="category-icon-badge">
                            <i class="fa-solid fa-shirt"></i>
                        </div>
                        <div>
                            <h3>FASHION & APPAREL</h3>
                            <p>Baju kurung, batik, tudung, modest wear, streetwear and accessories. Sizes, colours and requests go in order notes.</p>
                            <div class="card-action-btns">
                                <a href="#sample-fashion" class="btn-sample-action">SEE SAMPLE STORE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7rem;"></i></a>
                                <a href="#sample-fashion" class="btn-sample-outline">SAMPLE STORE</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Row: 3 Cards (Fresh Produce, Handmade Crafts, More Businesses) -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                
                <!-- Card 3: Fresh Produce -->
                <div class="col">
                    <div class="category-feature-card cat-card-fresh">
                        <div class="category-icon-badge">
                            <i class="fa-solid fa-carrot"></i>
                        </div>
                        <div>
                            <h3>FRESH PRODUCE</h3>
                            <p>Vegetables, fruits, seafood, eggs, local farm and pasar goods.</p>
                            <div class="card-action-btns">
                                <a href="#sample-fresh" class="btn-sample-action">SEE SAMPLE STORE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7rem;"></i></a>
                                <a href="#sample-fresh" class="btn-sample-outline">SAMPLE STORE</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Handmade Crafts -->
                <div class="col">
                    <div class="category-feature-card cat-card-crafts">
                        <div class="category-icon-badge">
                            <i class="fa-solid fa-gem"></i>
                        </div>
                        <div>
                            <h3>HANDMADE CRAFTS</h3>
                            <p>Weaving, beadwork, songket, batik, candles, soap, ceramics and personalised gifts.</p>
                            <div class="card-action-btns">
                                <a href="#sample-crafts" class="btn-sample-action">SEE SAMPLE STORE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7rem;"></i></a>
                                <a href="#sample-crafts" class="btn-sample-outline">SAMPLE STORE</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 5: More Businesses -->
                <div class="col">
                    <div class="category-feature-card cat-card-more">
                        <div class="category-icon-badge">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div>
                            <h3>MORE BUSINESSES</h3>
                            <p>Beauty and wellness, home goods, gifts and hampers, plants, pre-loved items and other small sellers.</p>
                            <div class="card-action-btns">
                                <a href="#sample-more" class="btn-sample-action">SEE SAMPLE STORE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7rem;"></i></a>
                                <a href="#sample-more" class="btn-sample-outline">SAMPLE STORE</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Tagline Footer -->
            <div class="text-center">
                <p class="fst-italic text-dark mb-0" style="font-size: 1.2rem; font-weight: 500;">
                    If you sell it, you can list it.
                </p>
            </div>

        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 3: FEATURE HIGHLIGHTS -->
    <!-- ==================================================== -->
    <section id="features" class="landing-full-row landing-row-white shadow-sm text-center">
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
    <section id="how-it-works" class="landing-full-row landing-row-how-it-works shadow-sm">
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
    <section class="landing-row-showcase shadow-sm text-center">
        <div class="landing-content-inner container py-2">
            
            <!-- Section Header -->
            <div class="mb-5">
                <p class="showcase-tagline fst-italic mb-1">
                    See it in action
                </p>
                <h2 class="showcase-headline mb-0">
                    YOUR STORE. YOUR<br>PRODUCTS. YOUR WAY.
                </h2>
            </div>

            <!-- 3-Phone Showcase & Steps -->
            <div class="row row-cols-1 row-cols-md-3 g-4 align-items-end justify-content-center mb-5 pt-2">
                
                <!-- Step 01: Browse the Store -->
                <div class="col showcase-phone-col">
                    <div class="showcase-phone-container phone-tilt-left">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary border-opacity-25 text-center">
                            <i class="fa-solid fa-mobile-screen display-3 text-success mb-2" style="color: #1e392a !important;"></i>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Browse the Store Screen</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Interactive catalog & pricing</p>
                        </div>
                    </div>
                    <div class="showcase-step-num">01</div>
                    <h4 class="showcase-step-title">BROWSE THE STORE</h4>
                    <p class="showcase-step-desc">
                        Beautiful product photography, descriptions and prices.
                    </p>
                </div>

                <!-- Step 02: Customise Your Order -->
                <div class="col showcase-phone-col">
                    <div class="showcase-phone-container phone-center-elevated">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary border-opacity-25 text-center">
                            <i class="fa-solid fa-mobile-screen display-3 text-success mb-2" style="color: #1e392a !important;"></i>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Customise Your Order Screen</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Quantities, notes & review list</p>
                        </div>
                    </div>
                    <div class="showcase-step-num">02</div>
                    <h4 class="showcase-step-title">CUSTOMISE YOUR ORDER</h4>
                    <p class="showcase-step-desc">
                        Select quantities, add notes and review the order list.
                    </p>
                </div>

                <!-- Step 03: Send to WhatsApp -->
                <div class="col showcase-phone-col">
                    <div class="showcase-phone-container phone-tilt-right">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-secondary border-opacity-25 text-center">
                            <i class="fa-solid fa-mobile-screen display-3 text-success mb-2" style="color: #1e392a !important;"></i>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">Send to WhatsApp Screen</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Clean summary & total payload</p>
                        </div>
                    </div>
                    <div class="showcase-step-num">03</div>
                    <h4 class="showcase-step-title">SEND TO WHATSAPP</h4>
                    <p class="showcase-step-desc">
                        A clean summary with name, items, notes and total.
                    </p>
                </div>

            </div>

            <!-- Bottom Handwritten Note -->
            <div class="pt-2">
                <p class="showcase-footer-note fst-italic mb-0">
                    Your customers don’t need to download anything or create an account. Just open the link and order.
                </p>
            </div>

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
    <!-- ROW 7: ORDERS MADE SIMPLE. PAYMENTS MADE DIRECT. -->
    <!-- ==================================================== -->
    <section class="landing-row-direct shadow-sm">
        <div class="landing-content-inner container py-2">
            
            <!-- Section Header -->
            <div class="text-center mb-5">
                <p class="direct-kicker mb-2">
                    NO MIDDLEMAN
                </p>
                <h2 class="direct-headline mb-0">
                    ORDERS MADE SIMPLE.<br>
                    PAYMENTS MADE DIRECT.
                </h2>
            </div>

            <!-- Two Feature Cards Grid -->
            <div class="row row-cols-1 row-cols-md-2 g-4">
                
                <!-- Left Card: Delivery -->
                <div class="col">
                    <div class="direct-info-card">
                        <div class="direct-icon-circle">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <h3>WHAT ABOUT DELIVERY?</h3>
                        <p class="direct-card-subtitle">
                            Want your customers to enjoy delivery?
                        </p>
                        <p class="direct-card-text">
                            Customers discuss directly with the vendor to arrange a third-party courier or delivery service. Customers pay the actual product price, while delivery charges are arranged and paid separately.
                        </p>
                    </div>
                </div>

                <!-- Right Card: Payments -->
                <div class="col">
                    <div class="direct-info-card">
                        <div class="direct-icon-circle">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <h3>HOW DO CUSTOMERS PAY?</h3>
                        <p class="direct-card-subtitle">
                            Simple. Direct. Between you and your customer.
                        </p>
                        <p class="direct-card-text">
                            When the order summary reaches your WhatsApp, you provide your payment details directly.
                        </p>
                        <div class="payment-method-pills">
                            <span class="payment-pill-badge">
                                <i class="fa-solid fa-qrcode"></i> DuitNow QR
                            </span>
                            <span class="payment-pill-badge">
                                <i class="fa-solid fa-building-columns"></i> Online bank transfer
                            </span>
                            <span class="payment-pill-badge">
                                <i class="fa-solid fa-handshake"></i> Other agreed arrangements
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Disclaimer Banner -->
            <div class="direct-disclaimer-box">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                    <strong class="text-dark">Kedai WA Kamek does not collect customer payments or process transactions.</strong> Payments are handled directly between customers and vendors.
                </div>
            </div>

        </div>
    </section>

    <!-- ==================================================== -->
    <!-- ROW 8: EXPLORE KEDAI DIRECTORY -->
    <!-- ==================================================== -->
    <section id="explore" class="landing-row-explore shadow-sm">
        <div class="landing-content-inner container py-2">
            
            <!-- Section Header & Top Explore Button -->
            <div class="row align-items-center mb-4">
                <div class="col-lg-8 text-center text-lg-start mb-3 mb-lg-0">
                    <p class="explore-tagline fst-italic mb-1">
                        Discover local businesses
                    </p>
                    <h2 class="explore-headline mb-2">
                        EXPLORE KEDAI
                    </h2>
                    <p class="explore-subtext mb-0 mx-auto mx-lg-0">
                        Browse local food, fashion, produce, crafts and more from businesses across Sarawak and Malaysia.
                    </p>
                </div>
                <div class="col-lg-4 text-center text-lg-end">
                    <a href="explore.php" class="btn-explore-all">
                        EXPLORE ALL KEDAI <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <!-- Category Filter Chips -->
            <div class="explore-filter-chips">
                <a href="#food" class="filter-chip active">Food & Beverages</a>
                <a href="#fashion" class="filter-chip">Fashion & Apparel</a>
                <a href="#produce" class="filter-chip">Fresh Produce</a>
                <a href="#crafts" class="filter-chip">Handmade Crafts</a>
                <a href="#others" class="filter-chip">Others</a>
            </div>

            <!-- 4-Column Vendor Cards Grid -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                
                <!-- Vendor Card 1: Dapur Mak Long -->
                <div class="col">
                    <div class="vendor-dir-card">
                        <div class="vendor-card-header-img" style="background-image: url('/simple_ordering_multivendor/assets/vendor-1.jpg');">
                            <span class="vendor-cat-badge">Food & Beverages</span>
                            <span class="vendor-sample-badge">Sample Listing</span>
                        </div>
                        <div class="vendor-avatar-row">
                            <div class="vendor-avatar-circle">
                                <i class="fa-solid fa-utensils"></i>
                            </div>
                            <span class="vendor-status-pill status-open">
                                <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Open
                            </span>
                        </div>
                        <div class="vendor-card-body">
                            <h3 class="vendor-store-title">DAPUR MAK LONG</h3>
                            <p class="vendor-store-desc">Masakan kampung, rasa macam rumah sendiri.</p>
                            <ul class="vendor-meta-list">
                                <li><i class="fa-solid fa-location-dot"></i> Gedong, Sarawak</li>
                                <li><i class="fa-solid fa-clock"></i> 7:00 am – 2:00 pm</li>
                                <li><i class="fa-solid fa-truck"></i> Delivery arranged with vendor</li>
                            </ul>
                            <a href="store.php?id=1" class="btn-view-store">VIEW STORE</a>
                        </div>
                    </div>
                </div>

                <!-- Vendor Card 2: Kopitiam Ah Seng -->
                <div class="col">
                    <div class="vendor-dir-card">
                        <div class="vendor-card-header-img" style="background-image: url('/simple_ordering_multivendor/assets/vendor-2.jpg');">
                            <span class="vendor-cat-badge">Food & Beverages</span>
                            <span class="vendor-sample-badge">Sample Listing</span>
                        </div>
                        <div class="vendor-avatar-row">
                            <div class="vendor-avatar-circle">
                                <i class="fa-solid fa-mug-hot"></i>
                            </div>
                            <span class="vendor-status-pill status-open">
                                <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Open
                            </span>
                        </div>
                        <div class="vendor-card-body">
                            <h3 class="vendor-store-title">KOPITIAM AH SENG</h3>
                            <p class="vendor-store-desc">Kopi tarik pagi, roti bakar kaya sejak 1988.</p>
                            <ul class="vendor-meta-list">
                                <li><i class="fa-solid fa-location-dot"></i> Kuching, Sarawak</li>
                                <li><i class="fa-solid fa-clock"></i> 6:30 am – 3:00 pm</li>
                                <li><i class="fa-solid fa-truck"></i> Delivery arranged with vendor</li>
                            </ul>
                            <a href="store.php?id=2" class="btn-view-store">VIEW STORE</a>
                        </div>
                    </div>
                </div>

                <!-- Vendor Card 3: Kek Lapis Mama Ros -->
                <div class="col">
                    <div class="vendor-dir-card">
                        <div class="vendor-card-header-img" style="background-image: url('/simple_ordering_multivendor/assets/vendor-3.jpg');">
                            <span class="vendor-cat-badge">Food & Beverages</span>
                            <span class="vendor-sample-badge">Sample Listing</span>
                        </div>
                        <div class="vendor-avatar-row">
                            <div class="vendor-avatar-circle" style="background-color: #b2ff81; color: #0b291a;">
                                <i class="fa-solid fa-cake-candles"></i>
                            </div>
                            <span class="vendor-status-pill status-closed">
                                <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Closed
                            </span>
                        </div>
                        <div class="vendor-card-body">
                            <h3 class="vendor-store-title">KEK LAPIS MAMA ROS</h3>
                            <p class="vendor-store-desc">Kek lapis Sarawak buatan tangan.</p>
                            <ul class="vendor-meta-list">
                                <li><i class="fa-solid fa-location-dot"></i> Kuching, Sarawak</li>
                                <li><i class="fa-solid fa-clock"></i> 9:00 am – 6:00 pm</li>
                                <li><i class="fa-solid fa-truck"></i> Delivery arranged with vendor</li>
                            </ul>
                            <a href="store.php?id=3" class="btn-view-store">VIEW STORE</a>
                        </div>
                    </div>
                </div>

                <!-- Vendor Card 4: Sample Batik & Tudung Store -->
                <div class="col">
                    <div class="vendor-dir-card">
                        <div class="vendor-card-header-img" style="background-image: url('/simple_ordering_multivendor/assets/vendor-4.jpg');">
                            <span class="vendor-cat-badge">Fashion & Apparel</span>
                            <span class="vendor-sample-badge">Sample Listing</span>
                        </div>
                        <div class="vendor-avatar-row">
                            <div class="vendor-avatar-circle">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <span class="vendor-status-pill status-open">
                                <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i> Open
                            </span>
                        </div>
                        <div class="vendor-card-body">
                            <h3 class="vendor-store-title">SAMPLE BATIK & TUDUNG</h3>
                            <p class="vendor-store-desc">Batik, baju kurung and everyday tudung.</p>
                            <ul class="vendor-meta-list">
                                <li><i class="fa-solid fa-location-dot"></i> Kuching, Sarawak</li>
                                <li><i class="fa-solid fa-clock"></i> 10:00 am – 8:00 pm</li>
                                <li><i class="fa-solid fa-truck"></i> Delivery arranged with vendor</li>
                            </ul>
                            <a href="store.php?id=4" class="btn-view-store">VIEW STORE</a>
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
    <!-- ROW 10: READY TO TAKE YOUR BUSINESS DIGITAL? (CTA) -->
    <!-- ==================================================== -->
    <section id="become-vendor" class="landing-row-cta-banner shadow-sm">
        <div class="landing-content-inner container py-2">
            
            <!-- Header & Subtext -->
            <p class="cta-banner-tagline fst-italic mb-2">
                Siap untuk berniaga online?
            </p>
            <h2 class="cta-banner-headline">
                READY TO TAKE YOUR<br>BUSINESS DIGITAL?
            </h2>
            <p class="cta-banner-subtext">
                Let your customers order easily while you focus on growing your business.
            </p>

            <!-- Primary CTAs -->
            <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mb-5">
                <a href="https://api.whatsapp.com/send/?phone=601169893389&text=Hi+Kedai+WA+Kamek%2C+I%27d+like+to+get+my+digital+store+set+up.&type=phone_number&app_absent=0" target="_blank" class="btn btn-cta-orange">
                    GET YOUR STORE SET UP
                </a>
                <a href="https://api.whatsapp.com/send/?phone=601169893389&text=Hi+Kedai+WA+Kamek%2C+I+have+a+store+with+you+and+I%27d+like+it+listed+in+Explore+Kedai.&type=phone_number&app_absent=0" target="_blank" class="btn btn-cta-outline">
                    LIST MY STORE
                </a>
            </div>

            <!-- WhatsApp Direct Contact Section -->
            <div>
                <p class="cta-whatsapp-label mb-3">
                    CHAT WITH US ON WHATSAPP
                </p>
                <div class="whatsapp-contact-pills">
                    <a href="https://api.whatsapp.com/send/?phone=601169893389" target="_blank" class="wa-pill-item">
                        <i class="fa-brands fa-whatsapp"></i> +6011-69893389
                    </a>
                    <a href="https://api.whatsapp.com/send/?phone=60103055330" target="_blank" class="wa-pill-item">
                        <i class="fa-brands fa-whatsapp"></i> +6010-3055330
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
                            Yes. Kedai WA Kamek works for any business that sells products. Customers can add sizes, colours, weights, quantities or personalisation requests in the order notes.
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
                            No. Customers can browse and order without signing in.
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
                            No. Your store opens in any web browser through a link or QR code.
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
                    <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            No. We charge a one-time setup fee per store. Your sales belong to you.
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
                            You share your payment details on WhatsApp, such as DuitNow QR or online bank transfer.
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
                            You arrange delivery directly with the customer or a third-party courier. Delivery charges are separate.
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
                    <div id="faqCollapse7" class="accordion-collapse collapse" aria-labelledby="faqHeading7" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Yes.
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
                    <div id="faqCollapse8" class="accordion-collapse collapse" aria-labelledby="faqHeading8" data-bs-parent="#kedaiFaqAccordion">
                        <div class="accordion-body">
                            Every store we set up can be listed in Explore Kedai. Contact us on WhatsApp and we will add you.
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