<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Statistik Dashboard
$stats = [];

// Total Pemesanan
$stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
$stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pemesanan Hari Ini
$stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE DATE(created_at) = CURDATE()");
$stats['today_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Pendapatan
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM pemesanan WHERE status_pembayaran = 'success'");
$stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pendapatan Bulan Ini
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM pemesanan WHERE status_pembayaran = 'success' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stats['month_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pembayaran Tunai (All payments)
$stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan");
$stats['cash_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Pengguna
$stmt = $conn->query("SELECT COUNT(*) as total FROM pengguna WHERE role = 'customer'");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Rute
$stmt = $conn->query("SELECT COUNT(*) as total FROM rute");
$stats['total_routes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Data untuk Chart Pembayaran (Online vs Tunai) - Hanya yang success
$stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE is_offline = 0 AND status_pembayaran = 'success'");
$stats['online_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM pemesanan WHERE is_offline = 1 AND status_pembayaran = 'success'");
$stats['cash_payments_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pendapatan dari Online - Hanya yang success
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM pemesanan WHERE is_offline = 0 AND status_pembayaran = 'success'");
$stats['online_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pendapatan dari Tunai - Hanya yang success
$stmt = $conn->query("SELECT COALESCE(SUM(total_harga), 0) as total FROM pemesanan WHERE is_offline = 1 AND status_pembayaran = 'success'");
$stats['cash_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Data untuk Chart Top 5 Routes
$stmt = $conn->query("
    SELECT 
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as route,
        COUNT(p.id) as total
    FROM pemesanan p
    JOIN rute r ON p.route_id = r.id
    WHERE p.status_pembayaran = 'success'
    GROUP BY p.route_id
    ORDER BY total DESC
    LIMIT 5
");
$top_routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Bookings
$stmt = $conn->query("
    SELECT 
        p.*,
        u.nama_lengkap,
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute,
        r.jam_berangkat
    FROM pemesanan p
    JOIN pengguna u ON p.user_id = u.id
    JOIN rute r ON p.route_id = r.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <h2>Dashboard</h2>
</div>

<!-- Stats Cards Row -->
<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card blue">
            <i class="bi bi-ticket-perforated stat-icon"></i>
            <h6>Total Pemesanan</h6>
            <h3><?php echo number_format($stats['total_bookings']); ?></h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card green">
            <i class="bi bi-calendar-check stat-icon"></i>
            <h6>Pemesanan Hari Ini</h6>
            <h3><?php echo number_format($stats['today_bookings']); ?></h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card yellow">
            <i class="bi bi-cash-stack stat-icon"></i>
            <h6>Total Pendapatan</h6>
            <h3>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card red">
            <i class="bi bi-graph-up stat-icon"></i>
            <h6>Pendapatan Bulan Ini</h6>
            <h3>Rp <?php echo number_format($stats['month_revenue'], 0, ',', '.'); ?></h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card orange">
            <i class="bi bi-people stat-icon"></i>
            <h6>Total Pengguna</h6>
            <h3><?php echo number_format($stats['total_users']); ?></h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card pink">
            <i class="bi bi-signpost-2 stat-icon"></i>
            <h6>Total Rute</h6>
            <h3><?php echo number_format($stats['total_routes']); ?></h3>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-lg-8 mb-4">
        <div class="chart-container" style="height: 280px;">
            <h5><i class="bi bi-bar-chart"></i> Top 5 Rute Terpopuler</h5>
            <div style="height: 220px;">
                <canvas id="routeChart"></canvas>
            </div>
        </div>
    </div>
   
</div>




<div class="table-container mt-4">
    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Pemesanan Terbaru</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Booking Code</th>
                    <th>Penumpang</th>
                    <th>Rute</th>
                    <th>Tanggal</th>
                    <th>Kursi</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_bookings as $booking): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($booking['midtrans_order_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($booking['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($booking['rute']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($booking['tanggal_berangkat'])); ?></td>
                    <td><?php echo htmlspecialchars($booking['kursi_dipilih']); ?></td>
                    <td>Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></td>
                    <td>
                        <?php if ($booking['is_offline']): ?>
                            <span class="badge bg-info">Tunai</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Online</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch ($booking['status_pembayaran']) {
                            case 'success':
                                $status_class = 'bg-success';
                                $status_text = 'Sukses';
                                break;
                            case 'pending':
                                $status_class = 'bg-warning';
                                $status_text = 'Pending';
                                break;
                            case 'failed':
                                $status_class = 'bg-danger';
                                $status_text = 'Gagal';
                                break;
                            case 'cancelled':
                                $status_class = 'bg-secondary';
                                $status_text = 'Dibatalkan';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Top Routes Chart (Bar)
const routeCtx = document.getElementById('routeChart').getContext('2d');
const routeChart = new Chart(routeCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($top_routes as $route): ?>
            '<?php echo addslashes($route['route']); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Jumlah Pemesanan',
            data: [
                <?php foreach ($top_routes as $route): ?>
                <?php echo $route['total']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                '#3498db',
                '#2ecc71',
                '#f39c12',
                '#e74c3c',
                '#9b59b6'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});


const paymentCtx = document.getElementById('paymentChart').getContext('2d');
const paymentChart = new Chart(paymentCtx, {
    type: 'pie',
    data: {
        labels: ['Pembayaran Online', 'Pembayaran Tunai'],
        datasets: [{
            data: [
                <?php echo (int)$stats['online_payments']; ?>,
                <?php echo (int)$stats['cash_payments_count']; ?>
            ],
            backgroundColor: [
                '#3498db',
                '#2ecc71'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' transaksi';
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        if (total > 0) {
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            label += ' (' + percentage + '%)';
                        }
                        return label;
                    }
                }
            }
        }
    }
});

// Grafik Pendapatan Berdasarkan Metode Pembayaran (Bar)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: ['Pembayaran Online', 'Pembayaran Tunai'],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: [
                <?php echo (int)$stats['online_revenue']; ?>,
                <?php echo (int)$stats['cash_revenue']; ?>
            ],
            backgroundColor: [
                '#3498db',
                '#2ecc71'
            ],
            borderWidth: 0,
            barThickness: 100
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                },
                grid: {
                    display: true,
                    drawBorder: false
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        return label;
                    }
                }
            },
            datalabels: {
                display: false
            }
        }
    }
});

// Log data untuk debugging
console.log('Payment Stats:', {
    online_count: <?php echo (int)$stats['online_payments']; ?>,
    cash_count: <?php echo (int)$stats['cash_payments_count']; ?>,
    online_revenue: <?php echo (int)$stats['online_revenue']; ?>,
    cash_revenue: <?php echo (int)$stats['cash_revenue']; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
