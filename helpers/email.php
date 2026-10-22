<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendEmailNotification($recipientEmail, $recipientName, $order_number, $total_amount, $payment_method) {
    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP Gmail / Email Server
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'idvisionstore@gmail.com'; // Email pengirim
        $mail->Password   = 'muhammadikhwan';       // App Password Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Pengirim & Penerima
        $mail->setFrom('emailtokoanda@gmail.com', 'IDVISION Store');
        $mail->addAddress($recipientEmail, $recipientName);

        // Konten Email
        $formattedTotal = "Rp " . number_format($total_amount, 0, ',', '.');
        $trackLink = "http://localhost:3000/checkout/success/" . $order_number;

        $mail->isHTML(true);
        $mail->Subject = "Konfirmasi Pesanan IDVISION - {$order_number}";
        $mail->Body    = "
            <h2>Halo {$recipientName},</h2>
            <p>Terima kasih telah berbelanja di IDVISION! Pesanan Anda telah kami terima.</p>
            <hr>
            <p><strong>No. Invoice:</strong> {$order_number}</p>
            <p><strong>Metode Pembayaran:</strong> {$payment_method}</p>
            <p><strong>Total Pembayaran:</strong> {$formattedTotal}</p>
            <hr>
            <p>Klik link di bawah ini untuk melihat status pesanan Anda:</p>
            <p><a href='{$trackLink}' style='background:#10b981;color:#fff;padding:10px 15px;text-decoration:none;border-radius:8px;'>Lacak Pesanan Saya</a></p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}