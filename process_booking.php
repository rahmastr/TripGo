<?php
require_once 'config.php';
require_once 'midtrans/Midtrans.php';

// Set Midtrans configuration
Midtrans::$serverKey = MIDTRANS_SERVER_KEY;
Midtrans::$clientKey = MIDTRANS_CLIENT_KEY;
Midtrans::$isProduction = MIDTRANS_IS_PRODUCTION;
Midtrans::$isSanitized = MIDTRANS_IS_SANITIZED;
Midtrans::$is3ds = MIDTRANS_IS_3DS;

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
$metode_pembayaran = $_POST['metode_pembayaran'] ?? 'cash'; // cash or online

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
    
    // Get user data for Midtrans
    $stmt = $conn->prepare("SELECT nama_lengkap, email, no_hp FROM pengguna WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get route data for transaction details
    $stmt = $conn->prepare("
        SELECT r.*, b.nama_bus, b.nomor_bus 
        FROM rute r 
        JOIN bus b ON r.bus_id = b.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$route_id]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine payment status and settings
    $status_pembayaran = 'pending';
    $is_offline = 1;
    $snap_token = null;
    $midtrans_order_id = $booking_code;
    
    if ($metode_pembayaran === 'online') {
        // Create Midtrans transaction
        try {
            $transaction_details = array(
                'order_id' => $booking_code,
                'gross_amount' => (int)$total_harga,
            );
            
            $item_details = array(
                array(
                    'id' => 'TICKET_' . $route_id,
                    'price' => (int)$route['harga'],
                    'quantity' => $jumlah_penumpang,
                    'name' => $route['kota_asal'] . ' - ' . $route['kota_tujuan'],
                )
            );
            
            $customer_details = array(
                'first_name' => $user['nama_lengkap'],
                'email' => $user['email'],
                'phone' => $user['no_hp'],
            );
            
            $transaction = array(
                'transaction_details' => $transaction_details,
                'item_details' => $item_details,
                'customer_details' => $customer_details,
                'enabled_payments' => array('bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va', 'gopay', 'shopeepay', 'qris', 'credit_card'),
                'expiry' => array(
                    'start_time' => date('Y-m-d H:i:s O'),
                    'unit' => 'hours',
                    'duration' => 2
                ),
            );
            
            $snapResponse = Midtrans::createTransaction($transaction);
            $snap_token = $snapResponse['token'];
            $is_offline = 0;
            
        } catch (Exception $e) {
            throw new Exception('Gagal membuat transaksi Midtrans: ' . $e->getMessage());
        }
    }
    
    // Expired 2 jam dari sekarang
    $expired_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    // Insert pemesanan
    $stmt = $conn->prepare("
        INSERT INTO pemesanan (
            user_id, route_id, tanggal_berangkat, jumlah_penumpang, 
            kursi_dipilih, total_harga, status_pembayaran, metode_pembayaran,
            is_offline, midtrans_order_id, snap_token, expired_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $route_id,
        $tanggal_berangkat,
        $jumlah_penumpang,
        $kursi_dipilih,
        $total_harga,
        $status_pembayaran,
        $metode_pembayaran,
        $is_offline,
        $midtrans_order_id,
        $snap_token,
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
    
    // Redirect ke halaman sukses
    if ($metode_pembayaran === 'online') {
        $_SESSION['success'] = 'Pemesanan berhasil! Silakan lanjutkan pembayaran.';
    } else {
        $_SESSION['success'] = 'Pemesanan berhasil! Silakan scan QR Code dan bayar di kasir dalam waktu 2 jam.';
    }
    
    header('Location: booking_success.php?id=' . $booking_id);
    exit();
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: booking.php?' . http_build_query($_GET));
    exit();
}
