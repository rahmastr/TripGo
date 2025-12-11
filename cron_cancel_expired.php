<?php
/**
 * Cron Job untuk Auto-Cancel Expired Bookings
 * 
 * Jalankan script ini secara berkala (setiap 5-10 menit) menggunakan Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\TripGo\cron_cancel_expired.php
 * Trigger: Setiap 5-10 menit
 */

require_once __DIR__ . '/config.php';

// Fungsi untuk mencatat log aktivitas cron job
function logCron($message) {
    $logFile = __DIR__ . '/logs/cron_expired.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logCron("=== Cron Job Started ===");

try {
    // Cari semua pemesanan yang sudah expired dan masih berstatus pending
    $stmt = $conn->prepare("
        SELECT id, midtrans_order_id, user_id, route_id, tanggal_berangkat
        FROM pemesanan 
        WHERE status_pembayaran = 'pending' 
        AND expired_at < NOW()
        AND expired_at IS NOT NULL
    ");
    $stmt->execute();
    $expiredBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cancelledCount = 0;
    
    foreach ($expiredBookings as $booking) {
        $conn->beginTransaction();
        
        try {
            // Update status pemesanan menjadi failed (gagal)
            $stmt = $conn->prepare("
                UPDATE pemesanan 
                SET status_pembayaran = 'failed', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$booking['id']]);
            
            // Bebaskan kursi yang dipesan agar bisa dipesan lagi
            $stmt = $conn->prepare("DELETE FROM kursi_terpesan WHERE booking_id = ?");
            $stmt->execute([$booking['id']]);
            
            $conn->commit();
            
            $cancelledCount++;
            logCron("Cancelled booking #{$booking['id']} - Order: {$booking['midtrans_order_id']}");
            
        } catch (Exception $e) {
            $conn->rollBack();
            logCron("Error cancelling booking #{$booking['id']}: " . $e->getMessage());
        }
    }
    
    logCron("Total expired bookings cancelled: $cancelledCount");
    
} catch (Exception $e) {
    logCron("Error: " . $e->getMessage());
}

logCron("=== Cron Job Finished ===\n");
