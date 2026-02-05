<?php
require_once 'config.php';
requireGuru();

// Handle Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $reset_type = $_POST['reset_type'];
    
    if ($reset_type === 'all') {
        // Reset all scores
        mysqli_query($conn, "DELETE FROM nilai_pretest");
        mysqli_query($conn, "DELETE FROM nilai_posttest");
        mysqli_query($conn, "DELETE FROM nilai_quiz");
        mysqli_query($conn, "DELETE FROM jawaban_siswa");
        mysqli_query($conn, "DELETE FROM kuis_esai");
        mysqli_query($conn, "DELETE FROM progress_siswa");
        $message = "Semua rekap nilai berhasil direset!";
    } elseif ($reset_type === 'pretest') {
        mysqli_query($conn, "DELETE FROM nilai_pretest");
        mysqli_query($conn, "DELETE FROM jawaban_siswa WHERE tipe = 'pretest'");
        $message = "Rekap nilai Pre-Test berhasil direset!";
    } elseif ($reset_type === 'posttest') {
        mysqli_query($conn, "DELETE FROM nilai_posttest");
        mysqli_query($conn, "DELETE FROM jawaban_siswa WHERE tipe = 'posttest'");
        $message = "Rekap nilai Post-Test berhasil direset!";
    } elseif ($reset_type === 'quiz') {
        mysqli_query($conn, "DELETE FROM nilai_quiz");
        mysqli_query($conn, "DELETE FROM jawaban_siswa WHERE tipe = 'quiz'");
        mysqli_query($conn, "DELETE FROM kuis_esai");
        $message = "Rekap nilai Kuis berhasil direset!";
    } elseif ($reset_type === 'progress') {
        mysqli_query($conn, "DELETE FROM progress_siswa");
        $message = "Progress siswa berhasil direset!";
    }
    
    header("Location: reset_nilai.php?success=" . urlencode($message));
    exit();
}

// Get statistics
$total_pretest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai_pretest"))['total'];
$total_posttest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai_posttest"))['total'];
$total_quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai_quiz"))['total'];
$total_jawaban = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jawaban_siswa"))['total'];
$total_progress = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM progress_siswa"))['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Nilai - LMS Jaringan Komputer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: white;
            color: #11998e;
        }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 36px;
            color: #11998e;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .reset-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .reset-card h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reset-option {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #dc3545;
        }
        
        .reset-option h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .reset-option p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .btn-reset {
            background: #dc3545;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            text-align: center;
        }
        
        .modal-content h3 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-confirm {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔄 Reset Rekap Nilai</h1>
        <a href="dashboard_guru.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <span style="font-size: 24px;">✓</span>
            <span><?php echo htmlspecialchars($_GET['success']); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <span style="font-size: 24px;">⚠️</span>
            <span>Perhatian: Tindakan reset tidak dapat dibatalkan! Pastikan Anda sudah backup data jika diperlukan.</span>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_pretest; ?></h3>
                <p>Nilai Pre-Test</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_quiz; ?></h3>
                <p>Nilai Kuis</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_posttest; ?></h3>
                <p>Nilai Post-Test</p>
            </div>
           
        </div>
        
        <div class="reset-card">
            <h3>🗑️ Opsi Reset</h3>
            
            <div class="reset-option">
                <h4>Reset Nilai Pre-Test</h4>
                <p>Menghapus semua nilai pre-test dan jawaban pre-test siswa</p>
                <button class="btn-reset" onclick="showModal('pretest', 'Pre-Test')">Reset Pre-Test</button>
            </div>
            
            <div class="reset-option">
                <h4>Reset Nilai Kuis</h4>
                <p>Menghapus semua nilai kuis interaktif dan kuis esai siswa</p>
                <button class="btn-reset" onclick="showModal('quiz', 'Kuis')">Reset Kuis</button>
            </div>
            
            <div class="reset-option">
                <h4>Reset Nilai Post-Test</h4>
                <p>Menghapus semua nilai post-test dan jawaban post-test siswa</p>
                <button class="btn-reset" onclick="showModal('posttest', 'Post-Test')">Reset Post-Test</button>
            </div>
            
            
            <div class="reset-option" style="border-left-color: #721c24; background: #f8d7da;">
                <h4 style="color: #721c24;">⚠️ Reset Semua Data</h4>
                <p style="color: #721c24;">Menghapus SEMUA nilai, jawaban, dan progress siswa. Gunakan dengan hati-hati!</p>
                <button class="btn-reset" style="background: #721c24;" onclick="showModal('all', 'SEMUA DATA')">Reset Semua</button>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmation -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3>⚠️ Konfirmasi Reset</h3>
            <p id="modalMessage"></p>
            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="reset_type" id="resetType">
                <div class="modal-buttons">
                    <button type="submit" name="confirm_reset" class="btn-confirm">Ya, Reset</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showModal(type, name) {
            document.getElementById('resetType').value = type;
            document.getElementById('modalMessage').textContent = 
                'Anda yakin ingin mereset ' + name + '? Tindakan ini tidak dapat dibatalkan!';
            document.getElementById('resetModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('resetModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('resetModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>