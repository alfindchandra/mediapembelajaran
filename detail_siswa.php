<?php
require_once 'config.php';
requireGuru();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data siswa
$siswa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE user_id = $user_id AND role = 'siswa'"
));

if (!$siswa) {
    header("Location: rekap_nilai_guru.php");
    exit();
}

// Nilai
$pretest = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM nilai_pretest WHERE user_id = $user_id"
));

$posttest = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM nilai_posttest WHERE user_id = $user_id"
));

$interaktif = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM nilai_quiz WHERE user_id = $user_id"
));

// Hitung rata-rata
$total_nilai = 0;
$count_nilai = 0;

foreach ([$pretest, $interaktif, $posttest] as $n) {
    if ($n) {
        $total_nilai += $n['nilai'];
        $count_nilai++;
    }
}

$rata_rata = $count_nilai > 0 ? $total_nilai / $count_nilai : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Nilai Siswa</title>

<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI', Tahoma, sans-serif; background:#f5f7fa; }

.navbar {
    background:linear-gradient(135deg,#11998e,#38ef7d);
    color:white;
    padding:20px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.navbar h1 { font-size:24px; }
.btn-back {
    color:white;
    text-decoration:none;
    padding:8px 20px;
    border:1px solid white;
    border-radius:5px;
}
.btn-back:hover { background:white; color:#11998e; }

.container { max-width:1000px; margin:40px auto; padding:0 20px; }

.header-card {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    text-align:center;
    margin-bottom:30px;
}
.average-score {
    font-size:48px;
    font-weight:bold;
    color:#11998e;
    margin:15px 0;
}
.score-label { color:#666; }

.progress-bar {
    width:100%;
    height:30px;
    background:#e0e0e0;
    border-radius:15px;
    overflow:hidden;
    margin-top:15px;
}
.progress-fill {
    height:100%;
    background:linear-gradient(135deg,#11998e,#38ef7d);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}

.summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:30px;
}
.summary-card {
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    text-align:center;
}
.summary-card h3 {
    font-size:32px;
    color:#11998e;
}

.scores-section {
    background:white;
    padding:30px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
    margin-bottom:30px;
}
.score-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#f8f9fa;
    padding:20px;
    border-radius:10px;
    border-left:4px solid #11998e;
}
.score-badge {
    font-size:26px;
    font-weight:bold;
    padding:10px 20px;
    border-radius:10px;
}
.score-excellent { background:#d4edda; color:#155724; }
.score-good { background:#d1ecf1; color:#0c5460; }
.score-average { background:#fff3cd; color:#856404; }
.score-poor { background:#f8d7da; color:#721c24; }
.score-na { background:#e0e0e0; color:#666; }
</style>
</head>

<body>

<nav class="navbar">
    <h1>📊 Detail Nilai Siswa</h1>
    <a href="rekap_nilai_guru.php" class="btn-back">← Kembali</a>
</nav>

<div class="container">

<div class="header-card">
    <h2><?= htmlspecialchars($siswa['full_name']); ?></h2>
    <p style="color:#666;">Rekapitulasi Nilai Pembelajaran</p>
    <div class="average-score"><?= number_format($rata_rata,2); ?></div>
    <div class="score-label">Nilai Rata-rata Keseluruhan</div>

    <div class="progress-bar">
        <div class="progress-fill" style="width:<?= min($rata_rata,100); ?>%">
            <?= number_format($rata_rata,1); ?>%
        </div>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <h3><?= $pretest ? number_format($pretest['nilai'],2) : 'N/A'; ?></h3>
        <p>Pre-Test</p>
    </div>
    <div class="summary-card">
        <h3><?= $interaktif ? number_format($interaktif['nilai'],2) : 'N/A'; ?></h3>
        <p>Interaktif</p>
    </div>
    <div class="summary-card">
        <h3><?= $posttest ? number_format($posttest['nilai'],2) : 'N/A'; ?></h3>
        <p>Post-Test</p>
    </div>
</div>

<?php
function badge($nilai){
    if ($nilai >= 85) return 'score-excellent';
    if ($nilai >= 70) return 'score-good';
    if ($nilai >= 60) return 'score-average';
    return 'score-poor';
}
?>

<div class="scores-section">
<h3>🧩 Evaluasi Interaktif</h3>
<?php if ($interaktif): ?>
<div class="score-item">
    <div>
        <strong>Interaktif Jaringan Komputer</strong><br>
        Diselesaikan: <?= formatTanggal($interaktif['completed_at']); ?><br>
        Benar: <?= $interaktif['jawaban_benar']; ?> dari <?= $interaktif['total_soal']; ?> soal
    </div>
    <div class="score-badge <?= badge($interaktif['nilai']); ?>">
        <?= number_format($interaktif['nilai'],2); ?>
    </div>
</div>
<?php else: ?>
<div class="score-item">
    <div>Belum dikerjakan</div>
    <div class="score-badge score-na">N/A</div>
</div>
<?php endif; ?>
</div>

</div>
</body>
</html>
