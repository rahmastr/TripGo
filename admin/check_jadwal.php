<?php
require_once '../config.php';

echo "=== SEMUA JADWAL DI DATABASE ===\n\n";

// Check semua jadwal tanpa filter
$stmt = $conn->query("
    SELECT 
        j.id,
        j.route_id,
        j.tanggal_operasional,
        j.status,
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute,
        r.jam_berangkat
    FROM jadwal j
    JOIN rute r ON j.route_id = r.id
    ORDER BY j.tanggal_operasional ASC
");
$allJadwals = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total jadwal: " . count($allJadwals) . "\n\n";

if (count($allJadwals) > 0) {
    foreach ($allJadwals as $j) {
        echo "ID: {$j['id']}\n";
        echo "  Route ID: {$j['route_id']} ({$j['rute']})\n";
        echo "  Tanggal: {$j['tanggal_operasional']}\n";
        echo "  Jam: {$j['jam_berangkat']}\n";
        echo "  Status: {$j['status']}\n";
        echo "  ----\n";
    }
}

echo "\n=== JADWAL AKTIF (>= HARI INI) ===\n\n";

// Check jadwal aktif
$stmt = $conn->query("
    SELECT COUNT(*) as total 
    FROM jadwal 
    WHERE status = 'aktif' AND tanggal_operasional >= CURDATE()
");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total jadwal aktif >= hari ini: " . $result['total'] . "\n\n";

// Detail jadwal aktif
$stmt = $conn->query("
    SELECT 
        j.id,
        j.route_id,
        j.tanggal_operasional,
        j.status,
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute,
        r.jam_berangkat
    FROM jadwal j
    JOIN rute r ON j.route_id = r.id
    WHERE j.status = 'aktif' AND j.tanggal_operasional >= CURDATE()
    ORDER BY j.tanggal_operasional ASC
");
$activeJadwals = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($activeJadwals) > 0) {
    foreach ($activeJadwals as $j) {
        echo "ID: {$j['id']}, Route: {$j['route_id']} ({$j['rute']}), Tanggal: {$j['tanggal_operasional']}, Jam: {$j['jam_berangkat']}\n";
    }
} else {
    echo "Tidak ada jadwal aktif >= hari ini\n";
}

echo "\nHari ini: " . date('Y-m-d') . "\n";

