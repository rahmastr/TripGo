<?php
require_once 'config.php';

// Set page title
$page_title = SITE_NAME . ' - ' . SITE_TAGLINE;
$navbar_fixed = true;

// Ambil semua kota unik untuk dropdown
$kota_asal_list = $conn->query("SELECT DISTINCT kota_asal FROM rute ORDER BY kota_asal")->fetchAll(PDO::FETCH_COLUMN);
$kota_tujuan_list = $conn->query("SELECT DISTINCT kota_tujuan FROM rute ORDER BY kota_tujuan")->fetchAll(PDO::FETCH_COLUMN);

// Include header
require_once 'includes/header.php';

// Include navbar
require_once 'includes/navbar.php';
?>

<?php if (isset($_GET['message']) && $_GET['message'] == 'account_deleted'): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> Akun Anda telah berhasil dihapus.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center" style="min-height: 60vh; padding-top: 100px;">
                <div class="col-lg-6 text-white">
                    <h1 class="display-3 fw-bold mb-4">Perjalanan Nyaman Bersama TripGo</h1>
                    <p class="lead mb-4">Pesan tiket bus dengan mudah, cepat, dan aman. Nikmati perjalanan Anda ke berbagai destinasi dengan harga terbaik.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&h=400&fit=crop" alt="Bus TripGo" class="img-fluid rounded shadow-lg" style="max-width: 100%; height: auto;">
                </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Form Section -->
    <section class="booking-form-section" style="margin-top: -60px; position: relative; z-index: 10;">
        <div class="container">
            <div class="card shadow-lg border-0 p-4">
                <form method="GET" action="search.php">
                    <input type="hidden" name="search" value="1">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-geo-alt-fill text-primary"></i> Kota Asal
                            </label>
                            <select name="kota_asal" class="form-select form-select-lg" required>
                                <option value="">Pilih Kota Asal</option>
                                <?php foreach ($kota_asal_list as $kota): ?>
                                <option value="<?php echo htmlspecialchars($kota); ?>" <?php echo (isset($_GET['kota_asal']) && $_GET['kota_asal'] == $kota) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kota); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-geo-fill text-primary"></i> Kota Tujuan
                            </label>
                            <select name="kota_tujuan" class="form-select form-select-lg" required>
                                <option value="">Pilih Kota Tujuan</option>
                                <?php foreach ($kota_tujuan_list as $kota): ?>
                                <option value="<?php echo htmlspecialchars($kota); ?>" <?php echo (isset($_GET['kota_tujuan']) && $_GET['kota_tujuan'] == $kota) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kota); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calendar-event text-primary"></i> Tanggal
                            </label>
                            <input type="date" name="tanggal" class="form-control form-control-lg" min="<?php echo date('Y-m-d'); ?>" value="<?php echo $_GET['tanggal'] ?? date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-lg-1 col-md-2">
                            <label class="form-label fw-bold">
                                <i class="bi bi-people-fill text-primary"></i> Jumlah
                            </label>
                            <input type="number" name="jumlah" class="form-control form-control-lg" value="<?php echo $_GET['jumlah'] ?? 1; ?>" min="1" max="10">
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-search"></i> Cari Bus
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Tentang Kami Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=600&h=400&fit=crop" alt="Tentang TripGo" class="img-fluid rounded shadow">
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Tentang TripGo</h2>
                    <p class="text-muted mb-3 text-justify">TripGo adalah aplikasi pemesanan tiket bus yang dirancang untuk memudahkan perjalanan Anda. Kami menghadirkan solusi perjalanan yang cepat, transparan, dan nyaman mulai dari pencarian rute, pemilihan kursi, hingga pembayaran online dalam satu platform.</p>
                    <p class="text-muted mb-4 text-justify">TripGo percaya bahwa setiap perjalanan memiliki tujuan. Karena itu, kami hadir bukan hanya sebagai aplikasi, tetapi sebagai partner perjalanan Anda lebih mudah, lebih fleksibel, dan lebih menyenangkan.</p>
                    <a href="tentang.php" class="btn btn-success btn-lg">
                        <i class="bi bi-arrow-right-circle"></i> Selengkapnya
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Keunggulan Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <h2 class="text-center mb-5 fw-bold">Mengapa Memilih TripGo?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="feature-icon bg-primary text-white rounded-circle mb-3 mx-auto">
                                <i class="bi bi-shield-check fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Aman & Terpercaya</h5>
                            <p class="card-text">Sistem pemesanan yang aman dan terjamin dengan pembayaran tunai di kasir.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="feature-icon bg-success text-white rounded-circle mb-3 mx-auto">
                                <i class="bi bi-lightning-charge fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">Pemesanan Cepat</h5>
                            <p class="card-text">Proses pemesanan tiket yang mudah dan cepat, hanya dalam hitungan menit.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="feature-icon bg-warning text-white rounded-circle mb-3 mx-auto">
                                <i class="bi bi-qr-code fs-1"></i>
                            </div>
                            <h5 class="card-title fw-bold">E-Ticket Digital</h5>
                            <p class="card-text">Tiket elektronik dengan QR code untuk kemudahan check-in.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cara Memesan Section -->
    <section class="py-5">
        <div class="container py-5">
            <h2 class="text-center mb-5 fw-bold">Cara Memesan Tiket</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <h2 class="fw-bold mb-0">1</h2>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Pilih Rute & Tanggal</h5>
                            <p class="card-text text-muted">Masukkan kota asal, tujuan, dan tanggal keberangkatan Anda di form pencarian.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <h2 class="fw-bold mb-0">2</h2>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Pilih Bus & Kursi</h5>
                            <p class="card-text text-muted">Pilih bus yang sesuai dengan jadwal Anda dan pilih nomor kursi favorit.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <h2 class="fw-bold mb-0">3</h2>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Isi Data Penumpang</h5>
                            <p class="card-text text-muted">Lengkapi data penumpang dengan benar sesuai identitas yang valid.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <h2 class="fw-bold mb-0">4</h2>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Bayar & Cetak Tiket</h5>
                            <p class="card-text text-muted">Lakukan pembayaran dan cetak tiket.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kelebihan Layanan Section -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <div class="container py-5">
            <h2 class="text-center display-5 fw-bold mb-5">Kelebihan Layanan Kami</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="bg-white text-success rounded-circle mx-auto mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-currency-dollar fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Tanpa Biaya Tambahan</h5>
                        <p>Harga yang Anda lihat adalah harga yang Anda bayar, tanpa ada biaya tersembunyi.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="bg-white text-success rounded-circle mx-auto mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-credit-card fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Pembayaran Online</h5>
                        <p>Bayar dengan mudah melalui transfer bank,<br>e-wallet, atau kartu kredit secara online.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-center">
                        <div class="bg-white text-success rounded-circle mx-auto mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-chat-square-text fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Pilih Tempat Duduk</h5>
                        <p>Pilih tempat duduk yang Anda mau sesuai dengan kenyamanan dan preferensi Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>



<?php
// Include footer (handles scripts and closing tags)
require_once 'includes/footer.php';
?>
