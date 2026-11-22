<?php
$currentVendorPage = $currentVendorPage ?? basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$vendorStoreName = $vendorStoreName ?? ($_SESSION['vendor_store_name'] ?? 'Roti Berkaus');
$vendorRoleLabel = $vendorRoleLabel ?? 'Panel peniaga';
$vendorLogoUrl = $vendorLogoUrl ?? 'https://placehold.co/64x64/14261d/9df557?text=RB';

$vendorNavItems = [
    [
        'key' => 'dashboard.php',
        'label' => 'Ringkasan',
        'icon' => 'bi-house-door-fill',
        'href' => 'dashboard.php',
    ],
    [
        'key' => 'products.php',
        'label' => 'Produk',
        'icon' => 'bi-box-seam-fill',
        'href' => 'products.php',
    ],
    [
        'key' => 'delivery.php',
        'label' => 'Delivery Settings',
        'icon' => 'bi-truck',
        'href' => 'delivery.php',
    ],
    [
        'key' => 'settings.php',
        'label' => 'Tetapan kedai',
        'icon' => 'bi-gear-fill',
        'href' => 'settings.php',
    ],
    [
        'key' => 'subscription.php',
        'label' => 'Langganan',
        'icon' => 'bi-receipt',
        'href' => 'subscription.php',
    ],
];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Docked Spacer on Mobile so Main Content Never Shifts When Floating Sidenav Opens -->
<div class="d-lg-none flex-shrink-0" style="width: 60px; height: 100vh; height: 100dvh;"></div>

<!-- Backdrop When Floating Sidenav is Expanded on Mobile -->
<div
    id="vendorSidenavBackdrop"
    class="d-none position-fixed top-0 start-0 w-100 h-100"
    style="z-index: 1040; background-color: rgba(14, 31, 23, 0.35);">
</div>

<!-- Static 100dvh Sidenav: Docked on Desktop (lg+), Floats Over Content on Mobile (< lg) -->
<aside
    id="vendorSidenav"
    class="border-end d-flex flex-column justify-content-between flex-shrink-0 position-fixed top-0 start-0 overflow-hidden"
    style="z-index: 1045; width: 60px; height: 100vh; height: 100dvh; max-height: 100dvh; background-color: #fcfdfb; border-color: #e6ebe7 !important; color: #13261d;">

    <!-- Top Brand Logo Toggle & Navigation Links -->
    <div class="p-2 p-lg-4">

        <!-- Top Brand Logo Acts as Expand / Shrink Toggle -->
        <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-3 mb-lg-4">
            <button
                type="button"
                id="vendorLogoToggleBtn"
                title="Kembang atau kecilkan menu"
                aria-label="Kembang atau kecilkan menu"
                aria-expanded="false"
                class="btn p-0 border-0 bg-transparent d-inline-flex align-items-center gap-2 text-decoration-none min-w-0">
                <img
                    src="https://placehold.co/40x40/0b291a/9df557?text=KWK"
                    alt="Kedai WA Kamek Logo Placeholder"
                    class="rounded-3 object-fit-cover flex-shrink-0"
                    style="width: 38px; height: 38px;"
                >
                <span class="vendor-rail-label d-none fw-bold fs-5 lh-1 text-truncate"
                      style="color: #0e1f17; letter-spacing: -0.02em;">
                    Kedai <span style="color: #169a5a;">WA Kamek</span>
                </span>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav aria-label="Navigasi Panel Peniaga">
            <ul class="nav nav-pills flex-column gap-1 gap-lg-2">
                <?php foreach ($vendorNavItems as $item): ?>
                    <?php $isActive = ($currentVendorPage === $item['key']); ?>
                    <li class="nav-item">
                        <a
                            href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="nav-link d-flex align-items-center justify-content-center gap-3 px-2 py-2 py-lg-3 rounded-3 fw-semibold <?php echo $isActive ? 'active' : ''; ?>"
                            title="<?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>"
                            style="<?php echo $isActive
                                ? 'background-color: #e9f3ec; color: #0e1f17; font-size: 0.95rem;'
                                : 'background-color: transparent; color: #1f3329; font-size: 0.95rem;'; ?>">
                            <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width: 22px; height: 22px;">
                                <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"
                                   style="font-size: 1.15rem; line-height: 1; color: <?php echo $isActive ? '#0e1f17' : '#1f3329'; ?>;"></i>
                            </span>
                            <span class="vendor-rail-label d-none text-truncate">
                                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

    </div>

    <!-- Bottom Store Profile & Little Logout Logo Button -->
    <div class="p-2 p-lg-4 pt-1 pt-lg-2 mt-auto">
        <div class="d-flex flex-column align-items-center justify-content-center gap-2 rounded-4 border bg-white p-2"
             style="border-color: #e4e9e5 !important;">

            <!-- Store Logo Placeholder + Store Name/Role -->
            <div class="d-flex align-items-center gap-2 min-w-0">
                <img
                    src="<?php echo htmlspecialchars($vendorLogoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?> Logo Placeholder"
                    class="rounded-circle object-fit-cover flex-shrink-0"
                    style="width: 36px; height: 36px;"
                >
                <div class="vendor-rail-label d-none min-w-0">
                    <div class="fw-bold small text-truncate" style="color: #0e1f17;">
                        <?php echo htmlspecialchars($vendorStoreName, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="small text-truncate" style="color: #6b7c73; font-size: 0.78rem;">
                        <?php echo htmlspecialchars($vendorRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </div>

            <!-- Little Logout Logo Button Beside Store/Username When Expanded -->
            <a href="../logout.php"
               title="Log keluar"
               aria-label="Log keluar"
               class="vendor-rail-logout btn btn-sm border rounded-3 d-none align-items-center justify-content-center flex-shrink-0 p-2"
               style="background-color: #f8faf9; border-color: #e4e9e5 !important; color: #26382f;">
                <i class="bi bi-box-arrow-right" style="font-size: 1rem; line-height: 1;"></i>
            </a>

        </div>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidenav = document.getElementById('vendorSidenav');
    const backdrop = document.getElementById('vendorSidenavBackdrop');
    const logoToggle = document.getElementById('vendorLogoToggleBtn');
    if (!sidenav || !logoToggle) return;

    const labels = sidenav.querySelectorAll('.vendor-rail-label');
    const navLinks = sidenav.querySelectorAll('nav .nav-link');
    const bottomBox = sidenav.querySelector('.mt-auto > div');
    const logoutBtn = sidenav.querySelector('.vendor-rail-logout');
    const brandRow = logoToggle.parentElement;
    let mobileFloating = false;
    let desktopExpanded = false;

    function applyState() {
        const isDesktop = window.innerWidth >= 992;
        const showFull = isDesktop ? desktopExpanded : mobileFloating;
        const isFloatingOverContent = !isDesktop && mobileFloating;

        // Keep sidenav docked in flex flow on desktop so main content is never covered
        sidenav.classList.toggle('position-fixed', !isDesktop);
        sidenav.classList.toggle('position-relative', isDesktop);
        sidenav.style.width = showFull ? '265px' : (isDesktop ? '76px' : '60px');
        sidenav.classList.toggle('shadow-lg', isFloatingOverContent);

        logoToggle.setAttribute('aria-expanded', String(showFull));

        if (brandRow) {
            brandRow.classList.toggle('justify-content-center', !showFull);
            brandRow.classList.toggle('justify-content-start', showFull);
        }

        if (backdrop) {
            backdrop.classList.toggle('d-none', !isFloatingOverContent);
            backdrop.classList.toggle('d-block', isFloatingOverContent);
        }

        labels.forEach(function (el) {
            if (el.tagName === 'SPAN') {
                el.classList.toggle('d-none', !showFull);
                el.classList.toggle('d-inline', showFull);
            } else {
                el.classList.toggle('d-none', !showFull);
                el.classList.toggle('d-block', showFull);
            }
        });

        navLinks.forEach(function (link) {
            link.classList.toggle('justify-content-center', !showFull);
            link.classList.toggle('justify-content-start', showFull);
            link.classList.toggle('px-2', !showFull);
            link.classList.toggle('px-lg-3', showFull);
        });

        if (bottomBox) {
            bottomBox.classList.toggle('flex-column', !showFull);
            bottomBox.classList.toggle('flex-row', showFull);
            bottomBox.classList.toggle('justify-content-center', !showFull);
            bottomBox.classList.toggle('justify-content-between', showFull);
        }

        if (logoutBtn) {
            logoutBtn.classList.toggle('d-none', !showFull);
            logoutBtn.classList.toggle('d-inline-flex', showFull);
        }
    }

    logoToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (window.innerWidth >= 992) {
            desktopExpanded = !desktopExpanded;
        } else {
            mobileFloating = !mobileFloating;
        }
        applyState();
    });

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            mobileFloating = false;
            applyState();
        });
    }

    window.addEventListener('resize', applyState);
    applyState();
});
</script>