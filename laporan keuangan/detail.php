<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$divisiId = (int)$_GET['id'];
$isAdmin = isAdmin();
$currentDivisi = $_SESSION['id_divisi'];

// Verifikasi akses
if (!$isAdmin && $divisiId != $currentDivisi) {
    header('Location: index.php');
    exit();
}

// Ambil data divisi
$stmt = $pdo->prepare("SELECT nama_divisi FROM divisi WHERE id_divisi = ?");
$stmt->execute([$divisiId]);
$divisi = $stmt->fetch();

if (!$divisi) {
    die("Divisi tidak ditemukan");
}

// Query detail program
$query = "SELECT p.id_program, p.nama_program,
                 p.anggaran,
                 COALESCE(SUM(k.realisasi), 0) as realisasi,
                 CASE WHEN p.anggaran > 0
                      THEN (COALESCE(SUM(k.realisasi), 0)/p.anggaran)*100
                      ELSE 0
                 END as persentase,
                 COUNT(k.id_kegiatan) as jumlah_kegiatan
          FROM program_kerja p
          LEFT JOIN kegiatan k ON p.id_program = k.id_program
          WHERE p.id_divisi = ?
          GROUP BY p.id_program";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$divisiId]);
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$pageTitle = "Detail Laporan - " . htmlspecialchars($divisi['nama_divisi']);
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><?= $pageTitle ?></h2>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detail Program</h6>
            <div>
                <a href="cetak_pdf.php?id=<?= $divisiId ?>" class="btn btn-danger btn-sm mr-2">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="cetak_excel.php?id=<?= $divisiId ?>" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Program</th>
                            <th>Anggaran</th>
                            <th>Realisasi</th>
                            <th>Kegiatan</th>
                            <th>Persentase</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($programs)): ?>
                            <tr><td colspan="7" class="text-center">Tidak ada program</td></tr>
                        <?php else: ?>
                            <?php foreach ($programs as $i => $program): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($program['nama_program']) ?></td>
                                    <td>Rp <?= number_format($program['anggaran'] ?? 0, 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($program['realisasi'] ?? 0, 0, ',', '.') ?></td>
                                    <td><?= $program['jumlah_kegiatan'] ?></td>
                                    <td>
                                        <?php if ($program['anggaran'] > 0): ?>
                                            <div class="progress">
                                                <div class="progress-bar <?= $program['persentase'] > 75 ? 'bg-success' : ($program['persentase'] > 50 ? 'bg-info' : 'bg-warning') ?>" 
                                                     style="width:<?= $program['persentase'] ?>%">
                                                    <?= round($program['persentase'], 2) ?>%
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm btn-detail" data-id="<?= $program['id_program'] ?>">
                                            <i class="fas fa-eye"></i> Lihat Detail
                                        </button>
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

<!-- Modal untuk menampilkan detail kegiatan -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Kegiatan</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Penanggung Jawab</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Anggaran</th>
                                <th>Realisasi</th>
                            </tr>
                        </thead>
                        <tbody id="kegiatanTableBody">
                            <!-- Data akan dimuat di sini -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Tangani klik tombol detail
    $(document).on('click', '.btn-detail', function() {
        var programId = $(this).data('id');
        var modal = new bootstrap.Modal(document.getElementById('detailModal'));
        
        // Tampilkan loading
        $('#kegiatanTableBody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        
        // Tampilkan modal
        modal.show();
        
        // Ambil data kegiatan via AJAX
        $.ajax({
            url: 'get_kegiatan.php',
            type: 'GET',
            data: { program_id: programId },
            dataType: 'json',
            success: function(response) {
                var tableBody = $('#kegiatanTableBody');
                tableBody.empty();
                
                if (response.length > 0) {
                    $.each(response, function(index, kegiatan) {
                        // Format progress bar
                        var progressBar = '<div class="progress" style="height: 20px;">' +
                            '<div class="progress-bar ' + getProgressBarClass(kegiatan.persentase) + '" ' +
                            'role="progressbar" style="width: ' + kegiatan.persentase + '%;" ' +
                            'aria-valuenow="' + kegiatan.persentase + '" aria-valuemin="0" aria-valuemax="100">' +
                            kegiatan.persentase + '%</div></div>';
                        
                        var row = '<tr>' +
                            '<td>' + kegiatan.nama_kegiatan + '</td>' +
                            '<td>' + kegiatan.tanggal_mulai + '</td>' +
                            '<td>' + kegiatan.tanggal_selesai + '</td>' +
                            '<td>' + (kegiatan.penanggung_jawab || '-') + '</td>' +
                            '<td>' + kegiatan.status + '</td>' +
                            '<td>' + progressBar + '</td>' +
                            '<td>Rp ' + formatNumber(kegiatan.anggaran || 0) + '</td>' +
                            '<td>Rp ' + formatNumber(kegiatan.realisasi || 0) + '</td>' +
                            '</tr>';
                        tableBody.append(row);
                    });
                } else {
                    tableBody.append('<tr><td colspan="8" class="text-center">Tidak ada kegiatan</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                $('#kegiatanTableBody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat data kegiatan</td></tr>');
                console.error('Error:', error);
            }
        });
    });
    
    // Fungsi untuk menentukan kelas progress bar berdasarkan persentase
    function getProgressBarClass(persentase) {
        if (persentase >= 75) return 'bg-success';
        if (persentase >= 50) return 'bg-info';
        if (persentase >= 25) return 'bg-warning';
        return 'bg-danger';
    }
    
    // Fungsi untuk format angka
    function formatNumber(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});
</script>

<?php include '../includes/footer.php'; ?>