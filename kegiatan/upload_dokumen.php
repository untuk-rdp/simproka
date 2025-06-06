<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Check if required data is present
if (!isset($_POST['id_kegiatan']) || empty($_FILES['file']['name'])) {
    $_SESSION['error'] = "Data tidak lengkap";
    header("Location: detail.php?id=" . ($_POST['id_kegiatan'] ?? ''));
    exit();
}

$id_kegiatan = $_POST['id_kegiatan'];
$nama_file = trim($_POST['nama_file']);
$file = $_FILES['file'];

// Validate activity exists
$stmt = $pdo->prepare("SELECT id_kegiatan FROM kegiatan WHERE id_kegiatan = ?");
$stmt->execute([$id_kegiatan]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Kegiatan tidak ditemukan";
    header("Location: index.php");
    exit();
}

// Check user permissions
$stmt = $pdo->prepare("SELECT penanggung_jawab FROM kegiatan WHERE id_kegiatan = ?");
$stmt->execute([$id_kegiatan]);
$kegiatan = $stmt->fetch();

if (!isAdmin() && !isManager() && $_SESSION['user_id'] != $kegiatan['penanggung_jawab']) {
    $_SESSION['error'] = "Anda tidak memiliki izin untuk mengupload dokumen";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// File validation
$allowed_types = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];
$max_size = 10 * 1024 * 1024; // 10MB

if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['error'] = "Jenis file tidak diizinkan. Hanya PDF yang diperbolehkan";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

if ($file['size'] > $max_size) {
    $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 10MB";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// Create uploads directory if not exists
if (!file_exists('../uploads')) {
    mkdir('../uploads', 0777, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('doc_') . '.' . strtolower($ext);
$upload_path = '../uploads/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO dokumen 
                              (id_kegiatan, nama_file, path_file, tipe_file, ukuran_file) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_kegiatan,
            $nama_file,
            $filename,
            $file['type'],
            $file['size']
        ]);
        
        $_SESSION['success'] = "Dokumen berhasil diupload";
    } catch (PDOException $e) {
        unlink($upload_path); // Delete file if database insert fails
        $_SESSION['error'] = "Gagal menyimpan data dokumen: " . $e->getMessage();
    }
} else {
    $error = error_get_last();
    $_SESSION['error'] = "Gagal mengupload file: " . ($error['message'] ?? 'Unknown error');
}

header("Location: detail.php?id=" . $id_kegiatan);
exit();
?><?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

// Check if required data is present
if (!isset($_POST['id_kegiatan']) || empty($_FILES['file']['name'])) {
    $_SESSION['error'] = "Data tidak lengkap";
    header("Location: detail.php?id=" . ($_POST['id_kegiatan'] ?? ''));
    exit();
}

$id_kegiatan = $_POST['id_kegiatan'];
$nama_file = trim($_POST['nama_file']);
$file = $_FILES['file'];

// Validate activity exists
$stmt = $pdo->prepare("SELECT id_kegiatan FROM kegiatan WHERE id_kegiatan = ?");
$stmt->execute([$id_kegiatan]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Kegiatan tidak ditemukan";
    header("Location: index.php");
    exit();
}

// Check user permissions
$stmt = $pdo->prepare("SELECT penanggung_jawab FROM kegiatan WHERE id_kegiatan = ?");
$stmt->execute([$id_kegiatan]);
$kegiatan = $stmt->fetch();

if (!isAdmin() && !isManager() && $_SESSION['user_id'] != $kegiatan['penanggung_jawab']) {
    $_SESSION['error'] = "Anda tidak memiliki izin untuk mengupload dokumen";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// File validation
$allowed_types = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];
$max_size = 10 * 1024 * 1024; // 10MB

if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['error'] = "Jenis file tidak diizinkan. Hanya PDF yang diperbolehkan";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

if ($file['size'] > $max_size) {
    $_SESSION['error'] = "Ukuran file terlalu besar. Maksimal 10MB";
    header("Location: detail.php?id=" . $id_kegiatan);
    exit();
}

// Create uploads directory if not exists
if (!file_exists('../uploads')) {
    mkdir('../uploads', 0777, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('doc_') . '.' . strtolower($ext);
$upload_path = '../uploads/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO dokumen 
                              (id_kegiatan, nama_file, path_file, tipe_file, ukuran_file) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $id_kegiatan,
            $nama_file,
            $filename,
            $file['type'],
            $file['size']
        ]);
        
        $_SESSION['success'] = "Dokumen berhasil diupload";
    } catch (PDOException $e) {
        unlink($upload_path); // Delete file if database insert fails
        $_SESSION['error'] = "Gagal menyimpan data dokumen: " . $e->getMessage();
    }
} else {
    $error = error_get_last();
    $_SESSION['error'] = "Gagal mengupload file: " . ($error['message'] ?? 'Unknown error');
}

header("Location: detail.php?id=" . $id_kegiatan);
exit();
?>