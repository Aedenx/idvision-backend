<?php
require_once '../backend/config/database.php';
require_once 'includes/header.php';

$pdo = getDbConnection();

// 1. Hitung Total Omzet (Hanya pesanan yang sudah lunas/paid)
$stmtOmzet = $pdo->query("SELECT SUM(total_amount) AS total FROM orders WHERE payment_status = 'paid'");
$totalOmzet = $stmtOmzet->fetch()['total'] ?? 0;

// 2. Hitung Total Pesanan Masuk
$stmtOrders = $pdo->query("SELECT COUNT(order_id) AS total FROM orders");
$totalOrders = $stmtOrders->fetch()['total'] ?? 0;

// 3. Hitung Total Produk Kacamata Aktif
$stmtProducts = $pdo->query("SELECT COUNT(product_id) AS total FROM products WHERE is_active = 1");
$totalProducts = $stmtProducts->fetch()['total'] ?? 0;

// 4. Hitung Total Member / Pelanggan
$stmtMembers = $pdo->query("SELECT COUNT(user_id) AS total FROM users WHERE role = 'customer'");
$totalMembers = $stmtMembers->fetch()['total'] ?? 0;

// 5. Ambil 5 Pesanan Terbaru
$stmtRecent = $pdo->query("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();

// Ambil Produk Stok Kritis (Stok <= 5)
$stmtLowStock = $pdo->query("
    SELECT p.name, pv.stock_quantity, pv.color_name
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.product_id
    WHERE pv.stock_quantity <= 5
    ORDER BY pv.stock_quantity ASC
    LIMIT 5
");
$lowStockItems = $stmtLowStock->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <!-- Welcome Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold m-0 text-body">Dashboard Overview</h3>
                    <small class="text-muted">Ringkasan performa dan transaksi toko IDVISION hari ini.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="products/create.php" class="btn btn-success btn-sm fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
                    </a>
                </div>
            </div>

            <!-- Widget Summary Cards (Filament Style) -->
            <div class="row g-3 mb-4">
                <!-- Card 1: Omzet -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-8 fw-semibold text-uppercase">Total Pendapatan</span>
                            <div class="p-2 rounded-3 bg-warning-subtle text-warning">
                                <i class="bi bi-wallet2 fs-5"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-success">Rp <?= number_format($totalOmzet, 0, ',', '.') ?></h4>
                        <small class="text-muted fs-8"><i class="bi bi-check-circle-fill text-success me-1"></i>Dari transaksi lunas</small>
                    </div>
                </div>

                <!-- Card 2: Orders -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-8 fw-semibold text-uppercase">Total Pesanan</span>
                            <div class="p-2 rounded-3 bg-info-subtle text-info">
                                <i class="bi bi-bag-check fs-5"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-body"><?= number_format($totalOrders) ?></h4>
                        <small class="text-muted fs-8">Pesanan telah tercatat</small>
                    </div>
                </div>

                <!-- Card 3: Products -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-8 fw-semibold text-uppercase">Produk Kacamata</span>
                            <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                                <i class="bi bi-eyeglasses fs-5"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-body"><?= number_format($totalProducts) ?></h4>
                        <small class="text-muted fs-8">Katalog aktif di toko</small>
                    </div>
                </div>

                <!-- Card 4: Members -->
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fs-8 fw-semibold text-uppercase">Total Member</span>
                            <div class="p-2 rounded-3 bg-success-subtle text-success">
                                <i class="bi bi-people fs-5"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-body"><?= number_format($totalMembers) ?></h4>
                        <small class="text-muted fs-8">Pelanggan terdaftar</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Orders Table -->
                <div class="col-lg-8">
                    <div class="card border-0 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0 text-body">Pesanan Terbaru</h5>
                            <a href="orders/index.php" class="btn btn-link btn-sm text-decoration-none text-warning fw-semibold p-0">
                                Lihat Semua <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-dark fs-8">
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Pembeli</th>
                                        <th>Total</th>
                                        <th>Status Order</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-7">
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi masuk.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $ro): ?>
                                            <tr>
                                                <td><code class="fw-semibold"><?= htmlspecialchars($ro['order_number']) ?></code></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($ro['shipping_recipient_name']) ?></td>
                                                <td class="fw-bold text-success">Rp <?= number_format($ro['total_amount'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($ro['order_status'] === 'pending'): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                                    <?php elseif ($ro['order_status'] === 'completed'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Selesai</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle"><?= ucfirst($ro['order_status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <a href="orders/detail.php?id=<?= $ro['order_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        Detail
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

                <!-- Low Stock Alert Box -->
                <div class="col-lg-4">
                    <div class="card border-0 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold m-0 text-body">Stok Menipis</h5>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Peringatan</span>
                        </div>

                        <?php if (empty($lowStockItems)): ?>
                            <div class="text-center py-4 text-muted fs-7">
                                <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                Semua stok kacamata aman.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($lowStockItems as $item): ?>
                                    <div class="p-2.5 rounded border border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold fs-7 text-body"><?= htmlspecialchars($item['name']) ?></div>
                                            <small class="text-muted fs-8"><?= htmlspecialchars($item['color_name'] ?? 'Standard') ?></small>
                                        </div>
                                        <span class="badge bg-danger text-white fw-bold">Sisa <?= $item['stock_quantity'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>