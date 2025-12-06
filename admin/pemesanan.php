<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle hapus pemesanan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $booking_id = $_GET['id'];
        
        // Hapus kursi terpesan terlebih dahulu
        $stmt = $conn->prepare("DELETE FROM kursi_terpesan WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        
        // Hapus pemesanan
        $stmt = $conn->prepare("DELETE FROM pemesanan WHERE id = ?");
        $stmt->execute([$booking_id]);
        
        $_SESSION['success'] = 'Pemesanan berhasil dihapus!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error menghapus pemesanan: ' . $e->getMessage();
    }
    header('Location: pemesanan.php');
    exit();
}

// Handle pemesanan tunai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cash_booking') {
    try {
        $conn->beginTransaction();
        
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $no_hp = trim($_POST['no_hp']);
        $route_id = $_POST['route_id'];
        $tanggal_berangkat = $_POST['tanggal_berangkat'];
        $jumlah_penumpang = $_POST['jumlah_penumpang'];
        $kursi_dipilih = $_POST['kursi_dipilih'];
        $total_harga = $_POST['total_harga'];
        
        // Cek apakah user dengan email ini sudah ada
        $stmt = $conn->prepare("SELECT id FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_user) {
            // Gunakan user yang sudah ada
            $user_id = $existing_user['id'];
        } else {
            // Buat user baru dengan password default
            $default_password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO pengguna (nama_lengkap, email, no_hp, password, role, created_at)
                VALUES (?, ?, ?, ?, 'customer', NOW())
            ");
            $stmt->execute([$nama_lengkap, $email, $no_hp, $default_password]);
            $user_id = $conn->lastInsertId();
        }
        
        // Generate booking code (format seperti Midtrans)
        $order_id = 'TG-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
        
        // Insert pemesanan
        $stmt = $conn->prepare("
            INSERT INTO pemesanan (
                user_id, route_id, tanggal_berangkat, jumlah_penumpang, 
                kursi_dipilih, total_harga, midtrans_order_id, 
                status_pembayaran, is_offline, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'success', 1, NOW())
        ");
        $stmt->execute([
            $user_id, $route_id, $tanggal_berangkat, $jumlah_penumpang,
            $kursi_dipilih, $total_harga, $order_id
        ]);
        
        $booking_id = $conn->lastInsertId();
        
        // Insert kursi terpesan
        $kursi_array = array_map('trim', explode(',', $kursi_dipilih));
        $stmt = $conn->prepare("
            INSERT INTO kursi_terpesan (route_id, tanggal_berangkat, nomor_kursi, booking_id)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($kursi_array as $kursi) {
            $stmt->execute([$route_id, $tanggal_berangkat, $kursi, $booking_id]);
        }
        
        $conn->commit();
        $_SESSION['success'] = 'Pemesanan tunai berhasil dibuat! Kode Booking: ' . $order_id . ' untuk ' . $nama_lengkap;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Error membuat pemesanan: ' . $e->getMessage();
    }
    header('Location: pemesanan.php');
    exit();
}



// Get all bookings with details (removed non-existent verified_by column)
$stmt = $conn->query("
    SELECT 
        p.*,
        u.nama_lengkap,
        u.email,
        u.no_hp,
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute,
        r.jam_berangkat,
        b.nama_bus,
        p.midtrans_order_id as booking_code
    FROM pemesanan p
    JOIN pengguna u ON p.user_id = u.id
    JOIN rute r ON p.route_id = r.id
    JOIN bus b ON r.bus_id = b.id
    ORDER BY p.created_at DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get routes for dropdown
$routes = $conn->query("
    SELECT r.id, CONCAT(r.kota_asal, ' - ', r.kota_tujuan, ' (', TIME_FORMAT(r.jam_berangkat, '%H:%i'), ')') as display_name, r.harga
    FROM rute r
    ORDER BY r.kota_asal
")->fetchAll(PDO::FETCH_ASSOC);

// Get users for dropdown
$users = $conn->query("SELECT id, nama_lengkap, email FROM pengguna WHERE role = 'customer' ORDER BY nama_lengkap")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Data Pemesanan';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-ticket-perforated"></i> Data Pemesanan</h2>
            <p>Kelola pemesanan tiket bus TripGo</p>
        </div>
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#cashBookingModal">
                <i class="bi bi-cash-coin"></i> Pemesanan Tunai
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scanQrModal">
                <i class="bi bi-qr-code-scan"></i> Scan QR
            </button>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle table-sm">
            <thead class="table-dark">
                <tr>
                    <th width="100"><small>Kode Booking</small></th>
                    <th width="130" class="text-center"><small>Nama Penumpang</small></th>
                    <th width="150"><small>Rute</small></th>
                    <th width="70"><small>Kursi</small></th>
                    <th width="100"><small>Harga</small></th>
                    <th width="70"><small>Payment</small></th>
                    <th width="80"><small>Status</small></th>
                    <th width="60"><small>Aksi</small></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="8" class="text-center py-3">
                        <i class="bi bi-inbox fs-4 text-muted"></i>
                        <p class="text-muted mb-0 small">Belum ada data pemesanan</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><small><strong class="text-primary"><?php echo htmlspecialchars($booking['booking_code']); ?></strong></small></td>
                        <td class="text-center"><small><?php echo htmlspecialchars($booking['nama_lengkap']); ?></small></td>
                        <td>
                            <small><strong><?php echo htmlspecialchars($booking['rute']); ?></strong></small><br>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($booking['tanggal_berangkat'])); ?></small>
                        </td>
                        <td><span class="badge bg-secondary" style="font-size: 0.7rem;"><?php echo htmlspecialchars($booking['kursi_dipilih']); ?></span></td>
                        <td><small><strong>Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></strong></small></td>
                        <td>
                            <?php if ($booking['is_offline']): ?>
                                <span class="badge bg-info" style="font-size: 0.7rem;">Tunai</span>
                            <?php else: ?>
                                <span class="badge bg-primary" style="font-size: 0.7rem;">Online</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            // Cek status berdasarkan QR scan atau pembayaran tunai
                            if ($booking['qr_used'] == 1 || $booking['is_offline'] == 1):
                            ?>
                                <span class="badge bg-success" style="font-size: 0.7rem;">
                                    <i class="bi bi-check-circle-fill"></i> Yes
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger" style="font-size: 0.7rem;">
                                    <i class="bi bi-x-circle-fill"></i> No
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-sm btn-primary" onclick="viewDetail(<?php echo htmlspecialchars(json_encode($booking)); ?>)" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBooking(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['booking_code']); ?>')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Scan QR -->
<div class="modal fade" id="scanQrModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-qr-code-scan"></i> Scan QR Code Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#scanTab">
                            <i class="bi bi-camera"></i> Scan QR Code
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manualTab">
                            <i class="bi bi-keyboard"></i> Input Manual
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- Tab Scan QR -->
                    <div class="tab-pane fade show active" id="scanTab">
                        <div id="qr-scanner-container">
                            <div id="qr-reader" style="width: 100%;"></div>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> Arahkan kamera ke QR code pada tiket pemesanan
                        </div>
                    </div>
                    
                    <!-- Tab Manual -->
                    <div class="tab-pane fade" id="manualTab">
                        <form id="manualVerifyForm">
                            <div class="mb-3">
                                <label class="form-label">Kode Booking</label>
                                <input type="text" class="form-control form-control-lg" id="manual_booking_code" placeholder="Masukkan kode booking">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check-circle"></i> Verifikasi
                            </button>
                        </form>
                    </div>
                </div>
                
                <div id="scanResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>



<!-- Modal Detail Booking -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-ticket-detailed"></i> Detail Pemesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Pemesanan Tunai -->
<div class="modal fade" id="cashBookingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Pemesanan Tunai Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="cashBookingForm">
                <input type="hidden" name="action" value="cash_booking">
                <div class="modal-body">
                    <div class="row">
                        <!-- Form Input -->
                        <div class="col-md-7">
                            <h6 class="text-primary mb-3"><i class="bi bi-person-fill"></i> Informasi Penumpang</h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" 
                                           placeholder="Masukkan nama lengkap penumpang" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" id="email" 
                                           placeholder="contoh@email.com" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">No HP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="no_hp" id="no_hp" 
                                           placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>

                            <h6 class="text-primary mb-3 mt-4"><i class="bi bi-bus-front"></i> Informasi Perjalanan</h6>
                            <div class="mb-3">
                                <label class="form-label">Pilih Rute <span class="text-danger">*</span></label>
                                <select class="form-select" name="route_id" id="route_select" required onchange="loadAvailableDates()">
                                    <option value="">-- Pilih Rute --</option>
                                    <?php foreach ($routes as $route): ?>
                                        <option value="<?php echo $route['id']; ?>" data-price="<?php echo $route['harga']; ?>">
                                            <?php echo htmlspecialchars($route['display_name']); ?> - Rp <?php echo number_format($route['harga'], 0, ',', '.'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tanggal Berangkat <span class="text-danger">*</span></label>
                                    <select class="form-select" name="tanggal_berangkat" id="tanggal_berangkat_select" onchange="loadSeats()" style="display: none;">
                                        <option value="">-- Pilih Tanggal --</option>
                                    </select>
                                    <input type="date" class="form-control" name="tanggal_berangkat_manual" id="tanggal_berangkat_manual" 
                                           min="<?php echo date('Y-m-d'); ?>" onchange="loadSeats()" style="display: none;">
                                    <small class="text-muted" id="tanggal_hint">Pilih rute terlebih dahulu</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jumlah Penumpang</label>
                                    <input type="number" class="form-control" name="jumlah_penumpang" id="jumlah_penumpang" 
                                           value="1" min="1" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Kursi Dipilih <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="kursi_dipilih" id="kursi_dipilih" 
                                       placeholder="Pilih kursi dari peta kursi" readonly>
                                <small class="text-muted">Klik pada peta kursi di samping untuk memilih</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Total Harga</label>
                                <input type="number" class="form-control form-control-lg fw-bold text-primary" 
                                       name="total_harga" id="total_harga" readonly value="0">
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Pembayaran Tunai:</strong> Pemesanan ini akan langsung dikonfirmasi sebagai sudah dibayar (cash).
                            </div>
                        </div>

                        <!-- Seat Map -->
                        <div class="col-md-5">
                            <h6 class="text-primary mb-3"><i class="bi bi-grid-3x3"></i> Peta Kursi</h6>
                            <div id="seatMap" style="display: none;">
                                <div class="mb-3">
                                    <span class="badge bg-secondary me-2">Tersedia</span>
                                    <span class="badge bg-danger me-2">Terisi</span>
                                    <span class="badge bg-success">Dipilih</span>
                                </div>
                                <div id="seatGrid" class="mb-3"></div>
                            </div>
                            <div id="seatMapPlaceholder" class="text-center text-muted py-5">
                                <i class="bi bi-arrow-left fs-1"></i>
                                <p class="mt-2">Pilih rute dan tanggal untuk melihat peta kursi</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitCashBooking" onclick="console.log('Button clicked!');">
                        <i class="bi bi-check-circle"></i> Buat Pemesanan Tunai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let html5QrCode = null;

// Initialize QR Scanner when modal is shown
$('#scanQrModal').on('shown.bs.modal', function() {
    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("qr-reader");
        startScanner();
    }
});

// Stop scanner when modal is hidden
$('#scanQrModal').on('hidden.bs.modal', function() {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop();
    }
});

function startScanner() {
    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 200, height: 200 }
        },
        onScanSuccess,
        onScanError
    ).catch(err => {
        console.error('Camera error:', err);
        $('#scanResult').html('<div class="alert alert-danger">Error mengakses kamera. Gunakan input manual.</div>');
    });
}

function onScanSuccess(decodedText, decodedResult) {
    verifyBooking(decodedText);
    if (html5QrCode.isScanning) {
        html5QrCode.stop();
    }
}

function onScanError(errorMessage) {
    // Ignore scan errors
}

function scanBooking(bookingCode) {
    $('#scanQrModal').modal('show');
    $('#manual_booking_code').val(bookingCode);
}

$('#manualVerifyForm').submit(function(e) {
    e.preventDefault();
    const bookingCode = $('#manual_booking_code').val();
    verifyBooking(bookingCode);
});

function verifyBooking(bookingCode) {
    $('#scanResult').html('<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Memverifikasi...</div>');
    
    $.ajax({
        url: 'scan_qr.php',
        method: 'POST',
        data: {
            booking_code: bookingCode,
            admin_id: <?php echo $_SESSION['user_id']; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#scanResult').html(
                    '<div class="alert alert-success">' +
                    '<i class="bi bi-check-circle-fill"></i> <strong>Verifikasi Berhasil!</strong><br>' +
                    'Booking Code: ' + response.booking_code + '<br>' +
                    'Penumpang: ' + response.passenger + '<br>' +
                    'Rute: ' + response.route + '<br>' +
                    'Tanggal: ' + response.date +
                    '</div>'
                );
                
                // Reload halaman setelah 2 detik
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $('#scanResult').html(
                    '<div class="alert alert-danger">' +
                    '<i class="bi bi-x-circle-fill"></i> ' + response.message +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#scanResult').html(
                '<div class="alert alert-danger">' +
                '<i class="bi bi-x-circle-fill"></i> Terjadi kesalahan sistem' +
                '</div>'
            );
        }
    });
}

function updatePrice() {
    const select = document.getElementById('route_select');
    const price = select.options[select.selectedIndex].getAttribute('data-price');
    updateTotal();
}

function updatePriceAndSeats() {
    console.log('updatePriceAndSeats called');
    updatePrice();
    loadAvailableSeats();
}

function loadAvailableSeats() {
    const routeId = document.getElementById('route_select').value;
    const tanggal = document.getElementById('tanggal_berangkat').value;
    
    console.log('Loading seats for route:', routeId, 'date:', tanggal);
    
    if (!routeId || !tanggal) {
        document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-info-circle"></i> Pilih rute dan tanggal untuk melihat sisa kursi';
        document.getElementById('seat_map').style.display = 'none';
        return;
    }
    
    document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-hourglass-split"></i> Memuat sisa kursi...';
    document.getElementById('seat_map').style.display = 'none';
    
    // Gunakan XMLHttpRequest sebagai alternatif
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'get_available_seats.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        console.log('XHR Response:', xhr.status, xhr.responseText);
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('Parsed response:', response);
                if (response.success) {
                    const sisaKursi = response.total_kursi - response.kursi_terpesan;
                    let html = '<div class="d-flex justify-content-between align-items-center">';
                    html += '<div><strong><i class="bi bi-check-circle text-success"></i> Sisa Kursi: ' + sisaKursi + ' dari ' + response.total_kursi + '</strong></div>';
                    html += '<div><small class="text-muted">Bus: ' + response.nama_bus + '</small></div>';
                    html += '</div>';
                    document.getElementById('sisa_kursi_display').innerHTML = html;
                    
                    // Generate seat map
                    console.log('Generating seat map...');
                    generateSeatMap(response.total_kursi, response.kursi_terpesan_list || []);
                } else {
                    document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-exclamation-circle text-warning"></i> ' + response.message;
                    document.getElementById('seat_map').style.display = 'none';
                }
            } catch (e) {
                console.error('Parse error:', e);
                document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Error parsing response';
                document.getElementById('seat_map').style.display = 'none';
            }
        } else {
            document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Gagal memuat data kursi';
            document.getElementById('seat_map').style.display = 'none';
        }
    };
    
    xhr.onerror = function() {
        document.getElementById('sisa_kursi_display').innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Gagal memuat data kursi';
        document.getElementById('seat_map').style.display = 'none';
    };
    
    xhr.send('route_id=' + encodeURIComponent(routeId) + '&tanggal=' + encodeURIComponent(tanggal));
}

function generateSeatMap(totalKursi, kursiTerpesan) {
    const seatGrid = document.getElementById('seat_grid');
    const seatMap = document.getElementById('seat_map');
    
    console.log('generateSeatMap called with:', totalKursi, kursiTerpesan);
    
    if (!seatGrid || !seatMap) {
        console.error('Seat grid or map element not found');
        return;
    }
    
    seatGrid.innerHTML = '';
    
    // Generate kursi dengan format A1, A2, B1, B2, dst
    const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    const seatsPerRow = 4; // 4 kursi per baris agar lebih rapi
    const totalRows = Math.ceil(totalKursi / seatsPerRow);
    
    let seatNumber = 1;
    let html = '<div class="w-100">';
    
    // Pastikan kursiTerpesan adalah array
    const bookedSeats = Array.isArray(kursiTerpesan) ? kursiTerpesan : [];
    console.log('Booked seats:', bookedSeats);
    
    for (let row = 0; row < totalRows && seatNumber <= totalKursi; row++) {
        html += '<div class="d-flex gap-2 mb-2 justify-content-center">';
        
        for (let col = 1; col <= seatsPerRow && seatNumber <= totalKursi; col++) {
            const seatCode = rows[row] + col;
            const isBooked = bookedSeats.includes(seatCode);
            const badgeClass = isBooked ? 'bg-danger' : 'bg-success';
            const cursor = isBooked ? 'not-allowed' : 'pointer';
            const onclick = isBooked ? '' : 'onclick="selectSeat(\'' + seatCode + '\')"';
            const title = isBooked ? 'Kursi sudah terpesan' : 'Klik untuk memilih kursi ' + seatCode;
            const opacity = isBooked ? 'opacity: 0.6;' : '';
            
            html += '<span class="badge ' + badgeClass + '" style="cursor: ' + cursor + '; padding: 10px 15px; min-width: 50px; font-size: 0.9rem; ' + opacity + '" ' + onclick + ' title="' + title + '">' + seatCode + '</span>';
            seatNumber++;
        }
        
        html += '</div>';
    }
    
    html += '</div>';
    
    seatGrid.innerHTML = html;
    seatMap.style.display = 'block';
    console.log('Seat map generated and displayed');
}

function selectSeat(seatCode) {
    const kursiInput = document.getElementById('kursi_dipilih');
    const currentValue = kursiInput.value.trim();
    
    if (currentValue === '') {
        kursiInput.value = seatCode;
    } else {
        const seats = currentValue.split(',').map(s => s.trim());
        const index = seats.indexOf(seatCode);
        
        if (index > -1) {
            // Jika sudah ada, hapus
            seats.splice(index, 1);
        } else {
            // Jika belum ada, tambah
            seats.push(seatCode);
        }
        
        kursiInput.value = seats.filter(s => s !== '').join(', ');
    }
    
    // Update jumlah penumpang sesuai kursi yang dipilih
    const selectedSeats = kursiInput.value.split(',').filter(s => s.trim() !== '');
    document.getElementById('jumlah_penumpang').value = selectedSeats.length || 1;
    updateTotal();
}

function updateTotal() {
    const select = document.getElementById('route_select');
    const price = select.options[select.selectedIndex]?.getAttribute('data-price') || 0;
    const qty = document.getElementById('jumlah_penumpang').value || 1;
    const total = price * qty;
    document.getElementById('total_harga').value = total;
}

function viewDetail(booking) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">Informasi Penumpang</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nama</strong></td><td>${booking.nama_lengkap}</td></tr>
                    <tr><td><strong>Email</strong></td><td>${booking.email}</td></tr>
                    <tr><td><strong>No HP</strong></td><td>${booking.no_hp}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Informasi Perjalanan</h6>
                <table class="table table-sm">
                    <tr><td><strong>Rute</strong></td><td>${booking.rute}</td></tr>
                    <tr><td><strong>Bus</strong></td><td>${booking.nama_bus}</td></tr>
                    <tr><td><strong>Tanggal</strong></td><td>${booking.tanggal_berangkat}</td></tr>
                    <tr><td><strong>Jam</strong></td><td>${booking.jam_berangkat}</td></tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <h6 class="text-muted">Informasi Pembayaran</h6>
                <table class="table table-sm">
                    <tr><td><strong>Booking Code</strong></td><td>${booking.booking_code}</td></tr>
                    <tr><td><strong>Metode</strong></td><td>${booking.is_offline ? 'Tunai' : 'Online'}</td></tr>
                    <tr><td><strong>Jumlah</strong></td><td>${booking.jumlah_penumpang} penumpang</td></tr>
                    <tr><td><strong>Kursi</strong></td><td>${booking.kursi_dipilih}</td></tr>
                    <tr><td><strong>Total</strong></td><td>Rp ${parseInt(booking.total_harga).toLocaleString('id-ID')}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Status Verifikasi</h6>
                <table class="table table-sm">
                    <tr><td><strong>Status</strong></td><td>${booking.status_pembayaran}</td></tr>
                    <tr><td><strong>QR Verified</strong></td><td>${booking.qr_used ? 'Ya' : 'Tidak'}</td></tr>
                    ${booking.qr_scanned_at ? `<tr><td><strong>Verified At</strong></td><td>${booking.qr_scanned_at}</td></tr>` : ''}
                </table>
            </div>
        </div>
    `;
    
    $('#detailContent').html(content);
    $('#detailModal').modal('show');
}

function deleteBooking(id, bookingCode) {
    if (confirm('Apakah Anda yakin ingin menghapus pemesanan dengan kode booking: ' + bookingCode + '?\n\nTindakan ini tidak dapat dibatalkan!')) {
        window.location.href = 'pemesanan.php?action=delete&id=' + id;
    }
}

// Fungsi untuk pemesanan tunai
let selectedSeats = [];

// Load available dates based on selected route
function loadAvailableDates() {
    const routeId = document.getElementById('route_select').value;
    const dateSelect = document.getElementById('tanggal_berangkat_select');
    const dateManual = document.getElementById('tanggal_berangkat_manual');
    const dateHint = document.getElementById('tanggal_hint');
    
    // Reset date fields
    dateSelect.innerHTML = '<option value="">-- Pilih Tanggal --</option>';
    dateSelect.style.display = 'none';
    dateManual.style.display = 'none';
    dateManual.value = '';
    dateSelect.removeAttribute('name');
    dateManual.removeAttribute('name');
    
    // Reset seat map
    selectedSeats = [];
    document.getElementById('kursi_dipilih').value = '';
    document.getElementById('total_harga').value = '';
    document.getElementById('jumlah_penumpang').value = '1';
    document.getElementById('seatMap').style.display = 'none';
    document.getElementById('seatMapPlaceholder').style.display = 'block';
    
    if (!routeId) {
        dateHint.textContent = 'Pilih rute terlebih dahulu';
        return;
    }
    
    dateHint.textContent = 'Memuat jadwal...';
    
    // Fetch available dates from jadwal
    fetch('get_available_dates.php?route_id=' + routeId)
    .then(response => response.json())
    .then(data => {
        console.log('Jadwal response:', data);
        if (data.success && data.dates.length > 0) {
            // Ada jadwal, gunakan dropdown
            data.dates.forEach(schedule => {
                const option = document.createElement('option');
                option.value = schedule.date_value;
                option.textContent = schedule.date_display + ' - ' + schedule.jam_berangkat.substring(0, 5);
                dateSelect.appendChild(option);
            });
            dateSelect.style.display = 'block';
            dateSelect.setAttribute('name', 'tanggal_berangkat');
            dateSelect.setAttribute('required', 'required');
            dateHint.textContent = 'Pilih dari jadwal yang tersedia';
        } else {
            // Tidak ada jadwal, gunakan date picker manual
            dateManual.style.display = 'block';
            dateManual.setAttribute('name', 'tanggal_berangkat');
            dateManual.setAttribute('required', 'required');
            dateHint.textContent = 'Tidak ada jadwal terdaftar, pilih tanggal manual';
        }
    })
    .catch(error => {
        console.error('Error loading dates:', error);
        // Fallback ke date picker jika error
        dateManual.style.display = 'block';
        dateManual.setAttribute('name', 'tanggal_berangkat');
        dateManual.setAttribute('required', 'required');
        dateHint.textContent = 'Pilih tanggal manual';
    });
}

function loadSeats() {
    const routeId = document.getElementById('route_select').value;
    const tanggal = document.getElementById('tanggal_berangkat_select').value || 
                    document.getElementById('tanggal_berangkat_manual').value;
    
    if (!routeId || !tanggal) {
        document.getElementById('seatMap').style.display = 'none';
        document.getElementById('seatMapPlaceholder').style.display = 'block';
        return;
    }
    
    document.getElementById('seatMapPlaceholder').style.display = 'none';
    
    // AJAX request untuk mendapatkan data kursi
    fetch('get_available_seats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'route_id=' + routeId + '&tanggal=' + tanggal
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateCashBookingSeatMap(data.total_kursi, data.kursi_terpesan_list || []);
            document.getElementById('seatMap').style.display = 'block';
        } else {
            alert(data.message || 'Gagal memuat data kursi');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memuat data kursi');
    });
}

function generateCashBookingSeatMap(totalSeats, bookedSeats) {
    const seatGrid = document.getElementById('seatGrid');
    selectedSeats = [];
    document.getElementById('kursi_dipilih').value = '';
    
    let html = '<div class="seat-layout-admin">';
    html += '<div class="text-center mb-2"><strong>DEPAN (Sopir)</strong></div>';
    
    let seatNumber = 1;
    const seatsPerRow = 4;
    const rows = Math.ceil(totalSeats / seatsPerRow);
    
    for (let row = 0; row < rows; row++) {
        html += '<div class="seat-row-admin">';
        
        for (let col = 0; col < seatsPerRow; col++) {
            if (seatNumber > totalSeats) break;
            
            const seatCode = convertToSeatCode(seatNumber);
            const isBooked = bookedSeats.includes(seatCode);
            
            if (col === 2) {
                html += '<div class="seat-gap-admin"></div>';
            }
            
            if (isBooked) {
                html += '<span class="seat-admin seat-booked-admin" title="Sudah terisi">' + seatCode + '</span>';
            } else {
                html += '<span class="seat-admin seat-available-admin" onclick="toggleSeat(\'' + seatCode + '\')" title="Klik untuk pilih">' + seatCode + '</span>';
            }
            
            seatNumber++;
        }
        
        html += '</div>';
    }
    
    html += '</div>';
    
    // Add CSS
    html += `<style>
        .seat-layout-admin { max-width: 100%; }
        .seat-row-admin { display: flex; justify-content: center; gap: 8px; margin-bottom: 8px; }
        .seat-gap-admin { width: 30px; }
        .seat-admin { 
            display: inline-block; 
            width: 45px; 
            height: 45px; 
            line-height: 45px; 
            text-align: center; 
            border-radius: 5px; 
            font-size: 0.75rem; 
            font-weight: bold; 
            cursor: pointer;
            border: 2px solid;
        }
        .seat-available-admin { 
            background: #f8f9fa; 
            border-color: #6c757d; 
            color: #6c757d; 
        }
        .seat-available-admin:hover { 
            background: #e9ecef; 
            transform: scale(1.05); 
        }
        .seat-booked-admin { 
            background: #dc3545; 
            border-color: #dc3545; 
            color: white; 
            cursor: not-allowed; 
            opacity: 0.6;
        }
        .seat-selected-admin { 
            background: #28a745; 
            border-color: #28a745; 
            color: white; 
        }
    </style>`;
    
    seatGrid.innerHTML = html;
}

function convertToSeatCode(number) {
    const row = Math.ceil(number / 4);
    const position = ((number - 1) % 4);
    const letters = ['A', 'B', 'C', 'D'];
    return letters[position] + row;
}

function toggleSeat(seatCode) {
    const index = selectedSeats.indexOf(seatCode);
    
    if (index > -1) {
        selectedSeats.splice(index, 1);
        document.querySelector('[onclick="toggleSeat(\'' + seatCode + '\')"]').classList.remove('seat-selected-admin');
        document.querySelector('[onclick="toggleSeat(\'' + seatCode + '\')"]').classList.add('seat-available-admin');
    } else {
        selectedSeats.push(seatCode);
        document.querySelector('[onclick="toggleSeat(\'' + seatCode + '\')"]').classList.remove('seat-available-admin');
        document.querySelector('[onclick="toggleSeat(\'' + seatCode + '\')"]').classList.add('seat-selected-admin');
    }
    
    document.getElementById('kursi_dipilih').value = selectedSeats.sort().join(', ');
    document.getElementById('jumlah_penumpang').value = selectedSeats.length || 1;
    updateTotal();
}

function updateTotal() {
    const select = document.getElementById('route_select');
    const price = select.options[select.selectedIndex]?.getAttribute('data-price') || 0;
    const qty = document.getElementById('jumlah_penumpang').value || 1;
    const total = price * qty;
    document.getElementById('total_harga').value = total;
}

// Reset form saat modal ditutup
$('#cashBookingModal').on('hidden.bs.modal', function() {
    document.getElementById('cashBookingForm').reset();
    selectedSeats = [];
    document.getElementById('tanggal_berangkat_select').innerHTML = '<option value="">-- Pilih Tanggal --</option>';
    document.getElementById('tanggal_berangkat_select').style.display = 'none';
    document.getElementById('tanggal_berangkat_manual').style.display = 'none';
    document.getElementById('tanggal_hint').textContent = 'Pilih rute terlebih dahulu';
    document.getElementById('seatMap').style.display = 'none';
    document.getElementById('seatMapPlaceholder').style.display = 'block';
});

// Validasi form sebelum submit
$('#cashBookingForm').on('submit', function(e) {
    const namaLengkap = document.getElementById('nama_lengkap').value.trim();
    const email = document.getElementById('email').value.trim();
    const noHp = document.getElementById('no_hp').value.trim();
    const routeId = document.getElementById('route_select').value;
    const tanggal = document.getElementById('tanggal_berangkat_select').value || document.getElementById('tanggal_berangkat_manual').value;
    const kursiDipilih = document.getElementById('kursi_dipilih').value;
    const totalHarga = document.getElementById('total_harga').value;
    
    console.log('Form validation:', {namaLengkap, email, noHp, routeId, tanggal, kursiDipilih, totalHarga});
    
    if (!namaLengkap) {
        e.preventDefault();
        alert('Nama lengkap harus diisi!');
        return false;
    }
    
    if (!email) {
        e.preventDefault();
        alert('Email harus diisi!');
        return false;
    }
    
    if (!noHp) {
        e.preventDefault();
        alert('No HP harus diisi!');
        return false;
    }
    
    if (!routeId) {
        e.preventDefault();
        alert('Silakan pilih rute!');
        return false;
    }
    
    if (!tanggal) {
        e.preventDefault();
        alert('Silakan pilih tanggal berangkat!');
        return false;
    }
    
    if (!kursiDipilih || kursiDipilih.trim() === '') {
        e.preventDefault();
        alert('Silakan pilih kursi terlebih dahulu!');
        return false;
    }
    
    if (!totalHarga || totalHarga == 0) {
        e.preventDefault();
        alert('Total harga tidak valid!');
        return false;
    }
    
    // Konfirmasi sebelum submit
    if (!confirm('Apakah Anda yakin ingin membuat pemesanan tunai ini?\n\nNama: ' + namaLengkap + '\nKursi: ' + kursiDipilih + '\nTotal: Rp ' + parseInt(totalHarga).toLocaleString('id-ID'))) {
        e.preventDefault();
        return false;
    }
    
    console.log('Form submitted successfully');
    return true;
});

</script>

<?php include 'includes/footer.php'; ?>
