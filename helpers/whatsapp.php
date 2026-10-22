<?php
function sendWhatsAppNotification($target, $order_number, $customer_name, $total_amount, $payment_method, $items = []) {
    // Token Fonnte aktif kamu
    $token = "MuJ8qodMxvny83zeiHm3";

    $formattedTotal = "Rp " . number_format($total_amount, 0, ',', '.');
    
    // Tautan pelacakan publik/lokal
    $trackLink = "http://localhost:3000/checkout/success/" . $order_number;

    // 1. Susun Rincian Item Barang
    $itemListText = "";
    if (!empty($items)) {
        foreach ($items as $item) {
            $pName = $item['name'] ?? 'Kacamata';
            $qty   = $item['quantity'] ?? 1;
            $price = "Rp " . number_format($item['price'] ?? 0, 0, ',', '.');
            $itemListText .= "• {$pName} ({$qty}x) - {$price}\n";
        }
    }

    // 2. Format Pesan WhatsApp Rapi & Emoji
    $message = "Halo *{$customer_name}*,\n\n";
    $message .= "Terima kasih telah berbelanja di *IDVISION*! 🕶️\n";
    $message .= "Pesanan Anda telah berhasil kami catat di sistem.\n\n";
    $message .= "📋 *DETAIL PESANAN:*\n";
    $message .= "• *No. Invoice:* {$order_number}\n";
    $message .= "• *Metode Bayar:* " . strtoupper($payment_method) . "\n";
    $message .= "• *Total Bayar:* {$formattedTotal}\n\n";

    if (!empty($itemListText)) {
        $message .= "🛍️ *RINGKASAN BARANG:*\n";
        $message .= $itemListText . "\n";
    }

    $message .= "🚚 *LACAK STATUS PENGIRIMAN:*\n";
    $message .= "Klik tautan berikut untuk memantau status pesanan Anda:\n";
    $message .= "{$trackLink}\n\n";
    $message .= "_Pesan ini dikirimkan otomatis oleh sistem IDVISION._";

    $curlData = array(
        'target'      => $target,
        'message'     => $message,
        'countryCode' => '62',
    );

    // Kirim tombol URL Fonnte jika bukan localhost / sudah domain http(s) valid
    if (filter_var($trackLink, FILTER_VALIDATE_URL) && !str_contains($trackLink, 'localhost')) {
        $curlData['url'] = "Lacak Pesanan|" . $trackLink;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $curlData,
        CURLOPT_HTTPHEADER => array(
            "Authorization: $token"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}