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
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO pengguna (nama_lengkap, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nama_lengkap'],
                    $_POST['email'],
                    $_POST['no_hp'],
                    $hashed_password,
                    $_POST['role']
                ]);
                $_SESSION['success'] = 'Pengguna berhasil ditambahkan!';
            } elseif ($_POST['action'] == 'update') {
                if (!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE pengguna SET nama_lengkap = ?, email = ?, no_hp = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nama_lengkap'],
                        $_POST['email'],
                        $_POST['no_hp'],
                        $hashed_password,
                        $_POST['role'],
                        $_POST['id']
                    ]);
                } else {
                    $stmt = $conn->prepare("UPDATE pengguna SET nama_lengkap = ?, email = ?, no_hp = ?, role = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nama_lengkap'],
                        $_POST['email'],
                        $_POST['no_hp'],
                        $_POST['role'],
                        $_POST['id']
                    ]);
                }
                $_SESSION['success'] = 'Pengguna berhasil diupdate!';
            } elseif ($_POST['action'] == 'delete') {
                // Cek jika ada pemesanan
                $stmt = $conn->prepare("SELECT COUNT(*) FROM pemesanan WHERE user_id = ?");
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $_SESSION['error'] = 'Tidak bisa menghapus pengguna yang memiliki riwayat pemesanan!';
                } else {
                    $stmt = $conn->prepare("DELETE FROM pengguna WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['success'] = 'Pengguna berhasil dihapus!';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        header('Location: pengguna.php');
        exit();
    }
}

// Ambil semua pengguna
$stmt = $conn->query("SELECT * FROM pengguna ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Data Pengguna';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-people"></i> Data Pengguna</h2>
            <p>Kelola pengguna TripGo (Customer & Admin)</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
            <i class="bi bi-plus-circle"></i> Tambah Pengguna
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
                <th>No</th>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <th>No HP</th>
                <th>Role</th>
                <th>Terdaftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($users as $user): 
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['no_hp']); ?></td>
                <td>
                    <?php if ($user['role'] == 'admin'): ?>
                        <span class="badge bg-danger"><i class="bi bi-shield-check"></i> Admin</span>
                    <?php else: ?>
                        <span class="badge bg-primary"><i class="bi bi-person"></i> Customer</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nama_lengkap']); ?>')">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal User -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="create">
                    <input type="hidden" name="id" id="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" class="form-control" name="no_hp" id="no_hp" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" id="password">
                        <small class="text-muted" id="Hint">Kosongkan jika tidak ingin mengubah </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
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
    document.getElementById('userForm').reset();
    document.getElementById('action').value = 'create';
    document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
    document.getElementById('password').required = true;
    document.getElementById('passwordHint').style.display = 'none';
}

function editUser(user) {
    document.getElementById('action').value = 'update';
    document.getElementById('user_id').value = user.id;
    document.getElementById('nama_lengkap').value = user.nama_lengkap;
    document.getElementById('email').value = user.email;
    document.getElementById('no_hp').value = user.no_hp;
    document.getElementById('role').value = user.role;
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Edit Pengguna';
    
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

function deleteUser(id, nama) {
    if (confirm('Yakin ingin menghapus pengguna ' + nama + '?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
