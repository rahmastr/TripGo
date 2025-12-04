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

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
                
                // Redirect ke halaman utama
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
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
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
