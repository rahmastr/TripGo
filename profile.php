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
    font-size: 0.85rem;
    font-weight: 600;
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
                                <span class="status-badge <?php echo $status_class; ?> text-white"><?php echo $status_text; ?></span>
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

<?php include 'includes/footer.php'; ?>
