<?php
require_once 'config/database.php';
$pageTitle = "Progress Divisi";

// Ambil data semua divisi
$stmt = $pdo->query("SELECT * FROM divisi ORDER BY id_divisi");
$divisiList = $stmt->fetchAll();

// Hitung program dan kegiatan per divisi
$divisiProgress = [];
$chartData = ['labels' => [], 'program_selesai' => [], 'program_berjalan' => [], 'kegiatan_selesai' => [], 'kegiatan_berjalan' => []];

foreach ($divisiList as $divisi) {
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
    
    // Data untuk chart batang
    $chartData['labels'][] = $divisi['nama_divisi'];
    $chartData['program_selesai'][] = $programStats['selesai'];
    $chartData['program_berjalan'][] = $programStats['berjalan'];
    $chartData['kegiatan_selesai'][] = $kegiatanStats['selesai'];
    $chartData['kegiatan_berjalan'][] = $kegiatanStats['berjalan'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .progress-container {
            margin-bottom: 20px;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="https://divisi.madrasahkudus.web.id/">
                <img src="https://divisi.madrasahkudus.web.id/assets/images/logo.png" alt="Logo" height="40" class="me-2">
                <span>SiMonPro TBS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                                    </ul>
                <ul class="navbar-nav">
                                            <li class="nav-item">
                            <a class="nav-link" href="https://divisi.madrasahkudus.web.id/login.php">Login</a>
                        </li>
                                    </ul>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <h2 class="text-center mb-4">Progress Tiap Divisi</h2>
    
    <!-- Diagram Batang Perbandingan Antar Divisi -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h4>Grafik Program & Kegiatan Selesai Antar Divisi</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Progress Tiap Divisi -->
    <?php foreach ($divisiProgress as $id_divisi => $divisi): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><?= htmlspecialchars($divisi['nama_divisi']) ?> (Divisi <?= $id_divisi ?>)</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Diagram Lingkaran Program -->
                    <div class="col-md-6">
                        <h5>Program Kerja</h5>
                        <p>Total: <?= $divisi['program']['total'] ?></p>
                        <div class="chart-container">
                            <?php if ($divisi['program']['total'] > 0): ?>
                                <canvas id="programChart-<?= $id_divisi ?>"></canvas>
                            <?php else: ?>
                                <div class="no-data">
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
                        <div class="chart-container">
                            <?php if ($divisi['kegiatan']['total'] > 0): ?>
                                <canvas id="kegiatanChart-<?= $id_divisi ?>"></canvas>
                            <?php else: ?>
                                <div class="no-data">
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
                        <div class="progress-container">
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
                        <div class="progress-container">
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
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// Diagram Batang Perbandingan Antar Divisi
document.addEventListener('DOMContentLoaded', function() {
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
                        text: 'Divisi Pengurus Yayasan TBS'
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
});
</script>

<?php
$jsFile = "form-validation.js";
include 'includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>