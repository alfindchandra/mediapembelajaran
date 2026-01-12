<?php
require_once 'config.php';
requireSiswa();

$user_id = $_SESSION['user_id'];

// Check if pretest is completed
$pretest_query = "SELECT * FROM nilai_pretest WHERE user_id = $user_id";
$pretest_result = mysqli_query($conn, $pretest_query);
$pretest_completed = mysqli_num_rows($pretest_result) > 0;

// Get all materi
$materi_query = "SELECT * FROM materi ORDER BY urutan";
$materi_result = mysqli_query($conn, $materi_query);

// Get progress
$progress_query = "SELECT * FROM progress_siswa WHERE user_id = $user_id";
$progress_result = mysqli_query($conn, $progress_query);
$progress_data = [];
while ($row = mysqli_fetch_assoc($progress_result)) {
    $progress_data[$row['materi_id']] = $row;
}

// Initialize progress if not exists
$materi_result_init = mysqli_query($conn, "SELECT materi_id FROM materi ORDER BY urutan");
$first_materi = true;
while ($materi = mysqli_fetch_assoc($materi_result_init)) {
    if (!isset($progress_data[$materi['materi_id']])) {
        $status = $first_materi && $pretest_completed ? 'in-progress' : 'locked';
        mysqli_query($conn, "INSERT INTO progress_siswa (user_id, materi_id, status) VALUES ($user_id, {$materi['materi_id']}, '$status')");
        if ($status === 'in-progress') {
            $first_materi = false;
        }
    } else {
        if ($progress_data[$materi['materi_id']]['status'] === 'in-progress' || $progress_data[$materi['materi_id']]['status'] === 'completed') {
            $first_materi = false; 
        }
    }
}

// Reload progress (needed after initialization)
$progress_result = mysqli_query($conn, $progress_query);
$progress_data = [];
while ($row = mysqli_fetch_assoc($progress_result)) {
    $progress_data[$row['materi_id']] = $row;
}

// Get nilai
$nilai_pretest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_pretest WHERE user_id = $user_id"));
$nilai_posttest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_posttest WHERE user_id = $user_id"));

// Check if all materi are completed for Post-Test
$all_completed = true;
// Re-fetch materi result for display, as the previous one might be exhausted
$materi_result_display = mysqli_query($conn, "SELECT * FROM materi ORDER BY urutan");

// Check completion status against all materi
while ($materi_check = mysqli_fetch_assoc($materi_result_display)) {
    if (!isset($progress_data[$materi_check['materi_id']]) || $progress_data[$materi_check['materi_id']]['status'] !== 'completed') {
        $all_completed = false;
        break;
    }
}

// Reset pointer for materi display loop
mysqli_data_seek($materi_result, 0);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LMS Jaringan Komputer</title>
    <style>
        /* CSS Umum dan Navbar tetap sama... */
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            font-size: 14px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 1px solid white;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
        }
        
        /* --- Bagian Penting untuk Responsif 3 Kolom --- */
        .assessment-section {
            /* Menyesuaikan untuk 3 kolom pada layar besar */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* 300px adalah min lebar per kartu */
            gap: 20px;
            margin-bottom: 40px;
        }
        
        /* Media Query untuk membuat menjadi 1 kolom di layar yang sangat kecil */
        @media (max-width: 768px) {
            .assessment-section {
                grid-template-columns: 1fr;
            }
        }

        /* Jika Anda ingin 2 kolom pada tablet (opsional): */
        @media (min-width: 769px) and (max-width: 1024px) {
             .assessment-section {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }
        /* --- Akhir Bagian Penting --- */

        .assessment-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .assessment-card:hover {
            transform: translateY(-5px);
        }
        
        .assessment-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .assessment-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .assessment-card p {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            height: 40px; /* Menjaga tinggi agar kartu seragam */
        }
        
        .btn-assessment {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-assessment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-assessment.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .btn-assessment.completed {
            background: #28a745;
        }

        .btn-assessment.secondary {
            background: #20c997;
        }
        .btn-assessment.secondary:hover {
            box-shadow: 0 5px 15px rgba(32, 201, 151, 0.4);
        }
        
        .materi-section h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .materi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .materi-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .materi-card:hover {
            transform: translateY(-5px);
        }
        
        .materi-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        
        .materi-card-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .materi-card-body {
            padding: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .status-locked {
            background: #e0e0e0;
            color: #666;
        }
        
        .status-progress {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .btn-materi {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .btn-materi:hover {
            background: #5568d3;
        }
        
        .btn-materi.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .nilai-badge {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🌐 LMS Jaringan Komputer</h1>
        <div class="navbar-right">
            <div class="user-info">
                <strong><?php echo $_SESSION['full_name']; ?></strong><br>
                <small>Siswa</small>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang, <?php echo $_SESSION['full_name']; ?>! 👋</h2>
            <p>Selamat belajar di platform LMS Jaringan Komputer. Ikuti semua materi dan selesaikan assessment untuk meningkatkan pemahaman Anda.</p>
        </div>
        
        <h2 style="color: #333; margin-bottom: 20px;">📝 Assessment & Sumber Belajar</h2>
        <div class="assessment-section">
             <div class="assessment-card">
                <div class="icon">📋</div>
                <h3>Pre-Test</h3>
                <p>Uji pengetahuan awal Anda</p>
                <?php if ($nilai_pretest): ?>
                    <a href="pretest.php" class="btn-assessment completed">Lihat Hasil</a>
                <?php else: ?>
                    <a href="pretest.php" class="btn-assessment">Mulai Pre-Test</a>
                <?php endif; ?>
            </div>
           

            <div class="assessment-card">
                <div class="icon">🎬</div>
                <h3>Animasi</h3>
                <p>Tonton animasi interaktif untuk pemahaman konsep</p>
                <a href="animasi.php" class="btn-assessment secondary">Tonton</a>
            </div>

             
            <div class="assessment-card">
                <div class="icon">📚</div>
                <h3>Buku Digital</h3>
                <p>Akses Flipbook dan semua sumber materi</p>
                <a href="book_viewer.php?book=JKD001"  class="btn-assessment secondary">
                    Buka Buku</a>
</div>

            <div class="assessment-card">
                <div class="icon">📖</div>
                <h3>Kuis Interaktif</h3>
                <p>Selesaikan kuis interaktif</p>
                <a href="quiz.php" class="btn-assessment">Lihat Materi</a>
            </div>
            
           
         <div class="assessment-card">
    <div class="icon">✅</div>
    <h3>Post-Test</h3>
    <p>Evaluasi akhir pembelajaran</p>

    <?php if ($nilai_posttest): ?>
        <span class="nilai-badge">
            <?php echo number_format($nilai_posttest['nilai'], 2); ?>
        </span>
        <br>
        <a href="posttest.php" class="btn-assessment completed">Lihat Hasil</a>
    <?php else: ?>
        <a href="posttest.php" class="btn-assessment">
            Mulai Post-Test
        </a>
    <?php endif; ?>
</div>

            <div class="assessment-card">
                <div class="icon">📊</div>
                <h3>Rekap Nilai</h3>
                <p>Lihat semua nilai yang telah Anda peroleh</p>
                <a href="rekap_nilai_siswa.php" class="btn-assessment">Lihat Rekap</a>
            </div>
        </div>

        <hr>
        
       
    </div>
</body>
</html>