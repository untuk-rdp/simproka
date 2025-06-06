<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

try {
    // Mulai transaction
    $pdo->beginTransaction();
    
    // Hapus kegiatan terkait dulu
    $stmt = $pdo->prepare("DELETE FROM kegiatan WHERE id_program = ?");
    $stmt->execute([$id]);
    
    // Hapus program
    $stmt = $pdo->prepare("DELETE FROM program_kerja WHERE id_program = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Program berhasil dihapus";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Gagal menghapus program: " . $e->getMessage();
}

header("Location: index.php");
exit();
?>