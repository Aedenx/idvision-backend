<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// 1. Logika Hapus Kategori (Ditaruh SEBELUM require_once header.php)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = :id");
        $stmt->execute(['id' => $id]);

        header("Location: index.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $errorMsg = 'Gagal menghapus kategori: ' . $e->getMessage();
    }
}

// 2. Sertakan Header HTML SETELAH redirect aman
require_once '../includes/header.php';

// Ambil Data Kategori
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_id DESC");
$categories = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-body">Kategori Produk</h3>
                    <small class="text-muted">Kelola pengelompokan produk kacamata IDVISION.</small>
                </div>
                <a href="create.php" class="btn btn-success fw-bold btn-sm py-2 px-3">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </a>
            </div>

            <?php if (isset($errorMsg)): ?>
                <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show"><?= htmlspecialchars($errorMsg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'created'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Kategori berhasil ditambahkan!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Kategori berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">Kategori berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-dark fs-8">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Nama Kategori</th>
                                    <th>Slug</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th style="width: 150px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fs-7">
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td class="text-muted fw-semibold">#<?= $cat['category_id'] ?></td>
                                            <td>
                                                <div class="fw-bold text-body"><?= htmlspecialchars($cat['name']) ?></div>
                                            </td>
                                            <td><code class="fw-semibold text-body-emphasis"><?= htmlspecialchars($cat['slug']) ?></code></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($cat['is_active']): ?>
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="edit.php?id=<?= $cat['category_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="index.php?action=delete&id=<?= $cat['category_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
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