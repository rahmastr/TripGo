<?php
require_once 'config.php';

// Jika sudah login sebagai pelanggan, redirect ke index
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer') {
    header('Location: index.php');
    exit();
}

// Jika sudah login tapi sebagai admin, logout dulu
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    session_unset();
    session_destroy();
    session_start();
}

$error = '';

// Simpan data booking jika ada (dari form booking tanpa login)
if (isset($_POST['route_id']) && isset($_POST['kursi_dipilih']) && !isset($_POST['email'])) {
    $_SESSION['booking_data'] = [
        'route_id' => $_POST['route_id'],
        'tanggal_berangkat' => $_POST['tanggal_berangkat'],
        'jumlah_penumpang' => $_POST['jumlah_penumpang'],
        'kursi_dipilih' => $_POST['kursi_dipilih'],
        'total_harga' => $_POST['total_harga'],
        'metode_pembayaran' => $_POST['metode_pembayaran']
    ];
}

// Handle proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        try {
            // Cek hanya untuk customer
            $stmt = $conn->prepare("SELECT * FROM pengguna WHERE email = ? AND role = 'customer'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Prioritas 1: Cek jika ada pending_booking dari session
                if (isset($_SESSION['pending_booking'])) {
                    $pending = $_SESSION['pending_booking'];
                    
                    // Cek apakah data masih valid (tidak lebih dari 30 menit)
                    if ((time() - $pending['timestamp']) < 1800) {
                        unset($_SESSION['pending_booking']);
                        
                        // Redirect ke process_booking dengan POST data
                        echo '<html><body>';
                        echo '<form id="autoSubmit" action="process_booking.php" method="POST">';
                        echo '<input type="hidden" name="route_id" value="' . htmlspecialchars($pending['route_id']) . '">';
                        echo '<input type="hidden" name="tanggal_berangkat" value="' . htmlspecialchars($pending['tanggal_berangkat']) . '">';
                        echo '<input type="hidden" name="jumlah_penumpang" value="' . htmlspecialchars($pending['jumlah_penumpang']) . '">';
                        echo '<input type="hidden" name="kursi_dipilih" value="' . htmlspecialchars($pending['kursi_dipilih']) . '">';
                        echo '<input type="hidden" name="total_harga" value="' . htmlspecialchars($pending['total_harga']) . '">';
                        echo '<input type="hidden" name="metode_pembayaran" value="' . htmlspecialchars($pending['metode_pembayaran']) . '">';
                        
                        // Data penumpang
                        if (isset($pending['penumpang']) && is_array($pending['penumpang'])) {
                            foreach ($pending['penumpang'] as $index => $penumpang) {
                                foreach ($penumpang as $key => $value) {
                                    echo '<input type="hidden" name="penumpang[' . $index . '][' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($value) . '">';
                                }
                            }
                        }
                        
                        echo '</form>';
                        echo '<script>document.getElementById("autoSubmit").submit();</script>';
                        echo '</body></html>';
                        exit();
                    } else {
                        // Data sudah kadaluarsa
                        unset($_SESSION['pending_booking']);
                        $_SESSION['info'] = 'Data booking Anda telah kadaluarsa. Silakan booking ulang.';
                    }
                }
                
                // Prioritas 2: Jika ada booking_data lama (untuk backward compatibility)
                if (isset($_SESSION['booking_data'])) {
                    // Submit data booking secara otomatis setelah login
                    $booking_data = $_SESSION['booking_data'];
                    unset($_SESSION['booking_data']);
                    
                    // Redirect ke process_booking dengan POST data
                    echo '<html><body>';
                    echo '<form id="autoSubmit" action="process_booking.php" method="POST">';
                    foreach ($booking_data as $key => $value) {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                    echo '</form>';
                    echo '<script>document.getElementById("autoSubmit").submit();</script>';
                    echo '</body></html>';
                    exit();
                }
                
                // Prioritas 3: dari URL parameter (?redirect=)
                if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                    $redirect_to = $_GET['redirect'];
                    // Validasi redirect URL untuk keamanan (pastikan tidak redirect ke luar domain)
                    if (strpos($redirect_to, 'http') === false) {
                        header('Location: ' . $redirect_to);
                        exit();
                    }
                }
                
                // Prioritas 3: dari session (untuk kompatibilitas dengan booking.php)
                if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    $redirect_to = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']); // Hapus setelah digunakan
                    header('Location: ' . $redirect_to);
                    exit();
                }
                
                // Default redirect ke halaman utama
                header('Location: index.php');
                exit();
            } else {
                $error = 'Email atau password salah! Jika Anda admin, silakan login di <a href="admin/login.php">halaman admin</a>.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 380px;
            width: 100%;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 20px 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .login-header p {
            opacity: 0.9;
            margin: 0;
        }
        
        .login-body {
            padding: 20px 25px;
        }
        
        .form-control {
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #059669;
            box-shadow: 0 0 0 0.2rem rgba(5, 150, 105, 0.25);
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(5, 150, 105, 0.3);
            color: white;
        }
        
        .divider {
            text-align: center;
            margin: 15px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-bus-front" style="font-size: 2rem;"></i>
                <h1 style="font-size: 1.5rem; margin-bottom: 5px;">Login Pelanggan</h1>
                <p style="font-size: 0.9rem;"><?php echo SITE_NAME; ?></p>
            </div>
            
            <div class="login-body">
                <?php if (isset($_GET['redirect'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i> Silakan login terlebih dahulu untuk melanjutkan pemesanan tiket.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i> <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control" name="email" placeholder="nama@email.com" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100 mb-2">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                    
                    <div class="text-center mb-3">
                        <a href="index.php" class="text-muted text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                        </a>
                    </div>
                    
                    <div class="divider">
                        <span>atau</span>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0">Belum punya akun? <a href="register.php" class="text-decoration-none fw-bold" style="color: #059669;">Daftar Sekarang</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
