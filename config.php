<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lms_jaringan_komputer');

// Create Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isGuru() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru';
}

function isSiswa() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'siswa';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireGuru() {
    requireLogin();
    if (!isGuru()) {
        header("Location: dashboard_siswa.php");
        exit();
    }
}

function requireSiswa() {
    requireLogin();
    if (!isSiswa()) {
        header("Location: dashboard_guru.php");
        exit();
    }
}

// Sanitize input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Format date
function formatTanggal($timestamp) {
    return date('d/m/Y H:i', strtotime($timestamp));
}

// Calculate score
function hitungNilai($jawaban_benar, $total_soal) {
    return ($jawaban_benar / $total_soal) * 100;
}
?>