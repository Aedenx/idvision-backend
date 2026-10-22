<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();
$error = '';

// Ambil semua kategori aktif untuk dropdown
$stmtCat = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $sku_base     = trim($_POST['sku_base'] ?? '');
    $base_price   = (float)($_POST['base_price'] ?? 0);
    $category_id  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description  = trim($_POST['description'] ?? '');
    $stock        = (int)($_POST['stock_quantity'] ?? 15);
    $color_name   = trim($_POST['color_name'] ?? 'Standard');
    $is_active    = isset($_POST['is_active']) ? 1 : 0;
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($name) || empty($sku_base)) {
        $error = 'Nama Produk dan SKU Base wajib diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            // Insert ke Tabel Products dengan category_id
            $stmt = $pdo->prepare("
                INSERT INTO products (name, slug, sku_base, description, category_id, base_price, is_active, is_featured)
                VALUES (:name, :slug, :sku, :desc, :cat_id, :price, :active, :featured)
            ");
            $stmt->execute([
                'name'     => $name,
                'slug'     => $slug,
                'sku'      => $sku_base,
                'desc'     => $description,
                'cat_id'   => $category_id,
                'price'    => $base_price,
                'active'   => $is_active,
                'featured' => $is_featured
            ]);

            $productId = $pdo->lastInsertId();

            // Insert Varian Default
            $stmtVar = $pdo->prepare("
                INSERT INTO product_variants (product_id, sku_variant, color_name, price, stock_quantity, is_active)
                VALUES (:pid, :sku, :color, :price, :stock, :active)
            ");
            $stmtVar->execute([
                'pid'    => $productId,
                'sku'    => $sku_base . '-VAR1',
                'color'  => $color_name,
                'price'  => $base_price,
                'stock'  => $stock,
                'active' => $is_active
            ]);

            // Upload Gambar Utama jika ada
            if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../backend/uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExt = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExt, $allowed)) {
                    $newFileName = 'idv_' . time() . '_' . uniqid() . '.' . $fileExt;
                    $targetPath  = $uploadDir . $newFileName;

                    if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $targetPath)) {
                        $stmtImg = $pdo->prepare("
                            INSERT INTO product_images (product_id, image_path, is_primary)
                            VALUES (:pid, :path, 1)
                        ");
                        $stmtImg->execute([
                            'pid'  => $productId,
                            'path' => $newFileName
                        ]);
                    }
                }
            }

            $pdo->commit();
            header("Location: index.php?msg=created");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan produk: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-body">Tambah Kacamata Baru</h3>
                    <small class="text-muted">Tambahkan kacamata baru ke dalam katalog IDVISION.</small>
                </div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- Form Kiri -->
                    <div class="col-lg-8">
                        <div class="card border-0 p-4">
                            <h5 class="fw-bold text-body mb-3">Informasi Utama</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Nama Produk Kacamata</label>
                                <input type="text" name="name" class="form-control" placeholder="Contoh: IDVISION Aviator Classic Gold" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold">SKU Base</label>
                                    <input type="text" name="sku_base" class="form-control" placeholder="Contoh: IDV-AVT-011" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold">Kategori Produk</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['category_id'] ?>">
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Deskripsi & Spesifikasi Produk</label>
                                <textarea name="description" class="form-control" rows="6" placeholder="Masukkan spesifikasi kacamata..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Kanan -->
                    <div class="col-lg-4">
                        <div class="card border-0 p-4 mb-4">
                            <h5 class="fw-bold text-body mb-3">Harga & Stok</h5>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Harga Base (Rp)</label>
                                <input type="number" step="0.01" name="base_price" class="form-control" placeholder="450000" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Jumlah Stok Varian</label>
                                <input type="number" name="stock_quantity" class="form-control" value="15" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Nama Warna Frame/Lensa</label>
                                <input type="text" name="color_name" class="form-control" value="Standard">
                            </div>
                        </div>

                        <div class="card border-0 p-4 mb-4">
                            <h5 class="fw-bold text-body mb-3">Foto Produk Utama</h5>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Unggah Gambar (JPG, PNG, WEBP)</label>
                                <input type="file" name="primary_image" class="form-control" accept="image/*">
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" checked>
                                <label class="form-check-label fs-8 fw-semibold" for="isActiveSwitch">Aktifkan di Toko</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedSwitch">
                                <label class="form-check-label fs-8 fw-semibold" for="isFeaturedSwitch">Tampilkan di Featured</label>
                            </div>

                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Simpan Produk Baru
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>