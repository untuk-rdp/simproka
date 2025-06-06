<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_GET['id']) || !isset($_GET['kegiatan'])) {
    $_SESSION['error'] = "Parameter tidak valid";
    header("Location: index.php");
    exit();
}

$id_dokumen = $_GET['id'];
$id_kegiatan = $_GET['kegiatan'];

// Get document data
$stmt = $pdo->prepare("SELECT * FROM dokumen WHERE id_dokumen = ?");
$stmt->execute([$id_dokumen]);
$dokumen = $stmt->fetch();

if (!$dokumen) {
    $_SESSION['error'] = "Dokumen tidak ditemukan";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// Verify activity exists
$stmt = $pdo->prepare("SELECT penanggung_jawab FROM kegiatan WHERE id_kegiatan = ?");
$stmt->execute([$id_kegiatan]);
$kegiatan = $stmt->fetch();

if (!$kegiatan) {
    $_SESSION['error'] = "Kegiatan tidak ditemukan";
    header("Location: index.php");
    exit();
}

// Check user permissions
if (!isAdmin() && !isManager() && $_SESSION['user_id'] != $kegiatan['penanggung_jawab']) {
    $_SESSION['error'] = "Anda tidak memiliki izin untuk menghapus dokumen ini";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// Delete file from server
$file_path = '../uploads/' . $dokumen['path_file'];
if (file_exists($file_path)) {
    if (!unlink($file_path)) {
        $_SESSION['error'] = "Gagal menghapus file dari server";
        header("Location: detail.php?id=" . $id_kegiatan);
        exit();
    }
}

// Delete record from database
try {
    $stmt = $pdo->prepare("DELETE FROM dokumen WHERE id_dokumen = ?");
    $stmt->execute([$id_dokumen]);
    
    $_SESSION['success'] = "Dokumen berhasil dihapus";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal menghapus data dokumen: " . $e->getMessage();
}

header("Location: detail.php?id=" . $id_kegiatan);
exit();
?>