<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// 1. Proses Hapus Produk (Wajib ditaruh SEBELUM header.php di-require)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->beginTransaction();

        // Ambil varian ID untuk membersihkan keranjang & wishlist
        $stmtVars = $pdo->prepare("SELECT variant_id FROM product_variants WHERE product_id = :id");
        $stmtVars->execute(['id' => $id]);
        $variantIds = $stmtVars->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($variantIds)) {
            $inClause = implode(',', array_map('intval', $variantIds));
            $pdo->exec("DELETE FROM cart_items WHERE variant_id IN ($inClause)");
            $pdo->exec("DELETE FROM wishlists WHERE variant_id IN ($inClause)");
        }

        // Hapus produk utama (product_variants & product_images terhapus otomatis via CASCADE)
        $pdo->prepare("DELETE FROM products WHERE product_id = :id")->execute(['id' => $id]);

        $pdo->commit();
        header("Location: index.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMsg = 'Gagal menghapus produk: ' . $e->getMessage();
    }
}

// 2. Sertakan Header HTML SETELAH logika redirect selesai
require_once '../includes/header.php';

$stmt = $pdo->query("
    SELECT 
        p.*, 
        c.name AS category_name,
        MIN(pv.price) AS variant_price, 
        COALESCE(SUM(pv.stock_quantity), 0) AS total_stock, 
        MAX(pi.image_path) AS image_path
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
    GROUP BY 
        p.product_id, 
        p.name, 
        p.slug, 
        p.sku_base, 
        p.description, 
        p.category_id, 
        p.base_price, 
        p.discount_percent,
        p.discount_price,
        p.is_active, 
        p.is_featured, 
        p.created_at, 
        p.updated_at,
        c.name
    ORDER BY p.product_id DESC
");
$products = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-body">Kelola Produk Kacamata</h3>
                    <small class="text-muted">Katalog lengkap produk kacamata IDVISION.</small>
                </div>
                <a href="create.php" class="btn btn-success fw-bold btn-sm py-2 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kacamata Baru
                </a>
            </div>

            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
                    <?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'created'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Produk berhasil ditambahkan!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Produk berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Produk berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-dark fs-8">
                                <tr>
                                    <th style="width: 70px;">Gambar</th>
                                    <th>SKU Base</th>
                                    <th>Nama Kacamata</th>
                                    <th>Kategori</th>
                                    <th>Harga / Promo</th>
                                    <th>Total Stok</th>
                                    <th>Status</th>
                                    <th style="width: 150px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada produk kacamata.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($p['image_path'])): ?>
                                                    <img src="../../backend/uploads/products/<?= htmlspecialchars($p['image_path']) ?>" alt="Kacamata" class="rounded border object-fit-cover" width="50" height="38">
                                                <?php else: ?>
                                                    <div class="bg-secondary-subtle rounded border text-center text-muted fs-8 py-2" style="width: 50px;">No Img</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><code class="fw-semibold text-body-emphasis"><?= htmlspecialchars($p['sku_base']) ?></code></td>
                                            <td>
                                                <div class="fw-bold text-body"><?= htmlspecialchars($p['name']) ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['category_name'])): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <?= htmlspecialchars($p['category_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                        Tanpa Kategori
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($p['discount_percent']) && $p['discount_percent'] > 0): ?>
                                                    <div>
                                                        <span class="text-decoration-line-through text-muted fs-8">Rp <?= number_format($p['base_price'], 0, ',', '.') ?></span>
                                                        <span class="badge bg-danger ms-1">-<?= $p['discount_percent'] ?>%</span>
                                                    </div>
                                                    <div class="fw-bold text-success">Rp <?= number_format($p['discount_price'], 0, ',', '.') ?></div>
                                                <?php else: ?>
                                                    <span class="fw-bold text-success">Rp <?= number_format($p['base_price'], 0, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($p['total_stock'] > 10): ?>
                                                    <span class="badge bg-success-subtle text-success"><?= $p['total_stock'] ?> pcs</span>
                                                <?php elseif ($p['total_stock'] > 0): ?>
                                                    <span class="badge bg-warning-subtle text-warning"><?= $p['total_stock'] ?> pcs</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger">Stok Habis</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($p['is_active']): ?>
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="edit.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="index.php?action=delete&id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>