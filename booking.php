<?php
require_once 'config.php';

// Validasi parameter
if (!isset($_GET['route_id']) || !isset($_GET['tanggal']) || !isset($_GET['jumlah'])) {
    header('Location: index.php');
    exit();
}

$route_id = $_GET['route_id'];
$tanggal = $_GET['tanggal'];
$jumlah = (int)$_GET['jumlah'];

// Ambil data rute dan jadwal
$stmt = $conn->prepare("
    SELECT 
        r.*,
        b.nama_bus,
        b.kapasitas,
        b.nomor_bus,
        j.id as jadwal_id,
        j.tanggal_operasional,
        j.status as jadwal_status,
        (SELECT COUNT(*) FROM kursi_terpesan kt WHERE kt.route_id = r.id AND kt.tanggal_berangkat = ?) as kursi_terpesan
    FROM rute r
    JOIN bus b ON r.bus_id = b.id
    JOIN jadwal j ON j.route_id = r.id
    WHERE r.id = ? AND j.tanggal_operasional = ? AND j.status = 'active'
");
$stmt->execute([$tanggal, $route_id, $tanggal]);
$route = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$route) {
    $_SESSION['error'] = 'Rute tidak ditemukan atau tidak tersedia untuk tanggal yang dipilih!';
    header('Location: index.php');
    exit();
}

$sisa_kursi = $route['kapasitas'] - $route['kursi_terpesan'];

// Validasi ketersediaan kursi
if ($sisa_kursi < $jumlah) {
    $_SESSION['error'] = 'Kursi tidak cukup! Sisa kursi: ' . $sisa_kursi;
    header('Location: search.php?' . http_build_query($_GET));
    exit();
}

// Ambil kursi yang sudah terpesan
$stmt = $conn->prepare("
    SELECT GROUP_CONCAT(nomor_kursi) as kursi_terpesan
    FROM kursi_terpesan
    WHERE route_id = ? AND tanggal_berangkat = ?
");
$stmt->execute([$route_id, $tanggal]);
$kursi_data = $stmt->fetch(PDO::FETCH_ASSOC);
$kursi_terpesan_array = $kursi_data['kursi_terpesan'] ? explode(',', $kursi_data['kursi_terpesan']) : [];

// Generate layout kursi
$total_kursi = $route['kapasitas'];
$kursi_per_baris = 4; // 2 kiri, 2 kanan (gang di tengah)

// Fungsi untuk konversi nomor kursi ke format alfanumerik (A1, B1, C1, D1, dst)
function convertToAlphanumericSeat($number) {
    $row = ceil($number / 4);
    $position = (($number - 1) % 4);
    $letters = ['A', 'B', 'C', 'D'];
    $letter = $letters[$position];
    return $letter . $row;
}

$page_title = 'Pemesanan Tiket - ' . SITE_NAME;
$navbar_fixed = false;

include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
.seat-layout {
    max-width: 380px;
    margin: 0 auto;
}

.seat-row {
    display: flex;
    justify-content: center;
    gap: 45px;
    margin-bottom: 10px;
}

.seat-group {
    display: flex;
    gap: 8px;
}

.seat {
    width: 38px;
    height: 38px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.7rem;
    font-weight: 600;
    transition: all 0.2s ease;
    background: white;
}

.seat:hover:not(.booked):not(.selected) {
    border-color: #198754;
    background: #d1f2dd;
    transform: scale(1.05);
}

.seat.available {
    background: #28a745;
    border-color: #28a745;
    color: white;
    font-weight: bold;
}

.seat.selected {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.seat.booked {
    background: #6c757d;
    border-color: #6c757d;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.driver-seat {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    margin-bottom: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
}

.legend-box {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 2px solid;
}

.sticky-summary {
    position: sticky;
    top: 20px;
}

.route-card {
    background: linear-gradient(135deg, #059669);
    color: white;
    border-radius: 12px;
    padding: 20px;
}

.price-highlight {
    font-size: 1.75rem;
    font-weight: 700;
}

.compact-card {
    margin-bottom: 15px;
}

.compact-card .card-header {
    padding: 12px 20px;
}

.compact-card .card-body {
    padding: 20px;
}
</style>

<!-- Booking Section -->
<section class="py-4">
    <div class="container">
        <div class="row g-3">
            <!-- Kolom Kiri: Pilih Kursi -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm compact-card">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap"></i> Pilih Kursi (<?php echo $jumlah; ?> penumpang)</h6>
                    </div>
                    <div class="card-body">
                        <!-- Legenda -->
                        <div class="d-flex justify-content-start gap-4 mb-3 ms-3">
                            <div class="legend-item">
                                <div class="legend-box" style="background: #28a745; border-color: #28a745;"></div>
                                <span>Tersedia</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box" style="background: #0d6efd; border-color: #0d6efd;"></div>
                                <span>Dipilih</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-box" style="background: #6c757d; border-color: #6c757d;"></div>
                                <span>Terisi</span>
                            </div>
                        </div>

                        <!-- Layout Kursi -->
                        <div class="seat-layout">
                            <!-- Supir -->
                            <div class="text-center mb-3">
                                <div class="driver-seat mx-auto">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">Supir</small>
                            </div>

                            <!-- Kursi Penumpang -->
                            <div id="seatLayout">
                                <?php
                                $nomor_kursi = 1;
                                $total_baris = ceil($total_kursi / $kursi_per_baris);
                                
                                for ($baris = 1; $baris <= $total_baris; $baris++) {
                                    echo '<div class="seat-row">';
                                    
                                    // Sisi kiri (2 kursi)
                                    echo '<div class="seat-group">';
                                    for ($i = 0; $i < 2; $i++) {
                                        if ($nomor_kursi <= $total_kursi) {
                                            $seat_label = convertToAlphanumericSeat($nomor_kursi);
                                            $is_booked = in_array($seat_label, $kursi_terpesan_array);
                                            $class = $is_booked ? 'seat booked' : 'seat available';
                                            $disabled = $is_booked ? 'disabled' : '';
                                            
                                            echo '<div class="' . $class . '" data-seat="' . $seat_label . '" ' . $disabled . '>';
                                            echo $seat_label;
                                            echo '</div>';
                                            $nomor_kursi++;
                                        }
                                    }
                                    echo '</div>';
                                    
                                    // Sisi kanan (2 kursi)
                                    echo '<div class="seat-group">';
                                    for ($i = 0; $i < 2; $i++) {
                                        if ($nomor_kursi <= $total_kursi) {
                                            $seat_label = convertToAlphanumericSeat($nomor_kursi);
                                            $is_booked = in_array($seat_label, $kursi_terpesan_array);
                                            $class = $is_booked ? 'seat booked' : 'seat available';
                                            $disabled = $is_booked ? 'disabled' : '';
                                            
                                            echo '<div class="' . $class . '" data-seat="' . $seat_label . '" ' . $disabled . '>';
                                            echo $seat_label;
                                            echo '</div>';
                                            $nomor_kursi++;
                                        }
                                    }
                                    echo '</div>';
                                    
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0 py-2">
                            <small><i class="bi bi-info-circle"></i> 
                            <strong>Kursi:</strong> 
                            <span id="selectedSeatsText">Belum dipilih</span></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Detail & Ringkasan -->
            <div class="col-lg-5">
                <div class="sticky-summary">
                    <!-- Detail Rute -->
                    <div class="route-card shadow-sm compact-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <small class="opacity-75">Bus</small>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($route['nama_bus']); ?></h6>
                                <small class="opacity-75"><?php echo htmlspecialchars($route['nomor_bus']); ?></small>
                            </div>
                            <span class="badge bg-white bg-opacity-25" style="font-size: 0.75rem;"><?php echo htmlspecialchars($route['tipe_bus']); ?></span>
                        </div>
                        
                        <div class="border-top border-white border-opacity-25 pt-2 mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="opacity-75">Dari</small>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($route['kota_asal']); ?></h6>
                                    <div style="font-size: 0.9rem;"><?php echo date('H:i', strtotime($route['jam_berangkat'])); ?></div>
                                </div>
                                <div class="text-center px-2">
                                    <i class="bi bi-arrow-right fs-5"></i>
                                    <div style="font-size: 0.7rem;" class="opacity-75"><?php echo htmlspecialchars($route['durasi_perjalanan']); ?></div>
                                </div>
                                <div class="text-end">
                                    <small class="opacity-75">Ke</small>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($route['kota_tujuan']); ?></h6>
                                    <small class="opacity-75">Tiba</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between" style="font-size: 0.85rem;">
                                <div>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <span><?php echo date('d M Y', strtotime($tanggal)); ?></span>
                                </div>
                                <div>
                                    <i class="bi bi-people me-1"></i>
                                    <span><?php echo $jumlah; ?> Penumpang</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Pembayaran -->
                    <div class="card border-0 shadow-sm compact-card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;"><i class="bi bi-receipt"></i> Ringkasan Pembayaran</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-2" style="font-size: 0.9rem;">
                                    <span class="text-muted">Harga per Kursi</span>
                                    <span class="fw-semibold">Rp <?php echo number_format($route['harga'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2" style="font-size: 0.9rem;">
                                    <span class="text-muted">Jumlah Kursi</span>
                                    <span class="fw-semibold"><?php echo $jumlah; ?> kursi</span>
                                </div>
                            </div>
                            
                            <div class="border-top pt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Bayar</span>
                                    <div class="text-primary price-highlight">Rp <?php echo number_format($route['harga'] * $jumlah, 0, ',', '.'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Pembayaran -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3"><i class="bi bi-credit-card"></i> Metode Pembayaran</h6>
                            
                            <div class="mb-0">
                                <div class="form-check payment-method-option">
                                    <input class="form-check-input" type="radio" name="metode_pembayaran" id="paymentOnline" value="online" checked>
                                    <label class="form-check-label w-100" for="paymentOnline">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-phone text-primary me-2" style="font-size: 1.5rem;"></i>
                                            <div>
                                                <strong>Pembayaran Online</strong>
                                                <small class="d-block text-muted">Transfer, E-Wallet, Kartu Kredit</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success mb-3" id="paymentInfoOnline" style="font-size: 0.9rem;">
                        <h6 class="fw-bold mb-2"><i class="bi bi-check-circle-fill"></i> Pembayaran Online</h6>
                        <ul class="mb-0 ps-3" style="font-size: 0.85rem;">
                            <li>Metode: Transfer Bank, E-Wallet, Kartu Kredit</li>
                            <li>Tiket otomatis terbit setelah pembayaran berhasil</li>
                        </ul>
                    </div>

                    <!-- Form Pemesanan -->
                    <form action="<?php echo !isset($_SESSION['user_id']) ? '' : 'process_booking.php'; ?>" method="POST" id="bookingForm">
                        <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                        <input type="hidden" name="tanggal_berangkat" value="<?php echo $tanggal; ?>">
                        <input type="hidden" name="jumlah_penumpang" value="<?php echo $jumlah; ?>">
                        <input type="hidden" name="kursi_dipilih" id="kursiDipilih" value="">
                        <input type="hidden" name="total_harga" value="<?php echo $route['harga'] * $jumlah; ?>">
                        <input type="hidden" name="metode_pembayaran" id="metodePembayaran" value="online">
                        
                        <!-- Data Penumpang -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3"><i class="bi bi-people-fill"></i> Data Penumpang</h6>
                                <p class="text-muted small mb-3">Silakan lengkapi data untuk setiap penumpang sesuai kursi yang dipilih</p>
                                
                                <!-- Container untuk form penumpang yang akan dibuat dinamis -->
                                <div id="passengerFormsContainer">
                                    <div class="alert alert-warning small mb-0">
                                        <i class="bi bi-info-circle"></i> Silakan pilih kursi terlebih dahulu untuk mengisi data penumpang
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg py-2 fw-semibold" id="btnSubmit" disabled>
                                <i class="bi bi-check-circle-fill"></i> Booking Sekarang
                            </button>
                            
                            <a href="search.php?kota_asal=<?php echo urlencode($route['kota_asal']); ?>&kota_tujuan=<?php echo urlencode($route['kota_tujuan']); ?>&tanggal=<?php echo $tanggal; ?>&jumlah=<?php echo $jumlah; ?>&search=1" class="btn btn-outline-secondary btn-lg py-2">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const selectedSeats = [];
const maxSeats = <?php echo $jumlah; ?>;
const pricePerSeat = <?php echo $route['harga']; ?>;

document.querySelectorAll('.seat.available').forEach(seat => {
    seat.addEventListener('click', function() {
        const seatNumber = this.getAttribute('data-seat');
        
        if (this.classList.contains('selected')) {
            // Unselect
            this.classList.remove('selected');
            this.classList.add('available');
            const index = selectedSeats.indexOf(seatNumber);
            if (index > -1) {
                selectedSeats.splice(index, 1);
            }
        } else {
            // Select
            if (selectedSeats.length < maxSeats) {
                this.classList.remove('available');
                this.classList.add('selected');
                selectedSeats.push(seatNumber);
            } else {
                alert('Anda hanya bisa memilih maksimal ' + maxSeats + ' kursi!');
            }
        }
        
        updateSummary();
    });
});

function updateSummary() {
    // Update selected seats text
    if (selectedSeats.length === 0) {
        document.getElementById('selectedSeatsText').textContent = 'Belum dipilih';
    } else {
        // Sort alfanumerik (A1, A2, B1, B2, C1, dll)
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
        document.getElementById('selectedSeatsText').textContent = sortedSeats.join(', ');
    }
    
    // Update hidden fields
    document.getElementById('kursiDipilih').value = selectedSeats.join(',');
    
    // Generate form penumpang dinamis
    generatePassengerForms();
    
    // Enable/disable submit button
    updateSubmitButton();
}

function generatePassengerForms() {
    const container = document.getElementById('passengerFormsContainer');
    
    if (selectedSeats.length === 0) {
        container.innerHTML = `
            <div class="alert alert-warning small mb-0">
                <i class="bi bi-info-circle"></i> Silakan pilih kursi terlebih dahulu untuk mengisi data penumpang
            </div>
        `;
        return;
    }
    
    // Sort kursi yang dipilih
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
    
    // Generate form untuk setiap kursi
    let formsHTML = '';
    sortedSeats.forEach((seat, index) => {
        formsHTML += `
            <div class="passenger-form mb-3 p-3 border rounded bg-light">
                <h6 class="text-primary mb-3">
                    <i class="bi bi-person-badge"></i> Penumpang ${index + 1} - Kursi ${seat}
                </h6>
                
                <input type="hidden" name="penumpang[${index}][kursi]" value="${seat}">
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm passenger-input" 
                               name="penumpang[${index}][nama]" 
                               placeholder="Sesuai identitas" 
                               required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small mb-1">No HP/WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm passenger-input" 
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
    
    // Tambahkan event listener untuk input validation
    document.querySelectorAll('.passenger-input').forEach(input => {
        input.addEventListener('input', updateSubmitButton);
    });
}

function updateSubmitButton() {
    const btnSubmit = document.getElementById('btnSubmit');
    
    // Cek apakah kursi sudah dipilih sesuai jumlah
    if (selectedSeats.length !== maxSeats) {
        btnSubmit.disabled = true;
        return;
    }
    
    // Cek apakah semua field required sudah diisi
    const requiredInputs = document.querySelectorAll('.passenger-input[required]');
    let allFilled = true;
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            allFilled = false;
        }
    });
    
    btnSubmit.disabled = !allFilled;
}

// Form validation
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default submit
    
    if (selectedSeats.length !== maxSeats) {
        alert('Silakan pilih ' + maxSeats + ' kursi terlebih dahulu!');
        return false;
    }
    
    // Validasi semua field required terisi
    const requiredInputs = document.querySelectorAll('.passenger-input[required]');
    let allFilled = true;
    
    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            allFilled = false;
        }
    });
    
    if (!allFilled) {
        alert('Silakan lengkapi data semua penumpang!');
        return false;
    }
    
    <?php if (!isset($_SESSION['user_id'])): ?>
    // Jika belum login, simpan data booking ke session via AJAX
    const formData = new FormData(this);
    
    fetch('save_booking_session.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect ke login dengan parameter return
            window.location.href = 'login.php?redirect=process_booking';
        } else {
            alert('Gagal menyimpan data. Silakan coba lagi.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    });
    <?php else: ?>
    // Jika sudah login, submit form langsung
    this.submit();
    <?php endif; ?>
    
    return false;
});

// Metode pembayaran sekarang tetap hanya online
document.getElementById('metodePembayaran').value = 'online';
</script>

<style>
.payment-method-option {
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.payment-method-option:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.payment-method-option .form-check-input:checked ~ .form-check-label {
    color: #0d6efd;
}

.payment-method-option .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<?php include 'includes/footer.php'; ?>
