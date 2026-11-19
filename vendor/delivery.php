<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch vendor record
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$user_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    echo '<div class="alert alert-danger">Vendor profile not found. Please contact admin.</div>';
    exit;
}

$vendor_id = $vendor['id'];
$specialLink = "http://" . $_SERVER['HTTP_HOST'] . "/simple_ordering_multivendor/index.php?token=" . $vendor['token'];

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = 'Tetapan penghantaran berjaya disimpan!';
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Settings — Panel Peniaga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #0b291a;
            --accent-lime: #b2ff81;
            --bg-cream: #f3f4ee;
        }
        body {
            background-color: var(--bg-cream);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #1a1a1a;
        }
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Styling */
        .dash-sidebar {
            width: 260px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar-brand-area {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .sidebar-menu {
            padding: 1rem;
            list-style: none;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1rem;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }
        .sidebar-menu li a:hover {
            background-color: #f8fafc;
            color: #0b291a;
        }
        .sidebar-menu li a.active {
            background-color: #eaf3ed;
            color: #0b291a;
        }
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #f1f5f9;
        }
        .vendor-profile-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        /* Main Area */
        .dash-main {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            width: calc(100% - 260px);
        }
        .dash-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dash-content-body {
            padding: 2rem 2.5rem;
            width: 100%;
        }
        /* Delivery Card Layout */
        .delivery-settings-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .zone-card-item {
            background: #fcfcfc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .zone-number-badge {
            width: 28px;
            height: 28px;
            background-color: #e2ebe3;
            color: #0b291a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .area-tag {
            background-color: #eaf3ed;
            color: #0b291a;
            border: 1px solid #d1e7dd;
            border-radius: 50rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .suggestion-pill {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            color: #475569;
            border-radius: 50rem;
            padding: 0.3rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-tambah-zon {
            background-color: #ffffff;
            color: #0b291a;
            border: 1px solid #cbd5e1;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .btn-tambah-zon:hover {
            background: #f1f5f9;
        }
        .btn-lihat-kedai {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0b291a;
            border-radius: 0.5rem;
            padding: 0.4rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .btn-lihat-kedai:hover {
            background: #f1f5f9;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    
    <!-- Sidenav Inclusion -->
    <?php include 'sidenav.php'; ?>

    <!-- Main Content -->
    <div class="dash-main">
        <!-- Topbar -->
        <div class="dash-topbar">
            <div>
                <span class="text-muted small">Panel peniaga</span>
                <h5 class="fw-bold mb-0 text-dark">Delivery Settings</h5>
            </div>
            <div>
                <a href="<?= htmlspecialchars($specialLink) ?>" target="_blank" class="btn-lihat-kedai">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i> Lihat kedai
                </a>
            </div>
        </div>

        <!-- Content Body -->
        <div class="dash-content-body">
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2 small mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                
                <!-- Card 1: Penghantaran Utama -->
                <div class="delivery-settings-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Penghantaran</h5>
                            <p class="text-muted small mb-0">Pelanggan boleh memilih Penghantaran atau COD apabila ini diaktifkan.</p>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input" type="checkbox" role="switch" checked name="enable_delivery">
                        </div>
                    </div>

                    <hr class="my-4 text-muted opacity-25">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Penghantaran percuma</h6>
                            <p class="text-muted small mb-0">Percuma apabila jumlah pesanan melebihi amaun ini.</p>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input" type="checkbox" role="switch" checked name="enable_free_delivery">
                        </div>
                    </div>

                    <div class="mb-2" style="max-width: 320px;">
                        <label class="form-label small fw-bold text-muted">Percuma untuk pesanan melebihi (RM)</label>
                        <input type="number" class="form-control" name="free_delivery_threshold" value="50">
                    </div>
                </div>

                <!-- Card 2: Zon Penghantaran -->
                <div class="delivery-settings-card">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Zon penghantaran</h5>
                            <p class="text-muted small mb-0">Tetapkan kawasan, caj dan minimum pesanan untuk setiap zon. Tambah seberapa banyak zon yang perlu.</p>
                        </div>
                        <div>
                            <button type="button" class="btn-tambah-zon" onclick="addNewZone()">
                                <i class="fa-solid fa-plus"></i> Tambah zon
                            </button>
                        </div>
                    </div>

                    <div id="zonesContainer">
                        <!-- ZON 1 -->
                        <div class="zone-card-item">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="zone-number-badge">1</div>
                                    <span class="fw-bold text-dark small">Nama zon</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-semibold text-success">Aktif</span>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" checked>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm text-danger border-0 bg-transparent p-0" onclick="this.closest('.zone-card-item').remove(); renumberZones();" title="Padam Zon">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="zones[1][name]" value="Zon 1 – Kawasan berdekatan">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Caj penghantaran (RM)</label>
                                    <input type="number" class="form-control zone-fee-input" name="zones[1][fee]" value="3" oninput="updateZoneSummary(this)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Minimum pesanan (RM)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control flex-grow-1 zone-min-input" name="zones[1][min]" value="10" oninput="updateZoneSummary(this)">
                                        <div class="form-check form-switch flex-shrink-0 text-nowrap">
                                            <input class="form-check-input" type="checkbox" role="switch" id="luarLiputan1">
                                            <label class="form-check-label small text-muted ms-1" for="luarLiputan1">Luar liputan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Kawasan diliputi</label>
                                <div class="p-2 bg-white border rounded-3 d-flex flex-wrap align-items-center gap-2 mb-2 area-tags-wrapper">
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Padungan <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Pending <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Jalan Song <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control area-text-input" placeholder="Taip nama kawasan...">
                                    <button class="btn btn-dark btn-sm px-3" type="button" style="background-color: #0b291a;" onclick="addAreaTag(this)">Tambah</button>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="text-muted small">Cadangan:</span>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pusat bandar')">+ Pusat bandar</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Taman perumahan berdekatan')">+ Taman perumahan berdekatan</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Kawasan industri')">+ Kawasan industri</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pinggir bandar')">+ Pinggir bandar</a>
                                </div>
                            </div>

                            <div class="text-muted small pt-2 border-top zone-summary-text">
                                Caj RM 3.00 &bull; minimum pesanan RM 10.00
                            </div>
                        </div>

                        <!-- ZON 2 -->
                        <div class="zone-card-item">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="zone-number-badge">2</div>
                                    <span class="fw-bold text-dark small">Nama zon</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-semibold text-success">Aktif</span>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" checked>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm text-danger border-0 bg-transparent p-0" onclick="this.closest('.zone-card-item').remove(); renumberZones();" title="Padam Zon">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="zones[2][name]" value="Zon 2 – Jarak sederhana">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Caj penghantaran (RM)</label>
                                    <input type="number" class="form-control zone-fee-input" name="zones[2][fee]" value="5" oninput="updateZoneSummary(this)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Minimum pesanan (RM)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control flex-grow-1 zone-min-input" name="zones[2][min]" value="15" oninput="updateZoneSummary(this)">
                                        <div class="form-check form-switch flex-shrink-0 text-nowrap">
                                            <input class="form-check-input" type="checkbox" role="switch" id="luarLiputan2">
                                            <label class="form-check-label small text-muted ms-1" for="luarLiputan2">Luar liputan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Kawasan diliputi</label>
                                <div class="p-2 bg-white border rounded-3 d-flex flex-wrap align-items-center gap-2 mb-2 area-tags-wrapper">
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Tabuan Jaya <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Stutong <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> BDC <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control area-text-input" placeholder="Taip nama kawasan...">
                                    <button class="btn btn-dark btn-sm px-3" type="button" style="background-color: #0b291a;" onclick="addAreaTag(this)">Tambah</button>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="text-muted small">Cadangan:</span>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pusat bandar')">+ Pusat bandar</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Taman perumahan berdekatan')">+ Taman perumahan berdekatan</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Kawasan industri')">+ Kawasan industri</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pinggir bandar')">+ Pinggir bandar</a>
                                </div>
                            </div>

                            <div class="text-muted small pt-2 border-top zone-summary-text">
                                Caj RM 5.00 &bull; minimum pesanan RM 15.00
                            </div>
                        </div>

                        <!-- ZON 3 -->
                        <div class="zone-card-item">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="zone-number-badge">3</div>
                                    <span class="fw-bold text-dark small">Nama zon</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-semibold text-success">Aktif</span>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" checked>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm text-danger border-0 bg-transparent p-0" onclick="this.closest('.zone-card-item').remove(); renumberZones();" title="Padam Zon">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="zones[3][name]" value="Zon 3 – Kawasan Jauh">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Caj penghantaran (RM)</label>
                                    <input type="number" class="form-control zone-fee-input" name="zones[3][fee]" value="8" oninput="updateZoneSummary(this)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Minimum pesanan (RM)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control flex-grow-1 zone-min-input" name="zones[3][min]" value="25" oninput="updateZoneSummary(this)">
                                        <div class="form-check form-switch flex-shrink-0 text-nowrap">
                                            <input class="form-check-input" type="checkbox" role="switch" id="luarLiputan3">
                                            <label class="form-check-label small text-muted ms-1" for="luarLiputan3">Luar liputan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Kawasan diliputi</label>
                                <div class="p-2 bg-white border rounded-3 d-flex flex-wrap align-items-center gap-2 mb-2 area-tags-wrapper">
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Kota Samarahan <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Petra Jaya <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Batu Kawa <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control area-text-input" placeholder="Taip nama kawasan...">
                                    <button class="btn btn-dark btn-sm px-3" type="button" style="background-color: #0b291a;" onclick="addAreaTag(this)">Tambah</button>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="text-muted small">Cadangan:</span>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pusat bandar')">+ Pusat bandar</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Taman perumahan berdekatan')">+ Taman perumahan berdekatan</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Kawasan industri')">+ Kawasan industri</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pinggir bandar')">+ Pinggir bandar</a>
                                </div>
                            </div>

                            <div class="text-muted small pt-2 border-top zone-summary-text">
                                Caj RM 8.00 &bull; minimum pesanan RM 25.00
                            </div>
                        </div>

                        <!-- ZON 4 -->
                        <div class="zone-card-item">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="zone-number-badge">4</div>
                                    <span class="fw-bold text-dark small">Nama zon</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="small fw-semibold text-success">Aktif</span>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch" checked>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm text-danger border-0 bg-transparent p-0" onclick="this.closest('.zone-card-item').remove(); renumberZones();" title="Padam Zon">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" name="zones[4][name]" value="Zon 4 – Luar liputan">
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Caj penghantaran (RM)</label>
                                    <input type="number" class="form-control zone-fee-input" name="zones[4][fee]" value="0" oninput="updateZoneSummary(this)" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Minimum pesanan (RM)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control flex-grow-1 zone-min-input" name="zones[4][min]" value="0" oninput="updateZoneSummary(this)" disabled>
                                        <div class="form-check form-switch flex-shrink-0 text-nowrap">
                                            <input class="form-check-input" type="checkbox" role="switch" id="luarLiputan4" checked onchange="toggleOutOfCoverage(this)">
                                            <label class="form-check-label small text-muted ms-1" for="luarLiputan4">Luar liputan</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Kawasan diliputi</label>
                                <div class="p-2 bg-white border rounded-3 d-flex flex-wrap align-items-center gap-2 mb-2 area-tags-wrapper">
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Serian <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Bau <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                    <span class="area-tag"><i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> Lundu <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i></span>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control area-text-input" placeholder="Taip nama kawasan...">
                                    <button class="btn btn-dark btn-sm px-3" type="button" style="background-color: #0b291a;" onclick="addAreaTag(this)">Tambah</button>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="text-muted small">Cadangan:</span>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pusat bandar')">+ Pusat bandar</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Taman perumahan berdekatan')">+ Taman perumahan berdekatan</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Kawasan industri')">+ Kawasan industri</a>
                                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pinggir bandar')">+ Pinggir bandar</a>
                                </div>
                            </div>

                            <div class="text-muted small pt-2 border-top zone-summary-text">
                                Luar liputan penghantaran (Kurier / Ambil sendiri sahaja)
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-dark fw-bold px-4 py-2" style="background-color: #0b291a; border-radius: 50rem;">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>

            <div class="text-muted text-center small pt-3">
                Prototip: data dan PIN disimpan dalam localStorage pelayar ini dan tidak selamat untuk produksi.
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addAreaTag(btn) {
    const zoneCard = btn.closest('.zone-card-item');
    const input = zoneCard.querySelector('.area-text-input');
    const val = input.value.trim();
    if (!val) return;
    appendTagElement(zoneCard, val);
    input.value = '';
}

function addSuggestionTag(link, areaName) {
    const zoneCard = link.closest('.zone-card-item');
    appendTagElement(zoneCard, areaName);
}

function appendTagElement(zoneCard, text) {
    const wrapper = zoneCard.querySelector('.area-tags-wrapper');
    const tag = document.createElement('span');
    tag.className = 'area-tag';
    tag.innerHTML = `<i class="fa-solid fa-location-dot" style="font-size: 0.7rem;"></i> ${text} <i class="fa-solid fa-xmark ms-1" style="cursor: pointer;" onclick="this.parentElement.remove()"></i>`;
    wrapper.appendChild(tag);
}

function updateZoneSummary(input) {
    const zoneCard = input.closest('.zone-card-item');
    const fee = parseFloat(zoneCard.querySelector('.zone-fee-input').value || 0).toFixed(2);
    const min = parseFloat(zoneCard.querySelector('.zone-min-input').value || 0).toFixed(2);
    zoneCard.querySelector('.zone-summary-text').innerHTML = `Caj RM ${fee} &bull; minimum pesanan RM ${min}`;
}

function toggleOutOfCoverage(checkbox) {
    const zoneCard = checkbox.closest('.zone-card-item');
    const feeInput = zoneCard.querySelector('.zone-fee-input');
    const minInput = zoneCard.querySelector('.zone-min-input');
    const summaryText = zoneCard.querySelector('.zone-summary-text');

    if (checkbox.checked) {
        feeInput.disabled = true;
        minInput.disabled = true;
        summaryText.innerHTML = 'Luar liputan penghantaran (Kurier / Ambil sendiri sahaja)';
    } else {
        feeInput.disabled = false;
        minInput.disabled = false;
        updateZoneSummary(feeInput);
    }
}

function renumberZones() {
    document.querySelectorAll('#zonesContainer .zone-card-item').forEach((card, index) => {
        card.querySelector('.zone-number-badge').textContent = index + 1;
    });
}

function addNewZone() {
    const container = document.getElementById('zonesContainer');
    const nextNum = container.children.length + 1;
    const html = `
        <div class="zone-card-item">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="zone-number-badge">${nextNum}</div>
                    <span class="fw-bold text-dark small">Nama zon</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-semibold text-success">Aktif</span>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" checked>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm text-danger border-0 bg-transparent p-0" onclick="this.closest('.zone-card-item').remove(); renumberZones();" title="Padam Zon">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="zones[${nextNum}][name]" value="Zon ${nextNum} – Kawasan baharu">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Caj penghantaran (RM)</label>
                    <input type="number" class="form-control zone-fee-input" name="zones[${nextNum}][fee]" value="5" oninput="updateZoneSummary(this)">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Minimum pesanan (RM)</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" class="form-control flex-grow-1 zone-min-input" name="zones[${nextNum}][min]" value="15" oninput="updateZoneSummary(this)">
                        <div class="form-check form-switch flex-shrink-0 text-nowrap">
                            <input class="form-check-input" type="checkbox" role="switch" id="luarLiputan${nextNum}" onchange="toggleOutOfCoverage(this)">
                            <label class="form-check-label small text-muted ms-1" for="luarLiputan${nextNum}">Luar liputan</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Kawasan diliputi</label>
                <div class="p-2 bg-white border rounded-3 d-flex flex-wrap align-items-center gap-2 mb-2 area-tags-wrapper"></div>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control area-text-input" placeholder="Taip nama kawasan...">
                    <button class="btn btn-dark btn-sm px-3" type="button" style="background-color: #0b291a;" onclick="addAreaTag(this)">Tambah</button>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small">Cadangan:</span>
                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pusat bandar')">+ Pusat bandar</a>
                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Taman perumahan berdekatan')">+ Taman perumahan berdekatan</a>
                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Kawasan industri')">+ Kawasan industri</a>
                    <a href="javascript:void(0)" class="suggestion-pill" onclick="addSuggestionTag(this, 'Pinggir bandar')">+ Pinggir bandar</a>
                </div>
            </div>
            <div class="text-muted small pt-2 border-top zone-summary-text">
                Caj RM 5.00 &bull; minimum pesanan RM 15.00
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}
</script>
</body>
</html>