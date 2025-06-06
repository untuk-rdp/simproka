<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

$pageTitle = "Manajemen Program Kerja";
$cssFile = "dashboard.css";

// Filter divisi jika ada
$filter_divisi = isset($_GET['divisi']) ? $_GET['divisi'] : null;

// Query berdasarkan level pengguna dan filter
if (isAdmin()) {
    $sql = "
        SELECT p.*, d.nama_divisi, u.nama_lengkap as creator,
               (SELECT COUNT(*) FROM kegiatan WHERE id_program = p.id_program) as jumlah_kegiatan
        FROM program_kerja p 
        JOIN divisi d ON p.id_divisi = d.id_divisi 
        LEFT JOIN pengguna u ON p.created_by = u.id_pengguna
    ";
    
    if ($filter_divisi) {
        $sql .= " WHERE p.id_divisi = :id_divisi ";
    }
    
    $sql .= " ORDER BY p.tanggal_mulai DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($filter_divisi) {
        $stmt->bindParam(':id_divisi', $filter_divisi);
    }
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, d.nama_divisi, u.nama_lengkap as creator,
               (SELECT COUNT(*) FROM kegiatan WHERE id_program = p.id_program) as jumlah_kegiatan
        FROM program_kerja p 
        JOIN divisi d ON p.id_divisi = d.id_divisi 
        LEFT JOIN pengguna u ON p.created_by = u.id_pengguna
        WHERE p.id_divisi = ? 
        ORDER BY p.created_at ASC
    ");
    $stmt->execute([$_SESSION['id_divisi']]);
}

$programs = $stmt->fetchAll();

// Ambil daftar divisi untuk filter (hanya admin)
$divisi_list = [];
if (isAdmin()) {
    $stmt = $pdo->query("SELECT id_divisi, nama_divisi FROM divisi ORDER BY nama_divisi");
    $divisi_list = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Manajemen Program Kerja</h2>
    <?php if (isAdmin() || isManager()): ?>
    <a href="tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Program Baru
    </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <?php if (isAdmin()): ?>
    <div class="card-header bg-light">
        <form method="get" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label for="divisi" class="form-label">Filter Divisi:</label>
                <select name="divisi" id="divisi" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Divisi</option>
                    <?php foreach ($divisi_list as $divisi): ?>
                        <option value="<?= $divisi['id_divisi'] ?>" <?= ($filter_divisi == $divisi['id_divisi']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($divisi['nama_divisi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="?" class="btn btn-secondary mt-4">Reset Filter</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <?php if (isAdmin()): ?>
                            <th width="12%">Divisi</th>
                        <?php endif; ?>
                        <th width="20%">Nama Program</th>
                        <th width="12%">Dibuat Oleh</th>
                        <th width="12%">Periode</th>
                        <th width="10%">Anggaran</th>
                        <th width="8%">Kegiatan</th>
                        <th width="8%">Status</th>
                        <th width="10%">Progress</th>
                        <th width="8%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($programs as $program): 
                        // Query untuk mendapatkan kegiatan terkait
                        $stmt_kegiatan = $pdo->prepare("
                            SELECT * FROM kegiatan 
                            WHERE id_program = ? 
                            ORDER BY tanggal_mulai ASC
                        ");
                        $stmt_kegiatan->execute([$program['id_program']]);
                        $kegiatans = $stmt_kegiatan->fetchAll();
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <?php if (isAdmin()): ?>
                                <td><?= htmlspecialchars($program['nama_divisi']) ?></td>
                            <?php endif; ?>
                            <td>
                                <strong><?= htmlspecialchars($program['nama_program']) ?></strong>
                                <?php if ($program['deskripsi']): ?>
                                    <p class="text-muted mb-0 small"><?= htmlspecialchars(substr($program['deskripsi'], 0, 50)) ?>...</p>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($program['creator'] ?? 'System') ?></td>
                            <td>
                                <?php if ($program['tanggal_mulai']): ?>
                                    <?= date('d M Y', strtotime($program['tanggal_mulai'])) ?><br>
                                    <span class="text-muted small">s/d</span><br>
                                    <?= date('d M Y', strtotime($program['tanggal_selesai'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum ditentukan</span>
                                <?php endif; ?>
                            </td>
                            <td>Rp <?= number_format($program['anggaran'], 0, ',', '.') ?></td>
                            <td>
                                <a href="#" class="jumlah-kegiatan" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#kegiatanModal" 
                                   data-program="<?= htmlspecialchars($program['nama_program']) ?>"
                                   data-kegiatan='<?= json_encode($kegiatans) ?>'>
                                    <?= $program['jumlah_kegiatan'] ?>
                                </a>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped" 
                                         role="progressbar" 
                                         style="width: <?= $program['persentase'] ?>%" 
                                         aria-valuenow="<?= $program['persentase'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= $program['persentase'] ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="detail.php?id=<?= $program['id_program'] ?>" 
                                       class="btn btn-info" 
                                       data-bs-toggle="tooltip" 
                                       title="Detail">
                                        <i class="bi bi-eye">detail</i>
                                    </a>
                                    <?php if (isAdmin() || isManager()): ?>
                                        <a href="edit.php?id=<?= $program['id_program'] ?>" 
                                           class="btn btn-warning" 
                                           data-bs-toggle="tooltip" 
                                           title="Edit">
                                            <i class="bi bi-pencil">edit</i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (isAdmin()): ?>
                                        <a href="hapus.php?id=<?= $program['id_program'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Yakin ingin menghapus program ini? Semua kegiatan terkait juga akan dihapus.')"
                                           data-bs-toggle="tooltip" 
                                           title="Hapus">
                                            <i class="bi bi-trash">hapus</i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal untuk menampilkan kegiatan -->
<div class="modal fade" id="kegiatanModal" tabindex="-1" aria-labelledby="kegiatanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kegiatanModalLabel">Daftar Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="programTitle" class="mb-3"></h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kegiatan</th>
                                <th>Tanggal</th>
                                <th>Anggaran</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="kegiatanList">
                            <!-- Data kegiatan akan diisi oleh JavaScript -->
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

<script>
// Script untuk menampilkan detail kegiatan saat jumlah kegiatan diklik
document.addEventListener('DOMContentLoaded', function() {
    const jumlahKegiatanLinks = document.querySelectorAll('.jumlah-kegiatan');
    
    jumlahKegiatanLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const programName = this.getAttribute('data-program');
            const kegiatanData = JSON.parse(this.getAttribute('data-kegiatan'));
            
            document.getElementById('programTitle').textContent = 'Program: ' + programName;
            
            const kegiatanList = document.getElementById('kegiatanList');
            kegiatanList.innerHTML = '';
            
            if (kegiatanData.length === 0) {
                kegiatanList.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada kegiatan</td></tr>';
                return;
            }
            
            kegiatanData.forEach((kegiatan, index) => {
                const row = document.createElement('tr');
                
                // Format tanggal
                const tanggalMulai = kegiatan.tanggal_mulai ? new Date(kegiatan.tanggal_mulai).toLocaleDateString('id-ID') : '-';
                const tanggalSelesai = kegiatan.tanggal_selesai ? new Date(kegiatan.tanggal_selesai).toLocaleDateString('id-ID') : '-';
                const tanggalText = tanggalMulai + ' s/d ' + tanggalSelesai;
                
                // Format anggaran
                const anggaran = kegiatan.anggaran ? 'Rp ' + parseInt(kegiatan.anggaran).toLocaleString('id-ID') : '-';
                
                // Status badge
                let statusClass = 'secondary';
                switch(kegiatan.status) {
                    case 'Selesai': statusClass = 'success'; break;
                    case 'Berjalan': statusClass = 'primary'; break;
                    case 'Tertunda': statusClass = 'danger'; break;
                }
                
                row.innerHTML = `
                    <td>${index + 1}</td>
                    <td>${kegiatan.nama_kegiatan || '-'}</td>
                    <td>${tanggalText}</td>
                    <td>${anggaran}</td>
                    <td><span class="badge bg-${statusClass}">${kegiatan.status || '-'}</span></td>
                `;
                
                kegiatanList.appendChild(row);
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>