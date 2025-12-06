<?php
require_once 'config.php';
require_once 'midtrans/Midtrans.php';

// Set Midtrans configuration
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
    // Get booking from database
    $stmt = $conn->prepare("
        SELECT * FROM pemesanan 
        WHERE midtrans_order_id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // If payment is already confirmed, return current status
    if ($booking['status_pembayaran'] === 'success') {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'payment_status' => 'success',
            'message' => 'Payment successful'
        ]);
        exit();
    }
    
    // Check status from Midtrans
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
            
            // Update status if changed
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
        // Cash payment
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
