<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    
    $user_id = $_SESSION['user_id'];
    $buku_id = (int)$_POST['buku_id'];
    $kode_buku = clean_input($_POST['kode_buku']);
    $jawabans = $_POST['jawaban']; // Array: [soal_id => text_jawaban]

    if (empty($jawabans)) {
        echo "<script>
                alert('Tidak ada jawaban yang dikirim!'); 
                window.history.back();
              </script>";
        exit;
    }

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
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
            $isi_jawaban = trim($isi_jawaban);
            
            if (empty($isi_jawaban)) {
                continue; // Skip empty answers
            }

            // Bind param: i (int), i (int), s (string)
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $soal_id, $isi_jawaban);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            }
        }

        mysqli_stmt_close($stmt);

        // Commit transaction
        mysqli_commit($conn);

        if ($success_count > 0) {
            echo "<script>
                    alert('✅ Berhasil menyimpan " . $success_count . " jawaban!\\n\\nJawaban Anda telah tersimpan.');
                    window.location.href = 'book_viewer.php?book=" . urlencode($kode_buku) . "';
                  </script>";
        } else {
            throw new Exception("Tidak ada jawaban yang berhasil disimpan");
        }

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        echo "<script>
                alert('❌ Gagal menyimpan jawaban: " . addslashes($e->getMessage()) . "\\n\\nSilakan coba lagi.');
                window.history.back();
              </script>";
    }

} else {
    // Jika akses langsung tanpa POST
    header("Location: dashboard_siswa.php");
    exit();
}
?>