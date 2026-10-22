<?php
require_once '../../backend/config/database.php';
require_once '../includes/header.php';

$pdo = getDbConnection();

// Parameter filter status dari URL (Default: all)
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Query data pesanan
$sql = "
    SELECT 
        o.order_id,
        o.order_number,
        o.customer_name,
        o.customer_phone,
        o.customer_email,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at,
        COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
";

if ($filterStatus !== 'all') {
    $sql .= " WHERE o.order_status = :status ";
}

$sql .= "
    GROUP BY 
        o.order_id, o.order_number, o.customer_name, o.customer_phone, 
        o.customer_email, o.total_amount, o.payment_method, o.payment_status, 
        o.order_status, o.created_at
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
if ($filterStatus !== 'all') {
    $stmt->execute(['status' => $filterStatus]);
} else {
    $stmt->execute();
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik pesanan
$stmtCount = $pdo->query("
    SELECT 
        COUNT(CASE WHEN order_status = 'pending' THEN 1 END) AS count_pending,
        COUNT(CASE WHEN order_status = 'processing' THEN 1 END) AS count_processing,
        COUNT(CASE WHEN order_status = 'shipped' THEN 1 END) AS count_shipped,
        COUNT(CASE WHEN order_status = 'completed' THEN 1 END) AS count_completed
    FROM orders
");
$counts = $stmtCount->fetch(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <!-- HEADER HALAMAN -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
                <div>
                    <h3 class="fw-bold m-0 text-body">Daftar Pesanan Masuk</h3>
                    <p class="text-muted fs-8 mb-0 mt-0.5">Kelola transaksi dan pemrosesan pengiriman kacamata IDVISION.</p>
                </div>
                <?php if (($counts['count_pending'] ?? 0) > 0): ?>
                    <div class="badge-pending-alert d-flex align-items-center gap-2 px-3 py-2 rounded-3">
                        <span class="status-dot-pulse"></span>
                        <span class="fw-semibold text-warning fs-8"><?= $counts['count_pending'] ?> Pesanan Perlu Diproses</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SEGMENTED CONTROL TABS (FILAMENT STYLE) -->
            <div class="filament-tabs-container mb-4">
                <a href="index.php?status=all" class="filament-tab-item <?= $filterStatus === 'all' ? 'active' : '' ?>">
                    Semua
                </a>
                <a href="index.php?status=pending" class="filament-tab-item <?= $filterStatus === 'pending' ? 'active' : '' ?>">
                    <span>Baru Masuk</span>
                    <?php if (($counts['count_pending'] ?? 0) > 0): ?>
                        <span class="count-pill badge-warning"><?= $counts['count_pending'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?status=processing" class="filament-tab-item <?= $filterStatus === 'processing' ? 'active' : '' ?>">
                    <span>Dikemas</span>
                    <span class="count-pill"><?= $counts['count_processing'] ?? 0 ?></span>
                </a>
                <a href="index.php?status=shipped" class="filament-tab-item <?= $filterStatus === 'shipped' ? 'active' : '' ?>">
                    <span>Dikirim</span>
                    <span class="count-pill"><?= $counts['count_shipped'] ?? 0 ?></span>
                </a>
                <a href="index.php?status=completed" class="filament-tab-item <?= $filterStatus === 'completed' ? 'active' : '' ?>">
                    <span>Selesai</span>
                    <span class="count-pill"><?= $counts['count_completed'] ?? 0 ?></span>
                </a>
            </div>

            <!-- CARD TABLE CONTAINER -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 custom-filament-table">
                        <thead>
                            <tr>
                                <th class="ps-4">No. Invoice</th>
                                <th>Tanggal</th>
                                <th>Pembeli</th>
                                <th>Metode Bayar</th>
                                <th>Total Tagihan</th>
                                <th>Status Pesanan</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                                        Tidak ada data pesanan pada kategori ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php 
                                        $buyerName  = !empty($o['customer_name']) ? $o['customer_name'] : 'Pembeli Guest';
                                        $buyerPhone = !empty($o['customer_phone']) ? $o['customer_phone'] : '-';
                                        $status     = strtolower($o['order_status'] ?? 'pending');
                                        $payMethod  = strtoupper($o['payment_method'] ?? 'MIDTRANS');
                                        $payStatus  = strtolower($o['payment_status'] ?? 'pending');
                                        $isNew      = ($status === 'pending');
                                    ?>
                                    <tr class="<?= $isNew ? 'row-highlight-new' : '' ?>">
                                        <!-- NO INVOICE -->
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($isNew): ?>
                                                    <span class="status-dot-pulse" title="Pesanan Baru"></span>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="detail.php?id=<?= $o['order_id'] ?>" class="fw-bold font-monospace text-primary text-decoration-none fs-7">
                                                        <?= htmlspecialchars($o['order_number']) ?>
                                                    </a>
                                                    <div class="text-muted fs-8 mt-0.5"><?= $o['total_items'] ?> Barang</div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- TANGGAL -->
                                        <td class="fs-8 text-muted">
                                            <div class="fw-semibold text-body"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                                            <div><?= date('H:i', strtotime($o['created_at'])) ?> WIB</div>
                                        </td>

                                        <!-- PEMBELI -->
                                        <td>
                                            <div class="fw-semibold text-body fs-7"><?= htmlspecialchars($buyerName) ?></div>
                                            <div class="text-muted fs-8"><i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($buyerPhone) ?></div>
                                        </td>

                                        <!-- METODE BAYAR -->
                                        <td>
                                            <span class="badge-pill-subtle <?= $payMethod === 'COD' ? 'bg-secondary-subtle text-body' : 'bg-info-subtle text-info' ?>">
                                                <?= $payMethod ?>
                                            </span>
                                            <div class="fs-8 mt-1 <?= $payStatus === 'paid' ? 'text-success fw-semibold' : 'text-warning' ?>">
                                                ● <?= ucfirst($payStatus) ?>
                                            </div>
                                        </td>

                                        <!-- TOTAL -->
                                        <td class="fw-bold fs-7 text-body">
                                            Rp <?= number_format($o['total_amount'], 0, ',', '.') ?>
                                        </td>

                                        <!-- STATUS PESANAN -->
                                        <td>
                                            <?php if ($status === 'pending'): ?>
                                                <span class="badge-pill-status bg-warning-subtle text-warning">
                                                    <i class="bi bi-clock-history me-1"></i> Menunggu Process
                                                </span>
                                            <?php elseif ($status === 'processing'): ?>
                                                <span class="badge-pill-status bg-info-subtle text-info">
                                                    <i class="bi bi-box-seam me-1"></i> Diproses
                                                </span>
                                            <?php elseif ($status === 'shipped'): ?>
                                                <span class="badge-pill-status bg-primary-subtle text-primary">
                                                    <i class="bi bi-truck me-1"></i> Dikirim
                                                </span>
                                            <?php elseif ($status === 'completed'): ?>
                                                <span class="badge-pill-status bg-success-subtle text-success">
                                                    <i class="bi bi-check-circle me-1"></i> Selesai
                                                </span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge-pill-status bg-danger-subtle text-danger">
                                                    <i class="bi bi-x-circle me-1"></i> Batal
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-status bg-secondary-subtle text-secondary"><?= ucfirst($status) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- AKSI -->
                                        <td class="text-end pe-4">
                                            <a href="detail.php?id=<?= $o['order_id'] ?>" class="btn-action-filament <?= $isNew ? 'btn-action-amber' : 'btn-action-ghost' ?>">
                                                <?= $isNew ? 'Proses' : 'Kelola' ?>
                                                <i class="bi bi-chevron-right fs-8"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* CSS Filament Styling Clean & Seamless */
.badge-pending-alert {
    background-color: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.status-dot-pulse {
    width: 8px;
    height: 8px;
    background-color: #f59e0b;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
    animation: pulseDot 1.6s infinite;
}

@keyframes pulseDot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

/* Filament Segmented Tabs */
.filament-tabs-container {
    display: inline-flex;
    background-color: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--filament-border-dark, #1f2937);
    padding: 3px;
    border-radius: 0.65rem;
    gap: 2px;
}

.filament-tab-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #94a3b8;
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.filament-tab-item:hover {
    color: #f8fafc;
    background-color: rgba(255, 255, 255, 0.05);
}

.filament-tab-item.active {
    background-color: #1f2937;
    color: #fbbf24;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.count-pill {
    background-color: rgba(255, 255, 255, 0.1);
    font-size: 0.7rem;
    padding: 1px 6px;
    border-radius: 10px;
    color: #cbd5e1;
}

.count-pill.badge-warning {
    background-color: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

/* Table Styling */
.custom-filament-table thead th {
    background-color: #0b0f19;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid var(--filament-border-dark, #1f2937);
}

.custom-filament-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.row-highlight-new {
    background-color: rgba(245, 158, 11, 0.03) !important;
}

/* Status & Subtles */
.badge-pill-subtle {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

.badge-pill-status {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
}

/* Action Buttons */
.btn-action-filament {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 0.45rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.btn-action-amber {
    background-color: #f59e0b;
    color: #000000;
}

.btn-action-amber:hover {
    background-color: #d97706;
    color: #ffffff;
}

.btn-action-ghost {
    background-color: rgba(255, 255, 255, 0.05);
    color: #cbd5e1;
    border: 1px solid var(--filament-border-dark, #1f2937);
}

.btn-action-ghost:hover {
    background-color: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
}
</style>

<?php require_once '../includes/footer.php'; ?>