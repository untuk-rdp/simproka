<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['program_id'])) {
    echo json_encode([]);
    exit();
}

$programId = (int)$_GET['program_id'];

try {
    $query = "SELECT 
                k.nama_kegiatan, 
                DATE_FORMAT(k.tanggal_mulai, '%d-%m-%Y') as tanggal_mulai, 
                DATE_FORMAT(k.tanggal_selesai, '%d-%m-%Y') as tanggal_selesai, 
                k.status, 
                k.persentase, 
                k.anggaran, 
                k.realisasi,
                u.nama_lengkap as penanggung_jawab
              FROM kegiatan k
              LEFT JOIN pengguna u ON k.penanggung_jawab = u.id_pengguna
              WHERE k.id_program = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$programId]);
    $kegiatan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($kegiatan);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}