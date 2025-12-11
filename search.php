<?php
require_once 'config.php';

// Set judul halaman
$page_title = 'Hasil Pencarian Rute - ' . SITE_NAME;
$navbar_fixed = false;

// Handle pencarian rute
$search_results = [];
$search_performed = false;

if (isset($_GET['search'])) {
    $search_performed = true;
    $kota_asal = $_GET['kota_asal'] ?? '';
    $kota_tujuan = $_GET['kota_tujuan'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    $jumlah = $_GET['jumlah'] ?? 1;
    
    $query = "
        SELECT 
            r.*,
            b.nama_bus,
            b.kapasitas,
            b.nomor_bus,
            j.id as jadwal_id,
            j.tanggal_operasional,
            j.status as jadwal_status,
            j.keterangan as jadwal_keterangan,
            (SELECT COUNT(*) FROM kursi_terpesan kt WHERE kt.route_id = r.id AND kt.tanggal_berangkat = ?) as kursi_terpesan
        FROM rute r
        JOIN bus b ON r.bus_id = b.id
        JOIN jadwal j ON j.route_id = r.id
        WHERE j.tanggal_operasional = ?
        AND j.status = 'active'
    ";
    
    $params = [$tanggal, $tanggal];
    
    if (!empty($kota_asal)) {
        $query .= " AND r.kota_asal = ?";
        $params[] = $kota_asal;
    }
    
    if (!empty($kota_tujuan)) {
        $query .= " AND r.kota_tujuan = ?";
        $params[] = $kota_tujuan;
    }
    
    $query .= " ORDER BY r.jam_berangkat ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include header dan navbar
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hasil Pencarian Section -->
<section class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">
                <i class="bi bi-list-ul"></i> Hasil Pencarian Rute
                <?php if (!empty($_GET['kota_asal']) && !empty($_GET['kota_tujuan'])): ?>
                    <span class="text-primary"><?php echo htmlspecialchars($_GET['kota_asal']); ?> → <?php echo htmlspecialchars($_GET['kota_tujuan']); ?></span>
                <?php endif; ?>
            </h4>
            <a href="index.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Pencarian
            </a>
        </div>

        <?php if (!$search_performed): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Silakan gunakan form pencarian di halaman utama untuk mencari rute bus.
            </div>
        <?php elseif (empty($search_results)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                Maaf, tidak ada rute yang tersedia untuk pencarian Anda. Silakan coba dengan rute atau tanggal lain.
            </div>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-search"></i> Coba Pencarian Lain
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-success mb-3 py-2">
                <i class="bi bi-check-circle"></i> 
                Ditemukan <strong><?php echo count($search_results); ?></strong> rute untuk tanggal <strong><?php echo date('d F Y', strtotime($_GET['tanggal'])); ?></strong>
            </div>

            <div class="row g-3">
                <?php foreach ($search_results as $route): 
                    $sisa_kursi = $route['kapasitas'] - $route['kursi_terpesan'];
                    $tersedia = $sisa_kursi > 0;
                    $jumlah_penumpang = $_GET['jumlah'] ?? 1;
                    $cukup_kursi = $sisa_kursi >= $jumlah_penumpang;
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm <?php echo !$tersedia ? 'bg-light' : ''; ?>" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-3">
                                <!-- Bus Info -->
                                <div class="col-lg-2 col-md-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                            <i class="bi bi-bus-front-fill text-primary fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 text-primary" style="font-size: 0.95rem;"><?php echo htmlspecialchars($route['nama_bus']); ?></div>
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($route['nomor_bus']); ?></small>
                                            <br>
                                            <span class="badge bg-info mt-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($route['tipe_bus']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Route Info -->
                                <div class="col-lg-4 col-md-5">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-start flex-grow-1">
                                            <div class="fw-bold mb-0" style="font-size: 1rem;"><?php echo htmlspecialchars($route['kota_asal']); ?></div>
                                            <div class="text-primary fw-semibold" style="font-size: 0.9rem;"><?php echo date('H:i', strtotime($route['jam_berangkat'])); ?></div>
                                        </div>
                                        <div class="text-center px-3">
                                            <i class="bi bi-arrow-right text-primary" style="font-size: 1.2rem;"></i>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($route['durasi_perjalanan']); ?></div>
                                        </div>
                                        <div class="text-end flex-grow-1">
                                            <div class="fw-bold mb-0" style="font-size: 1rem;"><?php echo htmlspecialchars($route['kota_tujuan']); ?></div>
                                            <div class="text-muted" style="font-size: 0.8rem;">Tiba</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Seat Availability -->
                                <div class="col-lg-2 col-md-4 text-center">
                                    <div class="mb-1 text-muted" style="font-size: 0.75rem;">Sisa Kursi:</div>
                                    <div class="fw-bold <?php echo $sisa_kursi <= 5 ? 'text-danger' : 'text-success'; ?>" style="font-size: 1.5rem;">
                                        <?php echo $sisa_kursi; ?> <span class="text-muted" style="font-size: 0.8rem;">/ <?php echo $route['kapasitas']; ?></span>
                                    </div>
                                    <?php if (!$cukup_kursi && $tersedia): ?>
                                        <small class="text-danger d-block mt-1" style="font-size: 0.7rem;">Kursi tidak cukup</small>
                                    <?php endif; ?>
                                </div>

                                <!-- Price & Action -->
                                <div class="col-lg-4 col-md-12">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="text-start">
                                            <div class="text-primary fw-bold mb-0" style="font-size: 1.3rem;">
                                                Rp <?php echo number_format($route['harga'], 0, ',', '.'); ?>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.75rem;">per orang</small>
                                            <?php if ($jumlah_penumpang > 1): ?>
                                                <div class="mt-1">
                                                    <strong class="text-dark" style="font-size: 0.85rem;">
                                                        Total: Rp <?php echo number_format($route['harga'] * $jumlah_penumpang, 0, ',', '.'); ?>
                                                    </strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ms-3">
                                            <?php if ($tersedia && $cukup_kursi): ?>
                                                <?php
                                                    // URL booking tujuan - langsung ke booking tanpa cek login
                                                    $booking_url = "booking.php?route_id=" . $route['id'] . "&tanggal=" . urlencode($_GET['tanggal']) . "&jumlah=" . $jumlah_penumpang;
                                                ?>
                                                <a href="<?php echo $booking_url; ?>" 
                                                   class="btn btn-primary px-3 py-2" style="font-size: 0.9rem;">
                                                    <i class="bi bi-ticket-perforated"></i> Pesan Sekarang
                                                </a>
                                            <?php elseif (!$tersedia): ?>
                                                <button class="btn btn-secondary px-3 py-2" style="font-size: 0.9rem;" disabled>
                                                    <i class="bi bi-x-circle"></i> Kursi Penuh
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-warning px-3 py-2" style="font-size: 0.9rem;" disabled>
                                                    <i class="bi bi-exclamation-triangle"></i> Tidak Cukup
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
