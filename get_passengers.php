<?php
require_once 'config.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validasi parameter
if (!isset($_GET['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID required']);
    exit();
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'customer';

try {
    // Jika bukan admin, verifikasi booking milik user yang login
    if ($user_role !== 'admin') {
        $stmt = $conn->prepare("SELECT id FROM pemesanan WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or unauthorized']);
            exit();
        }
    }
    
    // Ambil data penumpang
    $stmt = $conn->prepare("
        SELECT 
            nomor_kursi,
            nama_lengkap,
            no_hp,
            email,
            jenis_kelamin,
            usia,
            jenis_identitas,
            no_identitas
        FROM penumpang 
        WHERE booking_id = ? 
        ORDER BY nomor_kursi
    ");
    $stmt->execute([$booking_id]);
    $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'passengers' => $passengers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
