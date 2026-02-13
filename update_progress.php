<?php
require_once 'config.php';
requireLogin();

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$buku_id = isset($_POST['buku_id']) ? (int)$_POST['buku_id'] : 0;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 0;
$total = isset($_POST['total']) ? (int)$_POST['total'] : 0;

if ($buku_id <= 0 || $page <= 0 || $total <= 0) {
    http_response_code(400);
    exit();
}

$persentase = ($page / $total) * 100;
$status = ($persentase >= 100) ? 'selesai' : 'sedang_baca';

$query = "INSERT INTO progress_baca (user_id, buku_id, halaman_terakhir, persentase_selesai, status_baca) 
          VALUES (?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE 
          halaman_terakhir = ?, 
          persentase_selesai = ?,
          status_baca = ?,
          waktu_terakhir_baca = CURRENT_TIMESTAMP";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiddsdds", 
    $_SESSION['user_id'], 
    $buku_id, 
    $page, 
    $persentase,
    $status,
    $page, 
    $persentase,
    $status
);

if (mysqli_stmt_execute($stmt)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'percentage' => round($persentase, 2)]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>