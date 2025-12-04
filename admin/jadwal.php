<?php
session_start();
require_once '../config.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'create') {
                $stmt = $conn->prepare("INSERT INTO jadwal (route_id, tanggal_operasional, status, keterangan) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['route_id'],
                    $_POST['tanggal_operasional'],
                    $_POST['status'],
                    $_POST['keterangan']
                ]);
                $_SESSION['success'] = 'Jadwal berhasil ditambahkan!';
            } elseif ($_POST['action'] == 'update') {
                $stmt = $conn->prepare("UPDATE jadwal SET route_id = ?, tanggal_operasional = ?, status = ?, keterangan = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['route_id'],
                    $_POST['tanggal_operasional'],
                    $_POST['status'],
                    $_POST['keterangan'],
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Jadwal berhasil diupdate!';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Jadwal berhasil dihapus!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: jadwal.php');
        exit();
    }
}

// Get all schedules with route info
$stmt = $conn->query("
    SELECT j.*, CONCAT(r.kota_asal, ' - ', r.kota_tujuan) as rute, r.jam_berangkat, b.nama_bus
    FROM jadwal j
    JOIN rute r ON j.route_id = r.id
    JOIN bus b ON r.bus_id = b.id
    ORDER BY j.tanggal_operasional DESC, r.jam_berangkat ASC
");
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all routes for dropdown
$routes = $conn->query("
    SELECT r.id, CONCAT(r.kota_asal, ' - ', r.kota_tujuan, ' (', r.jam_berangkat, ')') as display_name
    FROM rute r
    ORDER BY r.kota_asal, r.jam_berangkat
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Jadwal Perjalanan';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-calendar-event"></i> Jadwal Perjalanan</h2>
            <p>Kelola jadwal operasional bus TripGo</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jadwalModal" onclick="resetForm()">
            <i class="bi bi-plus-circle"></i> Tambah Jadwal
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
                <th>Tanggal</th>
                <th>Rute</th>
                <th>Bus</th>
                <th>Jam Berangkat</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($jadwal as $schedule): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo date('d/m/Y', strtotime($schedule['tanggal_operasional'])); ?></td>
                <td><strong><?php echo htmlspecialchars($schedule['rute']); ?></strong></td>
                <td><?php echo htmlspecialchars($schedule['nama_bus']); ?></td>
                <td><?php echo date('H:i', strtotime($schedule['jam_berangkat'])); ?></td>
                <td>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    switch ($schedule['status']) {
                        case 'active':
                            $status_class = 'bg-success';
                            $status_text = 'Aktif';
                            break;
                        case 'cancelled':
                            $status_class = 'bg-danger';
                            $status_text = 'Dibatalkan';
                            break;
                        case 'full':
                            $status_class = 'bg-warning';
                            $status_text = 'Penuh';
                            break;
                    }
                    ?>
                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
                <td><?php echo htmlspecialchars($schedule['keterangan'] ?? '-'); ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-warning" onclick="editJadwal(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteJadwal(<?php echo $schedule['id']; ?>)">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Jadwal -->
<div class="modal fade" id="jadwalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="jadwalForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="create">
                    <input type="hidden" name="id" id="jadwal_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Rute</label>
                        <select class="form-select" name="route_id" id="route_id" required>
                            <option value="">Pilih Rute</option>
                            <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>">
                                <?php echo htmlspecialchars($route['display_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal Operasional</label>
                        <input type="date" class="form-control" name="tanggal_operasional" id="tanggal_operasional" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="active">Aktif</option>
                            <option value="cancelled">Dibatalkan</option>
                            <option value="full">Penuh</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="keterangan" rows="3"></textarea>
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

<script>
function resetForm() {
    document.getElementById('jadwalForm').reset();
    document.getElementById('action').value = 'create';
    document.getElementById('modalTitle').textContent = 'Tambah Jadwal';
}

function editJadwal(jadwal) {
    document.getElementById('action').value = 'update';
    document.getElementById('jadwal_id').value = jadwal.id;
    document.getElementById('route_id').value = jadwal.route_id;
    document.getElementById('tanggal_operasional').value = jadwal.tanggal_operasional;
    document.getElementById('status').value = jadwal.status;
    document.getElementById('keterangan').value = jadwal.keterangan || '';
    document.getElementById('modalTitle').textContent = 'Edit Jadwal';
    
    const modal = new bootstrap.Modal(document.getElementById('jadwalModal'));
    modal.show();
}

function deleteJadwal(id) {
    if (confirm('Yakin ingin menghapus jadwal ini?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
