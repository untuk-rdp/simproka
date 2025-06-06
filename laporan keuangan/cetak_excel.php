<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$isAdmin = isAdmin();
$currentDivisi = $_SESSION['id_divisi'];

if ($isAdmin && isset($_GET['type']) && $_GET['type'] === 'all') {
    // Admin view - all divisions with totals
    $queryDivisions = "SELECT id_divisi, nama_divisi FROM divisi ORDER BY nama_divisi";
    $stmt = $pdo->prepare($queryDivisions);
    $stmt->execute();
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allData = [];
    $grandTotalAnggaran = 0;
    $grandTotalRealisasi = 0;
    
    foreach ($divisions as $division) {
        $queryPrograms = "SELECT id_program, nama_program, anggaran 
                         FROM program_kerja 
                         WHERE id_divisi = ? 
                         ORDER BY nama_program";
        $stmt = $pdo->prepare($queryPrograms);
        $stmt->execute([$division['id_divisi']]);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $divTotalAnggaran = 0;
        $divTotalRealisasi = 0;
        
        foreach ($programs as &$program) {
            $queryActivities = "SELECT nama_kegiatan, realisasi 
                              FROM kegiatan 
                              WHERE id_program = ? 
                              ORDER BY nama_kegiatan";
            $stmt = $pdo->prepare($queryActivities);
            $stmt->execute([$program['id_program']]);
            $program['kegiatan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate program totals
            $program['total_realisasi'] = array_sum(array_column($program['kegiatan'], 'realisasi'));
            $program['saldo'] = $program['anggaran'] - $program['total_realisasi'];
            $program['persentase'] = $program['anggaran'] > 0 ? 
                ($program['total_realisasi'] / $program['anggaran']) * 100 : 0;
            
            $divTotalAnggaran += $program['anggaran'];
            $divTotalRealisasi += $program['total_realisasi'];
        }
        
        $grandTotalAnggaran += $divTotalAnggaran;
        $grandTotalRealisasi += $divTotalRealisasi;
        
        $allData[] = [
            'divisi' => $division,
            'programs' => $programs,
            'total_anggaran' => $divTotalAnggaran,
            'total_realisasi' => $divTotalRealisasi,
            'total_saldo' => $divTotalAnggaran - $divTotalRealisasi,
            'total_persentase' => $divTotalAnggaran > 0 ? ($divTotalRealisasi / $divTotalAnggaran) * 100 : 0
        ];
    }
    
    $printAll = true;
} else {
    // Single division view (for admin or regular user)
    $divisiId = isset($_GET['id']) ? (int)$_GET['id'] : $currentDivisi;
    
    // Verify access
    if (!$isAdmin && $divisiId != $currentDivisi) {
        die("Akses ditolak");
    }

    // Get division data
    $stmt = $pdo->prepare("SELECT id_divisi, nama_divisi FROM divisi WHERE id_divisi = ?");
    $stmt->execute([$divisiId]);
    $division = $stmt->fetch();

    if (!$division) {
        die("Divisi tidak ditemukan");
    }

    // Get programs for division
    $queryPrograms = "SELECT id_program, nama_program, anggaran 
                     FROM program_kerja 
                     WHERE id_divisi = ? 
                     ORDER BY nama_program";
    $stmt = $pdo->prepare($queryPrograms);
    $stmt->execute([$divisiId]);
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $divTotalAnggaran = 0;
    $divTotalRealisasi = 0;
    
    foreach ($programs as &$program) {
        $queryActivities = "SELECT nama_kegiatan, realisasi 
                          FROM kegiatan 
                          WHERE id_program = ? 
                          ORDER BY nama_kegiatan";
        $stmt = $pdo->prepare($queryActivities);
        $stmt->execute([$program['id_program']]);
        $program['kegiatan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate program totals
        $program['total_realisasi'] = array_sum(array_column($program['kegiatan'], 'realisasi'));
        $program['saldo'] = $program['anggaran'] - $program['total_realisasi'];
        $program['persentase'] = $program['anggaran'] > 0 ? 
            ($program['total_realisasi'] / $program['anggaran']) * 100 : 0;
        
        $divTotalAnggaran += $program['anggaran'];
        $divTotalRealisasi += $program['total_realisasi'];
    }
    
    $printAll = false;
}

// Set headers for Excel
$filename = $printAll 
    ? 'Laporan_Keuangan_Semua_Divisi_' . date('Ymd') . '.xls'
    : 'Laporan_Keuangan_Divisi_' . preg_replace('/[^a-z0-9]/i', '_', ($division['nama_divisi'] ?? 'divisi')) . '_' . date('Ymd') . '.xls';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <style>
        td { mso-number-format:\@; }
        .number { mso-number-format:Standard; }
        .divisi-row { font-weight: bold; background-color: #D9E1F2; }
        .program-row { font-weight: bold; background-color: #E2EFDA; }
        .kegiatan-row { background-color: #FFFFFF; }
        .total-row { font-weight: bold; background-color: #FCE4D6; }
        .grand-total-row { font-weight: bold; background-color: #F8CBAD; }
        .header-row { background-color: #4472C4; color: white; font-weight: bold; }
        .empty-col { width: 20px; }
    </style>
</head>
<body>
<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <th colspan="8" class="header-row" style="text-align:center;font-size:16px;">
            LAPORAN KEUANGAN DIVISI PENGURUS
        </th>
    </tr>
    <tr>
        <th colspan="8" class="header-row" style="text-align:center;">
            Yayasan TBS - <?= date('d F Y') ?>
        </th>
    </tr>
    <tr class="header-row">
        <th>No</th>
        <th colspan="3">Uraian Program dan Kegiatan</th>
        <th>Anggaran</th>
        <th>Realisasi</th>
        <th>Saldo</th>
        <th>Persentase</th>
    </tr>
    
    <?php if ($printAll): ?>
        <?php 
        $rowNumber = 1;
        foreach ($allData as $divIndex => $divData): 
        ?>
            <tr class="divisi-row">
                <td><?= $rowNumber++ ?></td>
                <td colspan="3"><?= ($divIndex + 1) ?>. Divisi <?= htmlspecialchars($divData['divisi']['nama_divisi'] ?? '') ?></td>
                <td class="number"></td>
                <td class="number"></td>
                <td class="number"></td>
                <td></td>
            </tr>
            
            <?php foreach ($divData['programs'] as $progIndex => $program): ?>
                <tr class="program-row">
                    <td><?= $rowNumber++ ?></td>
                    <td class="empty-col"></td>
                    <td colspan="2"><?= ($divIndex + 1) ?>.<?= ($progIndex + 1) ?> <?= htmlspecialchars($program['nama_program'] ?? '') ?></td>
                    <td class="number"><?= number_format($program['anggaran'] ?? 0, 0, ',', '.') ?></td>
                    <td class="number"><?= number_format($program['total_realisasi'] ?? 0, 0, ',', '.') ?></td>
                    <td class="number"><?= number_format($program['saldo'] ?? 0, 0, ',', '.') ?></td>
                    <td><?= round($program['persentase'] ?? 0, 2) ?>%</td>
                </tr>
                
                <?php foreach ($program['kegiatan'] as $actIndex => $kegiatan): ?>
                    <tr class="kegiatan-row">
                        <td><?= $rowNumber++ ?></td>
                        <td class="empty-col"></td>
                        <td class="empty-col"></td>
                        <td><?= ($divIndex + 1) ?>.<?= ($progIndex + 1) ?>.<?= ($actIndex + 1) ?> <?= htmlspecialchars($kegiatan['nama_kegiatan'] ?? '') ?></td>
                        <td class="number"></td>
                        <td class="number"><?= number_format($kegiatan['realisasi'] ?? 0, 0, ',', '.') ?></td>
                        <td class="number"></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td><?= $rowNumber++ ?></td>
                <td colspan="3">Jumlah Total (Divisi <?= htmlspecialchars($divData['divisi']['nama_divisi'] ?? '') ?>)</td>
                <td class="number"><?= number_format($divData['total_anggaran'] ?? 0, 0, ',', '.') ?></td>
                <td class="number"><?= number_format($divData['total_realisasi'] ?? 0, 0, ',', '.') ?></td>
                <td class="number"><?= number_format($divData['total_saldo'] ?? 0, 0, ',', '.') ?></td>
                <td><?= round($divData['total_persentase'] ?? 0, 2) ?>%</td>
            </tr>
        <?php endforeach; ?>
        
        <tr class="grand-total-row">
            <td><?= $rowNumber++ ?></td>
            <td colspan="3">Jumlah Total Semua Divisi</td>
            <td class="number"><?= number_format($grandTotalAnggaran ?? 0, 0, ',', '.') ?></td>
            <td class="number"><?= number_format($grandTotalRealisasi ?? 0, 0, ',', '.') ?></td>
            <td class="number"><?= number_format(($grandTotalAnggaran - $grandTotalRealisasi) ?? 0, 0, ',', '.') ?></td>
            <td><?= $grandTotalAnggaran > 0 ? round(($grandTotalRealisasi / $grandTotalAnggaran) * 100, 2) : 0 ?>%</td>
        </tr>
    <?php else: ?>
        <?php 
        $rowNumber = 1;
        if (!empty($programs)): ?>
            <tr class="divisi-row">
                <td><?= $rowNumber++ ?></td>
                <td colspan="3">1. Divisi <?= htmlspecialchars($division['nama_divisi'] ?? '') ?></td>
                <td class="number"></td>
                <td class="number"></td>
                <td class="number"></td>
                <td></td>
            </tr>
            
            <?php foreach ($programs as $progIndex => $program): ?>
                <tr class="program-row">
                    <td><?= $rowNumber++ ?></td>
                    <td class="empty-col"></td>
                    <td colspan="2">1.<?= ($progIndex + 1) ?> <?= htmlspecialchars($program['nama_program'] ?? '') ?></td>
                    <td class="number"><?= number_format($program['anggaran'] ?? 0, 0, ',', '.') ?></td>
                    <td class="number"><?= number_format($program['total_realisasi'] ?? 0, 0, ',', '.') ?></td>
                    <td class="number"><?= number_format($program['saldo'] ?? 0, 0, ',', '.') ?></td>
                    <td><?= round($program['persentase'] ?? 0, 2) ?>%</td>
                </tr>
                
                <?php foreach ($program['kegiatan'] as $actIndex => $kegiatan): ?>
                    <tr class="kegiatan-row">
                        <td><?= $rowNumber++ ?></td>
                        <td class="empty-col"></td>
                        <td class="empty-col"></td>
                        <td>1.<?= ($progIndex + 1) ?>.<?= ($actIndex + 1) ?> <?= htmlspecialchars($kegiatan['nama_kegiatan'] ?? '') ?></td>
                        <td class="number"></td>
                        <td class="number"><?= number_format($kegiatan['realisasi'] ?? 0, 0, ',', '.') ?></td>
                        <td class="number"></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td><?= $rowNumber++ ?></td>
                <td colspan="3">Jumlah Total (Divisi <?= htmlspecialchars($division['nama_divisi'] ?? '') ?>)</td>
                <td class="number"><?= number_format($divTotalAnggaran ?? 0, 0, ',', '.') ?></td>
                <td class="number"><?= number_format($divTotalRealisasi ?? 0, 0, ',', '.') ?></td>
                <td class="number"><?= number_format(($divTotalAnggaran - $divTotalRealisasi) ?? 0, 0, ',', '.') ?></td>
                <td><?= $divTotalAnggaran > 0 ? round(($divTotalRealisasi / $divTotalAnggaran) * 100, 2) : 0 ?>%</td>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align:center;">Divisi belum memiliki program kerja</td>
            </tr>
        <?php endif; ?>
    <?php endif; ?>
</table>
</body>
</html>
<?php exit(); ?>