<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// 1. PROSES HAPUS BANNER DITARUH SEBELUM HEADER HTML RENDER
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM banners WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: index.php?msg=deleted");
    exit;
}

// 2. BARU LOAD HEADER HTML
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC");
$banners = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Kelola Banner Hero</h2>
                <a href="create.php" class="btn btn-success fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Banner
                </a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'created'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Banner berhasil ditambahkan!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Banner berhasil diperbarui!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php elseif ($_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success alert-dismissible fade show">Banner berhasil dihapus!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 50px;">Urutan</th>
                                    <th style="width: 120px;">Gambar</th>
                                    <th>Judul & Subjudul</th>
                                    <th>CTA Link</th>
                                    <th>Status</th>
                                    <th style="width: 150px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($banners)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada banner. Silakan tambah banner baru.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($banners as $b): ?>
                                        <tr>
                                            <td class="fw-bold text-center"><?= $b['sort_order'] ?></td>
                                            <td>
                                                <img src="<?= htmlspecialchars($b['image']) ?>" alt="Banner" class="rounded object-fit-cover" width="100" height="50">
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($b['subtitle'] ?? '') ?></small>
                                            </td>
                                            <td><code><?= htmlspecialchars($b['cta_link'] ?? '-') ?></code></td>
                                            <td>
                                                <?php if ($b['is_active']): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border">Non-Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="edit.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <a href="index.php?action=delete&id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus banner ini?')">
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