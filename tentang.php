<?php
// Halaman Tentang Kami TripGo
require_once 'config.php';

// Set page title
$page_title = 'Tentang Kami - ' . SITE_NAME;
$navbar_fixed = false;

// Include header
require_once 'includes/header.php';

// Include navbar
require_once 'includes/navbar.php';

// Data statistik
$stats = [
    ['icon' => 'bi-people-fill', 'color' => 'primary', 'value' => '5 Juta+', 'label' => 'Pengguna Terdaftar'],
    ['icon' => 'bi-ticket-perforated-fill', 'color' => 'success', 'value' => '10 Juta+', 'label' => 'Tiket Terjual'],
    ['icon' => 'bi-bus-front-fill', 'color' => 'warning', 'value' => '200+', 'label' => 'Partner PO Bus'],
    ['icon' => 'bi-geo-alt-fill', 'color' => 'danger', 'value' => '1000+', 'label' => 'Rute Tersedia']
];

// Data tim
$team = [
    ['name' => 'Budi Santoso', 'position' => 'Chief Executive Officer', 'image' => 'CEO'],
    ['name' => 'Siti Nurhaliza', 'position' => 'Chief Technology Officer', 'image' => 'CTO'],
    ['name' => 'Ahmad Rizki', 'position' => 'Chief Operating Officer', 'image' => 'COO'],
    ['name' => 'Dewi Lestari', 'position' => 'Chief Marketing Officer', 'image' => 'CMO']
];
?>

    <!-- Hero Section -->
    <section class="text-white py-5" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="display-4 fw-bold mb-4">Tentang TripGo</h1>
                    
                </div>
            </div>
        </div>
    </section>

    <!-- Siapa Kami Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-4">Siapa Kami?</h2>
                    <p class="text-muted mb-3">TripGo adalah platform pemesanan tiket bus online yang hadir untuk memudahkan perjalanan Anda. Kami menghubungkan penumpang dengan berbagai pilihan bus dan rute di seluruh Indonesia.</p>
                    <p class="text-muted mb-3">Dengan teknologi modern dan antarmuka yang user-friendly, kami berkomitmen memberikan pengalaman pemesanan tiket yang cepat, aman, dan nyaman untuk setiap pelanggan.</p>
                    <p class="text-muted">Tim kami terdiri dari profesional berpengalaman di bidang teknologi, transportasi, dan layanan pelanggan yang berdedikasi untuk memberikan solusi perjalanan terbaik bagi Anda.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&h=400&fit=crop" alt="Tim TripGo" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Visi Misi Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4">
                        <div class="card-body">
                            <div class="text-primary mb-3">
                                <i class="bi bi-eye fs-1"></i>
                            </div>
                            <h3 class="fw-bold mb-3">Visi Kami</h3>
                            <p class="text-muted">Menjadi platform pemesanan tiket bus terdepan di Indonesia yang memberikan kemudahan, kenyamanan, dan kepercayaan bagi setiap perjalanan pelanggan kami.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm p-4">
                        <div class="card-body">
                            <div class="text-success mb-3">
                                <i class="bi bi-bullseye fs-1"></i>
                            </div>
                            <h3 class="fw-bold mb-3">Misi Kami</h3>
                            <p class="text-muted">Menyediakan layanan pemesanan tiket yang mudah, aman, dan terpercaya dengan teknologi terkini, serta memberikan pengalaman perjalanan terbaik bagi setiap pelanggan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Nilai-Nilai Kami Section -->
    <section class="py-5">
        <div class="container py-5">
            <h2 class="text-center fw-bold mb-5">Nilai-Nilai Kami</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-shield-check fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Terpercaya</h5>
                            <p class="text-muted">Kami berkomitmen untuk memberikan layanan yang dapat dipercaya dengan sistem keamanan terbaik untuk setiap transaksi dan data pelanggan.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-lightning-charge fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Cepat</h5>
                            <p class="text-muted">Proses pemesanan yang cepat dan efisien, sehingga Anda dapat memesan tiket dalam hitungan menit tanpa ribet.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-lock-fill fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Aman</h5>
                            <p class="text-muted">Keamanan data dan transaksi Anda adalah prioritas utama kami dengan enkripsi dan sistem pembayaran yang terjamin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   
    <!-- Stats Section -->
    <section class="py-5">
        <div class="container py-5">
            <h2 class="text-center fw-bold mb-5">Pencapaian Kami</h2>
            <div class="row g-4 text-center">
                <?php foreach ($stats as $stat): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="card-body">
                            <div class="text-<?php echo $stat['color']; ?> mb-3">
                                <i class="bi <?php echo $stat['icon']; ?> fs-1"></i>
                            </div>
                            <h2 class="fw-bold text-<?php echo $stat['color']; ?>"><?php echo $stat['value']; ?></h2>
                            <p class="text-muted mb-0"><?php echo $stat['label']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <h2 class="text-center fw-bold mb-5">Nilai-Nilai Kami</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-primary text-white rounded-circle p-4 mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-shield-check fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Kepercayaan</h5>
                            <p class="text-muted">Kami berkomitmen untuk memberikan layanan yang dapat dipercaya dengan sistem keamanan terbaik untuk setiap transaksi.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle p-4 mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-heart-fill fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Kepuasan Pelanggan</h5>
                            <p class="text-muted">Kepuasan pelanggan adalah prioritas utama kami. Kami selalu berusaha memberikan pengalaman terbaik.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-warning text-white rounded-circle p-4 mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-lightning-charge-fill fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3">Inovasi</h5>
                            <p class="text-muted">Kami terus berinovasi menghadirkan teknologi terbaru untuk kemudahan pemesanan tiket bus Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

  

    <!-- CTA Section -->
    <section class="py-5 text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <div class="container py-5 text-center">
            <h2 class="display-5 fw-bold mb-4">Bergabunglah dengan Kami</h2>
            <p class="lead mb-4">Mulai perjalanan Anda bersama TripGo hari ini!</p>
            <a href="cari-tiket.html" class="btn btn-light btn-lg px-5">
                <i class="bi bi-search"></i> Cari Tiket Sekarang
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-bus-front-fill"></i> <?php echo SITE_NAME; ?>
                    </h5>
                    <p><?php echo SITE_TAGLINE; ?> untuk kemudahan dan kenyamanan Anda.</p>
                    <div class="social-links">
                        <a href="<?php echo FACEBOOK_URL; ?>" class="text-white me-3"><i class="bi bi-facebook fs-4"></i></a>
                        <a href="<?php echo INSTAGRAM_URL; ?>" class="text-white me-3"><i class="bi bi-instagram fs-4"></i></a>
                        <a href="<?php echo TWITTER_URL; ?>" class="text-white me-3"><i class="bi bi-twitter fs-4"></i></a>
                        <a href="<?php echo YOUTUBE_URL; ?>" class="text-white"><i class="bi bi-youtube fs-4"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Perusahaan</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="tentang.php" class="text-white-50 text-decoration-none">Tentang Kami</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Karir</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Blog</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Kemitraan</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Layanan</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Cari Tiket</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Cek Pesanan</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Syarat & Ketentuan</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Kebijakan Privasi</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-telephone"></i> <?php echo SITE_PHONE; ?></li>
                        <li class="mb-2"><i class="bi bi-envelope"></i> <?php echo SITE_EMAIL; ?></li>
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo SITE_ADDRESS; ?></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-white">
            <p class="text-center mb-0 text-white-50">&copy; <?php echo CURRENT_YEAR; ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
