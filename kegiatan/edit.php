<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$pageTitle = "Edit Kegiatan";
$cssFile = "dashboard.css";

// Ambil data kegiatan
$stmt = $pdo->prepare("
    SELECT k.*, p.nama_program, p.id_divisi 
    FROM kegiatan k 
    JOIN program_kerja p ON k.id_program = p.id_program 
    WHERE k.id_kegiatan = ?
");
$stmt->execute([$id]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    header('Location: index.php');
    exit();
}

// Cek hak akses
if (!isAdmin() && !isManager() && $_SESSION['user_id'] != $kegiatan['penanggung_jawab']) {
    header('Location: index.php');
    exit();
}

// Ambil daftar program
if (isAdmin()) {
    $programList = $pdo->query("
        SELECT p.id_program, p.nama_program, d.nama_divisi 
        FROM program_kerja p 
        JOIN divisi d ON p.id_divisi = d.id_divisi 
        ORDER BY p.tanggal_mulai DESC
    ")->fetchAll();
} else {
    $programList = $pdo->prepare("
        SELECT p.id_program, p.nama_program, d.nama_divisi 
        FROM program_kerja p 
        JOIN divisi d ON p.id_divisi = d.id_divisi 
        WHERE p.id_divisi = ? 
        ORDER BY p.tanggal_mulai DESC
    ");
    $programList->execute([$_SESSION['id_divisi']]);
    $programList = $programList->fetchAll();
}

// Ambil daftar pengguna
$userList = $pdo->query("
    SELECT id_pengguna, nama_lengkap 
    FROM pengguna 
    WHERE level IN ('manager', 'staff') 
    ORDER BY nama_lengkap
")->fetchAll();

// Proses update kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programId = $_POST['id_program'];
    $nama = $_POST['nama_kegiatan'];
    $deskripsi = $_POST['deskripsi'];
    $tglMulai = $_POST['tanggal_mulai'];
    $tglSelesai = $_POST['tanggal_selesai'];
    $penanggungJawab = $_POST['penanggung_jawab'] ?: null;
    $anggaran = $_POST['anggaran'] ?: 0;
    $realisasi = $_POST['realisasi'] ?: 0;
    $status = $_POST['status'];
    $persentase = $_POST['persentase'];
    $catatan = $_POST['catatan'];

    try {
        $stmt = $pdo->prepare("UPDATE kegiatan SET 
                              id_program = ?, nama_kegiatan = ?, deskripsi = ?, 
                              tanggal_mulai = ?, tanggal_selesai = ?, penanggung_jawab = ?, 
                              anggaran = ?, realisasi = ?, status = ?, persentase = ?, catatan = ?
                              WHERE id_kegiatan = ?");
        $stmt->execute([$programId, $nama, $deskripsi, $tglMulai, $tglSelesai, 
                        $penanggungJawab, $anggaran, $realisasi, $status, $persentase, $catatan, $id]);
        
        $_SESSION['success'] = "Kegiatan berhasil diperbarui";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal memperbarui kegiatan: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Edit Kegiatan</h2>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Edit Kegiatan</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="id_program" class="form-label">Program</label>
                        <select class="form-select" id="id_program" name="id_program" required <?= (!isAdmin() && !isManager()) ? 'disabled' : '' ?>>
                            <?php foreach ($programList as $program): ?>
                                <option value="<?= $program['id_program'] ?>" 
                                    <?= ($program['id_program'] == $kegiatan['id_program']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($program['nama_program']) ?> (<?= htmlspecialchars($program['nama_divisi']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!isAdmin() && !isManager()): ?>
                            <input type="hidden" name="id_program" value="<?= $kegiatan['id_program'] ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback">Harap pilih program</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                        <input type="text" class="form-control" id="nama_kegiatan" 
                               name="nama_kegiatan" value="<?= htmlspecialchars($kegiatan['nama_kegiatan']) ?>" required>
                        <div class="invalid-feedback">Harap isi nama kegiatan</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($kegiatan['deskripsi']) ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="tanggal_mulai" 
                                       name="tanggal_mulai" value="<?= $kegiatan['tanggal_mulai'] ?>" required>
                                <div class="invalid-feedback">Harap isi tanggal mulai</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="tanggal_selesai" 
                                       name="tanggal_selesai" value="<?= $kegiatan['tanggal_selesai'] ?>" required>
                                <div class="invalid-feedback">Harap isi tanggal selesai</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                        <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" <?= (!isAdmin() && !isManager()) ? 'disabled' : '' ?>>
                            <option value="">-- Pilih Penanggung Jawab --</option>
                            <?php foreach ($userList as $user): ?>
                                <option value="<?= $user['id_pengguna'] ?>" 
                                    <?= ($user['id_pengguna'] == $kegiatan['penanggung_jawab']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!isAdmin() && !isManager()): ?>
                            <input type="hidden" name="penanggung_jawab" value="<?= $kegiatan['penanggung_jawab'] ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="anggaran" class="form-label">Anggaran (Rp)</label>
                                <input type="number" class="form-control" id="anggaran" 
                                       name="anggaran" min="0" value="<?= $kegiatan['anggaran'] ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="realisasi" class="form-label">Realisasi (Rp)</label>
                                <input type="number" class="form-control" id="realisasi" 
                                       name="realisasi" min="0" value="<?= $kegiatan['realisasi'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Belum Dimulai" <?= ($kegiatan['status'] == 'Belum Dimulai') ? 'selected' : '' ?>>Belum Dimulai</option>
                                    <option value="Berjalan" <?= ($kegiatan['status'] == 'Berjalan') ? 'selected' : '' ?>>Berjalan</option>
                                    <option value="Tertunda" <?= ($kegiatan['status'] == 'Tertunda') ? 'selected' : '' ?>>Tertunda</option>
                                    <option value="Selesai" <?= ($kegiatan['status'] == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="persentase" class="form-label">Progress (%)</label>
                                <input type="number" class="form-control" id="persentase" 
                                       name="persentase" min="0" max="100" value="<?= $kegiatan['persentase'] ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="catatan" class="form-label">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2"><?= htmlspecialchars($kegiatan['catatan']) ?></textarea>
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

<?php include '../includes/footer.php'; ?>