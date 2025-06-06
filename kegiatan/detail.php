<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$pageTitle = "Detail Kegiatan";
$cssFile = "dashboard.css";

// Helper function to format file sizes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Ambil data kegiatan
$stmt = $pdo->prepare("
    SELECT k.*, p.nama_program, p.id_divisi, d.nama_divisi, 
           u.nama_lengkap as penanggung_jawab_nama
    FROM kegiatan k 
    JOIN program_kerja p ON k.id_program = p.id_program 
    JOIN divisi d ON p.id_divisi = d.id_divisi 
    LEFT JOIN pengguna u ON k.penanggung_jawab = u.id_pengguna 
    WHERE k.id_kegiatan = ?
");
$stmt->execute([$id]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    header('Location: index.php');
    exit();
}

// Ambil semua dokumen pendukung
$dokumen = $pdo->prepare("
    SELECT * FROM dokumen 
    WHERE id_kegiatan = ? 
    ORDER BY tanggal_upload DESC
");
$dokumen->execute([$id]);
$dokumen = $dokumen->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0">Detail Kegiatan</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url() ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Kegiatan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informasi Kegiatan</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($kegiatan['deskripsi']) ?></p>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Program:</span>
                            <span><?= htmlspecialchars($kegiatan['nama_program']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Divisi:</span>
                            <span><?= htmlspecialchars($kegiatan['nama_divisi']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-<?= 
                                match($kegiatan['status']) {
                                    'Selesai' => 'success',
                                    'Berjalan' => 'primary',
                                    'Tertunda' => 'danger',
                                    default => 'secondary'
                                }
                            ?>">
                                <?= $kegiatan['status'] ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Progress:</span>
                            <span><?= $kegiatan['persentase'] ?>%</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Penanggung Jawab:</span>
                            <span><?= $kegiatan['penanggung_jawab_nama'] ? htmlspecialchars($kegiatan['penanggung_jawab_nama']) : '-' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="progress mb-4" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: <?= $kegiatan['persentase'] ?>%" 
                         aria-valuenow="<?= $kegiatan['persentase'] ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= $kegiatan['persentase'] ?>%
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Tanggal Mulai</h6>
                                <p class="card-text h5"><?= date('d F Y', strtotime($kegiatan['tanggal_mulai'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Tanggal Selesai</h6>
                                <p class="card-text h5"><?= date('d F Y', strtotime($kegiatan['tanggal_selesai'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Anggaran</h6>
                                <p class="card-text h5">Rp <?= number_format($kegiatan['anggaran'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Realisasi</h6>
                                <p class="card-text h5">Rp <?= number_format($kegiatan['realisasi'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Saldo</h6>
                                <p class="card-text h5">Rp <?= number_format($kegiatan['anggaran'] - $kegiatan['realisasi'], 0, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($kegiatan['catatan']): ?>
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Catatan</h6>
                        <p class="card-text"><?= nl2br(htmlspecialchars($kegiatan['catatan'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php if (isAdmin() || isManager() || $_SESSION['user_id'] == $kegiatan['penanggung_jawab']): ?>
            <div class="card-footer text-end">
                <a href="edit.php?id=<?= $kegiatan['id_kegiatan'] ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dokumen Pendukung</h5>
                    <?php if (isAdmin() || isManager() || $_SESSION['user_id'] == $kegiatan['penanggung_jawab']): ?>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-plus"></i> Upload
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($dokumen) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dokumen as $file): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width: 60%;">
                                        <i class="bi bi-file-earmark me-2"></i>
                                        <span title="<?= htmlspecialchars($file['nama_file']) ?>">
                                            <?= htmlspecialchars($file['nama_file']) ?>
                                        </span>
                                    </div>
                                    <div class="btn-group">
                                        <a href="<?= base_url('uploads/' . $file['path_file']) ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           target="_blank" 
                                           data-bs-toggle="tooltip" 
                                           title="Lihat">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('uploads/' . $file['path_file']) ?>" 
                                           class="btn btn-sm btn-outline-success" 
                                           download
                                           data-bs-toggle="tooltip" 
                                           title="Download (<?= formatBytes($file['ukuran_file']) ?>)">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php if (isAdmin() || isManager() || $_SESSION['user_id'] == $kegiatan['penanggung_jawab']): ?>
                                            <a href="hapus_dokumen.php?id=<?= $file['id_dokumen'] ?>&kegiatan=<?= $kegiatan['id_kegiatan'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Yakin ingin menghapus dokumen ini?')"
                                               data-bs-toggle="tooltip" 
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Diupload: <?= date('d M Y H:i', strtotime($file['tanggal_upload'])) ?>
                                    <span class="float-end"><?= formatBytes($file['ukuran_file']) ?></span>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="<?= base_url('assets/images/empty-state.svg') ?>" alt="No data" class="img-fluid mb-3" style="max-height: 100px;">
                        <p class="text-muted">Belum ada dokumen untuk kegiatan ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Dokumen -->
<?php if (isAdmin() || isManager() || $_SESSION['user_id'] == $kegiatan['penanggung_jawab']): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="upload_dokumen.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan'] ?>">
                    
                    <div class="mb-3">
                        <label for="nama_file" class="form-label">Nama Dokumen</label>
                        <input type="text" class="form-control" id="nama_file" name="nama_file" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <div class="form-text">
                            Format yang didukung: PDF Maksimal 10MB
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>