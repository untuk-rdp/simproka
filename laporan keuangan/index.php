<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$pageTitle = "Laporan Keuangan";
$isAdmin = isAdmin();
$currentDivisi = $_SESSION['id_divisi'];

// Query untuk data laporan
$query = $isAdmin 
    ? "SELECT d.id_divisi, d.nama_divisi, 
              COALESCE(SUM(p.anggaran), 0) as total_anggaran,
              COALESCE(SUM(k.realisasi), 0) as total_realisasi,
              CASE WHEN COALESCE(SUM(p.anggaran), 0) > 0 
                   THEN (COALESCE(SUM(k.realisasi), 0)/COALESCE(SUM(p.anggaran), 0))*100 
                   ELSE 0 
              END as persentase
       FROM divisi d
       LEFT JOIN program_kerja p ON d.id_divisi = p.id_divisi
       LEFT JOIN kegiatan k ON p.id_program = k.id_program
       GROUP BY d.id_divisi"
    : "SELECT d.id_divisi, d.nama_divisi,
              COALESCE(SUM(p.anggaran), 0) as total_anggaran,
              COALESCE(SUM(k.realisasi), 0) as total_realisasi,
              CASE WHEN COALESCE(SUM(p.anggaran), 0) > 0
                   THEN (COALESCE(SUM(k.realisasi), 0)/COALESCE(SUM(p.anggaran), 0))*100
                   ELSE 0
              END as persentase
       FROM divisi d
       LEFT JOIN program_kerja p ON d.id_divisi = p.id_divisi
       LEFT JOIN kegiatan k ON p.id_program = k.id_program
       WHERE d.id_divisi = :divisi_id
       GROUP BY d.id_divisi";

try {
    $stmt = $pdo->prepare($query);
    if (!$isAdmin) {
        $stmt->bindParam(':divisi_id', $currentDivisi, PDO::PARAM_INT);
    }
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Data Laporan</h6>
            <div>
                <?php if ($isAdmin): ?>
                    <a href="cetak_pdf.php?type=all" class="btn btn-danger btn-sm mr-2">
                        <i class="fas fa-file-pdf"></i> Cetak Semua PDF
                    </a>
                    <a href="cetak_excel.php?type=all" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Cetak Semua Excel
                    </a>
                <?php else: ?>
                    <a href="cetak_pdf.php?id=<?= $currentDivisi ?>" class="btn btn-danger btn-sm mr-2">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="cetak_excel.php?id=<?= $currentDivisi ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Divisi</th>
                            <th>Anggaran</th>
                            <th>Realisasi</th>
                            <th>Persentase</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
                        <?php else: ?>
                            <?php foreach ($reports as $i => $report): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($report['nama_divisi'] ?? '') ?></td>
                                    <td>Rp <?= number_format($report['total_anggaran'] ?? 0, 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($report['total_realisasi'] ?? 0, 0, ',', '.') ?></td>
                                    <td>
                                        <?php if (($report['total_anggaran'] ?? 0) > 0): ?>
                                            <div class="progress">
                                                <div class="progress-bar <?= ($report['persentase'] ?? 0) > 75 ? 'bg-success' : (($report['persentase'] ?? 0) > 50 ? 'bg-info' : 'bg-warning') ?>" 
                                                     style="width:<?= $report['persentase'] ?? 0 ?>%">
                                                    <?= round($report['persentase'] ?? 0, 2) ?>%
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Belum dianggarkan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="detail.php?id=<?= $report['id_divisi'] ?? '' ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <a href="cetak_pdf.php?id=<?= $report['id_divisi'] ?? '' ?>" class="btn btn-sm btn-danger ml-1">
                                            <i class="fas fa-file-pdf">PDF</i>
                                        </a>
                                        <a href="cetak_excel.php?id=<?= $report['id_divisi'] ?? '' ?>" class="btn btn-sm btn-success ml-1">
                                            <i class="fas fa-file-excel">EXCEL</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>