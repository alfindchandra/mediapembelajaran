<?php
require_once 'config.php';
requireSiswa();

$user_id = $_SESSION['user_id'];
$full_name = mysqli_real_escape_string($conn, $_SESSION['full_name']);

// Get Pre-Test score
$pretest = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM nilai_pretest WHERE user_id = $user_id"));

// Get Post-Test score
$posttest = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM nilai_posttest WHERE user_id = $user_id"));

// Get Interactive Quiz score (Menggunakan Nama untuk mencocokkan)
$interaktif = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM nilai_quiz WHERE user_id  = $user_id"));



// Calculate average
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

if ($interaktif) {
    $total_nilai += $interaktif['nilai'];
    $count_nilai++;
}


$rata_rata = $count_nilai > 0 ? $total_nilai / $count_nilai : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Nilai Saya - LMS Jaringan Komputer</title>
    <style>
        /* ... (CSS SAMA SEPERTI SEBELUMNYA) ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar h1 { font-size: 24px; }
        .btn-back { background: rgba(255,255,255,0.2); color: white; padding: 8px 20px; border: 1px solid white; border-radius: 5px; text-decoration: none; transition: all 0.3s; }
        .btn-back:hover { background: white; color: #667eea; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .header-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .header-card h2 { color: #333; margin-bottom: 10px; }
        .average-score { font-size: 48px; font-weight: bold; color: #667eea; margin: 20px 0; }
        .score-label { color: #666; font-size: 18px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s; }
        .summary-card:hover { transform: translateY(-5px); }
        .summary-card .icon { font-size: 40px; margin-bottom: 15px; }
        .summary-card h3 { font-size: 32px; color: #667eea; margin-bottom: 5px; }
        .summary-card p { color: #666; font-size: 14px; }
        .scores-section { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .scores-section h3 { color: #333; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0; }
        .score-item { display: flex; justify-content: space-between; align-items: center; padding: 20px; margin-bottom: 15px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #667eea; }
        .score-item-info h4 { color: #333; margin-bottom: 5px; }
        .score-item-info p { color: #666; font-size: 14px; }
        .score-badge { font-size: 28px; font-weight: bold; padding: 10px 20px; border-radius: 10px; min-width: 100px; text-align: center; }
        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #d1ecf1; color: #0c5460; }
        .score-average { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }
        .score-na { background: #e0e0e0; color: #666; font-size: 16px; }
        .btn-export { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-export:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .export-section { text-align: center; margin-top: 30px; }
        .progress-bar { width: 100%; height: 30px; background: #e0e0e0; border-radius: 15px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px; transition: width 0.5s; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Rekap Nilai Saya</h1>
        <a href="dashboard_siswa.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div class="header-card">
            <h2><?php echo $_SESSION['full_name']; ?></h2>
            <p style="color: #666;">Rekapitulasi Nilai Pembelajaran</p>
            <div class="average-score"><?php echo number_format($rata_rata, 2); ?></div>
            <div class="score-label">Nilai Rata-rata (Termasuk Interaktif)</div>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min($rata_rata, 100); ?>%">
                    <?php echo number_format($rata_rata, 1); ?>%
                </div>
            </div>
        </div>
        
        <div class="summary-grid">
            <div class="summary-card">
                <div class="icon">📋</div>
                <h3><?php echo $pretest ? number_format($pretest['nilai'], 2) : 'N/A'; ?></h3>
                <p>Pre-Test</p>
            </div>
            
            <div class="summary-card">
                <div class="icon">🧩</div>
                <h3><?php echo $interaktif ? number_format($interaktif['nilai'], 2) : 'N/A'; ?></h3>
                <p>Interaktif</p>
            </div>
            
            <div class="summary-card">
                <div class="icon">✅</div>
                <h3><?php echo $posttest ? number_format($posttest['nilai'], 2) : 'N/A'; ?></h3>
                <p>Post-Test</p>
            </div>
        </div>
        
        <div class="scores-section">
            <h3>📋 Pre-Test</h3>
            <?php if ($pretest): 
                $nilai = $pretest['nilai'];
                if ($nilai >= 85) $class = 'score-excellent';
                elseif ($nilai >= 70) $class = 'score-good';
                elseif ($nilai >= 60) $class = 'score-average';
                else $class = 'score-poor';
            ?>
            <div class="score-item">
                <div class="score-item-info">
                    <h4>Pre-Test Jaringan Komputer</h4>
                    <p>Diselesaikan: <?php echo formatTanggal($pretest['completed_at']); ?></p>
                    <p>Jawaban Benar: <?php echo $pretest['jawaban_benar']; ?> dari <?php echo $pretest['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="score-item">
                <div class="score-item-info">
                    <h4>Pre-Test Jaringan Komputer</h4>
                    <p>Belum dikerjakan</p>
                </div>
                <div class="score-badge score-na">N/A</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="scores-section">
            <h3 style="color: #27ae60;">🧩 Evaluasi Interaktif</h3>
            <?php if ($interaktif): 
                $nilai = $interaktif['nilai'];
                if ($nilai >= 85) $class = 'score-excellent';
                elseif ($nilai >= 70) $class = 'score-good';
                elseif ($nilai >= 60) $class = 'score-average';
                else $class = 'score-poor';
            ?>
            <div class="score-item" style="border-left-color: #27ae60;">
                <div class="score-item-info">
                    <h4>Quiz Interaktif TIK</h4>
                    <p>Diselesaikan: <?php echo isset($interaktif['tanggal_submit']) ? formatTanggal($interaktif['tanggal_submit']) : 'Baru saja'; ?></p>
                    <p>Jawaban Benar: <?php echo $interaktif['jawaban_benar']; ?> dari <?php echo $interaktif['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="score-item" style="border-left-color: #27ae60;">
                <div class="score-item-info">
                    <h4>Quiz Interaktif TIK</h4>
                    <p>Belum dikerjakan</p>
                    <a href="quiz.php" style="font-size:12px; color:#27ae60; text-decoration:none;">👉 Kerjakan Sekarang</a>
                </div>
                <div class="score-badge score-na">N/A</div>
            </div>
            <?php endif; ?>
        </div>
        
       
        
        <div class="scores-section">
            <h3>✅ Post-Test</h3>
            <?php if ($posttest): 
                $nilai = $posttest['nilai'];
                if ($nilai >= 85) $class = 'score-excellent';
                elseif ($nilai >= 70) $class = 'score-good';
                elseif ($nilai >= 60) $class = 'score-average';
                else $class = 'score-poor';
            ?>
            <div class="score-item">
                <div class="score-item-info">
                    <h4>Post-Test Jaringan Komputer</h4>
                    <p>Diselesaikan: <?php echo formatTanggal($posttest['completed_at']); ?></p>
                    <p>Jawaban Benar: <?php echo $posttest['jawaban_benar']; ?> dari <?php echo $posttest['total_soal']; ?> soal</p>
                </div>
                <div class="score-badge <?php echo $class; ?>">
                    <?php echo number_format($nilai, 2); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="score-item">
                <div class="score-item-info">
                    <h4>Post-Test Jaringan Komputer</h4>
                    <p>Selesaikan semua materi untuk membuka Post-Test</p>
                </div>
                <div class="score-badge score-na">N/A</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="export-section">
            <a href="export_nilai_siswa.php" class="btn-export">📥 Download Rekap Nilai (PDF)</a>
        </div>
    </div>
</body>
</html>