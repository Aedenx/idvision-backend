<?php

function restockOrderItems($pdo, $internalOrderId) {
    if (!$internalOrderId) return;

    // 1. Ambil item dalam pesanan beserta variant_id (jika ada)
    $stmtItems = $pdo->prepare("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = :order_id");
    $stmtItems->execute(['order_id' => $internalOrderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) return;

    // 2. Cek apakah ada tabel product_variants atau variants di database
    $stmtTables = $pdo->query("SHOW TABLES LIKE '%variant%'");
    $variantTable = $stmtTables->fetchColumn(); // Dapatkan nama tabel varian jika ada (misal: product_variants)

    if ($variantTable) {
        // Ambil nama kolom stok dan primary key di tabel varian
        $stmtCols = $pdo->query("SHOW COLUMNS FROM {$variantTable}");
        $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

        $stockCol = in_array('stock', $cols) ? 'stock' : (in_array('stok', $cols) ? 'stok' : (in_array('qty', $cols) ? 'qty' : null));
        $variantPkCol = in_array('variant_id', $cols) ? 'variant_id' : (in_array('id', $cols) ? 'id' : null);

        if ($stockCol) {
            foreach ($items as $item) {
                // Jika order_items menyimpan variant_id, update berdasarkan variant_id
                if (!empty($item['variant_id']) && $variantPkCol) {
                    $stmtRestock = $pdo->prepare("
                        UPDATE {$variantTable} 
                        SET {$stockCol} = {$stockCol} + :qty 
                        WHERE {$variantPkCol} = :variant_id
                    ");
                    $stmtRestock->execute([
                        'qty'        => $item['quantity'],
                        'variant_id' => $item['variant_id']
                    ]);
                } else {
                    // Fallback update stok varian berdasarkan product_id
                    $stmtRestock = $pdo->prepare("
                        UPDATE {$variantTable} 
                        SET {$stockCol} = {$stockCol} + :qty 
                        WHERE product_id = :product_id
                    ");
                    $stmtRestock->execute([
                        'qty'        => $item['quantity'],
                        'product_id' => $item['product_id']
                    ]);
                }
            }
        }
    }
}