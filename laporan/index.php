<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$pageTitle = "Laporan Program Kerja";
$cssFile = "dashboard.css";

// Filter berdasarkan divisi (jika bukan admin)
$divisiFilter = "";
$params = [];
if (!isAdmin()) {
    $divisiFilter = "WHERE p.id_divisi = ?";
    $params = [$_SESSION['id_divisi']];
}

// Query untuk statistik program
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'Berjalan' THEN 1 ELSE 0 END) as berjalan,
    SUM(CASE WHEN status = 'Tertunda' THEN 1 ELSE 0 END) as tertunda,
    SUM(CASE WHEN status = 'Perencanaan' THEN 1 ELSE 0 END) as perencanaan
    FROM program_kerja p $divisiFilter");
$stmt->execute($params);
$stats = $stmt->fetch();

// Query untuk program per divisi (admin only)
$programPerDivisi = [];
if (isAdmin()) {
    $stmt = $pdo->query("SELECT d.nama_divisi, COUNT(p.id_program) as jumlah 
                        FROM divisi d 
                        LEFT JOIN program_kerja p ON d.id_divisi = p.id_divisi 
                        GROUP BY d.id_divisi");
    $programPerDivisi = $stmt->fetchAll();
}

// Query untuk progress program dengan status
$progressProgram = [];
$stmt = $pdo->prepare("
    SELECT p.nama_program, p.persentase, p.status, d.nama_divisi 
    FROM program_kerja p 
    JOIN divisi d ON p.id_divisi = d.id_divisi 
    $divisiFilter
    ORDER BY p.persentase DESC 
    
");
$stmt->execute($params);
$progressProgram = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Laporan Program Kerja</h2>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Total Program</h5>
                <h1 class="display-4"><?= $stats['total'] ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Selesai</h5>
                <h1 class="display-4 text-success"><?= $stats['selesai'] ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Berjalan</h5>
                <h1 class="display-4 text-primary"><?= $stats['berjalan'] ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Tertunda</h5>
                <h1 class="display-4 text-danger"><?= $stats['tertunda'] ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Status Program</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Progres Program dari Tertinggi</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nama Program</th>
                        <?php if (isAdmin()): ?>
                            <th>Divisi</th>
                        <?php endif; ?>
                        <th>Progress</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progressProgram as $program): ?>
                        <tr>
                            <td><?= htmlspecialchars($program['nama_program']) ?></td>
                            <?php if (isAdmin()): ?>
                                <td><?= htmlspecialchars($program['nama_divisi']) ?></td>
                            <?php endif; ?>
                            <td><?= $program['persentase'] ?>%</td>
                            <td><?= htmlspecialchars($program['status'] ?? 'Tidak Ada Status') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("statusChart").getContext("2d");
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Selesai", "Berjalan", "Tertunda", "Perencanaan"],
            datasets: [{
                data: [<?= $stats['selesai'] ?>, <?= $stats['berjalan'] ?>, <?= $stats['tertunda'] ?>, <?= $stats['perencanaan'] ?>],
                backgroundColor: ["#28a745", "#007bff", "#dc3545", "#ffc107"],
                borderColor: "#ffffff",
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom"
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
