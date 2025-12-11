<?php
require_once 'config.php';
require_once 'midtrans/Midtrans.php';

// Set konfigurasi Midtrans
Midtrans::$serverKey = MIDTRANS_SERVER_KEY;
Midtrans::$isProduction = MIDTRANS_IS_PRODUCTION;

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit();
}

// Validasi parameter
if (!isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order ID required']);
    exit();
}

$orderId = $_GET['order_id'];
$userId = $_SESSION['user_id'];

try {
    // Ambil booking dari database
    $stmt = $conn->prepare("
        SELECT * FROM pemesanan 
        WHERE midtrans_order_id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Jika pembayaran sudah dikonfirmasi, return status saat ini
    if ($booking['status_pembayaran'] === 'success') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'payment_status' => 'success',
            'message' => 'Payment successful'
        ]);
        exit();
    }
    
    // Cek status dari Midtrans
    if ($booking['metode_pembayaran'] === 'online') {
        try {
            $statusResponse = Midtrans::status($orderId);
            
            $transactionStatus = $statusResponse['transaction_status'];
            $fraudStatus = $statusResponse['fraud_status'] ?? '';
            
            $newStatus = $booking['status_pembayaran'];
            
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $newStatus = 'success';
                }
            } else if ($transactionStatus == 'settlement') {
                $newStatus = 'success';
            } else if ($transactionStatus == 'pending') {
                $newStatus = 'pending';
            } else if ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
                $newStatus = 'failed';
            }
            
            // Update status jika berubah
            if ($newStatus !== $booking['status_pembayaran']) {
                $stmt = $conn->prepare("
                    UPDATE pemesanan 
                    SET status_pembayaran = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $booking['id']]);
                
                if ($newStatus === 'success') {
                    $stmt = $conn->prepare("UPDATE pemesanan SET is_offline = 0 WHERE id = ?");
                    $stmt->execute([$booking['id']]);
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'payment_status' => $newStatus,
                'transaction_status' => $transactionStatus,
                'message' => 'Status updated'
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to check payment status: ' . $e->getMessage()
            ]);
        }
    } else {
        // Pembayaran tunai
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'payment_status' => $booking['status_pembayaran'],
            'message' => 'Cash payment'
        ]);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
