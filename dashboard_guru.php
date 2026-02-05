<?php
require_once 'config.php';
requireGuru();

// Get statistics
$total_siswa_query = "SELECT COUNT(*) as total FROM users WHERE role = 'siswa'";
$total_siswa = mysqli_fetch_assoc(mysqli_query($conn, $total_siswa_query))['total'];

$pretest_completed_query = "SELECT COUNT(DISTINCT user_id) as total FROM nilai_pretest";
$pretest_completed = mysqli_fetch_assoc(mysqli_query($conn, $pretest_completed_query))['total'];

$posttest_completed_query = "SELECT COUNT(DISTINCT user_id) as total FROM nilai_posttest";
$posttest_completed = mysqli_fetch_assoc(mysqli_query($conn, $posttest_completed_query))['total'];



// Get recent activities
$recent_query = "
    SELECT u.full_name, 'Pre-Test' AS tipe, np.nilai, np.completed_at AS waktu
    FROM nilai_pretest np
    JOIN users u ON np.user_id = u.user_id

    UNION ALL

    SELECT u.full_name, 'Quiz Interaktif' AS tipe, nq.nilai, nq.completed_at AS waktu
    FROM nilai_quiz nq
    JOIN users u ON nq.user_id = u.user_id

    UNION ALL

    SELECT u.full_name, 'Post-Test' AS tipe, npo.nilai, npo.completed_at AS waktu
    FROM nilai_posttest npo
    JOIN users u ON npo.user_id = u.user_id

    ORDER BY waktu DESC
    LIMIT 10
";


$recent_result = mysqli_query($conn, $recent_query);

// Get average scores
$avg_pretest_query = "SELECT AVG(nilai) as avg_nilai FROM nilai_pretest";
$avg_pretest = mysqli_fetch_assoc(mysqli_query($conn, $avg_pretest_query))['avg_nilai'];

$avg_posttest_query = "SELECT AVG(nilai) as avg_nilai FROM nilai_posttest";
$avg_posttest = mysqli_fetch_assoc(mysqli_query($conn, $avg_posttest_query))['avg_nilai'];


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - LMS Jaringan Komputer</title>
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
            color: #11998e;
        }
        
        .container {
            max-width: 1400px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 50px;
        }
        
        .stat-info h3 {
            font-size: 32px;
            color: #11998e;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #666;
            font-size: 14px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
        }
        
        .menu-card .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .menu-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .menu-card p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-menu {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-menu:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        
        .recent-activity {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .recent-activity h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .activity-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }
        
        .activity-table tr:hover {
            background: #f8f9fa;
        }
        
        .badge-score {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .badge-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: #333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>👨‍🏫 Dashboard Guru - LMS Jaringan Komputer</h1>
        <div class="navbar-right">
            <div class="user-info">
                <strong><?php echo $_SESSION['full_name']; ?></strong><br>
                <small>Administrator</small>
            </div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Selamat Datang, <?php echo $_SESSION['full_name']; ?>! 👋</h2>
            <p>Kelola dan pantau progres pembelajaran siswa dalam mata pelajaran Jaringan Komputer.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?php echo $total_siswa; ?></h3>
                    <p>Total Siswa</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <h3><?php echo $pretest_completed; ?></h3>
                    <p>Pre-Test Selesai</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $posttest_completed; ?></h3>
                    <p>Post-Test Selesai</p>
                </div>
            </div>
            
            
        </div>
        
        <div class="section-header">
            <h2>📊 Menu Utama</h2>
        </div>
        
        <div class="menu-grid">
            <div class="menu-card">
                <div class="icon">📈</div>
                <h3>Rekap Nilai Lengkap</h3>
                <p>Lihat semua nilai siswa untuk Pre-Test, Post-Test, dan Kuis</p>
                <a href="rekap_nilai_guru.php" class="btn-menu">Lihat Rekap</a>
            </div>
            
            <div class="menu-card">
                <div class="icon">📝</div>
                <h3>Kelola Soal Test</h3>
                <p>Tambah, edit, atau hapus soal Pre-Test dan Post-Test</p>
                <a href="kelola_soal.php" class="btn-menu">Kelola Soal</a>
            </div>
            
            <div class="menu-card">
                <div class="icon">🔄</div>
                <h3>Reset Rekap Nilai</h3>
                <p>Reset nilai siswa untuk Pre-Test, Post-Test, atau semua data</p>
                <a href="reset_nilai.php" class="btn-menu" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">Reset Nilai</a>
            </div>
        </div>
        
        <div class="recent-activity">
            <h2>🕒 Aktivitas Terbaru</h2>
            
            <?php if (mysqli_num_rows($recent_result) > 0): ?>
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Jenis Assessment</th>
                        <th>Nilai</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($activity = mysqli_fetch_assoc($recent_result)): 
                        $nilai = $activity['nilai'];
                        if ($nilai >= 85) {
                            $badge_class = 'badge-excellent';
                        } elseif ($nilai >= 70) {
                            $badge_class = 'badge-good';
                        } elseif ($nilai >= 60) {
                            $badge_class = 'badge-average';
                        } else {
                            $badge_class = 'badge-poor';
                        }
                    ?>
                    <tr>
                        <td><?php echo $activity['full_name']; ?></td>
                        <td><?php echo $activity['tipe']; ?></td>
                        <td>
                            <span class="badge-score <?php echo $badge_class; ?>">
                                <?php echo number_format($nilai, 2); ?>
                            </span>
                        </td>
                        <td><?php echo formatTanggal($activity['waktu']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #666; padding: 20px;">Belum ada aktivitas terbaru.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>