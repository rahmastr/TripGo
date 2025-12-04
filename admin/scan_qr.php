<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_code'])) {
    try {
        $booking_code = trim($_POST['booking_code']);
        $admin_id = $_POST['admin_id'];
        
        // Cari pemesanan berdasarkan booking code
        $stmt = $conn->prepare("
            SELECT p.*, u.nama_lengkap, CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute,
                   p.midtrans_order_id as booking_code
            FROM pemesanan p
            JOIN pengguna u ON p.user_id = u.id
            JOIN rute r ON p.route_id = r.id
            WHERE p.midtrans_order_id = ?
        ");
        $stmt->execute([$booking_code]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking tidak ditemukan!'
            ]);
            exit();
        }
        
        // Cek apakah sudah pernah di-scan
        if ($booking['qr_used'] == 1) {
            echo json_encode([
                'success' => false,
                'message' => 'QR Code sudah pernah di-scan sebelumnya pada ' . 
                            date('d/m/Y H:i', strtotime($booking['qr_scanned_at']))
            ]);
            exit();
        }
        
        // Update status verifikasi (remove qr_scanned_by because column not present in schema)
        $stmt = $conn->prepare("
            UPDATE pemesanan 
            SET qr_used = 1, 
                qr_scanned_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$booking['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Verifikasi berhasil!',
            'booking_code' => $booking['booking_code'],
            'passenger' => $booking['nama_lengkap'],
            'route' => $booking['rute'],
            'date' => date('d/m/Y', strtotime($booking['tanggal_berangkat']))
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
