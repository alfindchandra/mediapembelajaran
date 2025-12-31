<?php
require_once 'config.php';
requireGuru();

// Get all students
$siswa_query = "SELECT * FROM users WHERE role = 'siswa' ORDER BY full_name";
$siswa_result = mysqli_query($conn, $siswa_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Nilai - LMS Jaringan Komputer</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-section h2 {
            color: #333;
        }
        
        .btn-export {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        
        .filter-section {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-group label {
            color: #333;
            font-weight: 600;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        .data-table thead {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge-score {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            min-width: 60px;
            text-align: center;
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
        
        .badge-na {
            background: #e0e0e0;
            color: #666;
        }
        
        .btn-detail {
            background: #667eea;
            color: white;
            padding: 6px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-detail:hover {
            background: #5568d3;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .summary-card h3 {
            font-size: 28px;
            color: #11998e;
            margin-bottom: 5px;
        }
        
        .summary-card p {
            color: #666;
            font-size: 14px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Rekap Nilai Siswa</h1>
        <a href="dashboard_guru.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div class="header-section">
            <div>
                <h2>Rekapitulasi Nilai Semua Siswa</h2>
                <p style="color: #666; margin-top: 5px;">Data lengkap Pre-Test, Post-Test, dan Kuis</p>
            </div>
            <a href="export_nilai.php" class="btn-export">📥 Export ke Excel</a>
        </div>
        
        <?php
        // Calculate summary statistics
        $total_siswa = mysqli_num_rows($siswa_result);
        
        $pretest_completed = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(DISTINCT user_id) as total FROM nilai_pretest"))['total'];
        
        $posttest_completed = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(DISTINCT user_id) as total FROM nilai_posttest"))['total'];
        
        $avg_pretest = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT AVG(nilai) as avg FROM nilai_pretest"))['avg'];
        
        $avg_posttest = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT AVG(nilai) as avg FROM nilai_posttest"))['avg'];
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <h3><?php echo $total_siswa; ?></h3>
                <p>Total Siswa</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $pretest_completed; ?></h3>
                <p>Pre-Test Selesai</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $posttest_completed; ?></h3>
                <p>Post-Test Selesai</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $avg_pretest ? number_format($avg_pretest, 1) : 'N/A'; ?></h3>
                <p>Rata-rata Pre-Test</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $avg_posttest ? number_format($avg_posttest, 1) : 'N/A'; ?></h3>
                <p>Rata-rata Post-Test</p>
            </div>
        </div>
        
        <div class="filter-section">
            <div class="filter-group">
                <label>🔍 Cari Siswa:</label>
                <input type="text" id="searchInput" placeholder="Nama siswa..." onkeyup="filterTable()">
            </div>
        </div>
        
        <div class="table-container">
            <?php mysqli_data_seek($siswa_result, 0); ?>
            <?php if (mysqli_num_rows($siswa_result) > 0): ?>
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Siswa</th>
                        <th>Username</th>
                        <th>Pre-Test</th>
                        <th>Kuis 1</th>
                        <th>Kuis 2</th>
                        <th>Kuis 3</th>
                        <th>Kuis 4</th>
                        <th>Kuis 5</th>
                        <th>Kuis 6</th>
                        <th>Post-Test</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while ($siswa = mysqli_fetch_assoc($siswa_result)): 
                        $user_id = $siswa['user_id'];
                        
                        // Get Pre-Test
                        $pretest = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT nilai FROM nilai_pretest WHERE user_id = $user_id"));
                        
                        // Get Post-Test
                        $posttest = mysqli_fetch_assoc(mysqli_query($conn, 
                            "SELECT nilai FROM nilai_posttest WHERE user_id = $user_id"));
                        
                        // Get Kuis scores
                        $kuis_scores = [];
                        for ($i = 1; $i <= 6; $i++) {
                            $kuis = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT nilai FROM nilai_kuis WHERE user_id = $user_id AND materi_id = $i"));
                            $kuis_scores[$i] = $kuis ? $kuis['nilai'] : null;
                        }
                        
                        function getBadgeClass($nilai) {
                            if ($nilai === null) return 'badge-na';
                            if ($nilai >= 85) return 'badge-excellent';
                            if ($nilai >= 70) return 'badge-good';
                            if ($nilai >= 60) return 'badge-average';
                            return 'badge-poor';
                        }
                        
                        function formatNilai($nilai) {
                            return $nilai !== null ? number_format($nilai, 2) : 'N/A';
                        }
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $siswa['full_name']; ?></strong></td>
                        <td><?php echo $siswa['username']; ?></td>
                        <td>
                            <span class="badge-score <?php echo getBadgeClass($pretest['nilai'] ?? null); ?>">
                                <?php echo formatNilai($pretest['nilai'] ?? null); ?>
                            </span>
                        </td>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <td>
                            <span class="badge-score <?php echo getBadgeClass($kuis_scores[$i]); ?>">
                                <?php echo formatNilai($kuis_scores[$i]); ?>
                            </span>
                        </td>
                        <?php endfor; ?>
                        <td>
                            <span class="badge-score <?php echo getBadgeClass($posttest['nilai'] ?? null); ?>">
                                <?php echo formatNilai($posttest['nilai'] ?? null); ?>
                            </span>
                        </td>
                        <td>
                            <a href="detail_siswa.php?id=<?php echo $user_id; ?>" class="btn-detail">Detail</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>Belum ada data siswa.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('dataTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1]; // Nama siswa column
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>