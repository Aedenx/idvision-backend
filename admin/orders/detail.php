<?php
require_once '../../backend/config/database.php';
require_once '../includes/header.php';

$pdo = getDbConnection();
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// PROSES UPDATE STATUS & RESI OLEH ADMIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $newOrderStatus   = trim($_POST['order_status'] ?? '');
    $newPaymentStatus = trim($_POST['payment_status'] ?? '');
    $trackingNumber   = trim($_POST['tracking_number'] ?? '');
    $shippingNotes    = trim($_POST['shipping_notes'] ?? '');

    try {
        $stmtUpdate = $pdo->prepare("
            UPDATE orders 
            SET order_status = :order_status,
                payment_status = :payment_status,
                tracking_number = :tracking_number,
                shipping_notes = :shipping_notes,
                updated_at = NOW()
            WHERE order_id = :order_id
        ");
        $stmtUpdate->execute([
            'order_status'    => $newOrderStatus,
            'payment_status'  => $newPaymentStatus,
            'tracking_number' => $trackingNumber,
            'shipping_notes'  => $shippingNotes,
            'order_id'        => $order_id
        ]);

        $message = "Status dan informasi pengiriman pesanan berhasil diperbarui!";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Gagal memperbarui data: " . $e->getMessage();
        $messageType = "danger";
    }
}

// AMBIL DATA PESANAN UTAMA
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$stmt->execute(['order_id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<div class='container py-5'><div class='alert alert-danger'>Pesanan tidak ditemukan.</div></div>";
    require_once '../includes/footer.php';
    exit;
}

// AMBIL DAFTAR ITEM BARANG
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmtItems->execute(['order_id' => $order_id]);
$orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary mb-2">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Pesanan
                    </a>
                    <h2 class="fw-bold mb-0">Kelola Pesanan: <span class="text-primary font-monospace"><?= htmlspecialchars($order['order_number']) ?></span></h2>
                </div>
                <div>
                    <span class="badge bg-dark fs-6 font-monospace py-2 px-3">
                        Tgl: <?= date('d M Y H:i', strtotime($order['created_at'])) ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- KOLOM KIRI: UPDATE STATUS & RESI -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-dark text-white fw-bold py-3">
                            <i class="bi bi-sliders me-2"></i> Kontrol Status & Pengiriman
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="update_order" value="1">

                                <!-- STATUS PESANAN -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status Pesanan / Pengiriman</label>
                                    <select name="order_status" class="form-select font-semibold">
                                        <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Menunggu / Pending</option>
                                        <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Diproses / Dikemas</option>
                                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Dalam Pengiriman / Dikirim</option>
                                        <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>Selesai / Diterima Pembeli</option>
                                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                    </select>
                                </div>

                                <!-- STATUS PEMBAYARAN -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        Status Pembayaran 
                                        <span class="badge bg-secondary ms-1"><?= strtoupper($order['payment_method']) ?></span>
                                    </label>
                                    <select name="payment_status" class="form-select font-semibold">
                                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending (Belum Lunas)</option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid (Lunas)</option>
                                        <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed / Expired</option>
                                    </select>
                                    <?php if ($order['payment_method'] === 'cod'): ?>
                                        <small class="text-muted d-block mt-1">Ubah jadi <strong>Paid</strong> jika uang COD sudah disetorkan oleh kurir.</small>
                                    <?php endif; ?>
                                </div>

                                <hr className="my-3">

                                <!-- NOMOR RESI PENGIRIMAN -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nomor Resi Ekspedisi</label>
                                    <input type="text" name="tracking_number" class="form-control font-monospace" 
                                           placeholder="Contoh: JNE1234567890 / SICEPAT..." 
                                           value="<?= htmlspecialchars($order['tracking_number'] ?? '') ?>">
                                </div>

                                <!-- CATATAN KENDALA PENGIRIMAN -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-danger">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Catatan Kendala Pengiriman (Opsional)
                                    </label>
                                    <textarea name="shipping_notes" class="form-control" rows="3" 
                                              placeholder="Isi jika ada masalah (misal: Rumah kosong, Alamat tidak ditemukan, dll)..."><?= htmlspecialchars($order['shipping_notes'] ?? '') ?></textarea>
                                    <small class="text-muted">Catatan ini akan langsung terlihat oleh pembeli saat melacak pesanan.</small>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan Status
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: DETAIL RINCIAN PEMBELI & BARANG -->
                <div class="col-lg-7 space-y-4">
                    <!-- DATA PEMBELI -->
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-light fw-bold py-3">
                            <i class="bi bi-person-lines-fill me-2"></i> Informasi Pembeli & Alamat
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 text-sm">
                                <div class="col-md-6">
                                    <span class="text-muted d-block">Nama Lengkap:</span>
                                    <strong class="fs-6 text-dark"><?= htmlspecialchars($order['customer_name']) ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted d-block">Nomor WhatsApp:</span>
                                    <strong class="text-success"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($order['customer_phone']) ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted d-block">Email:</span>
                                    <strong><?= htmlspecialchars($order['customer_email'] ?: '-') ?></strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted d-block">Metode Bayar:</span>
                                    <strong class="uppercase"><?= htmlspecialchars($order['payment_method']) ?></strong>
                                </div>
                                <div class="col-12 pt-2 border-top">
                                    <span class="text-muted d-block mb-1">Alamat Pengiriman Lengkap:</span>
                                    <div class="p-3 bg-light rounded border text-dark font-mono">
                                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RINCIAN BARANG -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-light fw-bold py-3">
                            <i class="bi bi-bag-check-fill me-2"></i> Item Barang Yang Dibeli
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Nama Produk</th>
                                            <th class="text-center">Harga</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end pe-3">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                                                <td class="text-center">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                                <td class="text-center"><span class="badge bg-secondary"><?= $item['quantity'] ?></span></td>
                                                <td class="text-end pe-3 fw-bold">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light border-top">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold ps-3">Total Pembayaran:</td>
                                            <td class="text-end pe-3 fw-black text-success fs-6">
                                                Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>