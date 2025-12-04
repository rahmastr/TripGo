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
                            <span class="badge bg-info" style="font-size: 0.7rem;">Tunai</span>
                        </td>
                        <td>
                            <?php
                            // Cek status berdasarkan QR scan
                            if ($booking['qr_used'] == 1):
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
</script>

<?php include 'includes/footer.php'; ?>
