<?php
require_once '../../backend/config/database.php';

$pdo = getDbConnection();

// 1. TANGKAP PARAMETER PHONE
$activePhone = $_GET['phone'] ?? '';

// 2. LOGIKA PROSES BALAS CHAT ADMIN (HARUS DITARUH SEBELUM HEADER HTML RENDER)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $reply = trim($_POST['reply_message']);
    if (!empty($reply) && !empty($activePhone)) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO chats (sender_type, sender_name, customer_phone, message)
            VALUES ('admin', 'Admin IDVISION', :phone, :message)
        ");
        $stmtInsert->execute([
            'phone'   => $activePhone,
            'message' => $reply
        ]);

        // Redirect aman tanpa error "headers already sent"
        header("Location: index.php?phone=" . urlencode($activePhone));
        exit;
    }
}

// 3. BARU LOAD HEADER HTML
require_once '../includes/header.php';

// Ambil daftar percakapan unik berdasarkan nomor HP
$stmtList = $pdo->query("
    SELECT customer_phone, sender_name, MAX(created_at) as last_chat, 
           (SELECT message FROM chats c2 WHERE c2.customer_phone = c.customer_phone ORDER BY created_at DESC LIMIT 1) as last_message
    FROM chats c
    GROUP BY customer_phone, sender_name
    ORDER BY last_chat DESC
");
$conversations = $stmtList->fetchAll(PDO::FETCH_ASSOC);

if (empty($activePhone) && !empty($conversations)) {
    $activePhone = $conversations[0]['customer_phone'];
}

// Ambil pesan percakapan aktif
$activeMessages = [];
if ($activePhone) {
    $stmtMsg = $pdo->prepare("SELECT * FROM chats WHERE customer_phone = :phone ORDER BY created_at ASC");
    $stmtMsg->execute(['phone' => $activePhone]);
    $activeMessages = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h3 class="fw-bold mb-4 text-body">Live Chat Pelanggan</h3>

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="height: 600px;">
                <div class="row g-0 h-100">
                    <!-- KOLOM KIRI: DAFTAR CHAT PELANGGAN -->
                    <div class="col-md-4 border-end border-secondary border-opacity-10 h-100 overflow-y-auto">
                        <div class="p-3 border-bottom border-secondary border-opacity-10 bg-body-tertiary fw-bold fs-7">
                            Daftar Percakapan
                        </div>
                        <div class="list-group list-group-flush">
                            <?php if (empty($conversations)): ?>
                                <div class="p-4 text-center text-muted fs-8">Belum ada obrolan masuk.</div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <?php $isActive = ($conv['customer_phone'] === $activePhone); ?>
                                    <a href="index.php?phone=<?= urlencode($conv['customer_phone']) ?>" 
                                       class="list-group-item list-group-item-action p-3 border-0 border-bottom border-secondary border-opacity-10 <?= $isActive ? 'bg-primary-subtle' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="fs-7 text-body"><?= htmlspecialchars($conv['sender_name']) ?></strong>
                                            <small class="text-muted fs-8"><?= date('H:i', strtotime($conv['last_chat'])) ?></small>
                                        </div>
                                        <div class="text-muted fs-8 text-truncate"><?= htmlspecialchars($conv['last_message']) ?></div>
                                        <small class="text-success fs-8"><i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($conv['customer_phone']) ?></small>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: CHAT ACTIVE BOX -->
                    <div class="col-md-8 h-100 d-flex flex-column bg-body-tertiary">
                        <?php if ($activePhone): ?>
                            <!-- Chat Header -->
                            <div class="p-3 bg-body border-bottom border-secondary border-opacity-10 fw-bold fs-7 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-person-circle me-1 text-primary"></i> Chat dengan: <span class="text-success"><?= htmlspecialchars($activePhone) ?></span>
                                </div>
                            </div>

                            <!-- Chat Messages Body -->
                            <div class="flex-grow-1 p-3 overflow-y-auto d-flex flex-column gap-2">
                                <?php foreach ($activeMessages as $m): ?>
                                    <?php 
                                        $isCustomer = ($m['sender_type'] === 'customer');
                                        $isAi       = (strpos($m['sender_name'], 'AI') !== false);
                                    ?>
                                    <div class="d-flex <?= $isCustomer ? 'justify-content-start' : 'justify-content-end' ?>">
                                        <div class="p-2.5 rounded-3 shadow-xs" style="max-width: 75%; <?= $isCustomer ? 'background-color: var(--bs-card-bg); border: 1px solid rgba(255,255,255,0.08);' : ($isAi ? 'background-color: #1e1b4b; color: #a5b4fc; border: 1px solid #4338ca;' : 'background-color: #f59e0b; color: #000; font-weight: 500;') ?>">
                                            
                                            <!-- Pembeda Pengirim (Badge) -->
                                            <div class="d-flex align-items-center gap-1 mb-1 fs-8 opacity-75">
                                                <?php if ($isCustomer): ?>
                                                    <i class="bi bi-person-fill"></i>
                                                    <span><?= htmlspecialchars($m['sender_name']) ?></span>
                                                <?php elseif ($isAi): ?>
                                                    <span class="badge bg-indigo text-white px-1.5 py-0.5 rounded" style="background-color: #4f46e5;"><i class="bi bi-robot me-1"></i>AI Bot</span>
                                                <?php else: ?>
                                                    <span class="badge bg-dark text-warning px-1.5 py-0.5 rounded"><i class="bi bi-headset me-1"></i>Admin CS</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="fs-7"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                                            <div class="fs-8 text-end opacity-75 mt-1"><?= date('H:i', strtotime($m['created_at'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Chat Input Form -->
                            <form method="POST" class="p-3 bg-body border-top border-secondary border-opacity-10 d-flex gap-2">
                                <input type="text" name="reply_message" placeholder="Tulis balasan admin manusia di sini..." class="form-control form-control-sm" required autocomplete="off">
                                <button type="submit" class="btn btn-warning btn-sm px-4 fw-bold text-dark">
                                    <i class="bi bi-send-fill me-1"></i> Balas
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="m-auto text-center text-muted fs-7">
                                <i class="bi bi-chat-left-text fs-1 d-block mb-2"></i>
                                Pilih percakapan pelanggan di sebelah kiri untuk mulai membalas chat.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>