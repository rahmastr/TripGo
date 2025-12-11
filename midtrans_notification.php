<?php
require_once 'config.php';
require_once 'midtrans/Midtrans.php';

// Set konfigurasi Midtrans
Midtrans::$serverKey = MIDTRANS_SERVER_KEY;
Midtrans::$isProduction = MIDTRANS_IS_PRODUCTION;

// Fungsi log untuk debugging
function logNotification($message) {
    $logFile = __DIR__ . '/logs/midtrans_notification.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

try {
    // Ambil data notifikasi dari Midtrans
    $json = file_get_contents('php://input');
    logNotification("=".str_repeat("=", 50));
    logNotification("NEW NOTIFICATION RECEIVED");
    logNotification("Raw JSON: " . $json);
    
    // Decode untuk debugging
    $rawData = json_decode($json, true);
    logNotification("Decoded Data: " . print_r($rawData, true));
    
    $notification = Midtrans::handleNotification($json);
    
    $orderId = $notification['order_id'];
    $transactionStatus = $notification['transaction_status'];
    $fraudStatus = $notification['fraud_status'] ?? 'accept';
    $paymentType = $notification['payment_type'] ?? '';
    $grossAmount = $notification['gross_amount'];
    
    logNotification("Parsed - Order ID: $orderId");
    logNotification("Parsed - Transaction Status: $transactionStatus");
    logNotification("Parsed - Fraud Status: $fraudStatus");
    logNotification("Parsed - Payment Type: $paymentType");
    logNotification("Parsed - Gross Amount: $grossAmount");
    
    // Cari booking berdasarkan order_id
    $stmt = $conn->prepare("SELECT * FROM pemesanan WHERE midtrans_order_id = ?");
    $stmt->execute([$orderId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        logNotification("Booking not found for order: $orderId");
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit;
    }
    
    logNotification("Current booking status: {$booking['status_pembayaran']}");
    
    // Tentukan status pembayaran
    $newStatus = $booking['status_pembayaran'];
    
    logNotification("Evaluating transaction status: $transactionStatus");
    
    if ($transactionStatus == 'capture') {
        logNotification("Status is 'capture', checking fraud status: $fraudStatus");
        if ($fraudStatus == 'accept') {
            $newStatus = 'success';
            logNotification("Fraud status accepted, setting to SUCCESS");
        } else {
            logNotification("Fraud status NOT accepted, keeping as pending");
        }
    } else if ($transactionStatus == 'settlement') {
        $newStatus = 'success';
        logNotification("Status is 'settlement', setting to SUCCESS");
    } else if ($transactionStatus == 'pending') {
        $newStatus = 'pending';
        logNotification("Status is 'pending', keeping as PENDING");
    } else if ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
        $newStatus = 'failed';
        logNotification("Status is '$transactionStatus', setting to FAILED");
    } else {
        logNotification("WARNING: Unknown transaction status: $transactionStatus");
    }
    
    logNotification("New status will be: $newStatus");
    
    // Update status booking
    if ($newStatus !== $booking['status_pembayaran']) {
        logNotification("Status changed from '{$booking['status_pembayaran']}' to '$newStatus', updating database...");
        
        $stmt = $conn->prepare("
            UPDATE pemesanan 
            SET status_pembayaran = ?, 
                midtrans_payment_type = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$newStatus, $paymentType, $booking['id']]);
        
        if ($result) {
            logNotification("✓ Successfully updated booking #{$booking['id']} status to: $newStatus");
        } else {
            logNotification("✗ Failed to update booking #{$booking['id']}");
        }
        
        // Jika pembayaran berhasil, perbarui is_offline menjadi 0
        if ($newStatus === 'success') {
            $stmt = $conn->prepare("UPDATE pemesanan SET is_offline = 0 WHERE id = ?");
            $stmt->execute([$booking['id']]);
            logNotification("✓ Payment confirmed, set is_offline = 0 for booking #{$booking['id']}");
        }
        
        // Jika pembayaran gagal, lepaskan kursi yang dipesan
        if ($newStatus === 'failed') {
            $stmt = $conn->prepare("DELETE FROM kursi_terpesan WHERE booking_id = ?");
            $stmt->execute([$booking['id']]);
            logNotification("✓ Released seats for failed booking #{$booking['id']}");
        }
    } else {
        logNotification("No status change needed (already '$newStatus')");
    }
    
    // Kirim respons sukses ke Midtrans
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Notification processed']);
    logNotification("✓✓✓ Notification processed successfully ✓✓✓");
    logNotification("=".str_repeat("=", 50));
    logNotification("");
    
} catch (Exception $e) {
    logNotification("✗✗✗ ERROR OCCURRED ✗✗✗");
    logNotification("Error Message: " . $e->getMessage());
    logNotification("Error Trace: " . $e->getTraceAsString());
    logNotification("=".str_repeat("=", 50));
    logNotification("");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
