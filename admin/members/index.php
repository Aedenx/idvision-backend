<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// Handling Toggle Status (Aktif / Nonaktifkan Member)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Cek status saat ini
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE user_id = :id AND role = 'customer'");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = $user['is_active'] ? 0 : 1;
        $updateStmt = $pdo->prepare("UPDATE users SET is_active = :status WHERE user_id = :id");
        $updateStmt->execute(['status' => $newStatus, 'id' => $userId]);
        
        header("Location: index.php?msg=updated");
        exit;
    }
}

// Search Filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE role = 'customer' 
          AND (full_name LIKE :s OR email LIKE :s OR phone LIKE :s)
        ORDER BY created_at DESC
    ");
    $stmt->execute(['s' => "%{$search}%"]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE role = 'customer' 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
}

$members = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold m-0">Kelola Member / Pelanggan</h2>
                    <p class="text-muted small m-0">Daftar pengguna yang terdaftar di platform IDVISION</p>
                </div>
                <span class="badge bg-primary px-3 py-2 fs-6">
                    Total: <?= count($members) ?> Member
                </span>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Status member berhasil diperbarui!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- BAR PENCARIAN -->
            <div class="card border-0 shadow-sm p-3 mb-4">
                <form action="" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau nomor HP member..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-dark font-weight-bold">Cari Member</button>
                    </div>
                </form>
            </div>

            <!-- TABEL MEMBER -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#ID</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>No. WhatsApp/HP</th>
                                <th>Tgl Terdaftar</th>
                                <th>Status Akun</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($members) > 0): ?>
                                <?php foreach ($members as $m): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $m['user_id'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['full_name']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($m['email']) ?></td>
                                        <td>
                                            <?php if (!empty($m['phone'])): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $m['phone']) ?>" target="_blank" class="text-decoration-none text-success fw-bold">
                                                    <?= htmlspecialchars($m['phone']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($m['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="index.php?action=toggle&id=<?= $m['user_id'] ?>" 
                                               onclick="return confirm('Apakah Anda yakin ingin mengubah status akun member ini?');"
                                               class="btn btn-sm <?= $m['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> fw-bold">
                                                <?= $m['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Tidak ada member/pelanggan yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>