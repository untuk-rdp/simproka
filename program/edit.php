<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$pageTitle = "Edit Program Kerja";
$cssFile = "dashboard.css";

// Ambil data program
$stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id_program = ?");
$stmt->execute([$id]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: index.php');
    exit();
}

// Ambil daftar divisi
$divisiList = $pdo->query("SELECT * FROM divisi ORDER BY nama_divisi")->fetchAll();

// Proses update program
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_program'];
    $deskripsi = $_POST['deskripsi'];
    $divisi = $_POST['id_divisi'];
    $tglMulai = $_POST['tanggal_mulai'];
    $tglSelesai = $_POST['tanggal_selesai'];
    $anggaran = str_replace(['.', ','], '', $_POST['anggaran']);
    $status = $_POST['status'];
    $persentase = $_POST['persentase'];

    try {
        $stmt = $pdo->prepare("UPDATE program_kerja SET 
                              id_divisi = ?, nama_program = ?, deskripsi = ?, 
                              tanggal_mulai = ?, tanggal_selesai = ?, anggaran = ?, 
                              status = ?, persentase = ?
                              WHERE id_program = ?");
        $stmt->execute([$divisi, $nama, $deskripsi, $tglMulai, $tglSelesai, $anggaran, $status, $persentase, $id]);
        
        $_SESSION['success'] = "Program kerja berhasil diperbarui";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal memperbarui program: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Edit Program Kerja</h2>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Edit Program</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_program" class="form-label">Nama Program</label>
                        <input type="text" class="form-control" id="nama_program" 
                               name="nama_program" value="<?= htmlspecialchars($program['nama_program']) ?>" required>
                        <div class="invalid-feedback">Harap isi nama program</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($program['deskripsi']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_divisi" class="form-label">Divisi</label>
                        <select class="form-select" id="id_divisi" name="id_divisi" required <?= (!isAdmin()) ? 'disabled' : '' ?>>
                            <?php foreach ($divisiList as $divisi): ?>
                                <option value="<?= $divisi['id_divisi'] ?>" <?= ($divisi['id_divisi'] == $program['id_divisi']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($divisi['nama_divisi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!isAdmin()): ?>
                            <input type="hidden" name="id_divisi" value="<?= $program['id_divisi'] ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback">Harap pilih divisi</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="tanggal_mulai" 
                                       name="tanggal_mulai" value="<?= $program['tanggal_mulai'] ?>" required>
                                <div class="invalid-feedback">Harap isi tanggal mulai</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="tanggal_selesai" 
                                       name="tanggal_selesai" value="<?= $program['tanggal_selesai'] ?>" required>
                                <div class="invalid-feedback">Harap isi tanggal selesai</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="anggaran" class="form-label">Anggaran (Rp)</label>
                        <input type="text" class="form-control" id="anggaran" name="anggaran" 
                               value="<?= number_format($program['anggaran'], 0, ',', '.') ?>" 
                               oninput="formatCurrency(this)" required>
                        <div class="invalid-feedback">Harap isi anggaran</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Perencanaan" <?= ($program['status'] == 'Perencanaan') ? 'selected' : '' ?>>Perencanaan</option>
                                    <option value="Berjalan" <?= ($program['status'] == 'Berjalan') ? 'selected' : '' ?>>Berjalan</option>
                                    <option value="Tertunda" <?= ($program['status'] == 'Tertunda') ? 'selected' : '' ?>>Tertunda</option>
                                    <option value="Selesai" <?= ($program['status'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="persentase" class="form-label">Progress (%)</label>
                                <input type="number" class="form-control" id="persentase" 
                                       name="persentase" min="0" max="100" value="<?= $program['persentase'] ?>" required>
                            </div>
                        </div>
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

<script>
// Format input currency
function formatCurrency(input) {
    // Hapus semua karakter selain angka
    let value = input.value.replace(/[^0-9]/g, '');
    
    // Format dengan titik sebagai pemisah ribuan
    if (value.length > 0) {
        value = parseInt(value).toLocaleString('id-ID');
    }
    
    input.value = value;
}
</script>

<?php include '../includes/footer.php'; ?>