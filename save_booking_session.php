<?php
require_once 'config.php';

header('Content-Type: application/json');

// Simpan data booking ke session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $_SESSION['pending_booking'] = [
            'route_id' => $_POST['route_id'] ?? null,
            'tanggal_berangkat' => $_POST['tanggal_berangkat'] ?? null,
            'jumlah_penumpang' => $_POST['jumlah_penumpang'] ?? null,
            'kursi_dipilih' => $_POST['kursi_dipilih'] ?? null,
            'total_harga' => $_POST['total_harga'] ?? null,
            'metode_pembayaran' => $_POST['metode_pembayaran'] ?? 'online',
            'penumpang' => $_POST['penumpang'] ?? [],
            'timestamp' => time()
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Data booking berhasil disimpan'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
