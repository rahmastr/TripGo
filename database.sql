-- ============================================
-- DATABASE TRIPGO - SISTEM PEMESANAN TIKET BUS
-- Created: 10 Desember 2025
-- Author: TripGo Development Team
-- ============================================

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS `tripgo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tripgo`;

-- ============================================
-- 1. TABEL PENGGUNA (Users)
-- ============================================
CREATE TABLE IF NOT EXISTS `pengguna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin
INSERT INTO `pengguna` (`nama_lengkap`, `email`, `no_hp`, `password`, `role`) VALUES
('Administrator', 'admin@tripgo.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password default: password

-- ============================================
-- 2. TABEL BUS
-- ============================================
CREATE TABLE IF NOT EXISTS `bus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_bus` varchar(100) NOT NULL,
  `nomor_bus` varchar(50) NOT NULL,
  `kapasitas` int(11) NOT NULL DEFAULT 40,
  `tipe_bus` enum('Ekonomi','Bisnis','Eksekutif') NOT NULL DEFAULT 'Ekonomi',
  `fasilitas` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_bus` (`nomor_bus`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data bus
INSERT INTO `bus` (`nama_bus`, `nomor_bus`, `kapasitas`, `tipe_bus`, `fasilitas`, `status`) VALUES
('Rajawali Express 1', 'RJ-001', 40, 'Ekonomi', 'AC, Reclining Seat, USB Charger', 'active'),
('Rajawali Express 2', 'RJ-002', 40, 'Bisnis', 'AC, Extra Legroom, WiFi, USB Charger', 'active'),
('Rajawali Executive 1', 'RJ-003', 32, 'Eksekutif', 'AC, Full Reclining Seat, WiFi, Entertainment, USB Charger', 'active'),
('Rajawali Executive 2', 'RJ-004', 32, 'Eksekutif', 'AC, Full Reclining Seat, WiFi, Entertainment, USB Charger', 'active'),
('Rajawali Express 3', 'RJ-005', 40, 'Ekonomi', 'AC, Reclining Seat, USB Charger', 'active');

-- ============================================
-- 3. TABEL RUTE
-- ============================================
CREATE TABLE IF NOT EXISTS `rute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_id` int(11) NOT NULL,
  `kota_asal` varchar(100) NOT NULL,
  `kota_tujuan` varchar(100) NOT NULL,
  `jam_berangkat` time NOT NULL,
  `durasi_perjalanan` varchar(50) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `tipe_bus` enum('Ekonomi','Bisnis','Eksekutif') NOT NULL DEFAULT 'Ekonomi',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bus_id` (`bus_id`),
  KEY `kota_asal` (`kota_asal`),
  KEY `kota_tujuan` (`kota_tujuan`),
  KEY `status` (`status`),
  CONSTRAINT `rute_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `bus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data rute
INSERT INTO `rute` (`bus_id`, `kota_asal`, `kota_tujuan`, `jam_berangkat`, `durasi_perjalanan`, `harga`, `tipe_bus`, `status`) VALUES
(1, 'Medan', 'Jakarta', '08:00:00', '36 jam', 450000.00, 'Ekonomi', 'active'),
(2, 'Medan', 'Jakarta', '19:00:00', '36 jam', 600000.00, 'Bisnis', 'active'),
(3, 'Medan', 'Bandung', '09:00:00', '38 jam', 750000.00, 'Eksekutif', 'active'),
(1, 'Medan', 'Pekanbaru', '06:00:00', '8 jam', 200000.00, 'Ekonomi', 'active'),
(2, 'Medan', 'Padang', '07:00:00', '12 jam', 300000.00, 'Bisnis', 'active'),
(4, 'Jakarta', 'Medan', '08:00:00', '36 jam', 750000.00, 'Eksekutif', 'active'),
(5, 'Jakarta', 'Bandung', '10:00:00', '3 jam', 100000.00, 'Ekonomi', 'active'),
(1, 'Bandung', 'Medan', '20:00:00', '38 jam', 450000.00, 'Ekonomi', 'active'),
(2, 'Pekanbaru', 'Medan', '14:00:00', '8 jam', 250000.00, 'Bisnis', 'active'),
(3, 'Padang', 'Medan', '18:00:00', '12 jam', 500000.00, 'Eksekutif', 'active');

-- ============================================
-- 4. TABEL JADWAL
-- ============================================
CREATE TABLE IF NOT EXISTS `jadwal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route_id` int(11) NOT NULL,
  `tanggal_operasional` date NOT NULL,
  `status` enum('active','cancelled','full') NOT NULL DEFAULT 'active',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  KEY `tanggal_operasional` (`tanggal_operasional`),
  KEY `status` (`status`),
  CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `rute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample jadwal untuk 30 hari ke depan
INSERT INTO `jadwal` (`route_id`, `tanggal_operasional`, `status`) 
SELECT 
    r.id,
    DATE_ADD(CURDATE(), INTERVAL d.day DAY),
    'active'
FROM rute r
CROSS JOIN (
    SELECT 0 as day UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION 
    SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION 
    SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION 
    SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION 
    SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION 
    SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
) d
WHERE r.status = 'active';

-- ============================================
-- 5. TABEL PEMESANAN (Bookings)
-- ============================================
CREATE TABLE IF NOT EXISTS `pemesanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `tanggal_berangkat` date NOT NULL,
  `jumlah_penumpang` int(11) NOT NULL DEFAULT 1,
  `kursi_dipilih` varchar(255) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `midtrans_order_id` varchar(100) DEFAULT NULL,
  `snap_token` varchar(255) DEFAULT NULL,
  `status_pembayaran` enum('pending','success','failed','expired','cancelled') NOT NULL DEFAULT 'pending',
  `metode_pembayaran` varchar(50) DEFAULT 'online',
  `is_offline` tinyint(1) NOT NULL DEFAULT 0,
  `qr_code` text DEFAULT NULL,
  `qr_used` tinyint(1) NOT NULL DEFAULT 0,
  `qr_scanned_at` datetime DEFAULT NULL,
  `qr_scanned_by` int(11) DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `midtrans_order_id` (`midtrans_order_id`),
  KEY `user_id` (`user_id`),
  KEY `route_id` (`route_id`),
  KEY `tanggal_berangkat` (`tanggal_berangkat`),
  KEY `status_pembayaran` (`status_pembayaran`),
  KEY `qr_scanned_by` (`qr_scanned_by`),
  CONSTRAINT `pemesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pemesanan_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `rute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pemesanan_ibfk_3` FOREIGN KEY (`qr_scanned_by`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 6. TABEL KURSI TERPESAN
-- ============================================
CREATE TABLE IF NOT EXISTS `kursi_terpesan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `tanggal_berangkat` date NOT NULL,
  `nomor_kursi` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `route_id` (`route_id`),
  KEY `tanggal_berangkat` (`tanggal_berangkat`),
  KEY `nomor_kursi` (`nomor_kursi`),
  KEY `route_date_seat` (`route_id`, `tanggal_berangkat`, `nomor_kursi`),
  CONSTRAINT `kursi_terpesan_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `pemesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `kursi_terpesan_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `rute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 7. TABEL PENUMPANG (Data Pelanggan)
-- ============================================
CREATE TABLE IF NOT EXISTS `penumpang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `nomor_kursi` varchar(10) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_identitas` varchar(50) DEFAULT NULL COMMENT 'KTP/SIM/Passport',
  `jenis_identitas` enum('KTP','SIM','Passport','Lainnya') DEFAULT 'KTP',
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `usia` enum('Dewasa','Anak-anak') DEFAULT 'Dewasa',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `nomor_kursi` (`nomor_kursi`),
  CONSTRAINT `penumpang_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `pemesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 8. TABEL LOG AKTIVITAS (Optional)
-- ============================================
CREATE TABLE IF NOT EXISTS `log_aktivitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 9. VIEWS (Optional - Untuk Reporting)
-- ============================================

-- View untuk statistik booking
CREATE OR REPLACE VIEW `v_statistik_booking` AS
SELECT 
    DATE(p.created_at) as tanggal,
    COUNT(*) as total_booking,
    SUM(p.jumlah_penumpang) as total_penumpang,
    SUM(CASE WHEN p.status_pembayaran = 'success' THEN 1 ELSE 0 END) as booking_sukses,
    SUM(CASE WHEN p.status_pembayaran = 'success' THEN p.total_harga ELSE 0 END) as total_pendapatan
FROM pemesanan p
GROUP BY DATE(p.created_at)
ORDER BY tanggal DESC;

-- View untuk rute populer
CREATE OR REPLACE VIEW `v_rute_populer` AS
SELECT 
    r.id,
    r.kota_asal,
    r.kota_tujuan,
    r.harga,
    b.nama_bus,
    COUNT(p.id) as total_booking,
    SUM(p.jumlah_penumpang) as total_penumpang,
    SUM(CASE WHEN p.status_pembayaran = 'success' THEN p.total_harga ELSE 0 END) as total_pendapatan
FROM rute r
JOIN bus b ON r.bus_id = b.id
LEFT JOIN pemesanan p ON r.id = p.route_id
GROUP BY r.id, r.kota_asal, r.kota_tujuan, r.harga, b.nama_bus
ORDER BY total_booking DESC;

-- ============================================
-- 10. STORED PROCEDURES (Optional)
-- ============================================

-- Procedure untuk cek ketersediaan kursi
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_cek_kursi_tersedia`(
    IN p_route_id INT,
    IN p_tanggal DATE
)
BEGIN
    SELECT 
        b.kapasitas,
        COALESCE(COUNT(kt.id), 0) as kursi_terpesan,
        (b.kapasitas - COALESCE(COUNT(kt.id), 0)) as sisa_kursi,
        GROUP_CONCAT(kt.nomor_kursi) as kursi_terpesan_list
    FROM rute r
    JOIN bus b ON r.bus_id = b.id
    LEFT JOIN kursi_terpesan kt ON r.id = kt.route_id AND kt.tanggal_berangkat = p_tanggal
    WHERE r.id = p_route_id
    GROUP BY b.kapasitas;
END$$
DELIMITER ;

-- Procedure untuk cancel booking yang expired
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS `sp_cancel_expired_bookings`()
BEGIN
    UPDATE pemesanan
    SET status_pembayaran = 'expired'
    WHERE status_pembayaran = 'pending'
    AND expired_at IS NOT NULL
    AND expired_at < NOW();
    
    SELECT ROW_COUNT() as bookings_cancelled;
END$$
DELIMITER ;

-- ============================================
-- 11. TRIGGERS
-- ============================================

-- Trigger untuk update status jadwal jika penuh
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `tr_update_jadwal_status` 
AFTER INSERT ON `kursi_terpesan`
FOR EACH ROW
BEGIN
    DECLARE v_kapasitas INT;
    DECLARE v_terpesan INT;
    
    SELECT b.kapasitas INTO v_kapasitas
    FROM rute r
    JOIN bus b ON r.bus_id = b.id
    WHERE r.id = NEW.route_id;
    
    SELECT COUNT(*) INTO v_terpesan
    FROM kursi_terpesan
    WHERE route_id = NEW.route_id
    AND tanggal_berangkat = NEW.tanggal_berangkat;
    
    IF v_terpesan >= v_kapasitas THEN
        UPDATE jadwal
        SET status = 'full'
        WHERE route_id = NEW.route_id
        AND tanggal_operasional = NEW.tanggal_berangkat;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- 12. INDEXES (Sudah dibuat di atas, tapi bisa ditambah)
-- ============================================

-- Index tambahan untuk performa query
ALTER TABLE `pemesanan` ADD INDEX `idx_user_status` (`user_id`, `status_pembayaran`);
ALTER TABLE `pemesanan` ADD INDEX `idx_route_date` (`route_id`, `tanggal_berangkat`);
ALTER TABLE `kursi_terpesan` ADD INDEX `idx_booking_seat` (`booking_id`, `nomor_kursi`);
ALTER TABLE `penumpang` ADD INDEX `idx_booking_nama` (`booking_id`, `nama_lengkap`);

-- ============================================
-- 13. SAMPLE CUSTOMER DATA
-- ============================================
INSERT INTO `pengguna` (`nama_lengkap`, `email`, `no_hp`, `password`, `role`) VALUES
('John Doe', 'john@example.com', '08123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('Jane Smith', 'jane@example.com', '08198765432', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('Bob Johnson', 'bob@example.com', '08112345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- ============================================
-- SELESAI - DATABASE TRIPGO
-- ============================================
-- 
-- Catatan:
-- 1. Password default untuk semua user: password
-- 2. Gunakan password_hash() di PHP untuk enkripsi password
-- 3. Jadwal di-generate otomatis untuk 30 hari ke depan
-- 4. Trigger otomatis update status jadwal jika penuh
-- 5. Stored procedure untuk cek kursi dan cancel expired booking
-- 6. Views untuk reporting dan statistik
-- 
-- Untuk menjalankan:
-- 1. Buka phpMyAdmin atau MySQL client
-- 2. Import file ini atau copy-paste ke SQL tab
-- 3. Database 'tripgo' akan dibuat otomatis
-- 4. Semua tabel, data sample, dan relasi akan dibuat
-- 
-- ============================================
