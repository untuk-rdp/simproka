<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$pageTitle = "Manajemen Pengguna";
$cssFile = "dashboard.css";

// Hanya admin yang bisa akses manajemen pengguna
if (!isAdmin()) {
    header('Location: ' . base_url('profil/edit.php'));
    exit();
}

// Ambil semua pengguna
$stmt = $pdo->query("
    SELECT u.*, d.nama_divisi 
    FROM pengguna u 
    LEFT JOIN divisi d ON u.id_divisi = d.id_divisi 
    ORDER BY u.level, u.nama_lengkap
");
$users = $stmt->fetchAll();

// Proses hapus pengguna
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Jangan izinkan menghapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Anda tidak dapat menghapus akun sendiri";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Pengguna berhasil dihapus";
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menghapus pengguna: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Manajemen Pengguna</h2>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Pengguna
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Daftar Pengguna</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Divisi</th>
                        <th>Level</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['nama_divisi'] ? htmlspecialchars($user['nama_divisi']) : '-' ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    match($user['level']) {
                                        'admin' => 'danger',
                                        'manager' => 'primary',
                                        default => 'secondary'
                                    }
                                ?>">
                                    <?= ucfirst($user['level']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="edit.php?id=<?= $user['id_pengguna'] ?>" 
                                       class="btn btn-warning" 
                                       data-bs-toggle="tooltip" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?hapus=<?= $user['id_pengguna'] ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Yakin ingin menghapus pengguna ini?')"
                                       data-bs-toggle="tooltip" 
                                       title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>