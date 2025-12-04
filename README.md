# TripGo - Sistem Pemesanan Tiket Bus Online

Sistem pemesanan tiket bus berbasis web yang dibangun dengan PHP murni, MySQL, dan Bootstrap 5.

## 🚀 Fitur

### Pengguna
- Pencarian bus berdasarkan rute dan tanggal
- Pemesanan tiket dengan pemilihan kursi
- QR Code untuk tiket elektronik
- Histori pemesanan
- Registrasi dan login pengguna

### Admin
- Dashboard statistik
- Manajemen bus
- Manajemen rute
- Manajemen jadwal
- Manajemen pemesanan
- Manajemen pengguna
- Laporan pemesanan
- Scan QR code untuk validasi tiket

## 📋 Prasyarat

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web Server (Apache/Nginx)
- XAMPP/WAMP/LAMP (untuk development lokal)

## 🔧 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/rahmastr/TripGo.git
cd TripGo
```

### 2. Setup Database

Import file database ke MySQL:

```bash
mysql -u root -p < tripgo.sql
```

Atau melalui phpMyAdmin:
1. Buat database baru bernama `tripgo`
2. Import file `tripgo.sql`

### 3. Konfigurasi Database

Buat file `config.php` di root folder dengan kredensial database Anda:

```php
<?php
// Database Configuration
$DB_HOST = 'localhost';
$DB_NAME = 'tripgo';
$DB_USER = 'root';      // Ganti dengan username database Anda
$DB_PASS = '';          // Ganti dengan password database Anda

// PDO Connection
try {
    $conn = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
```

**Catatan:** File ini sudah ada di `.gitignore` sehingga tidak akan ter-commit ke repository.

### 4. Jalankan Aplikasi

1. Pastikan Apache dan MySQL sudah berjalan
2. Akses di browser: `http://localhost/TripGo`

### 5. Login Admin

Gunakan kredensial admin yang tersedia di database (cek tabel `users` dengan role `admin`).

**⚠️ PENTING:** Segera ubah password admin setelah login pertama!

## 📁 Struktur Folder

```
TripGo/
├── admin/              # Panel admin
│   ├── css/           # Style admin
│   ├── includes/      # Header, footer, navbar admin
│   └── *.php          # Halaman admin
├── css/               # Style utama
├── includes/          # Header, footer, navbar
├── js/                # JavaScript
├── config.php         # Konfigurasi (JANGAN DI-COMMIT!)
├── index.php          # Halaman utama
├── booking.php        # Halaman pemesanan
├── search.php         # Halaman pencarian
└── tripgo.sql         # Database schema
```

## 🛠️ Teknologi

- **Backend:** PHP 7.4+
- **Database:** MySQL dengan PDO
- **Frontend:** Bootstrap 5.3, JavaScript
- **Icons:** Bootstrap Icons
- **QR Code:** QR Code Generator Library

## 📝 Lisensi

Silakan sesuaikan dengan kebutuhan Anda.

## 👨‍💻 Kontak

Untuk pertanyaan atau dukungan, silakan buka issue di repository ini.

---

Dibuat dengan ❤️ untuk kemudahan perjalanan Anda
