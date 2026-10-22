<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();
$message = '';

// Handle Form Submission untuk Update Diskon Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $product_id       = (int)$_POST['product_id'];
    $discount_percent = (int)$_POST['discount_percent'];

    // Ambil harga dasar produk
    $stmtP = $pdo->prepare("SELECT base_price FROM products WHERE product_id = :id");
    $stmtP->execute(['id' => $product_id]);
    $prod = $stmtP->fetch();

    if ($prod) {
        $basePrice = (float)$prod['base_price'];
        
        // Hitung harga diskon jika ada persentase
        if ($discount_percent > 0) {
            $discountPrice = $basePrice - ($basePrice * ($discount_percent / 100));
        } else {
            $discount_percent = 0;
            $discountPrice = null;
        }

        // Update database
        $stmtUp = $pdo->prepare("
            UPDATE products 
            SET discount_percent = :disc_pct, 
                discount_price = :disc_price 
            WHERE product_id = :id
        ");
        $stmtUp->execute([
            'disc_pct'   => $discount_percent,
            'disc_price' => $discountPrice,
            'id'         => $product_id
        ]);

        $message = 'Diskon produk berhasil diperbarui!';
    }
}

// Ambil semua produk beserta informasi diskonnya
$stmt = $pdo->query("
    SELECT p.product_id, p.name, p.sku_base, p.base_price, p.discount_percent, p.discount_price, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.product_id DESC
");
$products = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-body">Kelola Diskon & Promo Produk</h3>
                    <small class="text-muted">Atur persentase potongan harga untuk katalog kacamata IDVISION.</small>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-dark fs-8">
                                <tr>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga Normal</th>
                                    <th>Diskon (%)</th>
                                    <th>Harga Setelah Diskon</th>
                                    <th class="text-center" style="width: 220px;">Aksi Simpan</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                            <small class="text-muted">SKU: <?= htmlspecialchars($p['sku_base']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($p['category_name'] ?? 'Unisex') ?></span></td>
                                        <td class="fw-semibold">Rp <?= number_format($p['base_price'], 0, ',', '.') ?></td>
                                        
                                        <form method="POST" action="">
                                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                            <input type="hidden" name="update_discount" value="1">
                                            
                                            <td>
                                                <div class="input-group input-group-sm" style="width: 110px;">
                                                    <input type="number" name="discount_percent" min="0" max="100" class="form-control fw-bold text-danger" value="<?= (int)($p['discount_percent'] ?? 0) ?>">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['discount_percent']) && $p['discount_percent'] > 0): ?>
                                                    <span class="fw-bold text-success">Rp <?= number_format($p['discount_price'], 0, ',', '.') ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="submit" class="btn btn-sm btn-outline-success fw-bold px-3">
                                                    <i class="bi bi-check-lg me-1"></i> Simpan Diskon
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>