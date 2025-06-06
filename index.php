<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

$pageTitle = "Dashboard";

// Hitung total program, selesai, dan berjalan berdasarkan divisi user
if (!isAdmin()) {
    // Untuk non-admin, hitung berdasarkan divisinya
    $id_divisi = $_SESSION['id_divisi'];
    
    // Hitung total program divisi
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM program_kerja WHERE id_divisi = ?");
    $stmt->execute([$id_divisi]);
    $divisiProgramStats = $stmt->fetch();
    $totalProgram = $divisiProgramStats['total'];
    
    // Hitung program selesai divisi
    $stmt = $pdo->prepare("SELECT COUNT(*) as selesai FROM program_kerja WHERE id_divisi = ? AND status = 'Selesai'");
    $stmt->execute([$id_divisi]);
    $programSelesai = $stmt->fetch()['selesai'];
    
    // Hitung program berjalan divisi
    $stmt = $pdo->prepare("SELECT COUNT(*) as berjalan FROM program_kerja WHERE id_divisi = ? AND status = 'Berjalan'");
    $stmt->execute([$id_divisi]);
    $programBerjalan = $stmt->fetch()['berjalan'];
    
    // Ambil data anggaran dan realisasi untuk divisi user
    $stmt = $pdo->prepare("
        SELECT 
            nama_program,
            anggaran,
            (SELECT SUM(realisasi) FROM kegiatan WHERE id_program = program_kerja.id_program) as realisasi,
            (anggaran - (SELECT SUM(realisasi) FROM kegiatan WHERE id_program = program_kerja.id_program)) as sisa
        FROM program_kerja 
        WHERE id_divisi = ?
        ORDER BY tanggal_mulai
    ");
    $stmt->execute([$id_divisi]);
    $anggaranRealisasi = $stmt->fetchAll();
    
    // Siapkan data untuk chart
    $chartLabels = [];
    $chartAnggaran = [];
    $chartRealisasi = [];
    $chartSisa = [];
    
    foreach ($anggaranRealisasi as $data) {
        $chartLabels[] = $data['nama_program'];
        $chartAnggaran[] = $data['anggaran'];
        $chartRealisasi[] = $data['realisasi'] ?? 0;
        $chartSisa[] = $data['sisa'] ?? 0;
    }
    
    // Ambil program divisi
    $stmt = $pdo->prepare("SELECT * FROM program_kerja WHERE id_divisi = ? ORDER BY created_at DESC");
    $stmt->execute([$id_divisi]);
    $recentPrograms = $stmt->fetchAll();
} else {
    // Untuk admin, hitung semua program
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM program_kerja");
    $totalProgram = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as selesai FROM program_kerja WHERE status = 'Selesai'");
    $programSelesai = $stmt->fetch()['selesai'];

    $stmt = $pdo->query("SELECT COUNT(*) as berjalan FROM program_kerja WHERE status = 'Berjalan'");
    $programBerjalan = $stmt->fetch()['berjalan'];
    
    // Ambil semua program dan kelompokkan berdasarkan divisi
    $stmt = $pdo->query("
        SELECT d.nama_divisi, p.* 
        FROM program_kerja p 
        JOIN divisi d ON p.id_divisi = d.id_divisi 
        ORDER BY d.nama_divisi, p.tanggal_mulai DESC
    ");
    $allPrograms = $stmt->fetchAll();
    
    $groupedPrograms = [];
    foreach ($allPrograms as $program) {
        $divisiName = $program['nama_divisi'];
        if (!isset($groupedPrograms[$divisiName])) {
            $groupedPrograms[$divisiName] = [];
        }
        $groupedPrograms[$divisiName][] = $program;
    }
    
    // Ambil data progress divisi (hanya untuk admin)
    $stmt = $pdo->query("SELECT * FROM divisi ORDER BY id_divisi");
    $divisiList = $stmt->fetchAll();

    // Buat mapping nomor urut untuk divisi
    $divisiNumberMapping = [];
    $counter = 1;
    foreach ($divisiList as $divisi) {
        $divisiNumberMapping[$divisi['nama_divisi']] = $counter++;
    }

    $divisiProgress = [];
    $chartData = ['labels' => [], 'program_selesai' => [], 'program_berjalan' => [], 'kegiatan_selesai' => [], 'kegiatan_berjalan' => []];
    
    // Data untuk chart anggaran dan realisasi per divisi
    $anggaranChartData = [];
    
    foreach ($divisiList as $divisi) {
        // Hitung total anggaran, realisasi, dan sisa per divisi
        $stmt = $pdo->prepare("
            SELECT 
                SUM(anggaran) as total_anggaran,
                (SELECT SUM(realisasi) FROM kegiatan k JOIN program_kerja p ON k.id_program = p.id_program WHERE p.id_divisi = ?) as total_realisasi
            FROM program_kerja
            WHERE id_divisi = ?
        ");
        $stmt->execute([$divisi['id_divisi'], $divisi['id_divisi']]);
        $anggaranStats = $stmt->fetch();
        
        $anggaranChartData[$divisi['nama_divisi']] = [
            'anggaran' => $anggaranStats['total_anggaran'] ?? 0,
            'realisasi' => $anggaranStats['total_realisasi'] ?? 0,
            'sisa' => ($anggaranStats['total_anggaran'] ?? 0) - ($anggaranStats['total_realisasi'] ?? 0)
        ];
        
        // Hitung program per status
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN p.status = 'Perencanaan' THEN 1 ELSE 0 END) as perencanaan,
                SUM(CASE WHEN p.status = 'Berjalan' THEN 1 ELSE 0 END) as berjalan,
                SUM(CASE WHEN p.status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN p.status = 'Tertunda' THEN 1 ELSE 0 END) as tertunda
            FROM program_kerja p
            WHERE p.id_divisi = ?
        ");
        $stmt->execute([$divisi['id_divisi']]);
        $programStats = $stmt->fetch();
        
        // Hitung kegiatan per status
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN k.status = 'Belum Dimulai' THEN 1 ELSE 0 END) as belum_dimulai,
                SUM(CASE WHEN k.status = 'Berjalan' THEN 1 ELSE 0 END) as berjalan,
                SUM(CASE WHEN k.status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN k.status = 'Tertunda' THEN 1 ELSE 0 END) as tertunda
            FROM kegiatan k
            JOIN program_kerja p ON k.id_program = p.id_program
            WHERE p.id_divisi = ?
        ");
        $stmt->execute([$divisi['id_divisi']]);
        $kegiatanStats = $stmt->fetch();
        
        // Pastikan tidak ada nilai null
        $programStats = array_map(function($value) {
            return $value !== null ? $value : 0;
        }, $programStats);
        
        $kegiatanStats = array_map(function($value) {
            return $value !== null ? $value : 0;
        }, $kegiatanStats);
        
        $divisiProgress[$divisi['id_divisi']] = [
            'id_divisi' => $divisi['id_divisi'],
            'nama_divisi' => $divisi['nama_divisi'],
            'program' => $programStats,
            'kegiatan' => $kegiatanStats
        ];
        
        // Data untuk chart batang (gunakan nomor urut)
        $chartData['labels'][] = $divisiNumberMapping[$divisi['nama_divisi']];
        $chartData['program_selesai'][] = $programStats['selesai'];
        $chartData['program_berjalan'][] = $programStats['berjalan'];
        $chartData['kegiatan_selesai'][] = $kegiatanStats['selesai'];
        $chartData['kegiatan_berjalan'][] = $kegiatanStats['berjalan'];
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Dashboard</h2>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Program <?= !isAdmin() ? 'Divisi' : '' ?></h5>
                    <h1 class="display-4"><?= $totalProgram ?></h1>
                    <?php if (!isAdmin()): ?>
                        <small>Total program di divisi Anda</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Program Selesai <?= !isAdmin() ? 'Divisi' : '' ?></h5>
                    <h1 class="display-4"><?= $programSelesai ?></h1>
                    <?php if (!isAdmin()): ?>
                        <small>Program selesai di divisi Anda</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Program Berjalan <?= !isAdmin() ? 'Divisi' : '' ?></h5>
                    <h1 class="display-4"><?= $programBerjalan ?></h1>
                    <?php if (!isAdmin()): ?>
                        <small>Program berjalan di divisi Anda</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!isAdmin() && !empty($chartLabels)): ?>
    <!-- Grafik Anggaran dan Realisasi untuk User Divisi -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5>Grafik Anggaran & Realisasi Program Divisi Anda</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="anggaranRealisasiChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isAdmin()): ?>
    <!-- Progress Divisi Section (Only for Admin) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h4>Grafik Program & Kegiatan Divisi</h4>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 300px; margin-bottom: 30px;">
                <canvas id="barChart"></canvas>
            </div>
            <div class="divisi-legend mt-3">
    <h6>Keterangan Nomor Divisi:</h6>
    <ul class="list-unstyled">
        <?php foreach ($divisiNumberMapping as $nama_divisi => $nomor): ?>
            <li><strong><?= $nomor ?>:</strong> <?= $nama_divisi ?></li>
        <?php endforeach; ?>
    </ul>
</div>
        </div>
    </div>
    
    <!-- Grafik Anggaran dan Realisasi per Divisi untuk Admin -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>Grafik Anggaran & Realisasi per Divisi</h4>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height: 400px;">
                <canvas id="anggaranDivisiChart"></canvas>
            </div>
            <div class="divisi-legend mt-3">
    <h6>Keterangan Nomor Divisi:</h6>
    <ul class="list-unstyled">
        <?php foreach ($divisiNumberMapping as $nama_divisi => $nomor): ?>
            <li><strong><?= $nomor ?>:</strong> <?= $nama_divisi ?></li>
        <?php endforeach; ?>
    </ul>
</div>
        </div>
    </div>
    
    <!-- Progress Tiap Divisi -->
    <?php foreach ($divisiProgress as $id_divisi => $divisi): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><?= htmlspecialchars($divisi['nama_divisi']) ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Diagram Lingkaran Program -->
                    <div class="col-md-6">
                        <h5>Program Kerja</h5>
                        <p>Total: <?= $divisi['program']['total'] ?></p>
                        <div class="chart-container" style="position: relative; height: 300px; margin-bottom: 30px;">
                            <?php if ($divisi['program']['total'] > 0): ?>
                                <canvas id="programChart-<?= $id_divisi ?>"></canvas>
                            <?php else: ?>
                                <div class="no-data" style="text-align: center; padding: 50px; background-color: #f8f9fa; border-radius: 5px; color: #6c757d;">
                                    <i class="fas fa-chart-pie fa-3x mb-2"></i><br>
                                    Tidak ada data program
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Diagram Lingkaran Kegiatan -->
                    <div class="col-md-6">
                        <h5>Kegiatan</h5>
                        <p>Total: <?= $divisi['kegiatan']['total'] ?></p>
                        <div class="chart-container" style="position: relative; height: 300px; margin-bottom: 30px;">
                            <?php if ($divisi['kegiatan']['total'] > 0): ?>
                                <canvas id="kegiatanChart-<?= $id_divisi ?>"></canvas>
                            <?php else: ?>
                                <div class="no-data" style="text-align: center; padding: 50px; background-color: #f8f9fa; border-radius: 5px; color: #6c757d;">
                                    <i class="fas fa-chart-pie fa-3x mb-2"></i><br>
                                    Tidak ada data kegiatan
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar (Tambahan) -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="progress-container" style="margin-bottom: 20px;">
                            <h6>Detail Program</h6>
                            <div class="progress mb-2" style="height: 20px;">
                                <?php if ($divisi['program']['total'] > 0): ?>
                                    <div class="progress-bar bg-success" style="width: <?= ($divisi['program']['selesai']/$divisi['program']['total'])*100 ?>%">
                                        Selesai (<?= $divisi['program']['selesai'] ?>)
                                    </div>
                                    <div class="progress-bar bg-primary" style="width: <?= ($divisi['program']['berjalan']/$divisi['program']['total'])*100 ?>%">
                                        Berjalan (<?= $divisi['program']['berjalan'] ?>)
                                    </div>
                                    <div class="progress-bar bg-secondary" style="width: <?= ($divisi['program']['perencanaan']/$divisi['program']['total'])*100 ?>%">
                                        Perencanaan (<?= $divisi['program']['perencanaan'] ?>)
                                    </div>
                                    <div class="progress-bar bg-danger" style="width: <?= ($divisi['program']['tertunda']/$divisi['program']['total'])*100 ?>%">
                                        Tertunda (<?= $divisi['program']['tertunda'] ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-light text-dark" style="width: 100%">
                                        Tidak ada program
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="progress-container" style="margin-bottom: 20px;">
                            <h6>Detail Kegiatan</h6>
                            <div class="progress mb-2" style="height: 20px;">
                                <?php if ($divisi['kegiatan']['total'] > 0): ?>
                                    <div class="progress-bar bg-success" style="width: <?= ($divisi['kegiatan']['selesai']/$divisi['kegiatan']['total'])*100 ?>%">
                                        Selesai (<?= $divisi['kegiatan']['selesai'] ?>)
                                    </div>
                                    <div class="progress-bar bg-primary" style="width: <?= ($divisi['kegiatan']['berjalan']/$divisi['kegiatan']['total'])*100 ?>%">
                                        Berjalan (<?= $divisi['kegiatan']['berjalan'] ?>)
                                    </div>
                                    <div class="progress-bar bg-secondary" style="width: <?= ($divisi['kegiatan']['belum_dimulai']/$divisi['kegiatan']['total'])*100 ?>%">
                                        Belum Dimulai (<?= $divisi['kegiatan']['belum_dimulai'] ?>)
                                    </div>
                                    <div class="progress-bar bg-danger" style="width: <?= ($divisi['kegiatan']['tertunda']/$divisi['kegiatan']['total'])*100 ?>%">
                                        Tertunda (<?= $divisi['kegiatan']['tertunda'] ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar bg-light text-dark" style="width: 100%">
                                        Tidak ada kegiatan
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Program Kerja Divisi -->
                <div class="mt-4">
                    <h5 class="mb-3"><b>Program Kerja <?= htmlspecialchars($divisi['nama_divisi']) ?></b></h5>
                    <div class="table-responsive">
                        <?php if (isset($groupedPrograms[$divisi['nama_divisi']]) && !empty($groupedPrograms[$divisi['nama_divisi']])): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Program</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Tanggal Selesai</th>
                                        <th>Anggaran</th>
                                        <th>Realisasi</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($groupedPrograms[$divisi['nama_divisi']] as $program): ?>
                                        <tr>
                                            <td data-label="No"><?= $no++ ?></td>
                                            <td data-label="Nama Program"><?= htmlspecialchars($program['nama_program']) ?></td>
                                            <td data-label="Tanggal Mulai"><?= $program['tanggal_mulai'] ? date('d M Y', strtotime($program['tanggal_mulai'])) : '-' ?></td>
                                            <td data-label="Tanggal Selesai"><?= $program['tanggal_selesai'] ? date('d M Y', strtotime($program['tanggal_selesai'])) : '-' ?></td>
                                            <td data-label="Anggaran" class="text-end"><?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                            <td data-label="Realisasi" class="text-end">
                                                <?php 
                                                // Calculate total realisasi from kegiatan
                                                $stmt = $pdo->prepare("SELECT SUM(realisasi) as total_realisasi FROM kegiatan WHERE id_program = ?");
                                                $stmt->execute([$program['id_program']]);
                                                $realisasi = $stmt->fetch()['total_realisasi'];
                                                echo number_format($realisasi ?? 0, 0, ',', '.');
                                                ?>
                                            </td>
                                            <td data-label="Sisa" class="text-end">
                                                <?= number_format($program['anggaran'] - ($realisasi ?? 0), 0, ',', '.') ?>
                                            </td>
                                            <td data-label="Status">
                                                <span class="badge text-white bg-<?= 
                                                    match($program['status']) {
                                                        'Selesai' => 'success',
                                                        'Berjalan' => 'primary',
                                                        'Tertunda' => 'danger',
                                                        'Perencanaan' => 'info',
                                                        default => 'secondary'
                                                    }
                                                ?>">
                                                    <?= $program['status'] ?>
                                                </span>
                                            </td>
                                            <td data-label="Progress">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar progress-bar-striped 
                                                    <?= ($program['persentase'] == 100 ? 'bg-success' : ($program['persentase'] > 50 ? 'bg-primary' : 'bg-danger')) ?>" 
                                                         role="progressbar" 
                                                         style="width: <?= $program['persentase'] ?>%" 
                                                         aria-valuenow="<?= $program['persentase'] ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?= $program['persentase'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Tidak ada program kerja untuk divisi ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Program Terbaru Section (only for non-admin) -->
    <?php if (!isAdmin()): ?>
    <div class="card">
        <div class="card-header">
            <h5>Program Terbaru Divisi Anda</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Program</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Anggaran</th>
                            <th>Realisasi</th>
                            <th>Sisa</th>
                            <th>Status</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($recentPrograms as $program): ?>
                            <tr>
                                <td data-label="No"><?= $no++ ?></td>
                                <td data-label="Nama Program"><?= htmlspecialchars($program['nama_program']) ?></td>
                                <td data-label="Tanggal Mulai"><?= $program['tanggal_mulai'] ? date('d M Y', strtotime($program['tanggal_mulai'])) : '-' ?></td>
                                <td data-label="Tanggal Selesai"><?= $program['tanggal_selesai'] ? date('d M Y', strtotime($program['tanggal_selesai'])) : '-' ?></td>
                                <td data-label="Anggaran" class="text-end"><?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                                <td data-label="Realisasi" class="text-end">
                                    <?php 
                                    $stmt = $pdo->prepare("SELECT SUM(realisasi) as total_realisasi FROM kegiatan WHERE id_program = ?");
                                    $stmt->execute([$program['id_program']]);
                                    $realisasi = $stmt->fetch()['total_realisasi'];
                                    echo number_format($realisasi ?? 0, 0, ',', '.');
                                    ?>
                                </td>
                                <td data-label="Sisa" class="text-end">
                                    <?= number_format($program['anggaran'] - ($realisasi ?? 0), 0, ',', '.') ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge text-white bg-<?= 
                                        match($program['status']) {
                                            'Selesai' => 'success',
                                            'Berjalan' => 'primary',
                                            'Tertunda' => 'danger',
                                            'Perencanaan' => 'info',
                                            default => 'secondary'
                                        }
                                    ?>">
                                        <?= $program['status'] ?>
                                    </span>
                                </td>
                                <td data-label="Progress">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped 
                                        <?= ($program['persentase'] == 100 ? 'bg-success' : ($program['persentase'] > 50 ? 'bg-primary' : 'bg-danger')) ?>" 
                                             role="progressbar" 
                                             style="width: <?= $program['persentase'] ?>%" 
                                             aria-valuenow="<?= $program['persentase'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $program['persentase'] ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isAdmin() || !isAdmin()): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isAdmin()): ?>
    // Diagram Batang Perbandingan Antar Divisi
    const barCtx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartData['labels']) ?>,
            datasets: [
                {
                    label: 'Program Selesai',
                    data: <?= json_encode($chartData['program_selesai']) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Program Berjalan',
                    data: <?= json_encode($chartData['program_berjalan']) ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Kegiatan Selesai',
                    data: <?= json_encode($chartData['kegiatan_selesai']) ?>,
                    backgroundColor: 'rgba(111, 66, 193, 0.7)',
                    borderColor: 'rgba(111, 66, 193, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Kegiatan Berjalan',
                    data: <?= json_encode($chartData['kegiatan_berjalan']) ?>,
                    backgroundColor: 'rgba(253, 126, 20, 0.7)',
                    borderColor: 'rgba(253, 126, 20, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Banyak Program / Kegiatan'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Divisi (lihat keterangan di bawah grafik)'
                    }
                }
            }
        }
    });
    
    // Grafik Anggaran dan Realisasi per Divisi untuk Admin
    const anggaranDivisiCtx = document.getElementById('anggaranDivisiChart').getContext('2d');
    const anggaranDivisiChart = new Chart(anggaranDivisiCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($divisiNumberMapping)) ?>,
            datasets: [
                {
                    label: 'Anggaran',
                    data: <?= json_encode(array_column($anggaranChartData, 'anggaran')) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Realisasi',
                    data: <?= json_encode(array_column($anggaranChartData, 'realisasi')) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Sisa Anggaran',
                    data: <?= json_encode(array_column($anggaranChartData, 'sisa')) ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah (Rp)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Divisi (lihat keterangan di bawah grafik)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'Rp ' + context.raw.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // Diagram Lingkaran untuk tiap divisi
    <?php foreach ($divisiProgress as $id_divisi => $divisi): ?>
        <?php if ($divisi['program']['total'] > 0): ?>
            // Chart Program
            const programCtx<?= $id_divisi ?> = document.getElementById('programChart-<?= $id_divisi ?>')?.getContext('2d');
            if (programCtx<?= $id_divisi ?>) {
                new Chart(programCtx<?= $id_divisi ?>, {
                    type: 'pie',
                    data: {
                        labels: ['Selesai', 'Berjalan', 'Perencanaan', 'Tertunda'],
                        datasets: [{
                            data: [
                                <?= $divisi['program']['selesai'] ?>,
                                <?= $divisi['program']['berjalan'] ?>,
                                <?= $divisi['program']['perencanaan'] ?>,
                                <?= $divisi['program']['tertunda'] ?>
                            ],
                            backgroundColor: [
                                'rgba(40, 167, 69, 0.7)',
                                'rgba(0, 123, 255, 0.7)',
                                'rgba(108, 117, 125, 0.7)',
                                'rgba(220, 53, 69, 0.7)'
                            ],
                            borderColor: [
                                'rgba(40, 167, 69, 1)',
                                'rgba(0, 123, 255, 1)',
                                'rgba(108, 117, 125, 1)',
                                'rgba(220, 53, 69, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribusi Status Program'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
        
        <?php if ($divisi['kegiatan']['total'] > 0): ?>
            // Chart Kegiatan
            const kegiatanCtx<?= $id_divisi ?> = document.getElementById('kegiatanChart-<?= $id_divisi ?>')?.getContext('2d');
            if (kegiatanCtx<?= $id_divisi ?>) {
                new Chart(kegiatanCtx<?= $id_divisi ?>, {
                    type: 'pie',
                    data: {
                        labels: ['Selesai', 'Berjalan', 'Belum Dimulai', 'Tertunda'],
                        datasets: [{
                            data: [
                                <?= $divisi['kegiatan']['selesai'] ?>,
                                <?= $divisi['kegiatan']['berjalan'] ?>,
                                <?= $divisi['kegiatan']['belum_dimulai'] ?>,
                                <?= $divisi['kegiatan']['tertunda'] ?>
                            ],
                            backgroundColor: [
                                'rgba(40, 167, 69, 0.7)',
                                'rgba(0, 123, 255, 0.7)',
                                'rgba(108, 117, 125, 0.7)',
                                'rgba(220, 53, 69, 0.7)'
                            ],
                            borderColor: [
                                'rgba(40, 167, 69, 1)',
                                'rgba(0, 123, 255, 1)',
                                'rgba(108, 117, 125, 1)',
                                'rgba(220, 53, 69, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Distribusi Status Kegiatan'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
    <?php endforeach; ?>
    <?php elseif (!isAdmin() && !empty($chartLabels)): ?>
    // Grafik Anggaran dan Realisasi untuk User Divisi
    const anggaranRealisasiCtx = document.getElementById('anggaranRealisasiChart').getContext('2d');
    const anggaranRealisasiChart = new Chart(anggaranRealisasiCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Anggaran',
                    data: <?= json_encode($chartAnggaran) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Realisasi',
                    data: <?= json_encode($chartRealisasi) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                },
                {
                    label: 'Sisa Anggaran',
                    data: <?= json_encode($chartSisa) ?>,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah (Rp)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Program Kerja'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'Rp ' + context.raw.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            return label;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>