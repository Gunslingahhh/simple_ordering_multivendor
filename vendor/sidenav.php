<?php
// Determine current script name to highlight active link
$current_script = basename($_SERVER['PHP_SELF']);
?>
<nav class="dash-sidebar">
    <div class="sidebar-brand-area">
        <div class="d-flex align-items-center gap-2">
            <div class="text-white rounded-2 p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #0b291a !important;">
                <i class="fa-solid fa-bag-shopping fs-6"></i>
            </div>
            <span class="fw-bold fs-6" style="color: #0b291a;">Kedai <span style="color: #198754;">WA Kamek</span></span>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= $current_script === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> Ringkasan
            </a>
        </li>
        <li>
            <a href="products.php" class="<?= $current_script === 'products.php' || $current_script === 'add_item.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-cube"></i> Produk
            </a>
        </li>
        <li>
            <a href="delivery.php" class="<?= $current_script === 'delivery.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-truck"></i> Delivery Settings
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= $current_script === 'settings.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gear"></i> Tetapan kedai
            </a>
        </li>
        <li>
            <a href="subscription.php" class="<?= $current_script === 'subscription.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Langganan
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="vendor-profile-box">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    <?= strtoupper(substr($vendor['store_name'], 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($vendor['store_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Panel peniaga</div>
                </div>
            </div>
            <a href="../logout.php" class="text-muted" title="Log keluar">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</nav>