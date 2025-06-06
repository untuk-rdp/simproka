<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$pageTitle = "Tambah Pengguna";
$cssFile = "dashboard.css";

// Ambil daftar divisi
$divisiList = $pdo->query("SELECT * FROM divisi ORDER BY nama_divisi")->fetchAll();

// Proses tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $divisi = $_POST['id_divisi'] ?: null;
    $level = $_POST['level'];

    try {
        $stmt = $pdo->prepare("INSERT INTO pengguna 
                              (username, password, nama_lengkap, email, id_divisi, level) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama, $email, $divisi, $level]);
        
        $_SESSION['success'] = "Pengguna berhasil ditambahkan";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambahkan pengguna: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Tambah Pengguna</h2>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Tambah Pengguna</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">Harap isi username</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary password-toggle" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Harap isi password</div>
                        <div class="password-strength mt-1 small"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        <div class="invalid-feedback">Harap isi nama lengkap</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">Harap isi email yang valid</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_divisi" class="form-label">Divisi</label>
                        <select class="form-select" id="id_divisi" name="id_divisi">
                            <option value="">-- Pilih Divisi --</option>
                            <?php foreach ($divisiList as $divisi): ?>
                                <option value="<?= $divisi['id_divisi'] ?>"><?= htmlspecialchars($divisi['nama_divisi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="level" class="form-label">Level Akses</label>
                        <select class="form-select" id="level" name="level" required>
                            <option value="admin">Admin</option>
                            <option value="manager" selected>Manager</option>
                            <option value="staff">Staff</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$jsFile = "form-validation.js";
include '../includes/footer.php';
?>