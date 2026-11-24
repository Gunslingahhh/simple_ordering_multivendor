<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$activeScriptName = basename((string) ($_SERVER['PHP_SELF'] ?? 'dashboard.php'));
$currentAdminPage = $currentAdminPage ?? $activeScriptName;
$adminDisplayName = $adminDisplayName ?? (string) ($_SESSION['username'] ?? 'Admin');
$adminRoleLabel = $adminRoleLabel ?? 'Panel admin';
$adminCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $adminDisplayName), 0, 2) ?: 'AD');
$adminAvatarUrl = $adminAvatarUrl ?? ('https://placehold.co/64x64/14261d/9df557?text=' . urlencode($adminCode));

$adminNavItems = [
    [
        'key' => 'dashboard.php',
        'aliases' => ['dashboard.php', 'index.php'],
        'label' => 'Ringkasan',
        'icon' => 'bi-grid',
        'href' => 'dashboard.php',
    ],
    [
        'key' => 'vendors.php',
        'aliases' => ['vendors.php'],
        'label' => 'Peniaga',
        'icon' => 'bi-shop',
        'href' => 'vendors.php',
    ],
    [
        'key' => 'menu.php',
        'aliases' => ['menu.php'],
        'label' => 'Menu',
        'icon' => 'bi-egg-fried',
        'href' => 'menu.php',
    ],
    [
        'key' => 'codes.php',
        'aliases' => ['activation.php', 'codes.php'],
        'label' => 'Kod pengaktifan',
        'icon' => 'bi-ticket-perforated',
        'href' => 'activation.php',
    ],
    [
        'key' => 'branding.php',
        'aliases' => ['settings.php', 'branding.php'],
        'label' => 'Jenama & penaja',
        'icon' => 'bi-palette',
        'href' => 'settings.php',
    ],
];

function isAdminNavItemActive(array $item, string $currentAdminPage, string $activeScriptName): bool
{
    if ($currentAdminPage === $item['key'] || $activeScriptName === $item['key']) {
        return true;
    }
    $aliases = $item['aliases'] ?? [];
    return in_array($currentAdminPage, $aliases, true) || in_array($activeScriptName, $aliases, true);
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Docked Spacer on Mobile so Main Content Never Shifts When Floating Sidenav Opens -->
<div class="d-lg-none flex-shrink-0" style="width: 60px; height: 100vh; height: 100dvh;"></div>

<!-- Backdrop When Floating Sidenav is Expanded on Mobile -->
<div
    id="adminSidenavBackdrop"
    class="d-none position-fixed top-0 start-0 w-100 h-100"
    style="z-index: 1040; background-color: rgba(14, 31, 23, 0.35);">
</div>

<!-- Static 100dvh Sidenav: Docked on Desktop (lg+), Floats Over Content on Mobile (< lg) -->
<aside
    id="adminSidenav"
    class="border-end d-flex flex-column justify-content-between flex-shrink-0 position-fixed top-0 start-0 overflow-hidden"
    style="z-index: 1045; width: 60px; height: 100vh; height: 100dvh; max-height: 100dvh; background-color: #fcfdfb; border-color: #e6ebe7 !important; color: #13261d;">

    <!-- Top Brand Logo Toggle & Navigation Links -->
    <div class="p-2 p-lg-4">

        <!-- Top Brand Logo Acts as Expand / Shrink Toggle -->
        <div class="d-flex align-items-center justify-content-center justify-content-lg-start mb-3 mb-lg-4">
            <button
                type="button"
                id="adminLogoToggleBtn"
                title="Kembang atau kecilkan menu"
                aria-label="Kembang atau kecilkan menu"
                aria-expanded="false"
                class="btn p-0 border-0 bg-transparent d-inline-flex align-items-center gap-2 text-decoration-none min-w-0">
                <img
                    src="https://placehold.co/44x40/169a5a/ffffff?text=KWK"
                    alt="Kedai WA Kamek Logo Placeholder"
                    class="rounded-3 object-fit-cover flex-shrink-0"
                    style="width: 40px; height: 38px;"
                >
                <span class="admin-rail-label d-none fw-bold fs-5 lh-1 text-truncate"
                      style="color: #0e1f17; letter-spacing: -0.02em;">
                    Kedai <span style="color: #169a5a;">WA Kamek</span>
                </span>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav aria-label="Navigasi Panel Admin">
            <ul class="nav nav-pills flex-column gap-1 gap-lg-2" id="adminSidenavList">
                <?php foreach ($adminNavItems as $item): ?>
                    <?php
                    $isActive = isAdminNavItemActive($item, (string) $currentAdminPage, $activeScriptName);
                    $targetFile = basename((string) parse_url($item['href'], PHP_URL_PATH));
                    $targetHash = (string) parse_url($item['href'], PHP_URL_FRAGMENT);
                    ?>
                    <li class="nav-item">
                        <a
                            href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-target-file="<?php echo htmlspecialchars($targetFile, ENT_QUOTES, 'UTF-8'); ?>"
                            data-target-hash="<?php echo htmlspecialchars($targetHash, ENT_QUOTES, 'UTF-8'); ?>"
                            aria-current="<?php echo $isActive ? 'page' : 'false'; ?>"
                            class="nav-link admin-nav-link d-flex align-items-center justify-content-center gap-3 px-2 py-2 py-lg-3 rounded-3 fw-semibold <?php echo $isActive ? 'active' : ''; ?>"
                            title="<?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>"
                            style="<?php echo $isActive
                                ? 'background-color: #eef2ef; color: #0e1f17; font-size: 0.95rem;'
                                : 'background-color: transparent; color: #1f3329; font-size: 0.95rem;'; ?>">
                            <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width: 22px; height: 22px;">
                                <i class="bi <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"
                                   style="font-size: 1.15rem; line-height: 1; color: <?php echo $isActive ? '#143326' : '#52635a'; ?>;"></i>
                            </span>
                            <span class="admin-rail-label d-none text-truncate">
                                <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

    </div>

    <!-- Bottom Admin Profile & Little Logout Logo Button -->
    <div class="p-2 p-lg-4 pt-1 pt-lg-2 mt-auto">
        <div class="d-flex flex-column align-items-center justify-content-center gap-2 rounded-4 border bg-white p-2"
             style="border-color: #e4e9e5 !important;">

            <!-- Admin Avatar Placeholder + Name/Role -->
            <div class="d-flex align-items-center gap-2 min-w-0">
                <img
                    src="<?php echo htmlspecialchars($adminAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8'); ?> Logo Placeholder"
                    class="rounded-circle object-fit-cover flex-shrink-0"
                    style="width: 36px; height: 36px;"
                >
                <div class="admin-rail-label d-none min-w-0">
                    <div class="fw-bold small text-truncate" style="color: #0e1f17;">
                        <?php echo htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div class="small text-truncate" style="color: #6b7c73; font-size: 0.78rem;">
                        <?php echo htmlspecialchars($adminRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </div>

            <!-- Little Logout Logo Button Beside Username When Expanded -->
            <a href="../logout.php"
               title="Log keluar"
               aria-label="Log keluar"
               class="admin-rail-logout btn btn-sm border rounded-3 d-none align-items-center justify-content-center flex-shrink-0 p-2"
               style="background-color: #f8faf9; border-color: #e4e9e5 !important; color: #26382f;">
                <i class="bi bi-box-arrow-right" style="font-size: 1rem; line-height: 1;"></i>
            </a>

        </div>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidenav = document.getElementById('adminSidenav');
    const backdrop = document.getElementById('adminSidenavBackdrop');
    const logoToggle = document.getElementById('adminLogoToggleBtn');
    if (!sidenav || !logoToggle) return;

    const labels = sidenav.querySelectorAll('.admin-rail-label');
    const navLinks = sidenav.querySelectorAll('nav .admin-nav-link');
    const bottomBox = sidenav.querySelector('.mt-auto > div');
    const logoutBtn = sidenav.querySelector('.admin-rail-logout');
    const brandRow = logoToggle.parentElement;
    let mobileFloating = false;
    let desktopExpanded = false;

    function syncActiveLinkHighlight() {
        const currentPathFile = (window.location.pathname.split('/').pop() || 'dashboard.php').toLowerCase();
        const currentHash = (window.location.hash || '').replace(/^#/, '').toLowerCase();

        let matchedLink = null;

        navLinks.forEach(function (link) {
            const targetFile = (link.getAttribute('data-target-file') || '').toLowerCase();
            const targetHash = (link.getAttribute('data-target-hash') || '').toLowerCase();

            if (currentHash && targetHash && currentHash === targetHash) {
                matchedLink = link;
            } else if (!matchedLink && targetFile && targetFile === currentPathFile && !targetHash) {
                matchedLink = link;
            }
        });

        if (!matchedLink) {
            navLinks.forEach(function (link) {
                if (!matchedLink && link.classList.contains('active')) {
                    matchedLink = link;
                }
            });
        }

        navLinks.forEach(function (link) {
            const isSelected = (link === matchedLink);
            const iconEl = link.querySelector('i');

            link.classList.toggle('active', isSelected);
            link.setAttribute('aria-current', isSelected ? 'page' : 'false');
            link.style.backgroundColor = isSelected ? '#eef2ef' : 'transparent';
            link.style.color = isSelected ? '#0e1f17' : '#1f3329';

            if (iconEl) {
                iconEl.style.color = isSelected ? '#143326' : '#52635a';
            }
        });
    }

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

    window.addEventListener('hashchange', syncActiveLinkHighlight);
    window.addEventListener('resize', applyState);

    syncActiveLinkHighlight();
    applyState();
});
</script>