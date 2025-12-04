<?php
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Validasi POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$route_id = $_POST['route_id'];
$tanggal_berangkat = $_POST['tanggal_berangkat'];
$jumlah_penumpang = (int)$_POST['jumlah_penumpang'];
$kursi_dipilih = $_POST['kursi_dipilih'];
$total_harga = (float)$_POST['total_harga'];

try {
    $conn->beginTransaction();
    
    // Validasi kursi yang dipilih masih tersedia
    $kursi_array = explode(',', $kursi_dipilih);
    if (count($kursi_array) !== $jumlah_penumpang) {
        throw new Exception('Jumlah kursi tidak sesuai!');
    }
    
    // Cek apakah kursi sudah dipesan
    $placeholders = str_repeat('?,', count($kursi_array) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM kursi_terpesan
        WHERE route_id = ? AND tanggal_berangkat = ? AND nomor_kursi IN ($placeholders)
    ");
    $params = array_merge([$route_id, $tanggal_berangkat], $kursi_array);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        throw new Exception('Maaf, ada kursi yang sudah dipesan oleh pengguna lain. Silakan pilih kursi lain.');
    }
    
    // Generate random alphanumeric booking code
    $booking_code = 'TG' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
    
    // Cash payment only
    $status_pembayaran = 'pending';
    $is_offline = 1;
    // Expired 2 jam dari sekarang
    $expired_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    // Insert pemesanan
    $stmt = $conn->prepare("
        INSERT INTO pemesanan (
            user_id, route_id, tanggal_berangkat, jumlah_penumpang, 
            kursi_dipilih, total_harga, status_pembayaran, metode_pembayaran,
            is_offline, midtrans_order_id, expired_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $route_id,
        $tanggal_berangkat,
        $jumlah_penumpang,
        $kursi_dipilih,
        $total_harga,
        $status_pembayaran,
        'cash',
        $is_offline,
        $booking_code,
        $expired_at
    ]);
    
    $booking_id = $conn->lastInsertId();
    
    // Insert kursi terpesan
    $stmt = $conn->prepare("
        INSERT INTO kursi_terpesan (booking_id, route_id, tanggal_berangkat, nomor_kursi)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($kursi_array as $kursi) {
        $stmt->execute([$booking_id, $route_id, $tanggal_berangkat, trim($kursi)]);
    }
    
    $conn->commit();
    
    // Redirect ke halaman sukses dengan QR code
    $_SESSION['success'] = 'Pemesanan berhasil! Silakan scan QR Code dan bayar di kasir dalam waktu 2 jam.';
    header('Location: booking_success.php?id=' . $booking_id);
    exit();
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: booking.php?' . http_build_query($_GET));
    exit();
}
