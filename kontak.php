<?php
// Halaman Kontak TripGo
require_once 'config.php';

// Set page title
$page_title = 'Kontak Kami - ' . SITE_NAME;
$navbar_fixed = false;

// Include header
require_once 'includes/header.php';

// Include navbar
require_once 'includes/navbar.php';

// Data kontak
$contact_info = [
    [
        'icon' => 'bi-telephone-fill',
        'title' => 'Telepon',
        'subtitle' => 'Hubungi kami di:',
        'value' => SITE_PHONE,
        'extra' => 'Senin - Minggu, 24 Jam'
    ],
    [
        'icon' => 'bi-envelope-fill',
        'title' => 'Email',
        'subtitle' => 'Kirim email ke:',
        'value' => SITE_EMAIL,
        'extra' => 'Respon dalam 1x24 jam'
    ],
    [
        'icon' => 'bi-geo-alt-fill',
        'title' => 'Alamat',
        'subtitle' => 'Kunjungi kantor kami:',
        'value' => SITE_ADDRESS,
        'extra' => SITE_COUNTRY
    ]
];

// Data social media
$social_media = [
    ['icon' => 'bi-facebook', 'color' => 'primary', 'name' => 'Facebook', 'handle' => '@TripGoID', 'url' => FACEBOOK_URL],
    ['icon' => 'bi-instagram', 'color' => 'danger', 'name' => 'Instagram', 'handle' => '@tripgo.id', 'url' => INSTAGRAM_URL],
    ['icon' => 'bi-twitter', 'color' => 'info', 'name' => 'Twitter', 'handle' => '@TripGoID', 'url' => TWITTER_URL],
    ['icon' => 'bi-whatsapp', 'color' => 'success', 'name' => 'WhatsApp', 'handle' => WHATSAPP_NUMBER, 'url' => 'https://wa.me/' . str_replace(['+', ' ', '-'], '', WHATSAPP_NUMBER)]
];

// Data FAQ
$faqs = [
    [
        'id' => 'faq1',
        'question' => 'Bagaimana cara memesan tiket di TripGo?',
        'answer' => 'Anda dapat memesan tiket dengan mudah melalui website kami. Pilih rute dan tanggal keberangkatan, pilih kursi, isi data penumpang, lalu lakukan pembayaran secara online.',
        'show' => true
    ],
    [
        'id' => 'faq2',
        'question' => 'Metode pembayaran apa saja yang tersedia?',
        'answer' => 'Pembayaran dilakukan secara tunai di kasir. Silakan datang ke loket kami untuk melakukan pembayaran setelah melakukan booking.',
        'show' => false
    ],
    [
        'id' => 'faq3',
        'question' => 'Apakah saya bisa membatalkan atau mengubah jadwal tiket?',
        'answer' => 'Ya, Anda dapat mengubah jadwal tiket sesuai dengan kebijakan yang berlaku. Hubungi customer service kami untuk bantuan lebih lanjut mengenai pembatalan atau perubahan jadwal.',
        'show' => false
    ],
    [
        'id' => 'faq4',
        'question' => 'Bagaimana cara mendapatkan e-ticket saya?',
        'answer' => 'E-ticket akan dikirim langsung ke email Anda setelah pembayaran berhasil. Anda juga dapat mengunduhnya dari akun TripGo Anda.',
        'show' => false
    ]
];
?>

    <!-- Hero Section -->
    <section class="text-white py-5" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h1 class="display-4 fw-bold mb-4">Hubungi Kami</h1>
                    <p class="lead">Kami siap membantu Anda. Hubungi kami melalui berbagai saluran komunikasi yang tersedia.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="row g-4">
                <?php foreach ($contact_info as $info): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm text-center p-4">
                        <div class="card-body">
                            <div class="bg-success text-white rounded-circle mx-auto mb-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi <?php echo $info['icon']; ?> fs-2"></i>
                            </div>
                            <h5 class="fw-bold mb-3"><?php echo $info['title']; ?></h5>
                            <p class="text-muted mb-2"><?php echo $info['subtitle']; ?></p>
                            <p class="fw-bold text-success"><?php echo $info['value']; ?></p>
                            <small class="text-muted"><?php echo $info['extra']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Kirim Pesan</h2>
                    <p class="text-muted mb-4">Silakan isi formulir di bawah ini dan kami akan segera menghubungi Anda.</p>
                    <form>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" placeholder="Masukkan nama Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control form-control-lg" placeholder="Masukkan email Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Telepon</label>
                            <input type="tel" class="form-control form-control-lg" placeholder="Masukkan nomor telepon Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subjek</label>
                            <input type="text" class="form-control form-control-lg" placeholder="Subjek pesan" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pesan</label>
                            <textarea class="form-control" rows="5" placeholder="Tulis pesan Anda di sini..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-send-fill"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Lokasi Kami</h2>
                    <p class="text-muted mb-4">Temukan kami di peta untuk kunjungan langsung ke kantor kami.</p>
                    <div class="ratio ratio-1x1">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3982.204601230853!2d98.6526128!3d3.5402063999999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3031255628ec6a87%3A0xb8b763cb30851d0!2sCV.%20Rajawali%20Citra%20Transport!5e0!3m2!1sen!2sid!4v1764069396834!5m2!1sen!2sid" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media Section -->
    <section class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Ikuti Kami di Media Sosial</h2>
                <p class="text-muted">Tetap terhubung dengan kami untuk mendapatkan info terbaru dan promo menarik!</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php foreach ($social_media as $social): ?>
                <div class="col-md-3 col-6">
                    <a href="<?php echo $social['url']; ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm text-center p-4 h-100 social-card">
                            <div class="card-body">
                                <i class="bi <?php echo $social['icon']; ?> fs-1 text-<?php echo $social['color']; ?> mb-3"></i>
                                <h6 class="fw-bold"><?php echo $social['name']; ?></h6>
                                <small class="text-muted"><?php echo $social['handle']; ?></small>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Pertanyaan yang Sering Diajukan</h2>
                <p class="text-muted">Temukan jawaban untuk pertanyaan umum seputar layanan kami</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $faq): ?>
                        <div class="accordion-item border-0 shadow-sm mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo !$faq['show'] ? 'collapsed' : ''; ?> fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $faq['id']; ?>">
                                    <?php echo $faq['question']; ?>
                                </button>
                            </h2>
                            <div id="<?php echo $faq['id']; ?>" class="accordion-collapse collapse <?php echo $faq['show'] ? 'show' : ''; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    <?php echo $faq['answer']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3">
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
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-telephone"></i> <?php echo SITE_PHONE; ?></li>
                        <li class="mb-2"><i class="bi bi-envelope"></i> <?php echo SITE_EMAIL; ?></li>
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo SITE_ADDRESS; ?></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-3">Lokasi Kami</h6>
                    <div class="ratio ratio-16x9">
                        <iframe src="<?php echo GOOGLE_MAPS_EMBED; ?>" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white">
            <p class="text-center mb-0 text-white-50">&copy; <?php echo CURRENT_YEAR; ?> <?php echo SITE_NAME; ?>. Enjoy Your Trip!</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
