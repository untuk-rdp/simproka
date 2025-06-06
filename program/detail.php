<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
$pageTitle = "Detail Program Kerja";
$cssFile = "dashboard.css";

// Ambil data program
$stmt = $pdo->prepare("
    SELECT p.*, d.nama_divisi 
    FROM program_kerja p 
    JOIN divisi d ON p.id_divisi = d.id_divisi 
    WHERE p.id_program = ?
");
$stmt->execute([$id]);
$program = $stmt->fetch();

if (!$program) {
    header('Location: index.php');
    exit();
}

// Ambil daftar kegiatan
$stmt = $pdo->prepare("
    SELECT k.*, u.nama_lengkap as penanggung_jawab 
    FROM kegiatan k 
    LEFT JOIN pengguna u ON k.penanggung_jawab = u.id_pengguna 
    WHERE k.id_program = ?
    ORDER BY k.tanggal_mulai
");
$stmt->execute([$id]);
$kegiatans = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0">Detail Program Kerja</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url() ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Program Kerja</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informasi Program</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4><?= htmlspecialchars($program['nama_program']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($program['deskripsi']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Divisi:</span>
                            <span><?= htmlspecialchars($program['nama_divisi']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-<?= 
                                match($program['status']) {
                                    'Selesai' => 'success',
                                    'Berjalan' => 'primary',
                                    'Tertunda' => 'danger',
                                    default => 'secondary'
                                }
                            ?>">
                                <?= $program['status'] ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Progress:</span>
                            <span><?= $program['persentase'] ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="progress mb-4" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: <?= $program['persentase'] ?>%" 
                         aria-valuenow="<?= $program['persentase'] ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= $program['persentase'] ?>%
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Tanggal Mulai</h6>
                                <p class="card-text h5"><?= date('d F Y', strtotime($program['tanggal_mulai'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Tanggal Selesai</h6>
                                <p class="card-text h5"><?= date('d F Y', strtotime($program['tanggal_selesai'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Anggaran</h6>
                        <p class="card-text h3 text-primary">Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>
            <?php if (isAdmin() || isManager()): ?>
            <div class="card-footer text-end">
                <a href="edit.php?id=<?= $program['id_program'] ?>" class="btn btn-warning">
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
                    <h5 class="mb-0">Kegiatan</h5>
                    <?php if (isAdmin() || isManager()): ?>
                        <a href="../kegiatan/tambah.php?program=<?= $program['id_program'] ?>" 
                           class="btn btn-sm btn-light">
                            <i class="bi bi-plus"></i> Tambah
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($kegiatans) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($kegiatans as $kegiatan): ?>
                            <a href="../kegiatan/detail.php?id=<?= $kegiatan['id_kegiatan'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></h6>
                                    <small class="text-<?= 
                                        match($kegiatan['status']) {
                                            'Selesai' => 'success',
                                            'Berjalan' => 'primary',
                                            'Tertunda' => 'danger',
                                            default => 'secondary'
                                        }
                                    ?>">
                                        <?= $kegiatan['status'] ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($kegiatan['tanggal_mulai'])) ?> - 
                                    <?= date('d M Y', strtotime($kegiatan['tanggal_selesai'])) ?>
                                </small>
                                <?php if ($kegiatan['penanggung_jawab']): ?>
                                    <div class="mt-1">
                                        <small class="text-muted">Penanggung jawab: <?= htmlspecialchars($kegiatan['penanggung_jawab']) ?></small>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="<?= base_url('assets/images/empty-state.svg') ?>" alt="No data" class="img-fluid mb-3" style="max-height: 100px;">
                        <p class="text-muted">Belum ada kegiatan untuk program ini</p>
                        <?php if (isAdmin() || isManager()): ?>
                            <a href="../kegiatan/tambah.php?program=<?= $program['id_program'] ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus"></i> Tambah Kegiatan
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>