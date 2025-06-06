<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();
if (!isAdmin() && !isManager()) {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Tambah Program Kerja";
$cssFile = "dashboard.css";

// Ambil daftar divisi
$divisiList = $pdo->query("SELECT * FROM divisi ORDER BY nama_divisi")->fetchAll();

// Proses tambah program
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_program'];
    $deskripsi = $_POST['deskripsi'];
    $divisi = $_POST['id_divisi'];
    $tglMulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $tglSelesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
    $anggaran = str_replace(['.', ','], '', $_POST['anggaran']);
    $status = $_POST['status'];
    $persentase = $_POST['persentase'];

    try {
        $stmt = $pdo->prepare("INSERT INTO program_kerja 
                              (id_divisi, nama_program, deskripsi, tanggal_mulai, tanggal_selesai, 
                               anggaran, status, persentase, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$divisi, $nama, $deskripsi, $tglMulai, $tglSelesai, $anggaran, $status, $persentase, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "Program kerja berhasil ditambahkan";
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $error = "Gagal menambahkan program: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <h2 class="mb-4">Tambah Program Kerja</h2>
    <a href="index.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Tambah Program</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_program" class="form-label">Nama Program</label>
                        <input type="text" class="form-control" id="nama_program" name="nama_program" required>
                        <div class="invalid-feedback">Harap isi nama program</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_divisi" class="form-label">Divisi</label>
                        <select class="form-select" id="id_divisi" name="id_divisi" required>
                            <?php if (isAdmin()): ?>
                                <option value="">-- Pilih Divisi --</option>
                                <?php foreach ($divisiList as $divisi): ?>
                                    <option value="<?= $divisi['id_divisi'] ?>"><?= htmlspecialchars($divisi['nama_divisi']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php 
                                    $divisi = $pdo->query("SELECT * FROM divisi WHERE id_divisi = " . $_SESSION['id_divisi'])->fetch();
                                ?>
                                <option value="<?= $divisi['id_divisi'] ?>"><?= htmlspecialchars($divisi['nama_divisi']) ?></option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">Harap pilih divisi</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai">
                                <div class="invalid-feedback">Format tanggal tidak valid</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai">
                                <div class="invalid-feedback">Format tanggal tidak valid</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="anggaran" class="form-label">Anggaran (Rp)</label>
                        <input type="text" class="form-control" id="anggaran" name="anggaran" 
                               oninput="formatCurrency(this)" required>
                        <div class="invalid-feedback">Harap isi anggaran</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Perencanaan">Perencanaan</option>
                                    <option value="Berjalan">Berjalan</option>
                                    <option value="Tertunda">Tertunda</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                                <div class="invalid-feedback">Harap pilih status</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="persentase" class="form-label">Progress (%)</label>
                                <input type="number" class="form-control" id="persentase" 
                                       name="persentase" min="0" max="100" value="0" required>
                                <div class="invalid-feedback">Harap isi progress</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Format input currency
function formatCurrency(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value.length > 0) {
        value = parseInt(value).toLocaleString('id-ID');
    }
    input.value = value;
}

// Validasi form saat submit
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('.needs-validation');

    form.addEventListener('submit', function (event) {
        // Remove required from date fields before validation
        document.getElementById('tanggal_mulai').required = false;
        document.getElementById('tanggal_selesai').required = false;

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();

            // Ambil semua elemen yang tidak valid
            const invalidFields = form.querySelectorAll(':invalid');
            let messages = [];

            invalidFields.forEach(field => {
                const label = form.querySelector(`label[for="${field.id}"]`);
                const labelText = label ? label.innerText : field.name;
                messages.push(`- ${labelText} wajib diisi`);
            });

            alert("Mohon lengkapi form berikut:\n\n" + messages.join('\n'));
        }

        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include '../includes/footer.php'; ?>