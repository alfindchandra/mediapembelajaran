<?php
require_once 'config.php';
requireGuru();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get student info
$siswa_query = "SELECT * FROM users WHERE user_id = $user_id AND role = 'siswa'";
$siswa_result = mysqli_query($conn, $siswa_query);

if (mysqli_num_rows($siswa_result) === 0) {
    header("Location: rekap_nilai_guru.php");
    exit();
}

$siswa = mysqli_fetch_assoc($siswa_result);

// Get scores
$pretest = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM nilai_pretest WHERE user_id = $user_id"));

$posttest = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM nilai_posttest WHERE user_id = $user_id"));

$kuis_query = "SELECT nk.*, m.judul, m.urutan 
               FROM nilai_kuis nk 
               JOIN materi m ON nk.materi_id = m.materi_id 
               WHERE nk.user_id = $user_id 
               ORDER BY m.urutan";
$kuis_result = mysqli_query($conn, $kuis_query);

// Get progress
$progress_query = "SELECT ps.*, m.judul, m.urutan 
                   FROM progress_siswa ps 
                   JOIN materi m ON ps.materi_id = m.materi_id 
                   WHERE ps.user_id = $user_id 
                   ORDER BY m.urutan";
$progress_result = mysqli_query($conn, $progress_query);

// Calculate stats
$total_nilai = 0;
$count_nilai = 0;

if ($pretest) {
    $total_nilai += $pretest['nilai'];
    $count_nilai++;
}

if ($posttest) {
    $total_nilai += $posttest['nilai'];
    $count_nilai++;
}

$kuis_total = 0;
$kuis_count = 0;
mysqli_data_seek($kuis_result, 0);
while ($kuis = mysqli_fetch_assoc($kuis_result)) {
    $total_nilai += $kuis['nilai'];
    $count_nilai++;
    $kuis_total += $kuis['nilai'];
    $kuis_count++;
}

$rata_rata = $count_nilai > 0 ? $total_nilai / $count_nilai : 0;
$rata_kuis = $kuis_count > 0 ? $kuis_total / $kuis_count : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa - <?php echo $siswa['full_name']; ?></title>
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .student-header {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .student-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin: 0 auto 20px;
        }
        
        .student-name {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .student-info {
            color: #666;
            margin-bottom: 20px;
        }
        
        .overall-score {
            font-size: 48px;
            font-weight: bold;
            color: #11998e;
            margin: 20px 0 10px;
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
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 28px;
            color: #11998e;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .progress-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #11998e;
        }
        
        .progress-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .progress-info p {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
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
        
        .score-badge {
            font-size: 24px;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 10px;
            min-width: 80px;
            text-align: center;
        }
        
        .score-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .score-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .score-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .score-na {
            background: #e0e0e0;
            color: #666;
            font-size: 16px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 250px;
            padding: 20px 0;
        }
        
        .bar {
            flex: 1;
            max-width: 80px;
            background: linear-gradient(to top, #11998e 0%, #38ef7d 100%);
            border-radius: 5px 5px 0 0;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s;
        }
        
        .bar:hover {
            opacity: 0.8;
        }
        
        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }
        
        .bar-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Detail Progress Siswa</h1>
        <a href="rekap_nilai_guru.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div class="student-header">
            <div class="student-avatar">👤</div>
            <div class="student-name"><?php echo $siswa['full_name']; ?></div>
            <div class="student-info">
                <strong>Username:</strong> <?php echo $siswa['username']; ?><br>
                <strong>Terdaftar:</strong> <?php echo formatTanggal($siswa['created_at']); ?>
            </div>
            <div class="overall-score"><?php echo number_format($rata_rata, 2); ?></div>
            <div>Nilai Rata-rata Keseluruhan</div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">📋</div>
                <h3><?php echo $pretest ? number_format($pretest['nilai'], 2) : 'N/A'; ?></h3>
                <p>Pre-Test</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">🎯</div>
                <h3><?php echo $kuis_count; ?>/6</h3>
                <p>Kuis Selesai</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">📊</div>
                <h3><?php echo $kuis_count > 0 ? number_format($rata_kuis, 2) : 'N/A'; ?></h3>
                <p>Rata-rata Kuis</p>
            </div>
            
            <div class="stat-card">
                <div class="icon">✅</div>
                <h3><?php echo $posttest ? number_format($posttest['nilai'], 2) : 'N/A'; ?></h3>
                <p>Post-Test</p>
            </div>
        </div>
        
        <div class="section">
            <h2>📈 Grafik Nilai</h2>
            <div class="chart-container">
                <div class="bar-chart">
                    <?php if ($pretest): ?>
                    <div class="bar" style="height: <?php echo $pretest['nilai']; ?>%">
                        <div class="bar-value"><?php echo number_format($pretest['nilai'], 1); ?></div>
                        <div class="bar-label">Pre-Test</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    mysqli_data_seek($kuis_result, 0);
                    while ($kuis = mysqli_fetch_assoc($kuis_result)): 
                    ?>
                    <div class="bar" style="height: <?php echo $kuis['nilai']; ?>%">
                        <div class="bar-value"><?php echo number_format($kuis['nilai'], 1); ?></div>
                        <div class="bar-label">Kuis <?php echo $kuis['urutan']; ?></div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($posttest): ?>
                    <div class="bar" style="height: <?php echo $posttest['nilai']; ?>%">
                        <div class="bar-value"><?php echo number_format($posttest['nilai'], 1); ?></div>
                        <div class="bar-label">Post-Test</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>📚 Progress Materi</h2>
            <?php while ($progress = mysqli_fetch_assoc($progress_result)): 
                $status_labels = [
                    'locked' => 'Terkunci',
                    'in-progress' => 'Sedang Belajar',
                    'completed' => 'Selesai'
                ];
                $status_class = 'status-' . str_replace('-', '', $progress['status']);
            ?>
            <div class="progress-item">
                <div class="progress-info">
                    <h4>Materi <?php echo $progress['urutan']; ?>: <?php echo $progress['judul']; ?></h4>
                    <p>Terakhir diakses: <?php echo formatTanggal($progress['last_accessed']); ?></p>
                </div>
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo $status_labels[$progress['status']]; ?>
                </span>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="section">
            <h2>🎯 Detail Nilai Kuis</h2>
            <?php 
            mysqli_data_seek($kuis_result, 0);
            if (mysqli_num_rows($kuis_result) > 0):
                while ($kuis = mysqli_fetch_assoc($kuis_result)): 
                    $nilai = $kuis['nilai'];
                    if ($nilai >= 85) $class = 'score-excellent';
                    elseif ($nilai >= 70) $class = 'score-good';
                    elseif ($nilai >= 60) $class = 'score-average';
                    else $class = 'score-poor';
            ?>
            <div class="progress-item">
                <div class="progress-info">
                    <h4>Kuis <?php echo $kuis['urutan']; ?>: <?php echo $kuis['judul']; ?></h4>
                    <p>Diselesaikan: <?php echo formatTanggal($kuis['completed_at']); ?></p>
                    <p>Benar: <?php echo $kuis['jawaban_benar']; ?> dari <?php echo $kuis['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
            <?php 
                endwhile;
            else: 
            ?>
            <p style="text-align: center; color: #666; padding: 20px;">Siswa belum menyelesaikan kuis apapun.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($pretest): ?>
        <div class="section">
            <h2>📋 Pre-Test</h2>
            <?php
                $nilai = $pretest['nilai'];
                if ($nilai >= 85) $class = 'score-excellent';
                elseif ($nilai >= 70) $class = 'score-good';
                elseif ($nilai >= 60) $class = 'score-average';
                else $class = 'score-poor';
            ?>
            <div class="progress-item">
                <div class="progress-info">
                    <h4>Pre-Test Jaringan Komputer</h4>
                    <p>Diselesaikan: <?php echo formatTanggal($pretest['completed_at']); ?></p>
                    <p>Benar: <?php echo $pretest['jawaban_benar']; ?> dari <?php echo $pretest['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($posttest): ?>
        <div class="section">
            <h2>✅ Post-Test</h2>
            <?php
                $nilai = $posttest['nilai'];
                if ($nilai >= 85) $class = 'score-excellent';
                elseif ($nilai >= 70) $class = 'score-good';
                elseif ($nilai >= 60) $class = 'score-average';
                else $class = 'score-poor';
            ?>
            <div class="progress-item">
                <div class="progress-info">
                    <h4>Post-Test Jaringan Komputer</h4>
                    <p>Diselesaikan: <?php echo formatTanggal($posttest['completed_at']); ?></p>
                    <p>Benar: <?php echo $posttest['jawaban_benar']; ?> dari <?php echo $posttest['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>