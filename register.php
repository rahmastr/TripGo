<?php
require_once 'config.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Handle proses registrasi
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
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .register-container {
            max-width: 400px;
            width: 100%;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 15px 25px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .register-header p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.85rem;
        }
        
        .register-body {
            padding: 15px 25px;
        }
        
        .form-label {
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
        }
        
        .form-control {
            padding: 7px 10px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            font-size: 0.85rem;
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
            padding: 0.375rem 0.75rem;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 8px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-register:hover {
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
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="bi bi-person-plus" style="font-size: 1.5rem;"></i>
                <h1 style="font-size: 1.3rem; margin-bottom: 3px;">Daftar Akun</h1>
                <p style="font-size: 0.85rem;"><?php echo SITE_NAME; ?></p>
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
                    <div class="mb-2">
                        <label class="form-label">Nama Lengkap</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" class="form-control" name="nama_lengkap" placeholder="Nama lengkap Anda" required autofocus value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" class="form-control" name="email" placeholder="nama@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">No HP / WhatsApp</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-phone"></i>
                            </span>
                            <input type="text" class="form-control" name="no_hp" placeholder="08xxxxxxxxxx" required value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Password</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Konfirmasi Password</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-register w-100 mb-2 mt-2">
                        <i class="bi bi-person-plus"></i> Daftar
                    </button>
                    
                    <div class="divider" style="margin: 8px 0;">
                        <span style="font-size: 0.8rem;">atau</span>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0" style="font-size: 0.85rem;">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold" style="color: #059669;">Login</a></p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
