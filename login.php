<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . base_url());
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Username tidak ditemukan!";
        } elseif ($password !== $user['password']) { // Perbandingan plain text
            $error = "Password salah!";
        } else {
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['id_divisi'] = $user['id_divisi'];
            
            $redirectUrl = $_SESSION['redirect_url'] ?? base_url();
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirectUrl);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
    }
}

$pageTitle = "Login";
$cssFile = "auth.css";
include 'includes/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-card card">
                    <div class="card-body p-4 p-sm-5">
                        <div class="auth-logo text-center mb-4">
    <img src="<?= base_url('assets/images/logotbs.png') ?>" alt="Logo" style="max-width: 100px; width: 100%; height: auto;">
</div>

                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required autofocus>
                                <div class="invalid-feedback">Harap masukkan username</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Harap masukkan password</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="auth-footer mt-4 text-center">
                            <p class="mb-0">Belum punya akun? <br>Hubungi <b>divisi IT, Publikasi & Media</b></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$jsFile = "form-validation.js";
include 'includes/footer.php';
?>