<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$pageTitle = "Manajemen Divisi";

// Ambil semua divisi
$stmt = $pdo->query("SELECT * FROM divisi ORDER BY nama_divisi");
$divisiList = $stmt->fetchAll();

// Tambah divisi baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    
    $stmt = $pdo->prepare("INSERT INTO divisi (nama_divisi, deskripsi) VALUES (?, ?)");
    $stmt->execute([$nama, $deskripsi]);
    
    header("Location: index.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Manajemen Divisi</h2>
    
<!-- Container untuk tombol dengan alignment ke kanan -->
<div class="d-flex justify-content-end mb-4">
    <button type="button" class="btn btn-primary" id="tampilkanFormDivisi">
        + Tambah Divisi Baru
    </button>
</div>

<!-- Form yang awalnya tersembunyi -->
<div class="card mb-4" id="formTambahDivisi" style="display: none;">
    <div class="card-header bg-primary text-white">
        <h5>Tambah Divisi Baru</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Divisi</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
            <button type="button" class="btn btn-secondary ms-2" id="sembunyikanFormDivisi">Batal</button>
        </form>
    </div>
</div>

<!-- JavaScript untuk mengontrol tampilan form -->
<script>
    document.getElementById('tampilkanFormDivisi').addEventListener('click', function() {
        document.getElementById('formTambahDivisi').style.display = 'block';
        this.style.display = 'none'; // Sembunyikan tombol setelah diklik
    });
    
    document.getElementById('sembunyikanFormDivisi').addEventListener('click', function() {
        document.getElementById('formTambahDivisi').style.display = 'none';
        document.getElementById('tampilkanFormDivisi').style.display = 'block'; // Tampilkan kembali tombol
    });
</script>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5>Daftar Divisi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Divisi</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($divisiList as $index => $divisi): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($divisi['nama_divisi']) ?></td>
                                <td><?= htmlspecialchars($divisi['deskripsi']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $divisi['id_divisi'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="hapus.php?id=<?= $divisi['id_divisi'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>