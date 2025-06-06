<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

$pageTitle = "Edit Divisi";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Ambil data divisi
$stmt = $pdo->prepare("SELECT * FROM divisi WHERE id_divisi = ?");
$stmt->execute([$id]);
$divisi = $stmt->fetch();

if (!$divisi) {
    header("Location: index.php");
    exit();
}

// Update data divisi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    
    $stmt = $pdo->prepare("UPDATE divisi SET nama_divisi = ?, deskripsi = ? WHERE id_divisi = ?");
    $stmt->execute([$nama, $deskripsi, $id]);
    
    header("Location: index.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Edit Divisi</h2>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5>Form Edit Divisi</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Divisi</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($divisi['nama_divisi']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($divisi['deskripsi']) ?></textarea>
                </div>
                <button type="submit" name="update" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>