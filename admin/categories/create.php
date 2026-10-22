<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Nama kategori wajib diisi!';
    } else {
        try {
            // Auto-generate Slug dari Nama Kategori
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $stmt = $pdo->prepare("
                INSERT INTO categories (name, slug, description, is_active)
                VALUES (:name, :slug, :desc, :active)
            ");
            $stmt->execute([
                'name'   => $name,
                'slug'   => $slug,
                'desc'   => $description,
                'active' => $is_active
            ]);

            header("Location: index.php?msg=created");
            exit;
        } catch (Exception $e) {
            $error = 'Gagal menambah kategori: ' . $e->getMessage();
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
                    <h3 class="fw-bold m-0 text-body">Tambah Kategori Baru</h3>
                    <small class="text-muted">Buat kelompok kategori baru untuk produk IDVISION.</small>
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

            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-0 p-4">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Nama Kategori</label>
                                <input type="text" name="name" class="form-control" placeholder="Contoh: Kacamata Hitam / Accessories" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Deskripsi Singkat</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Penjelasan singkat mengenai kategori ini..."></textarea>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCat" checked>
                                <label class="form-check-label fs-8 fw-semibold" for="isActiveCat">Aktifkan Kategori</label>
                            </div>

                            <button type="submit" class="btn btn-success fw-bold py-2 w-100">
                                <i class="bi bi-plus-circle me-1"></i> Simpan Kategori
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>