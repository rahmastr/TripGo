<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Tangani Operasi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'create') {
                $stmt = $conn->prepare("INSERT INTO rute (bus_id, kota_asal, kota_tujuan, jam_berangkat, durasi_perjalanan, harga, tipe_bus) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['bus_id'],
                    $_POST['kota_asal'],
                    $_POST['kota_tujuan'],
                    $_POST['jam_berangkat'],
                    $_POST['durasi_perjalanan'],
                    $_POST['harga'],
                    $_POST['tipe_bus']
                ]);
                $_SESSION['success'] = 'Rute berhasil ditambahkan!';
            } elseif ($_POST['action'] == 'update') {
                $stmt = $conn->prepare("UPDATE rute SET bus_id = ?, kota_asal = ?, kota_tujuan = ?, jam_berangkat = ?, durasi_perjalanan = ?, harga = ?, tipe_bus = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['bus_id'],
                    $_POST['kota_asal'],
                    $_POST['kota_tujuan'],
                    $_POST['jam_berangkat'],
                    $_POST['durasi_perjalanan'],
                    $_POST['harga'],
                    $_POST['tipe_bus'],
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Rute berhasil diupdate!';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $conn->prepare("DELETE FROM rute WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Rute berhasil dihapus!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: rute.php');
        exit();
    }
}

// Ambil semua rute dengan info bus
$stmt = $conn->query("
    SELECT r.*, b.nama_bus, b.nomor_bus 
    FROM rute r 
    JOIN bus b ON r.bus_id = b.id 
    ORDER BY r.id DESC
");
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua bus untuk dropdown
$buses = $conn->query("SELECT * FROM bus ORDER BY nama_bus")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Data Rute';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2> Data Rute</h2>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ruteModal" onclick="resetForm()">
            <i class="bi bi-plus-circle"></i> Tambah Rute
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="table-container">
    <table class="table table-hover datatable">
        <thead>
            <tr>
                <th width="50">No</th>
                <th>Bus</th>
                <th>Rute</th>
                <th>Jam Berangkat</th>
                <th>Durasi</th>
                <th>Harga</th>
                <th>Tipe Bus</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($routes as $route): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($route['nama_bus']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($route['nomor_bus']); ?></small>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($route['kota_asal']); ?></strong> 
                    <i class="bi bi-arrow-right"></i> 
                    <strong><?php echo htmlspecialchars($route['kota_tujuan']); ?></strong>
                </td>
                <td><?php echo date('H:i', strtotime($route['jam_berangkat'])); ?></td>
                <td><?php echo htmlspecialchars($route['durasi_perjalanan']); ?></td>
                <td><strong>Rp <?php echo number_format($route['harga'], 0, ',', '.'); ?></strong></td>
                <td><span class="badge bg-primary"><?php echo htmlspecialchars($route['tipe_bus']); ?></span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning" onclick="editRute(<?php echo htmlspecialchars(json_encode($route)); ?>)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteRute(<?php echo $route['id']; ?>, '<?php echo htmlspecialchars($route['kota_asal'] . ' - ' . $route['kota_tujuan']); ?>')" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Rute -->
<div class="modal fade" id="ruteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Rute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="ruteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="create">
                    <input type="hidden" name="id" id="rute_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bus</label>
                            <select class="form-select" name="bus_id" id="bus_id" required>
                                <option value="">Pilih Bus</option>
                                <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['id']; ?>">
                                    <?php echo htmlspecialchars($bus['nama_bus']) . ' (' . $bus['nomor_bus'] . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipe Bus</label>
                            <select class="form-select" name="tipe_bus" id="tipe_bus" required>
                                <option value="">Pilih Tipe</option>
                                <option value="Executive">Executive</option>
                                <option value="Sleeper">Sleeper</option>
                                <option value="Economy">Economy</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kota Asal</label>
                            <input type="text" class="form-control" name="kota_asal" id="kota_asal" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kota Tujuan</label>
                            <input type="text" class="form-control" name="kota_tujuan" id="kota_tujuan" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jam Berangkat</label>
                            <input type="time" class="form-control" name="jam_berangkat" id="jam_berangkat" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Durasi Perjalanan</label>
                            <input type="text" class="form-control" name="durasi_perjalanan" id="durasi_perjalanan" placeholder="contoh: 8 jam" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" class="form-control" name="harga" id="harga" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda ingin menghapus rute <strong id="deleteRuteName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tidak</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Ya</button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteIdTemp = null;

function resetForm() {
    document.getElementById('ruteForm').reset();
    document.getElementById('action').value = 'create';
    document.getElementById('modalTitle').textContent = 'Tambah Rute';
}

function editRute(rute) {
    document.getElementById('action').value = 'update';
    document.getElementById('rute_id').value = rute.id;
    document.getElementById('bus_id').value = rute.bus_id;
    document.getElementById('kota_asal').value = rute.kota_asal;
    document.getElementById('kota_tujuan').value = rute.kota_tujuan;
    document.getElementById('jam_berangkat').value = rute.jam_berangkat;
    document.getElementById('durasi_perjalanan').value = rute.durasi_perjalanan;
    document.getElementById('harga').value = rute.harga;
    document.getElementById('tipe_bus').value = rute.tipe_bus;
    document.getElementById('modalTitle').textContent = 'Edit Rute';
    
    const modal = new bootstrap.Modal(document.getElementById('ruteModal'));
    modal.show();
}

function deleteRute(id, rute) {
    deleteIdTemp = id;
    document.getElementById('deleteRuteName').textContent = rute;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function confirmDelete() {
    if (deleteIdTemp) {
        document.getElementById('delete_id').value = deleteIdTemp;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
