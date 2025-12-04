<?php
require_once 'config.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } else {
        try {
            // Cek email sudah terdaftar
            $stmt = $conn->prepare("SELECT id FROM pengguna WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Insert user baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO pengguna (nama_lengkap, email, no_hp, password, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->execute([$nama_lengkap, $email, $no_hp, $hashed_password]);
                
                $success = 'Pendaftaran berhasil! Silakan login.';
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
    <title>Daftar - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .register-body {
            padding: 40px 30px;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-person-plus" style="font-size: 2.5rem;"></i>
                <h1>Daftar Akun</h1>
                <p class="mb-0">Buat akun untuk mulai memesan tiket</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-register w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login Sekarang
                    </a>
                </div>
                <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" placeholder="Nama lengkap Anda" required autofocus value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="nama@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">No HP / WhatsApp</label>
                        <input type="text" class="form-control" name="no_hp" placeholder="08xxxxxxxxxx" required value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter" required>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-register w-100 mb-3">
                        <i class="bi bi-person-plus"></i> Daftar
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold">Login</a></p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
