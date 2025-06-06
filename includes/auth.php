<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['level']) && $_SESSION['level'] === 'admin';
}

function isManager() {
    return isset($_SESSION['level']) && $_SESSION['level'] === 'manager';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . base_url('login.php'));
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: ' . base_url('index.php'));
        exit();
    }
}

function redirectIfNotManager() {
    if (!isManager() && !isAdmin()) {
        header('Location: ' . base_url('index.php'));
        exit();
    }
}

function displayError($message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

function displaySuccess($message) {
    echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}
?>