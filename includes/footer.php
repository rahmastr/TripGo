<!-- Footer -->
<footer class="bg-dark text-white py-5" id="kontak">
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
                <h6 class="fw-bold mb-3">Layanan</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Syarat & Ketentuan</a></li>
                    <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Kebijakan Privasi</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-telephone"></i> <?php echo SITE_PHONE; ?></li>
                    <li class="mb-2"><i class="bi bi-envelope"></i> <?php echo SITE_EMAIL; ?></li>
                    <li class="mb-2"><i class="bi bi-geo-alt"></i> <?php echo SITE_ADDRESS; ?></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="fw-bold mb-3">Lokasi Kami</h6>
                <div class="ratio ratio-4x3">
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
<script>
// Pastikan dropdown bekerja
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi semua dropdown
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>
<script src="js/script.js"></script>
</body>
</html>
