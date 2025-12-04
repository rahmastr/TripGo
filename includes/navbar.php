<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark <?php echo isset($navbar_fixed) && $navbar_fixed ? 'fixed-top' : ''; ?>" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="index.php">
            <i class="bi bi-bus-front-fill"></i> <?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>" href="index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'tentang' ? 'active' : ''; ?>" href="tentang.php">Tentang Kami</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'kontak' ? 'active' : ''; ?>" href="kontak.php">Kontak</a>
                </li>
                
                <?php if (!empty($_SESSION['user_id']) && !empty($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
                    <!-- User sudah login -->
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="btn btn-light dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 25px; font-weight: 600; padding: 10px 20px; text-decoration: none;">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header"><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-badge"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- User belum login -->
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light" href="login.php" style="border-radius: 25px; font-weight: 600; padding: 10px 20px;">
                            <i class="bi bi-person-circle"></i> Masuk
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
