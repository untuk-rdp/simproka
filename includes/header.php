<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sistem Monitoring Program Kerja') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <?php if (isset($cssFile)): ?>
        <link href="<?= base_url('assets/css/' . $cssFile) ?>" rel="stylesheet">
    <?php endif; ?>
    <link rel="icon" href="<?= base_url('assets/images/favicon.ico') ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= base_url() ?>">
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" height="40" class="me-2">
                <span>Yayasan TBS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url() ?>">Dashboard</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= base_url('divisi/') ?>">Divisi</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('program/') ?>">Program</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('kegiatan/') ?>">Kegiatan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('grafik/') ?>">Grafik Progres</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('laporan keuangan/') ?>">Laporan Keuangan</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="me-2"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
                                <small class="badge bg-light text-dark"><?= ucfirst($_SESSION['level']) ?></small>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= base_url('profil/') ?>">Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= base_url('logout.php') ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!--<li class="nav-item">
                            <a class="nav-link" href="<?= base_url('progres.php') ?>">progres</a>
                        </li>-->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= base_url('login.php') ?>">Login</a>
                        </li>
                        
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">