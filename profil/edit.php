<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Jika admin mengedit pengguna lain
$editUserId = $_GET['id'] ?? $_SESSION['user_id'];
$pageTitle = "Edit Profil";
$cssFile = "dashboard.css";

// Ambil data pengguna
$stmt = $pdo->prepare("
    SELECT u.*, d.nama_divisi 
    FROM pengguna u 
    LEFT JOIN divisi d ON u.id_divisi = d.id_divisi 
    WHERE u.id_pengguna = ?
");
$stmt->execute([$editUserId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . base_url());
    exit();
}

// Hanya admin yang bisa edit pengguna lain
if ($editUserId != $_SESSION['user_id'] && !isAdmin()) {
    header('Location: ' . base_url());
    exit();
}

// Ambil daftar divisi
$divisiList = [];
if (isAdmin()) {
    $divisiList = $pdo->query("SELECT * FROM divisi ORDER BY nama_divisi")->fetchAll();
}

// Update data pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $divisi = $_POST['id_divisi'] ?? null;
    $level = $_POST['level'] ?? $user['level'];
    $password = $_POST['password'] ?? null;
    
    try {
        // Jika admin yang mengedit
        if (isAdmin() && $editUserId != $_SESSION['user_id']) {
            if ($password) {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                                      nama_lengkap = ?, email = ?, id_divisi = ?, level = ?, password = ? 
                                      WHERE id_pengguna = ?");
                $stmt->execute([$nama, $email, $divisi, $level, $password, $editUserId]);
            } else {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                                      nama_lengkap = ?, email = ?, id_divisi = ?, level = ? 
                                      WHERE id_pengguna = ?");
                $stmt->execute([$nama, $email, $divisi, $level, $editUserId]);
            }
        } 
        // Jika mengedit profil sendiri
        else {
            if ($password) {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                                      nama_lengkap = ?, email = ?, password = ? 
                                      WHERE id_pengguna = ?");
                $stmt->execute([$nama, $email, $password, $editUserId]);
            } else {
                $stmt = $pdo->prepare("UPDATE pengguna SET 
                                      nama_lengkap = ?, email = ? 
                                      WHERE id_pengguna = ?");
                $stmt->execute([$nama, $email, $editUserId]);
            }
            
            // Update session jika mengedit diri sendiri
            if ($editUserId == $_SESSION['user_id']) {
                $_SESSION['nama_lengkap'] = $nama;
                $_SESSION['email'] = $email;
            }
        }
        
        $_SESSION['success'] = "Profil berhasil diperbarui";
        header('Location: ' . ($editUserId == $_SESSION['user_id'] ? 'edit.php' : 'index.php'));
        exit();
    } catch (PDOException $e) {
        $error = "Gagal memperbarui profil: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4"><?= ($editUserId == $_SESSION['user_id']) ? 'Edit Profil Saya' : 'Edit Pengguna' ?></h2>
    <a href="<?= ($editUserId == $_SESSION['user_id']) ? base_url() : 'index.php' ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Edit Profil</h5>
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
                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" 
                               name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        <div class="invalid-feedback">Harap isi nama lengkap</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" 
                               name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        <div class="invalid-feedback">Harap isi email yang valid</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if (isAdmin() && $editUserId != $_SESSION['user_id']): ?>
                        <div class="mb-3">
                            <label for="id_divisi" class="form-label">Divisi</label>
                            <select class="form-select" id="id_divisi" name="id_divisi">
                                <option value="">-- Pilih Divisi --</option>
                                <?php foreach ($divisiList as $divisi): ?>
                                    <option value="<?= $divisi['id_divisi'] ?>" 
                                        <?= ($divisi['id_divisi'] == $user['id_divisi']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($divisi['nama_divisi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">Level Akses</label>
                            <select class="form-select" id="level" name="level" required>
                                <option value="admin" <?= ($user['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= ($user['level'] == 'manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="staff" <?= ($user['level'] == 'staff') ? 'selected' : '' ?>>Staff</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Divisi</label>
                            <input type="text" class="form-control" value="<?= $user['nama_divisi'] ?? '-' ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Level Akses</label>
                            <input type="text" class="form-control" value="<?= ucfirst($user['level']) ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password">
                            <button class="btn btn-outline-secondary password-toggle" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-1 small"></div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$jsFile = "form-validation.js";
include '../includes/footer.php';
?>