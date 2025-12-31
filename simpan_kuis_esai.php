<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    
    $user_id = $_SESSION['user_id'];
    $buku_id = (int)$_POST['buku_id'];
    $jawabans = $_POST['jawaban']; // Ini berbentuk array: [soal_id => text_jawaban]

    if (empty($jawabans)) {
        echo "<script>alert('Tidak ada jawaban yang dikirim!'); window.history.back();</script>";
        exit;
    }

    // Prepare statement untuk INSERT atau UPDATE
    $query = "INSERT INTO kuis_esai (user_id, soal_id, jawaban, completed_at) 
              VALUES (?, ?, ?, NOW()) 
              ON DUPLICATE KEY UPDATE 
              jawaban = VALUES(jawaban), 
              completed_at = NOW()";
              
    $stmt = mysqli_prepare($conn, $query);

    $success_count = 0;

    foreach ($jawabans as $soal_id => $isi_jawaban) {
        $soal_id = (int)$soal_id;
        $isi_jawaban = clean_input($isi_jawaban); // Pastikan fungsi clean_input ada di config.php

        // Bind param: i (int), i (int), s (string)
        mysqli_stmt_bind_param($stmt, "iis", $user_id, $soal_id, $isi_jawaban);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        }
    }

    mysqli_stmt_close($stmt);

    if ($success_count > 0) {
        echo "<script>
                alert('Berhasil menyimpan " . $success_count . " jawaban!');
                window.location.href = 'book_viewer.php?buku_id=" . $buku_id . "&quiz=esai';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menyimpan jawaban. Silakan coba lagi.');
                window.history.back();
              </script>";
    }

} else {
    // Jika akses langsung tanpa post
    header("Location: dashboard_siswa.php");
    exit();
}
?>