<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

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

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 5,
    'margin_footer' => 5
]);

$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: Arial; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 10px; }
        .logo { width: 60px; }
        .title { font-size: 12pt; font-weight: bold; margin-bottom: 3px; color: #2F5597; }
        .subtitle { font-size: 11pt; margin-bottom: 5px; color: #2F5597; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; border: 1px solid #DDD; }
        th, td { border: 1px solid #DDD; padding: 5px; text-align: left; }
        th { background-color: #4472C4; color: white; font-weight: bold; }
        .text-right { text-align: right; }
        .divisi-row { font-weight: bold; background-color: #D9E1F2; }
        .program-row { font-weight: bold; background-color: #E2EFDA; }
        .kegiatan-row { padding-left: 15px; background-color: #FFFFFF; }
        .total-row { font-weight: bold; background-color: #FCE4D6; }
        .grand-total-row { font-weight: bold; background-color: #F8CBAD; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <img src="' . base_url('assets/images/logo.png') . '" class="logo">
        <div class="title">LAPORAN KEUANGAN DIVISI ' . ($printAll ? 'PENGURUS' : strtoupper(htmlspecialchars($division['nama_divisi'] ?? ''))) . '</div>
        <div class="subtitle">Yayasan TBS - ' . date('d F Y') . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Uraian Program dan Kegiatan</th>
                <th width="15%">Anggaran</th>
                <th width="15%">Realisasi</th>
                <th width="10%">Saldo</th>
                <th width="10%">Persentase</th>
            </tr>
        </thead>
        <tbody>';

if ($printAll) {
    foreach ($allData as $divIndex => $divData) {
        $html .= '
            <tr class="divisi-row">
                <td>' . ($divIndex + 1) . '</td>
                <td>Divisi ' . htmlspecialchars($divData['divisi']['nama_divisi'] ?? '') . '</td>
                <td class="number"></td>
                <td class="number"></td>
                <td class="number"></td>
                <td></td>
            </tr>';
        
        foreach ($divData['programs'] as $progIndex => $program) {
            $html .= '
            <tr class="program-row">
                <td>' . ($divIndex + 1) . '.' . ($progIndex + 1) . '</td>
                <td>' . htmlspecialchars($program['nama_program'] ?? '') . '</td>
                <td class="number">Rp ' . number_format($program['anggaran'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($program['total_realisasi'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($program['saldo'] ?? 0, 0, ',', '.') . '</td>
                <td>' . round($program['persentase'] ?? 0, 2) . '%</td>
            </tr>';
            
            foreach ($program['kegiatan'] as $actIndex => $kegiatan) {
                $html .= '
                <tr class="kegiatan-row">
                    <td>' . ($divIndex + 1) . '.' . ($progIndex + 1) . '.' . ($actIndex + 1) . '</td>
                    <td>' . htmlspecialchars($kegiatan['nama_kegiatan'] ?? '') . '</td>
                    <td class="number"></td>
                    <td class="number">Rp ' . number_format($kegiatan['realisasi'] ?? 0, 0, ',', '.') . '</td>
                    <td class="number"></td>
                    <td></td>
                </tr>';
            }
        }
        
        $html .= '
            <tr class="total-row">
                <td></td>
                <td>Jumlah Total</td>
                <td class="number">Rp ' . number_format($divData['total_anggaran'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($divData['total_realisasi'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($divData['total_saldo'] ?? 0, 0, ',', '.') . '</td>
                <td>' . round($divData['total_persentase'] ?? 0, 2) . '%</td>
            </tr>';
    }
    
    $html .= '
            <tr class="grand-total-row">
                <td></td>
                <td>Jumlah Total Semua Divisi</td>
                <td class="number">Rp ' . number_format($grandTotalAnggaran ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($grandTotalRealisasi ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format(($grandTotalAnggaran - $grandTotalRealisasi) ?? 0, 0, ',', '.') . '</td>
                <td>' . ($grandTotalAnggaran > 0 ? round(($grandTotalRealisasi / $grandTotalAnggaran) * 100, 2) : 0) . '%</td>
            </tr>';
} else {
    if (!empty($programs)) {
        $html .= '
            <tr class="divisi-row">
                <td>1</td>
                <td>Divisi ' . htmlspecialchars($division['nama_divisi'] ?? '') . '</td>
                <td class="number"></td>
                <td class="number"></td>
                <td class="number"></td>
                <td></td>
            </tr>';
        
        foreach ($programs as $progIndex => $program) {
            $html .= '
            <tr class="program-row">
                <td>1.' . ($progIndex + 1) . '</td>
                <td>' . htmlspecialchars($program['nama_program'] ?? '') . '</td>
                <td class="number">Rp ' . number_format($program['anggaran'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($program['total_realisasi'] ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($program['saldo'] ?? 0, 0, ',', '.') . '</td>
                <td>' . round($program['persentase'] ?? 0, 2) . '%</td>
            </tr>';
            
            foreach ($program['kegiatan'] as $actIndex => $kegiatan) {
                $html .= '
                <tr class="kegiatan-row">
                    <td>1.' . ($progIndex + 1) . '.' . ($actIndex + 1) . '</td>
                    <td>' . htmlspecialchars($kegiatan['nama_kegiatan'] ?? '') . '</td>
                    <td class="number"></td>
                    <td class="number">Rp ' . number_format($kegiatan['realisasi'] ?? 0, 0, ',', '.') . '</td>
                    <td class="number"></td>
                    <td></td>
                </tr>';
            }
        }
        
        $html .= '
            <tr class="total-row">
                <td></td>
                <td>Jumlah Total</td>
                <td class="number">Rp ' . number_format($divTotalAnggaran ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format($divTotalRealisasi ?? 0, 0, ',', '.') . '</td>
                <td class="number">Rp ' . number_format(($divTotalAnggaran - $divTotalRealisasi) ?? 0, 0, ',', '.') . '</td>
                <td>' . ($divTotalAnggaran > 0 ? round(($divTotalRealisasi / $divTotalAnggaran) * 100, 2) : 0) . '%</td>
            </tr>';
    } else {
        $html .= '
            <tr>
                <td colspan="6" style="text-align: center;">Divisi belum memiliki program kerja</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$mpdf->WriteHTML($html);
$filename = $printAll 
    ? 'Laporan_Keuangan_Semua_Divisi.pdf' 
    : 'Laporan_Keuangan_Divisi_' . preg_replace('/[^a-z0-9]/i', '_', ($division['nama_divisi'] ?? 'divisi')) . '.pdf';
$mpdf->Output($filename, 'D');
exit();