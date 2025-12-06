<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Default date range (bulan ini)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Statistik berdasarkan filter tanggal
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(total_harga), 0) as total_revenue
    FROM pemesanan 
    WHERE status_pembayaran = 'success' 
    AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Pendapatan Harian
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COALESCE(SUM(total_harga), 0) as revenue
    FROM pemesanan
    WHERE status_pembayaran = 'success'
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$start_date, $end_date]);
$daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Revenue by Route
$stmt = $conn->prepare("
    SELECT 
        CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as route,
        COUNT(p.id) as total_bookings,
        COALESCE(SUM(p.total_harga), 0) as revenue
    FROM pemesanan p
    JOIN rute r ON p.route_id = r.id
    WHERE p.status_pembayaran = 'success'
    AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY p.route_id
    ORDER BY revenue DESC
");
$stmt->execute([$start_date, $end_date]);
$route_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Customers
$stmt = $conn->prepare("
    SELECT 
        u.nama_lengkap,
        u.email,
        COUNT(p.id) as total_bookings,
        COALESCE(SUM(p.total_harga), 0) as total_spent
    FROM pemesanan p
    JOIN pengguna u ON p.user_id = u.id
    WHERE p.status_pembayaran = 'success'
    AND DATE(p.created_at) BETWEEN ? AND ?
    GROUP BY p.user_id
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment Method Stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN is_offline = 0 THEN 1 END) as online_count,
        COUNT(CASE WHEN is_offline = 1 THEN 1 END) as cash_count,
        COALESCE(SUM(CASE WHEN is_offline = 0 THEN total_harga ELSE 0 END), 0) as online_revenue,
        COALESCE(SUM(CASE WHEN is_offline = 1 THEN total_harga ELSE 0 END), 0) as cash_revenue
    FROM pemesanan
    WHERE status_pembayaran = 'success'
    AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$payment_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Laporan';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-graph-up"></i> Laporan Keuangan</h2>
            <p>Analisis pendapatan dan statistik pemesanan</p>
        </div>
    </div>
</div>

<!-- Filter Date Range -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportExcel()">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="stat-card blue">
            <i class="bi bi-ticket-perforated stat-icon"></i>
            <h6>Total Pemesanan</h6>
            <h3><?php echo number_format($stats['total_bookings']); ?></h3>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stat-card green">
            <i class="bi bi-cash-stack stat-icon"></i>
            <h6>Total Pendapatan</h6>
            <h3>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></h3>
        </div>
    </div>
</div>

<!-- Chart Pendapatan Harian -->
<div class="chart-container mb-4">
    <h5><i class="bi bi-graph-up"></i> Grafik Pendapatan Harian</h5>
    <canvas id="dailyRevenueChart" height="80"></canvas>
</div>

<!-- Revenue by Route Table -->
<div class="table-container mb-4">
    <h5 class="mb-3"><i class="bi bi-signpost-2"></i> Pendapatan Per Rute</h5>
    <div class="table-responsive">
        <table class="table table-hover datatable" id="routeTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Rute</th>
                    <th>Total Pemesanan</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($route_revenue as $route): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><strong><?php echo htmlspecialchars($route['route']); ?></strong></td>
                    <td><?php echo number_format($route['total_bookings']); ?> booking</td>
                    <td><strong>Rp <?php echo number_format($route['revenue'], 0, ',', '.'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-primary">
                    <th colspan="3">TOTAL</th>
                    <th>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
// Daily Revenue Chart
<?php if (!empty($daily_revenue)): ?>
const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
const dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($daily_revenue as $day): ?>
            '<?php echo date('d M', strtotime($day['date'])); ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: [
                <?php foreach ($daily_revenue as $day): ?>
                <?php echo $day['revenue']; ?>,
                <?php endforeach; ?>
            ],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#3498db',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            intersect: false,
            mode: 'index'
        },
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
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
<?php else: ?>
// Tampilkan pesan jika tidak ada data
document.getElementById('dailyRevenueChart').parentElement.innerHTML = '<div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Tidak ada data pendapatan untuk periode ini</div>';
<?php endif; ?>

// Payment Method Pie Chart
const paymentCtx = document.getElementById('paymentMethodChart').getContext('2d');
const paymentChart = new Chart(paymentCtx, {
    type: 'pie',
    data: {
        labels: ['Pembayaran Online', 'Pembayaran Tunai'],
        datasets: [{
            data: [
                <?php echo $payment_stats['online_count']; ?>,
                <?php echo $payment_stats['cash_count']; ?>
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
        maintainAspectRatio: true,
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
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        label += ' (' + percentage + '%)';
                        return label;
                    }
                }
            }
        }
    }
});

function exportExcel() {
    // Simple export using table2excel library or custom implementation
    alert('Fitur export Excel sedang dalam pengembangan.\n\nAnda dapat menggunakan Print to PDF atau copy table data.');
    
    // Alternative: Open print dialog
    window.print();
}

// Print styling
const style = document.createElement('style');
style.textContent = `
    @media print {
        .sidebar, .navbar, .btn, .action-buttons, .page-header p {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
        }
        .table-container, .chart-container {
            box-shadow: none !important;
            page-break-inside: avoid;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php include 'includes/footer.php'; ?>
