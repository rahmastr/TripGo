<?php
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM pengguna WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data pemesanan user
$stmt = $conn->prepare("
    SELECT 
        p.*,
        r.kota_asal,
        r.kota_tujuan,
        r.jam_berangkat,
        b.nama_bus,
        p.midtrans_order_id as booking_code
    FROM pemesanan p
    JOIN rute r ON p.route_id = r.id
    JOIN bus b ON r.bus_id = b.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle hapus akun
if (isset($_POST['delete_account']) && $_POST['delete_account'] == 'confirm') {
    try {
        $conn->beginTransaction();
        
        // Hapus kursi terpesan
        $stmt = $conn->prepare("DELETE kt FROM kursi_terpesan kt INNER JOIN pemesanan p ON kt.booking_id = p.id WHERE p.user_id = ?");
        $stmt->execute([$user_id]);
        
        // Hapus pemesanan
        $stmt = $conn->prepare("DELETE FROM pemesanan WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Hapus user
        $stmt = $conn->prepare("DELETE FROM pengguna WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $conn->commit();
        
        // Logout dan redirect
        session_destroy();
        header('Location: index.php?message=account_deleted');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = 'Gagal menghapus akun: ' . $e->getMessage();
    }
}

$page_title = 'Profile Saya - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 30px;
}

.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 20px;
}

.stat-box {
    text-align: center;
    padding: 20px;
    border-radius: 10px;
    background: #f8f9fa;
}

.stat-box h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #059669;
}

.stat-box p {
    margin: 5px 0 0 0;
    color: #6c757d;
}

.booking-item {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.booking-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    min-width: 70px;
    text-align: center;
}

.action-buttons .btn {
    border-radius: 20px;
    padding: 8px 20px;
    font-weight: 600;
}
</style>

<!-- Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi bi-person-circle" style="font-size: 4rem;"></i>
                    </div>
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h2>
                        <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="mb-0"><i class="bi bi-phone"></i> <?php echo htmlspecialchars($user['no_hp']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Statistik -->
        <div class="col-md-12 mb-4">
            <div class="profile-card">
                <h5 class="mb-4"><i class="bi bi-graph-up"></i> Ringkasan Pemesanan</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3><?php echo count($bookings); ?></h3>
                            <p>Total Pemesanan</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3><?php echo count(array_filter($bookings, function($b) { return $b['status_pembayaran'] == 'success'; })); ?></h3>
                            <p>Pembayaran Berhasil</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <h3>Rp <?php echo number_format(array_sum(array_map(function($b) { return $b['status_pembayaran'] == 'success' ? $b['total_harga'] : 0; }, $bookings)), 0, ',', '.'); ?></h3>
                            <p>Total Pengeluaran</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Pemesanan -->
        <div class="col-md-12 mb-4">
            <div class="profile-card">
                <h5 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pemesanan</h5>
                
                <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Belum ada pemesanan</p>
                    <a href="search.php" class="btn btn-primary">Pesan Tiket Sekarang</a>
                </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <div class="booking-item">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6 class="mb-1 text-primary"><?php echo htmlspecialchars($booking['booking_code']); ?></h6>
                                <small class="text-muted"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></small>
                            </div>
                            <div class="col-md-4">
                                <strong><?php echo htmlspecialchars($booking['kota_asal']); ?> → <?php echo htmlspecialchars($booking['kota_tujuan']); ?></strong><br>
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($booking['tanggal_berangkat'])); ?> | 
                                    <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($booking['jam_berangkat'])); ?>
                                </small><br>
                                <small><i class="bi bi-person"></i> <?php echo $booking['jumlah_penumpang']; ?> Penumpang | Kursi: <?php echo htmlspecialchars($booking['kursi_dipilih']); ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <strong class="text-success">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></strong>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php
                                $status_class = 'bg-secondary';
                                $status_text = 'Pending';
                                
                                if ($booking['status_pembayaran'] == 'success') {
                                    $status_class = 'bg-success';
                                    $status_text = 'Lunas';
                                } elseif ($booking['status_pembayaran'] == 'pending') {
                                    $status_class = 'bg-warning';
                                    $status_text = 'Menunggu';
                                } elseif ($booking['status_pembayaran'] == 'failed') {
                                    $status_class = 'bg-danger';
                                    $status_text = 'Gagal';
                                }
                                ?>
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <span class="status-badge <?php echo $status_class; ?> text-white"><?php echo $status_text; ?></span>
                                    <button class="btn btn-sm btn-primary" onclick="viewBookingDetail(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-md-12">
            <div class="profile-card">
                <h5 class="mb-4"><i class="bi bi-gear"></i> Pengaturan Akun</h5>
                <div class="action-buttons d-flex gap-3 flex-wrap">
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-house"></i> Kembali ke Beranda
                    </a>
                    <a href="logout.php" class="btn btn-warning">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i> Hapus Akun
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Akun -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus Akun</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan.</p>
                <p>Dengan menghapus akun, semua data Anda termasuk riwayat pemesanan akan dihapus secara permanen.</p>
                <p>Apakah Anda yakin ingin menghapus akun?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_account" value="confirm">
                    <button type="submit" class="btn btn-danger">Ya, Hapus Akun Saya</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pemesanan -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-ticket-detailed"></i> Detail Pemesanan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Informasi Pemesanan -->
                    <div class="col-md-7">
                        <h6 class="text-primary mb-3"><i class="bi bi-info-circle"></i> Informasi Pemesanan</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="150"><strong>Kode Booking</strong></td>
                                <td id="detail_booking_code"></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Pesan</strong></td>
                                <td id="detail_created_at"></td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td id="detail_status"></td>
                            </tr>
                            <tr>
                                <td><strong>Metode Bayar</strong></td>
                                <td id="detail_payment_method"></td>
                            </tr>
                        </table>

                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-bus-front"></i> Informasi Perjalanan</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td width="150"><strong>Rute</strong></td>
                                <td id="detail_route"></td>
                            </tr>
                            <tr>
                                <td><strong>Bus</strong></td>
                                <td id="detail_bus"></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal</strong></td>
                                <td id="detail_date"></td>
                            </tr>
                            <tr>
                                <td><strong>Jam</strong></td>
                                <td id="detail_time"></td>
                            </tr>
                            <tr>
                                <td><strong>Kursi</strong></td>
                                <td id="detail_seats"></td>
                            </tr>
                            <tr>
                                <td><strong>Penumpang</strong></td>
                                <td id="detail_passengers"></td>
                            </tr>
                            <tr>
                                <td><strong>Total Harga</strong></td>
                                <td id="detail_price" class="text-success fw-bold"></td>
                            </tr>
                        </table>
                    </div>

                    <!-- QR Code -->
                    <div class="col-md-5 text-center">
                        <h6 class="text-primary mb-3"><i class="bi bi-qr-code"></i> QR Code Tiket</h6>
                        <div class="border rounded p-3 bg-light">
                            <div id="qrcode" class="mb-3"></div>
                            <p class="small text-muted mb-3">Tunjukkan QR ini saat boarding</p>
                            <button class="btn btn-success btn-sm" onclick="downloadQR()">
                                <i class="bi bi-file-earmark-pdf"></i> Unduh E-Ticket (PDF)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<!-- jsPDF Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
let currentQRCode = null;
let currentBookingCode = '';
let currentBookingData = null;

function viewBookingDetail(booking) {
    // Simpan data booking untuk PDF
    currentBookingData = booking;
    
    // Set informasi pemesanan
    document.getElementById('detail_booking_code').innerHTML = '<strong class="text-primary">' + booking.booking_code + '</strong>';
    document.getElementById('detail_created_at').textContent = formatDateTime(booking.created_at);
    
    // Status
    let statusBadge = '';
    if (booking.status_pembayaran === 'success') {
        statusBadge = '<span class="badge bg-success">Lunas</span>';
    } else if (booking.status_pembayaran === 'pending') {
        statusBadge = '<span class="badge bg-warning">Menunggu</span>';
    } else if (booking.status_pembayaran === 'failed') {
        statusBadge = '<span class="badge bg-danger">Gagal</span>';
    }
    document.getElementById('detail_status').innerHTML = statusBadge;
    
    // Metode pembayaran
    const paymentMethod = booking.is_offline ? '<span class="badge bg-info">Tunai</span>' : '<span class="badge bg-primary">Online</span>';
    document.getElementById('detail_payment_method').innerHTML = paymentMethod;
    
    // Informasi perjalanan
    document.getElementById('detail_route').innerHTML = '<strong>' + booking.kota_asal + ' → ' + booking.kota_tujuan + '</strong>';
    document.getElementById('detail_bus').textContent = booking.nama_bus;
    document.getElementById('detail_date').textContent = formatDate(booking.tanggal_berangkat);
    document.getElementById('detail_time').textContent = booking.jam_berangkat;
    document.getElementById('detail_seats').innerHTML = '<span class="badge bg-secondary">' + booking.kursi_dipilih + '</span>';
    document.getElementById('detail_passengers').textContent = booking.jumlah_penumpang + ' orang';
    document.getElementById('detail_price').textContent = 'Rp ' + parseInt(booking.total_harga).toLocaleString('id-ID');
    
    // Generate QR Code
    currentBookingCode = booking.booking_code;
    generateQRCode(booking.booking_code);
    
    // Show modal
    new bootstrap.Modal(document.getElementById('bookingDetailModal')).show();
}

function generateQRCode(bookingCode) {
    // Clear previous QR code
    document.getElementById('qrcode').innerHTML = '';
    
    // Generate new QR code
    currentQRCode = new QRCode(document.getElementById('qrcode'), {
        text: bookingCode,
        width: 200,
        height: 200,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });
}

function downloadQR() {
    const qrCanvas = document.querySelector('#qrcode canvas');
    if (!qrCanvas) {
        alert('QR Code belum siap, silakan coba lagi');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
    });
    
    // Get QR code as base64
    const qrDataUrl = qrCanvas.toDataURL('image/png');
    
    // Header
    doc.setFillColor(5, 150, 105);
    doc.rect(0, 0, 210, 40, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont(undefined, 'bold');
    doc.text('TRIPGO', 105, 20, { align: 'center' });
    
    doc.setFontSize(12);
    doc.setFont(undefined, 'normal');
    doc.text('E-Ticket Bus', 105, 30, { align: 'center' });
    
    // Reset text color
    doc.setTextColor(0, 0, 0);
    
    // Booking Code
    doc.setFontSize(16);
    doc.setFont(undefined, 'bold');
    doc.text('Kode Booking', 20, 55);
    doc.setFontSize(14);
    doc.setFont(undefined, 'normal');
    doc.text(currentBookingData.booking_code, 20, 65);
    
    // Status
    doc.setFontSize(10);
    if (currentBookingData.status_pembayaran === 'success') {
        doc.setFillColor(34, 197, 94);
        doc.roundedRect(150, 50, 40, 8, 2, 2, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('LUNAS', 170, 55, { align: 'center' });
    }
    
    doc.setTextColor(0, 0, 0);
    
    // Line separator
    doc.setDrawColor(200, 200, 200);
    doc.line(20, 75, 190, 75);
    
    // Informasi Perjalanan
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Informasi Perjalanan', 20, 85);
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    
    let y = 95;
    doc.text('Rute:', 20, y);
    doc.setFont(undefined, 'bold');
    doc.text(currentBookingData.kota_asal + ' → ' + currentBookingData.kota_tujuan, 60, y);
    doc.setFont(undefined, 'normal');
    
    y += 10;
    doc.text('Bus:', 20, y);
    doc.text(currentBookingData.nama_bus, 60, y);
    
    y += 10;
    doc.text('Tanggal:', 20, y);
    doc.text(formatDate(currentBookingData.tanggal_berangkat), 60, y);
    
    y += 10;
    doc.text('Jam Berangkat:', 20, y);
    doc.text(currentBookingData.jam_berangkat, 60, y);
    
    y += 10;
    doc.text('Kursi:', 20, y);
    doc.text(currentBookingData.kursi_dipilih, 60, y);
    
    y += 10;
    doc.text('Jumlah Penumpang:', 20, y);
    doc.text(currentBookingData.jumlah_penumpang + ' orang', 60, y);
    
    y += 10;
    doc.text('Total Harga:', 20, y);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(5, 150, 105);
    doc.text('Rp ' + parseInt(currentBookingData.total_harga).toLocaleString('id-ID'), 60, y);
    doc.setTextColor(0, 0, 0);
    doc.setFont(undefined, 'normal');
    
    // Line separator
    y += 10;
    doc.setDrawColor(200, 200, 200);
    doc.line(20, y, 190, y);
    
    // QR Code
    y += 10;
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('QR Code Tiket', 105, y, { align: 'center' });
    
    y += 5;
    // Add QR code image (centered)
    doc.addImage(qrDataUrl, 'PNG', 70, y, 70, 70);
    
    y += 75;
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text('Tunjukkan QR Code ini saat boarding', 105, y, { align: 'center' });
    
    // Footer
    doc.setFontSize(9);
    doc.setTextColor(128, 128, 128);
    doc.text('Dicetak pada: ' + new Date().toLocaleString('id-ID'), 105, 280, { align: 'center' });
    doc.text('TripGo - Perjalanan Anda, Prioritas Kami', 105, 285, { align: 'center' });
    
    // Save PDF
    doc.save('E-Ticket-' + currentBookingData.booking_code + '.pdf');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('id-ID', options);
}
</script>

<?php include 'includes/footer.php'; ?>
