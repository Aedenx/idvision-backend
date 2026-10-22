<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']);
    $cta_link   = trim($_POST['cta_link']);
    $sort_order = (int)$_POST['sort_order'];
    $is_active  = isset($_POST['is_active']) ? 1 : 0;
    $image_url  = '';

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['image_file']['tmp_name'];
        $fileName      = $_FILES['image_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts   = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
        
        if (in_array($fileExtension, $allowedExts)) {
            $uploadDir = '../../backend/uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $isHeic      = ($fileExtension === 'heic' || $fileExtension === 'heif');
            $newExt      = $isHeic ? 'jpg' : $fileExtension;
            $newFileName = time() . '_' . uniqid() . '.' . $newExt;
            $destPath    = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_url = 'http://localhost/idvision/backend/uploads/banners/' . $newFileName;
            } else {
                $error = 'Gagal memindahkan file gambar ke folder backend/uploads/banners/';
            }
        } else {
            $error = 'Format file tidak didukung!';
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_url = trim($_POST['image_url']);
    } else {
        $error = 'Harap pilih file gambar atau isi URL gambar!';
    }

    if (empty($error) && !empty($title)) {
        $stmt = $pdo->prepare("
            INSERT INTO banners (title, image, cta_link, sort_order, is_active)
            VALUES (:title, :image, :cta_link, :sort_order, :is_active)
        ");
        $stmt->execute([
            'title'      => $title,
            'image'      => $image_url,
            'cta_link'   => $cta_link,
            'sort_order' => $sort_order,
            'is_active'  => $is_active,
        ]);

        header("Location: index.php?msg=created");
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Tambah Banner Promo</h2>
                <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm p-4">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Nama / Judul Banner (Internal)</label>
                            <input type="text" name="title" class="form-control" placeholder="Contoh: Banner Promo Summit Series 2026" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Urutan Tampil (Sort Order)</label>
                            <input type="number" name="sort_order" class="form-control" value="1" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Upload File Gambar Banner</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Atau Gunakan URL Gambar</label>
                            <input type="text" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Link Path Tujuan Saat Banner Di-klik</label>
                            <input type="text" name="cta_link" class="form-control" placeholder="Contoh: /products/kacamata-polarized-summit atau /promo" required>
                            <small class="text-muted d-block mt-1">
                                Masukkan path halaman tujuan, misal: <code>/products/kacamata-polarized-black</code>, <code>/products?category=summit</code>, atau <code>/promo</code>.
                            </small>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label fw-bold" for="isActive">Aktifkan Banner Ini</label>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <button type="submit" class="btn btn-success fw-bold px-4">Simpan Banner</button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>