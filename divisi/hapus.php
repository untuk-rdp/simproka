<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Cek apakah divisi memiliki program
$stmt = $pdo->prepare("SELECT COUNT(*) FROM program_kerja WHERE id_divisi = ?");
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    $_SESSION['error'] = "Divisi tidak dapat dihapus karena memiliki program kerja terkait";
    header("Location: index.php");
    exit();
}

// Hapus divisi
$stmt = $pdo->prepare("DELETE FROM divisi WHERE id_divisi = ?");
$stmt->execute([$id]);

header("Location: index.php");
exit();
?>