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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['route_id']) && isset($_POST['tanggal'])) {
    try {
        $route_id = $_POST['route_id'];
        $tanggal = $_POST['tanggal'];
        
        // Ambil data bus dan kapasitas dari rute
        $stmt = $conn->prepare("
            SELECT b.kapasitas, b.nama_bus
            FROM rute r
            JOIN bus b ON r.bus_id = b.id
            WHERE r.id = ?
        ");
        $stmt->execute([$route_id]);
        $bus_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bus_data) {
            echo json_encode([
                'success' => false,
                'message' => 'Data bus tidak ditemukan'
            ]);
            exit();
        }
        
        $total_kursi = $bus_data['kapasitas'];
        
        // Ambil kursi yang sudah terpesan untuk rute dan tanggal tertentu
        $stmt_kursi = $conn->prepare("
            SELECT DISTINCT nomor_kursi
            FROM kursi_terpesan
            WHERE route_id = ? AND tanggal_berangkat = ?
            ORDER BY nomor_kursi
        ");
        $stmt_kursi->execute([$route_id, $tanggal]);
        $kursi_terpesan = $stmt_kursi->fetchAll(PDO::FETCH_COLUMN);
        
        $jumlah_terpesan = count($kursi_terpesan);
        $sisa_kursi = $total_kursi - $jumlah_terpesan;
        
        echo json_encode([
            'success' => true,
            'total_kursi' => $total_kursi,
            'kursi_terpesan' => $jumlah_terpesan,
            'sisa_kursi' => $sisa_kursi,
            'kursi_terpesan_list' => $kursi_terpesan,
            'nama_bus' => $bus_data['nama_bus']
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
