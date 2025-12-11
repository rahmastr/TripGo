<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['route_id'])) {
    echo json_encode(['success' => false, 'message' => 'Route ID required']);
    exit;
}

$route_id = $_GET['route_id'];

try {
    // Ambil tanggal yang tersedia dari tabel jadwal untuk rute ini
    $stmt = $conn->prepare("
        SELECT 
            j.id as jadwal_id,
            j.tanggal_operasional,
            j.status,
            r.jam_berangkat,
            DATE_FORMAT(j.tanggal_operasional, '%Y-%m-%d') as date_value,
            DATE_FORMAT(j.tanggal_operasional, '%d/%m/%Y') as date_display
        FROM jadwal j
        JOIN rute r ON j.route_id = r.id
        WHERE j.route_id = ? 
        AND (j.status = 'aktif' OR j.status = 'active')
        AND j.tanggal_operasional >= CURDATE()
        ORDER BY j.tanggal_operasional ASC
    ");
    $stmt->execute([$route_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'dates' => $dates
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
