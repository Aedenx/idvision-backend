<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// 1. Ambil daftar semua kategori
$stmtCat = $pdo->query("SELECT category_id, name, is_active FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

// 2. Ambil Data Produk
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

// 3. Ambil Varian & Gambar
$stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = :id LIMIT 1");
$stmtVar->execute(['id' => $id]);
$variant = $stmtVar->fetch();

$stmtImg = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :id AND is_primary = 1 LIMIT 1");
$stmtImg->execute(['id' => $id]);
$image = $stmtImg->fetch();

// Handle Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $sku_base         = trim($_POST['sku_base'] ?? '');
    $base_price       = (float)($_POST['base_price'] ?? 0);
    $discount_percent = (int)($_POST['discount_percent'] ?? 0);
    $category_id      = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $description      = trim($_POST['description'] ?? '');
    $stock            = (int)($_POST['stock_quantity'] ?? 0);
    $color_name       = trim($_POST['color_name'] ?? 'Standard');
    $is_active        = isset($_POST['is_active']) ? 1 : 0;
    $is_featured      = isset($_POST['is_featured']) ? 1 : 0;

    // Hitung Harga Setelah Diskon Otomatis
    $discount_price = null;
    if ($discount_percent > 0) {
        $discount_price = $base_price - ($base_price * ($discount_percent / 100));
    } else {
        $discount_percent = 0;
    }

    if (empty($name) || empty($sku_base)) {
        $error = 'Nama Produk dan SKU Base wajib diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            // Query UPDATE ke tabel products
            $stmtUp = $pdo->prepare("
                UPDATE products 
                SET name = :name, 
                    slug = :slug, 
                    sku_base = :sku, 
                    description = :desc, 
                    category_id = :cat_id, 
                    base_price = :price, 
                    discount_percent = :disc_pct,
                    discount_price = :disc_price,
                    is_active = :active, 
                    is_featured = :featured
                WHERE product_id = :id
            ");

            $stmtUp->bindValue(':name', $name);
            $stmtUp->bindValue(':slug', $slug);
            $stmtUp->bindValue(':sku', $sku_base);
            $stmtUp->bindValue(':desc', $description);
            if ($category_id !== null) {
                $stmtUp->bindValue(':cat_id', $category_id, PDO::PARAM_INT);
            } else {
                $stmtUp->bindValue(':cat_id', null, PDO::PARAM_NULL);
            }
            $stmtUp->bindValue(':price', $base_price);
            $stmtUp->bindValue(':disc_pct', $discount_percent, PDO::PARAM_INT);
            if ($discount_price !== null) {
                $stmtUp->bindValue(':disc_price', $discount_price);
            } else {
                $stmtUp->bindValue(':disc_price', null, PDO::PARAM_NULL);
            }
            $stmtUp->bindValue(':active', $is_active, PDO::PARAM_INT);
            $stmtUp->bindValue(':featured', $is_featured, PDO::PARAM_INT);
            $stmtUp->bindValue(':id', $id, PDO::PARAM_INT);

            $stmtUp->execute();

            // Update / Insert Varian
            if ($variant) {
                $stmtVarUp = $pdo->prepare("
                    UPDATE product_variants 
                    SET price = :price, stock_quantity = :stock, color_name = :color, is_active = :active 
                    WHERE variant_id = :vid
                ");
                $stmtVarUp->execute([
                    'price'  => $base_price,
                    'stock'  => $stock,
                    'color'  => $color_name,
                    'active' => $is_active,
                    'vid'    => $variant['variant_id']
                ]);
            } else {
                $stmtVarIn = $pdo->prepare("
                    INSERT INTO product_variants (product_id, sku_variant, color_name, price, stock_quantity, is_active)
                    VALUES (:pid, :sku, :color, :price, :stock, :active)
                ");
                $stmtVarIn->execute([
                    'pid'    => $id,
                    'sku'    => $sku_base . '-VAR1',
                    'color'  => $color_name,
                    'price'  => $base_price,
                    'stock'  => $stock,
                    'active' => $is_active
                ]);
            }

            // Upload Gambar Utama Jika Ada
            if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../backend/uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExt  = strtolower(pathinfo($_FILES['primary_image']['name'], PATHINFO_EXTENSION));
                $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExt, $allowed)) {
                    $newFileName = 'idv_' . time() . '_' . uniqid() . '.' . $fileExt;
                    $targetPath  = $uploadDir . $newFileName;

                    if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $targetPath)) {
                        if ($image) {
                            $stmtImgUp = $pdo->prepare("UPDATE product_images SET image_path = :path WHERE image_id = :iid");
                            $stmtImgUp->execute(['path' => $newFileName, 'iid' => $image['image_id']]);
                        } else {
                            $stmtImgIn = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (:pid, :path, 1)");
                            $stmtImgIn->execute(['pid' => $id, 'path' => $newFileName]);
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: index.php?msg=updated");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menyimpan perubahan: ' . $e->getMessage();
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
                    <h3 class="fw-bold m-0 text-body">Edit Produk Kacamata</h3>
                    <small class="text-muted">Perbarui informasi detail, diskon, dan foto kacamata IDVISION.</small>
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
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold">SKU Base</label>
                                    <input type="text" name="sku_base" class="form-control" value="<?= htmlspecialchars($product['sku_base']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold">Kategori Produk</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <?php 
                                                $isSelected = ($product['category_id'] == $cat['category_id']) ? 'selected="selected"' : '';
                                            ?>
                                            <option value="<?= $cat['category_id'] ?>" <?= $isSelected ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Deskripsi & Spesifikasi Produk</label>
                                <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Kanan -->
                    <div class="col-lg-4">
                        <div class="card border-0 p-4 mb-4">
                            <h5 class="fw-bold text-body mb-3">Harga & Diskon</h5>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Harga Base (Rp)</label>
                                <input type="number" step="0.01" name="base_price" class="form-control" value="<?= $product['base_price'] ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Diskon Promo (%)</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="100" name="discount_percent" class="form-control fw-bold text-danger" value="<?= (int)($product['discount_percent'] ?? 0) ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted fs-8">Isi 0 jika tidak ada promo diskon.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Jumlah Stok Varian</label>
                                <input type="number" name="stock_quantity" class="form-control" value="<?= $variant['stock_quantity'] ?? 15 ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Nama Warna Frame/Lensa</label>
                                <input type="text" name="color_name" class="form-control" value="<?= htmlspecialchars($variant['color_name'] ?? 'Standard') ?>">
                            </div>
                        </div>

                        <div class="card border-0 p-4 mb-4">
                            <h5 class="fw-bold text-body mb-3">Foto & Opsi Publikasi</h5>

                            <?php if ($image && !empty($image['image_path'])): ?>
                                <div class="mb-3 text-center p-2 rounded border border-secondary border-opacity-10">
                                    <img src="../../backend/uploads/products/<?= htmlspecialchars($image['image_path']) ?>" class="img-fluid rounded" style="max-height: 140px; object-fit: contain;">
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Ganti Gambar (JPG, PNG, WEBP)</label>
                                <input type="file" name="primary_image" class="form-control" accept="image/*">
                            </div>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" <?= $product['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label fs-8 fw-semibold" for="isActiveSwitch">Aktifkan di Toko</label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="isFeaturedSwitch" <?= $product['is_featured'] ? 'checked' : '' ?>>
                                <label class="form-check-label fs-8 fw-semibold" for="isFeaturedSwitch">Tampilkan di Featured</label>
                            </div>

                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 mt-2">
                                <i class="bi bi-floppy me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>