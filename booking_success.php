<?php
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Validasi parameter
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data pemesanan
$stmt = $conn->prepare("
    SELECT 
        p.*,
        r.kota_asal,
        r.kota_tujuan,
        r.jam_berangkat,
        r.durasi_perjalanan,
        r.harga,
        b.nama_bus,
        b.nomor_bus,
        b.kapasitas,
        u.nama_lengkap,
        u.email,
        u.no_hp,
        p.midtrans_order_id as booking_code,
        p.snap_token
    FROM pemesanan p
    JOIN rute r ON p.route_id = r.id
    JOIN bus b ON r.bus_id = b.id
    JOIN pengguna u ON p.user_id = u.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = 'Pemesanan tidak ditemukan!';
    header('Location: index.php');
    exit();
}

$page_title = 'Pemesanan Berhasil - ' . SITE_NAME;
$navbar_fixed = false;

// Determine payment method badge
$payment_badge = '';
$payment_icon = '';
if ($booking['metode_pembayaran'] === 'cash') {
    $payment_badge = 'Bayar di Kasir';
    $payment_icon = 'bi bi-cash';
} else {
    // Online payment
    if ($booking['status_pembayaran'] === 'pending') {
        $payment_badge = 'Menunggu Pembayaran';
        $payment_icon = 'bi bi-clock-history';
    } elseif ($booking['status_pembayaran'] === 'success') {
        $payment_badge = 'Sudah Dibayar';
        $payment_icon = 'bi bi-check-circle-fill';
    } elseif ($booking['status_pembayaran'] === 'failed') {
        $payment_badge = 'Pembayaran Gagal';
        $payment_icon = 'bi bi-x-circle-fill';
    } else {
        // Unknown status
        $payment_badge = 'Status: ' . ucfirst($booking['status_pembayaran']);
        $payment_icon = 'bi bi-question-circle';
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
.success-icon {
    font-size: 5rem;
    color: #198754;
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.ticket-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.ticket-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
    pointer-events: none;
}

.ticket-divider {
    border-top: 2px dashed rgba(255, 255, 255, 0.3);
    margin: 20px 0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.qr-code-container {
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.qr-instruction {
    background: #f8f9fa;
    border-left: 4px solid #059669;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}

.payment-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #fbbf24;
    color: #78350f;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.75rem;
}
</style>

<!-- Success Section -->
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <!-- Success Message -->
                <div class="text-center mb-4">
                    <h2 class="fw-bold mb-2">Pemesanan Berhasil!</h2>
                    <p class="text-muted mb-0">Booking ID: <strong class="text-primary"><?php echo htmlspecialchars($booking['booking_code']); ?></strong></p>
                </div>

                <!-- QR Code -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center p-3">
                        <?php if ($booking['metode_pembayaran'] === 'cash'): ?>
                            <h6 class="fw-bold mb-3" style="font-size: 0.95rem;"><i class="bi bi-qr-code-scan"></i> QR Code Pembayaran</h6>
                            <div class="qr-code-container d-flex justify-content-center">
                                <div id="qrcode"></div>
                            </div>
                        <?php else: ?>
                            <h6 class="fw-bold mb-3" style="font-size: 0.95rem;"><i class="bi bi-credit-card"></i> Pembayaran Online</h6>
                            <?php if ($booking['status_pembayaran'] === 'pending'): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-clock-history"></i> Menunggu pembayaran...
                                </div>
                                <button id="pay-button" class="btn btn-primary btn-lg px-5 mb-2">
                                    <i class="bi bi-wallet2"></i> Bayar Sekarang
                                </button>
                                <p class="text-muted mt-2 mb-3" style="font-size: 0.85rem;">
                                    Klik tombol di atas untuk melanjutkan pembayaran melalui Midtrans
                                </p>
                                
                                <div class="border-top pt-3 mt-3">
                                    <p class="text-muted mb-2" style="font-size: 0.85rem;">
                                        <strong>Sudah bayar tapi status belum berubah?</strong><br>
                                        Klik tombol di bawah untuk sinkronisasi status:
                                    </p>
                                    <button id="sync-button" class="btn btn-warning btn-sm">
                                        <i class="bi bi-arrow-repeat"></i> Refresh Status Pembayaran
                                    </button>
                                    <div id="sync-result" class="mt-2"></div>
                                </div>
                            <?php elseif ($booking['status_pembayaran'] === 'success'): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill"></i> Pembayaran berhasil! Tiket Anda sudah aktif.
                                </div>
                                <div class="qr-code-container d-flex justify-content-center">
                                    <div id="qrcode"></div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-x-circle-fill"></i> Pembayaran dibatalkan atau gagal.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ticket Card -->
                <div class="ticket-card shadow-lg mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;">
                                    <i class="bi bi-ticket-perforated-fill"></i> Detail Perjalanan
                                </h6>
                                <span class="payment-badge">
                                    <i class="<?php echo $payment_icon; ?>"></i> <?php echo $payment_badge; ?>
                                </span>
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="opacity-75" style="font-size: 0.7rem;">Dari</div>
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['kota_asal']); ?></h6>
                                        <div class="fw-semibold" style="font-size: 0.85rem;"><?php echo date('H:i', strtotime($booking['jam_berangkat'])); ?></div>
                                    </div>
                                    <div class="text-center px-2">
                                        <i class="bi bi-arrow-right" style="font-size: 1.2rem;"></i>
                                        <div style="font-size: 0.65rem;"><?php echo htmlspecialchars($booking['durasi_perjalanan']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="opacity-75" style="font-size: 0.7rem;">Ke</div>
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['kota_tujuan']); ?></h6>
                                        <div style="font-size: 0.75rem;">Tiba</div>
                                    </div>
                                </div>
                            </div>

                            <div class="ticket-divider"></div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="opacity-75" style="font-size: 0.7rem;">Bus</div>
                                    <div class="fw-bold" style="font-size: 0.85rem;"><?php echo htmlspecialchars($booking['nama_bus']); ?></div>
                                    <div style="font-size: 0.7rem;"><?php echo htmlspecialchars($booking['nomor_bus']); ?></div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="opacity-75" style="font-size: 0.7rem;">Tanggal</div>
                                    <div class="fw-bold" style="font-size: 0.85rem;"><?php echo date('d M Y', strtotime($booking['tanggal_berangkat'])); ?></div>
                                </div>
                            </div>

                            <div class="ticket-divider"></div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="opacity-75" style="font-size: 0.7rem;">Kursi</div>
                                    <div class="fw-bold" style="font-size: 1rem;"><?php echo htmlspecialchars($booking['kursi_dipilih']); ?></div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="opacity-75" style="font-size: 0.7rem;">Penumpang</div>
                                    <div class="fw-bold" style="font-size: 0.85rem;"><?php echo $booking['jumlah_penumpang']; ?> Orang</div>
                                </div>
                            </div>
                </div>

                <!-- Data Pemesan -->
                <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-0 py-2">
                                <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;"><i class="bi bi-person-circle"></i> Data Pemesan</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="info-row py-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">Nama Lengkap</div>
                                    <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($booking['nama_lengkap']); ?></div>
                                </div>
                                <div class="info-row py-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">Email</div>
                                    <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($booking['email']); ?></div>
                                </div>
                                <div class="info-row py-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">No. Handphone</div>
                                    <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($booking['no_hp']); ?></div>
                                </div>
                            </div>
                </div>

                <!-- Ringkasan Pembayaran -->
                <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white border-0 py-2">
                                <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;"><i class="bi bi-receipt"></i> Ringkasan Pembayaran</h6>
                            </div>
                            <div class="card-body py-2">
                                <div class="info-row py-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">Harga per Kursi</div>
                                    <div class="fw-semibold" style="font-size: 0.9rem;">Rp <?php echo number_format($booking['harga'], 0, ',', '.'); ?></div>
                                </div>
                                <div class="info-row py-2">
                                    <div class="text-muted" style="font-size: 0.8rem;">Jumlah Kursi</div>
                                    <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo $booking['jumlah_penumpang']; ?> Kursi</div>
                                </div>
                                <div class="info-row bg-light p-2 rounded mt-2">
                                    <div class="fw-bold" style="font-size: 0.95rem;">Total Pembayaran</div>
                                    <div class="fw-bold text-primary" style="font-size: 1.3rem;">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                                </div>
                            </div>
                </div>

                <!-- Instruksi -->
                <?php if ($booking['metode_pembayaran'] === 'cash'): ?>
                <div class="alert alert-warning mb-3 py-2">
                            <h6 class="fw-bold mb-2" style="font-size: 0.9rem;"><i class="bi bi-exclamation-triangle-fill"></i> Instruksi Pembayaran:</h6>
                            <ol class="mb-0 ps-3" style="font-size: 0.85rem;">
                                <li>Tunjukkan QR Code di atas kepada kasir</li>
                                <li>Lakukan pembayaran dan scan sebelum <strong>2 jam keberangkatan</strong></li>
                            </ol>
                </div>
                <?php elseif ($booking['status_pembayaran'] === 'success'): ?>
                <div class="alert alert-success mb-3 py-2">
                            <h6 class="fw-bold mb-2" style="font-size: 0.9rem;"><i class="bi bi-check-circle-fill"></i> Tiket Anda Sudah Aktif!</h6>
                            <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                                <li>Tunjukkan QR Code saat boarding</li>
                                <li>Datang 30 menit sebelum keberangkatan</li>
                            </ul>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-3 py-2">
                            <h6 class="fw-bold mb-2" style="font-size: 0.9rem;"><i class="bi bi-info-circle-fill"></i> Menunggu Pembayaran:</h6>
                            <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                                <li>Selesaikan pembayaran melalui Midtrans</li>
                                <li>Pembayaran akan expired dalam <strong>2 jam</strong></li>
                                <li>QR Code akan muncul setelah pembayaran berhasil</li>
                            </ul>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <?php if ($booking['metode_pembayaran'] === 'online'): ?>
                    <a href="debug_webhook.php?order_id=<?php echo $booking['booking_code']; ?>" class="btn btn-outline-warning" target="_blank">
                        <i class="bi bi-bug"></i> Debug Payment Status
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-house"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- Midtrans Snap -->
<?php if ($booking['metode_pembayaran'] === 'online' && $booking['snap_token']): ?>
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo MIDTRANS_CLIENT_KEY; ?>"></script>
<?php endif; ?>

<script>
<?php if ($booking['metode_pembayaran'] === 'cash' || $booking['status_pembayaran'] === 'success'): ?>
// Generate QR Code dengan booking code
const bookingData = '<?php echo $booking['booking_code']; ?>';

const qrcode = new QRCode(document.getElementById("qrcode"), {
    text: bookingData,
    width: 250,
    height: 250,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
<?php endif; ?>

<?php if ($booking['metode_pembayaran'] === 'online' && $booking['snap_token'] && $booking['status_pembayaran'] === 'pending'): ?>
// Midtrans Snap Payment
const payButton = document.getElementById('pay-button');
payButton.addEventListener('click', function() {
    snap.pay('<?php echo $booking['snap_token']; ?>', {
        onSuccess: function(result) {
            console.log('Payment success:', result);
            alert('Pembayaran berhasil! Halaman akan di-refresh.');
            window.location.reload();
        },
        onPending: function(result) {
            console.log('Payment pending:', result);
            alert('Menunggu pembayaran Anda. Silakan selesaikan pembayaran.');
        },
        onError: function(result) {
            console.log('Payment error:', result);
            alert('Pembayaran gagal. Silakan coba lagi.');
        },
        onClose: function() {
            console.log('Payment popup closed');
        }
    });
});

// Manual sync button
const syncButton = document.getElementById('sync-button');
if (syncButton) {
    syncButton.addEventListener('click', function() {
        const resultDiv = document.getElementById('sync-result');
        const orderId = '<?php echo $booking['booking_code']; ?>';
        
        syncButton.disabled = true;
        syncButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Mengecek...';
        resultDiv.innerHTML = '';
        
        fetch('sync_payment_manual.php?order_id=' + orderId)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.db_status === 'success') {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success alert-sm mb-0">
                                <i class="bi bi-check-circle-fill"></i> <strong>Pembayaran Berhasil!</strong><br>
                                <small>Halaman akan di-refresh otomatis...</small>
                            </div>
                        `;
                        setTimeout(() => location.reload(), 1500);
                    } else if (data.db_status === 'pending') {
                        resultDiv.innerHTML = `
                            <div class="alert alert-warning alert-sm mb-0">
                                <i class="bi bi-clock"></i> <small>Status masih pending di Midtrans. Pastikan pembayaran sudah selesai.</small>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="alert alert-danger alert-sm mb-0">
                                <i class="bi bi-x-circle"></i> <small>Status: ${data.db_status}</small>
                            </div>
                        `;
                    }
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger alert-sm mb-0">
                            <small>${data.message}</small>
                        </div>
                    `;
                }
                syncButton.disabled = false;
                syncButton.innerHTML = '<i class="bi bi-arrow-repeat"></i> Refresh Status Pembayaran';
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger alert-sm mb-0">
                        <small>Error: ${error.message}</small>
                    </div>
                `;
                syncButton.disabled = false;
                syncButton.innerHTML = '<i class="bi bi-arrow-repeat"></i> Refresh Status Pembayaran';
            });
    });
}

// Auto-check payment status every 10 seconds (increased from 5)
setInterval(function() {
    fetch('payment_status.php?order_id=<?php echo $booking['booking_code']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.payment_status === 'success') {
                // Payment successful, reload page to show QR code
                window.location.reload();
            }
        })
        .catch(error => console.error('Error checking payment status:', error));
}, 10000); // Check every 10 seconds
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
