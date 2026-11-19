<?php
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
            <a href="vendors.php" class="<?= $current_script === 'vendors.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i> Peniaga
            </a>
        </li>
        <li>
            <a href="menu.php" class="<?= $current_script === 'menu.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-utensils"></i> Menu
            </a>
        </li>
        <li>
            <a href="activation.php" class="<?= $current_script === 'activation.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-key"></i> Kod pengaktifan
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= $current_script === 'settings.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-sliders"></i> Jenama & penaja
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="vendor-profile-box">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem; background-color: #0b291a !important;">
                    P
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-dark small text-truncate">Pentadbir</div>
                    <div class="text-muted" style="font-size: 0.7rem;">Admin platform</div>
                </div>
            </div>
            <a href="../logout.php" class="text-muted" title="Log keluar">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</nav>