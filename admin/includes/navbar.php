<?php
// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Ambil nama admin
$admin_name = $_SESSION['nama_lengkap'] ?? 'Admin';

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-bus-front-fill"></i> TripGo Admin</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'bus.php' ? 'active' : ''; ?>" href="bus.php">
                <i class="bi bi-bus-front"></i> Bus
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'rute.php' ? 'active' : ''; ?>" href="rute.php">
                <i class="bi bi-signpost-2"></i> Rute
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'jadwal.php' ? 'active' : ''; ?>" href="jadwal.php">
                <i class="bi bi-calendar-event"></i> Jadwal
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'pemesanan.php' ? 'active' : ''; ?>" href="pemesanan.php">
                <i class="bi bi-ticket-perforated"></i> Pemesanan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'pengguna.php' ? 'active' : ''; ?>" href="pengguna.php">
                <i class="bi bi-people"></i> Pengguna
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                <i class="bi bi-graph-up"></i> Laporan
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</div>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
        <button class="btn btn-link" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="navbar-text ms-auto">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($admin_name); ?>
        </span>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
