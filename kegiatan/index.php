<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$pageTitle = "Manajemen Kegiatan";
$cssFile = "dashboard.css";

// Ambil parameter filter
$filter_divisi = isset($_GET['divisi']) ? $_GET['divisi'] : null;
$filter_program = isset($_GET['program']) ? $_GET['program'] : null;
$filter_status = isset($_GET['status']) ? $_GET['status'] : null;

// Query dasar
$sql = "
    SELECT k.*, p.nama_program, d.nama_divisi, u.nama_lengkap as penanggung_jawab 
    FROM kegiatan k 
    JOIN program_kerja p ON k.id_program = p.id_program 
    JOIN divisi d ON p.id_divisi = d.id_divisi 
    LEFT JOIN pengguna u ON k.penanggung_jawab = u.id_pengguna 
";

// Tambahkan kondisi berdasarkan level pengguna dan filter
$conditions = [];
$params = [];

if (!isAdmin()) {
    if (isManager()) {
        $conditions[] = "p.id_divisi = ?";
        $params[] = $_SESSION['id_divisi'];
    } else {
        $conditions[] = "k.penanggung_jawab = ?";
        $params[] = $_SESSION['user_id'];
    }
}

// Tambahkan filter divisi jika dipilih
if ($filter_divisi && isAdmin()) {
    $conditions[] = "p.id_divisi = ?";
    $params[] = $filter_divisi;
}

// Tambahkan filter program jika dipilih
if ($filter_program) {
    $conditions[] = "k.id_program = ?";
    $params[] = $filter_program;
}

// Tambahkan filter status jika dipilih
if ($filter_status) {
    $conditions[] = "k.status = ?";
    $params[] = $filter_status;
}

// Gabungkan kondisi jika ada
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY k.tanggal_mulai DESC";

// Eksekusi query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kegiatans = $stmt->fetchAll();

// Hitung total saldo
$totalSaldo = 0;
foreach ($kegiatans as $kegiatan) {
    $anggaran = $kegiatan['anggaran'] ?? 0;
    $realisasi = $kegiatan['realisasi'] ?? 0;
    $totalSaldo += ($anggaran - $realisasi);
}

// Ambil daftar divisi untuk filter (hanya admin)
$divisi_list = [];
if (isAdmin()) {
    $stmt = $pdo->query("SELECT id_divisi, nama_divisi FROM divisi ORDER BY nama_divisi");
    $divisi_list = $stmt->fetchAll();
}

// Ambil daftar program untuk filter
$program_list = [];
$program_sql = "SELECT id_program, nama_program FROM program_kerja";
if (!isAdmin()) {
    $program_sql .= " WHERE id_divisi = ?";
    $program_stmt = $pdo->prepare($program_sql);
    $program_stmt->execute([$_SESSION['id_divisi']]);
} else {
    $program_stmt = $pdo->query($program_sql);
}
$program_list = $program_stmt->fetchAll();

// Daftar status untuk filter
$status_list = ['Belum Dimulai', 'Berjalan', 'Tertunda', 'Selesai'];

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Manajemen Kegiatan</h2>
    <?php if (isAdmin() || isManager()): ?>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Kegiatan Baru
    </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <form method="get" class="row g-3 align-items-center">
            <?php if (isAdmin()): ?>
            <div class="col-md-3">
                <label for="divisi" class="form-label">Filter Divisi:</label>
                <select name="divisi" id="divisi" class="form-select">
                    <option value="">Semua Divisi</option>
                    <?php foreach ($divisi_list as $divisi): ?>
                        <option value="<?= $divisi['id_divisi'] ?>" <?= ($filter_divisi == $divisi['id_divisi']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($divisi['nama_divisi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3">
                <label for="program" class="form-label">Filter Program:</label>
                <select name="program" id="program" class="form-select">
                    <option value="">Semua Program</option>
                    <?php foreach ($program_list as $program): ?>
                        <option value="<?= $program['id_program'] ?>" <?= ($filter_program == $program['id_program']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($program['nama_program']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="status" class="form-label">Filter Status:</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php foreach ($status_list as $status): ?>
                        <option value="<?= $status ?>" <?= ($filter_status == $status) ? 'selected' : '' ?>>
                            <?= $status ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="?" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th width="18%">Nama Kegiatan</th>
                        <th width="12%">Program</th>
                        <?php if (isAdmin()): ?>
                            <th width="8%">Divisi</th>
                        <?php endif; ?>
                        <th width="8%">Penanggung Jawab</th>
                        <th width="8%">Periode</th>
                        <th width="10%">Anggaran (Rp)</th>
                        <th width="10%">Realisasi (Rp)</th>
                        <th width="10%">Saldo (Rp)</th>
                        <th width="8%">Status</th>
                        <th width="8%">Progress</th>
                        <th width="8%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kegiatans)): ?>
                        <tr>
                            <td colspan="<?= isAdmin() ? 12 : 11 ?>" class="text-center">Tidak ada data kegiatan</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($kegiatans as $kegiatan): 
                            $anggaran = $kegiatan['anggaran'] ?? 0;
                            $realisasi = $kegiatan['realisasi'] ?? 0;
                            $saldo = $anggaran - $realisasi;
                            $saldoClass = $saldo < 0 ? 'text-danger' : ($saldo > 0 ? 'text-success' : '');
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></strong>
                                    <?php if ($kegiatan['deskripsi']): ?>
                                        <p class="text-muted mb-0 small"><?= htmlspecialchars(substr($kegiatan['deskripsi'], 0, 50)) ?>...</p>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($kegiatan['nama_program']) ?></td>
                                <?php if (isAdmin()): ?>
                                    <td><?= htmlspecialchars($kegiatan['nama_divisi']) ?></td>
                                <?php endif; ?>
                                <td><?= $kegiatan['penanggung_jawab'] ? htmlspecialchars($kegiatan['penanggung_jawab']) : '-' ?></td>
                                <td>
                                    <?= date('d M', strtotime($kegiatan['tanggal_mulai'])) ?><br>
                                    <span class="text-muted small">s/d</span><br>
                                    <?= date('d M', strtotime($kegiatan['tanggal_selesai'])) ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($anggaran, 0, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <?= number_format($realisasi, 0, ',', '.') ?>
                                </td>
                                <td class="text-end <?= $saldoClass ?>">
                                    <?= number_format($saldo, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        match($kegiatan['status']) {
                                            'Selesai' => 'success',
                                            'Berjalan' => 'primary',
                                            'Tertunda' => 'danger',
                                            'Belum Dimulai' => 'secondary',
                                            default => 'warning'
                                        }
                                    ?>">
                                        <?= $kegiatan['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped" 
                                             role="progressbar" 
                                             style="width: <?= $kegiatan['persentase'] ?>%" 
                                             aria-valuenow="<?= $kegiatan['persentase'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $kegiatan['persentase'] ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="detail.php?id=<?= $kegiatan['id_kegiatan'] ?>" 
                                           class="btn btn-info" 
                                           data-bs-toggle="tooltip" 
                                           title="Detail">
                                            <i class="bi bi-eye">detail</i>
                                        </a>
                                        <?php if (isAdmin() || isManager() || $_SESSION['user_id'] == $kegiatan['penanggung_jawab']): ?>
                                            <a href="edit.php?id=<?= $kegiatan['id_kegiatan'] ?>" 
                                               class="btn btn-warning" 
                                               data-bs-toggle="tooltip" 
                                               title="Edit">
                                                <i class="bi bi-pencil">edit</i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>