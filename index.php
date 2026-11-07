<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// Fetch active vendors
$stmt = $pdo->query("SELECT * FROM vendors WHERE is_active = 1 ORDER BY store_name ASC");
$vendors = $stmt->fetchAll();
?>

<div class="p-5 mb-4 bg-white rounded-3 shadow-sm border text-center">
    <div class="container-fluid py-3">
        <h1 class="display-5 fw-bold text-dark">Delicious Food, Direct to Your WhatsApp</h1>
        <p class="col-md-8 fs-4 mx-auto text-muted">Choose your favorite vendor, browse their mouth-watering menu, and order instantly via WhatsApp without registration hassle!</p>
    </div>
</div>

<h2 class="mb-4 fw-bold text-secondary"><i class="fa-solid fa-store me-2 text-warning"></i>Available Vendors</h2>

<?php if (empty($vendors)): ?>
    <div class="alert alert-info text-center py-4">
        <h4>No vendors available right now.</h4>
        <p class="mb-0">Please check back later or contact the administrator.</p>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($vendors as $vendor): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="uploads/vendors/<?= htmlspecialchars($vendor['logo'] ?: 'default_logo.png') ?>" class="card-img-top object-fit-cover" height="200" alt="<?= htmlspecialchars($vendor['store_name']) ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($vendor['store_name']) ?></h5>
                            <?php if ($vendor['status'] === 'open'): ?>
                                <span class="badge bg-success">Open</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Closed</span>
                            <?php endif; ?>
                        </div>
                        <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars(substr($vendor['description'], 0, 100)) ?>...</p>
                        <p class="card-text mb-2"><small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($vendor['address']) ?></small></p>
                        <?php if ($vendor['status'] === 'open'): ?>
                            <a href="store.php?id=<?= $vendor['id'] ?>" class="btn btn-dark w-100 mt-auto"><i class="fa-solid fa-utensils me-1"></i> View Menu & Order</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 mt-auto" disabled>Currently Closed</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
