<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Nama kategori wajib diisi!';
    } else {
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $stmtUp = $pdo->prepare("
                UPDATE categories 
                SET name = :name, slug = :slug, description = :desc, is_active = :active
                WHERE category_id = :id
            ");
            $stmtUp->execute([
                'name'   => $name,
                'slug'   => $slug,
                'desc'   => $description,
                'active' => $is_active,
                'id'     => $id
            ]);

            header("Location: index.php?msg=updated");
            exit;
        } catch (Exception $e) {
            $error = 'Gagal merubah kategori: ' . $e->getMessage();
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
                    <h3 class="fw-bold m-0 text-body">Edit Kategori</h3>
                    <small class="text-muted">Perbarui nama atau deskripsi kategori produk.</small>
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
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-8 fw-semibold">Deskripsi Singkat</label>
                                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCat" <?= $category['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label fs-8 fw-semibold" for="isActiveCat">Aktifkan Kategori</label>
                            </div>

                            <button type="submit" class="btn btn-success fw-bold py-2 w-100">
                                <i class="bi bi-floppy me-1"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>