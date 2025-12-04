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
                $stmt = $conn->prepare("INSERT INTO bus (nomor_bus, nama_bus, kapasitas) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['nomor_bus'],
                    $_POST['nama_bus'],
                    $_POST['kapasitas']
                ]);
                $_SESSION['success'] = 'Bus berhasil ditambahkan!';
            } elseif ($_POST['action'] == 'update') {
                $stmt = $conn->prepare("UPDATE bus SET nomor_bus = ?, nama_bus = ?, kapasitas = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['nomor_bus'],
                    $_POST['nama_bus'],
                    $_POST['kapasitas'],
                    $_POST['id']
                ]);
                $_SESSION['success'] = 'Bus berhasil diupdate!';
            } elseif ($_POST['action'] == 'delete') {
                $stmt = $conn->prepare("DELETE FROM bus WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['success'] = 'Bus berhasil dihapus!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: bus.php');
        exit();
    }
}

// Get all buses
$stmt = $conn->query("SELECT * FROM bus ORDER BY id DESC");
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Data Bus';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-bus-front"></i> Data Bus</h2>
            <p>Kelola data armada bus TripGo</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busModal" onclick="resetForm()">
            <i class="bi bi-plus-circle"></i> Tambah Bus
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
                <th>Nomor Bus</th>
                <th>Nama Bus</th>
                <th>Kapasitas</th>
                <th>Tanggal Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($buses as $bus): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo htmlspecialchars($bus['nomor_bus']); ?></strong></td>
                <td><?php echo htmlspecialchars($bus['nama_bus']); ?></td>
                <td><span class="badge bg-info"><?php echo $bus['kapasitas']; ?> kursi</span></td>
                <td><?php echo date('d/m/Y H:i', strtotime($bus['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-warning" onclick="editBus(<?php echo htmlspecialchars(json_encode($bus)); ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBus(<?php echo $bus['id']; ?>, '<?php echo htmlspecialchars($bus['nama_bus']); ?>')">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Bus -->
<div class="modal fade" id="busModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Bus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="busForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="create">
                    <input type="hidden" name="id" id="bus_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nomor Bus / Plat Nomor</label>
                        <input type="text" class="form-control" name="nomor_bus" id="nomor_bus" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Bus</label>
                        <input type="text" class="form-control" name="nama_bus" id="nama_bus" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" class="form-control" name="kapasitas" id="kapasitas" min="1" required>
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
    document.getElementById('busForm').reset();
    document.getElementById('action').value = 'create';
    document.getElementById('modalTitle').textContent = 'Tambah Bus';
}

function editBus(bus) {
    document.getElementById('action').value = 'update';
    document.getElementById('bus_id').value = bus.id;
    document.getElementById('nomor_bus').value = bus.nomor_bus;
    document.getElementById('nama_bus').value = bus.nama_bus;
    document.getElementById('kapasitas').value = bus.kapasitas;
    document.getElementById('modalTitle').textContent = 'Edit Bus';
    
    const modal = new bootstrap.Modal(document.getElementById('busModal'));
    modal.show();
}

function deleteBus(id, nama) {
    if (confirm('Yakin ingin menghapus bus ' + nama + '?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
