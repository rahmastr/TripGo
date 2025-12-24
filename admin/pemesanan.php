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

// Ambil semua pemesanan dengan detail (menghapus kolom verified_by yang tidak ada)
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

// Ambil rute untuk dropdown
$routes = $conn->query("
    SELECT r.id, CONCAT(r.kota_asal, ' - ', r.kota_tujuan, ' (', TIME_FORMAT(r.jam_berangkat, '%H:%i'), ')') as display_name, r.harga
    FROM rute r
    ORDER BY r.kota_asal
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil pengguna untuk dropdown
$users = $conn->query("SELECT id, nama_lengkap, email FROM pengguna WHERE role = 'customer' ORDER BY nama_lengkap")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Data Pemesanan';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Data Pemesanan</h2>
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
                    <th width="80"><small>Status</small></th>
                    <th width="60"><small>Aksi</small></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr>
                    <td colspan="7" class="text-center py-3">
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

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda ingin menghapus pemesanan dengan kode booking <strong id="deleteBookingCode"></strong>?</p>
                <input type="hidden" id="deleteBookingId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Ya</button>
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
    // Abaikan kesalahan pemindaian
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
                
                // Muat ulang halaman setelah 2 detik
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
    // Muat data penumpang melalui AJAX
    $.ajax({
        url: '../get_passengers.php',
        method: 'GET',
        data: { booking_id: booking.id },
        dataType: 'json',
        success: function(response) {
            let passengerTableHtml = '';
            
            if (response.success && response.passengers.length > 0) {
                passengerTableHtml = `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-muted"><i class="bi bi-people-fill"></i> Data Penumpang</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Kursi</th>
                                            <th>Nama Lengkap</th>
                                            <th>No HP</th>
                                            <th>Email</th>
                                            <th>Jenis Kelamin</th>
                                            <th>Usia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                response.passengers.forEach(p => {
                    passengerTableHtml += `
                        <tr>
                            <td><span class="badge bg-info">${p.nomor_kursi}</span></td>
                            <td><strong>${p.nama_lengkap}</strong></td>
                            <td>${p.no_hp}</td>
                            <td>${p.email || '-'}</td>
                            <td>${p.jenis_kelamin || '-'}</td>
                            <td>${p.usia || '-'}</td>
                        </tr>
                    `;
                });
                
                passengerTableHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                passengerTableHtml = `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-muted"><i class="bi bi-people-fill"></i> Data Penumpang</h6>
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle"></i> Data penumpang tidak tersedia untuk booking ini.
                            </div>
                        </div>
                    </div>
                `;
            }
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-person-circle"></i> Data Pemesan</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Nama</strong></td><td>${booking.nama_lengkap}</td></tr>
                            <tr><td><strong>Email</strong></td><td>${booking.email}</td></tr>
                            <tr><td><strong>No HP</strong></td><td>${booking.no_hp}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-bus-front"></i> Informasi Perjalanan</h6>
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
                        <h6 class="text-muted"><i class="bi bi-receipt"></i> Informasi Pembayaran</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Booking Code</strong></td><td><span class="text-primary fw-bold">${booking.booking_code}</span></td></tr>
                            <tr><td><strong>Jumlah</strong></td><td>${booking.jumlah_penumpang} penumpang</td></tr>
                            <tr><td><strong>Kursi</strong></td><td><span class="badge bg-secondary">${booking.kursi_dipilih}</span></td></tr>
                            <tr><td><strong>Total</strong></td><td><strong class="text-success">Rp ${parseInt(booking.total_harga).toLocaleString('id-ID')}</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-shield-check"></i> Status Verifikasi</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Status</strong></td><td><span class="badge ${booking.status_pembayaran === 'success' ? 'bg-success' : booking.status_pembayaran === 'pending' ? 'bg-warning' : 'bg-danger'}">${booking.status_pembayaran}</span></td></tr>
                            <tr><td><strong>QR Verified</strong></td><td>${booking.qr_used ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'}</td></tr>
                            ${booking.qr_scanned_at ? `<tr><td><strong>Verified At</strong></td><td>${booking.qr_scanned_at}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                ${passengerTableHtml}
            `;
            
            $('#detailContent').html(content);
        },
        error: function() {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-person-circle"></i> Data Pemesan</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Nama</strong></td><td>${booking.nama_lengkap}</td></tr>
                            <tr><td><strong>Email</strong></td><td>${booking.email}</td></tr>
                            <tr><td><strong>No HP</strong></td><td>${booking.no_hp}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-bus-front"></i> Informasi Perjalanan</h6>
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
                        <h6 class="text-muted"><i class="bi bi-receipt"></i> Informasi Pembayaran</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Booking Code</strong></td><td><span class="text-primary fw-bold">${booking.booking_code}</span></td></tr>
                            <tr><td><strong>Jumlah</strong></td><td>${booking.jumlah_penumpang} penumpang</td></tr>
                            <tr><td><strong>Kursi</strong></td><td><span class="badge bg-secondary">${booking.kursi_dipilih}</span></td></tr>
                            <tr><td><strong>Total</strong></td><td><strong class="text-success">Rp ${parseInt(booking.total_harga).toLocaleString('id-ID')}</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="bi bi-shield-check"></i> Status Verifikasi</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Status</strong></td><td><span class="badge ${booking.status_pembayaran === 'success' ? 'bg-success' : booking.status_pembayaran === 'pending' ? 'bg-warning' : 'bg-danger'}">${booking.status_pembayaran}</span></td></tr>
                            <tr><td><strong>QR Verified</strong></td><td>${booking.qr_used ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Ya</span>' : '<span class="badge bg-secondary">Tidak</span>'}</td></tr>
                            ${booking.qr_scanned_at ? `<tr><td><strong>Verified At</strong></td><td>${booking.qr_scanned_at}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> Gagal memuat data penumpang
                        </div>
                    </div>
                </div>
            `;
            $('#detailContent').html(content);
        }
    });
    
    // Tampilkan modal segera dengan status memuat
    $('#detailContent').html('<div class="text-center py-5"><i class="bi bi-hourglass-split fs-1"></i><p class="mt-2">Memuat data...</p></div>');
    $('#detailModal').modal('show');
}

function deleteBooking(id, bookingCode) {
    // Set data untuk modal
    document.getElementById('deleteBookingCode').textContent = bookingCode;
    document.getElementById('deleteBookingId').value = id;
    
    // Tampilkan modal konfirmasi
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function confirmDelete() {
    const id = document.getElementById('deleteBookingId').value;
    if (id) {
        window.location.href = 'pemesanan.php?action=delete&id=' + id;
    }
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
    
    // Tambahkan CSS
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
    generatePassengerFormsAdmin();
}

function generatePassengerFormsAdmin() {
    const container = document.getElementById('passengerFormsContainer');
    
    if (selectedSeats.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    const sortedSeats = selectedSeats.sort((a, b) => {
        const letterA = a.charAt(0);
        const letterB = b.charAt(0);
        const numberA = parseInt(a.substring(1));
        const numberB = parseInt(b.substring(1));
        
        if (numberA !== numberB) {
            return numberA - numberB;
        }
        return letterA.localeCompare(letterB);
    });
    
    let formsHTML = '<h6 class="text-primary mb-3 mt-3"><i class="bi bi-people-fill"></i> Data Penumpang</h6>';
    
    sortedSeats.forEach((seat, index) => {
        formsHTML += `
            <div class="passenger-form-admin border rounded p-3 mb-3 bg-light">
                <h6 class="text-secondary mb-3">
                    <i class="bi bi-person-badge"></i> Penumpang ${index + 1} - Kursi ${seat}
                </h6>
                
                <input type="hidden" name="penumpang[${index}][kursi]" value="${seat}">
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm passenger-input-admin" 
                               name="penumpang[${index}][nama]" 
                               placeholder="Sesuai identitas" 
                               required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">No HP/WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm passenger-input-admin" 
                               name="penumpang[${index}][no_hp]" 
                               placeholder="08xxxxxxxxxx" 
                               required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Email</label>
                        <input type="email" class="form-control form-control-sm" 
                               name="penumpang[${index}][email]" 
                               placeholder="email@contoh.com">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Jenis Kelamin</label>
                        <select class="form-select form-select-sm" name="penumpang[${index}][jenis_kelamin]">
                            <option value="">Pilih</option>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Jenis Identitas</label>
                        <select class="form-select form-select-sm" name="penumpang[${index}][jenis_identitas]">
                            <option value="KTP">KTP</option>
                            <option value="SIM">SIM</option>
                            <option value="Passport">Passport</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">No Identitas</label>
                        <input type="text" class="form-control form-control-sm" 
                               name="penumpang[${index}][no_identitas]" 
                               placeholder="Nomor KTP/SIM/Passport">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Usia</label>
                        <select class="form-select form-select-sm" name="penumpang[${index}][usia]">
                            <option value="Dewasa">Dewasa</option>
                            <option value="Anak-anak">Anak-anak</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = formsHTML;
}

function updateTotal() {
    const select = document.getElementById('route_select');
    const price = select.options[select.selectedIndex]?.getAttribute('data-price') || 0;
    const qty = document.getElementById('jumlah_penumpang').value || 1;
    const total = price * qty;
    document.getElementById('total_harga').value = total;
}

</script>

<?php include 'includes/footer.php'; ?>
