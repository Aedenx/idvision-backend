<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';

$pdo = getDbConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['customer_phone']) || empty($data['message'])) {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
    exit;
}

$phone      = trim($data['customer_phone']);
$senderName = trim($data['sender_name'] ?? 'Pelanggan');
$message    = trim($data['message']);
$senderType = trim($data['sender_type'] ?? 'customer');

try {
    // 1. Ambil STATUS TERBARU secara murni dari baris chat terakhir pada nomor ini
    $stmtCheck = $pdo->prepare("
        SELECT session_status 
        FROM chats 
        WHERE customer_phone = :phone 
        ORDER BY created_at DESC, chat_id DESC 
        LIMIT 1
    ");
    $stmtCheck->execute(['phone' => $phone]);
    $lastRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // Default status jika belum ada percakapan adalah ai_active
    $currentStatus = $lastRow['session_status'] ?? 'ai_active';

    // Jika percakapan sebelumnya sudah diakhiri (ended), pesan baru dari customer akan mereset status kembali ke AI
    if ($currentStatus === 'ended') {
        $currentStatus = 'ai_active';
    }

    // 2. Simpan Pesan Masuk dari Customer dengan status yang berlaku saat ini
    $stmt = $pdo->prepare("
        INSERT INTO chats (sender_type, sender_name, customer_phone, message, session_status) 
        VALUES (:sender_type, :sender_name, :phone, :message, :status)
    ");
    $stmt->execute([
        'sender_type' => $senderType,
        'sender_name' => $senderName,
        'phone'       => $phone,
        'message'     => $message,
        'status'      => $currentStatus
    ]);

    // 3. JIKA STATUS ADALAH 'ai_active', AI BOT AKAN MEMBALAS
    if ($currentStatus === 'ai_active') {
        
        $aiReply = "Terima kasih telah menghubungi IDVISION! Pertanyaan Anda tentang '" . $message . "' telah diterima. Ada yang bisa kami bantu lagi?";

        if (stristr($message, 'admin') !== false || stristr($message, 'bantuan') !== false) {
            $aiReply = "Baik, pesan Anda telah diteruskan ke Admin CS kami. Mohon tunggu sebentar ya!";
        }

        // Simpan Balasan AI
        $stmtAi = $pdo->prepare("
            INSERT INTO chats (sender_type, sender_name, customer_phone, message, session_status) 
            VALUES ('ai', 'AI Assistant', :phone, :message, 'ai_active')
        ");
        $stmtAi->execute([
            'phone'   => $phone,
            'message' => $aiReply
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Pesan berhasil dikirim"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}