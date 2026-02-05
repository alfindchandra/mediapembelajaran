<?php
require_once 'config.php';
requireGuru();

// Handle Reset Nilai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reset_nilai') {
    $user_id = intval($_POST['user_id']);
    $tipe_reset = $_POST['tipe_reset']; // pretest, posttest, quiz, all
    
    $success = false;
    
    if ($tipe_reset == 'pretest') {
        $query = "DELETE FROM nilai_pretest WHERE user_id = $user_id";
        $success = mysqli_query($conn, $query);
    } elseif ($tipe_reset == 'posttest') {
        $query = "DELETE FROM nilai_posttest WHERE user_id = $user_id";
        $success = mysqli_query($conn, $query);
    } elseif ($tipe_reset == 'quiz') {
        $query = "DELETE FROM nilai_quiz WHERE user_id = $user_id";
        $success = mysqli_query($conn, $query);
    } elseif ($tipe_reset == 'all') {
        $query1 = "DELETE FROM nilai_pretest WHERE user_id = $user_id";
        $query2 = "DELETE FROM nilai_posttest WHERE user_id = $user_id";
        $query3 = "DELETE FROM nilai_quiz WHERE user_id = $user_id";
        
        $success = mysqli_query($conn, $query1) && 
                   mysqli_query($conn, $query2) && 
                   mysqli_query($conn, $query3);
    }
    
    if ($success) {
        $response = ['status' => 'success', 'message' => 'Nilai berhasil direset!'];
    } else {
        $response = ['status' => 'error', 'message' => 'Gagal mereset nilai: ' . mysqli_error($conn)];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

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
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-detail {
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-detail:hover {
            background: #5568d3;
        }
        
        .btn-reset {
            background: #ff6b6b;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-reset:hover {
            background: #ff5252;
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-body {
            margin-bottom: 20px;
            color: #666;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            background: #e0e0e0;
            color: #333;
            padding: 8px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #d0d0d0;
        }
        
        .btn-confirm {
            background: #ff6b6b;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-confirm:hover {
            background: #ff5252;
        }
        
        .reset-option {
            margin: 12px 0;
        }
        
        .reset-option input[type="radio"] {
            margin-right: 8px;
            cursor: pointer;
        }
        
        .reset-option label {
            cursor: pointer;
            color: #333;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: slideIn 0.3s;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Rekap Nilai Siswa</h1>
        <a href="dashboard_guru.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div id="alertBox" class="alert"></div>
        
        <div class="header-section">
            <div>
                <h2>Rekapitulasi Nilai Semua Siswa</h2>
                <p style="color: #666; margin-top: 5px;">Data lengkap Pre-Test, Post-Test, dan Kuis</p>
            </div>
            <a href="download_pdf.php" class="btn-export">📥 Download Rekap PDF</a>
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
                        <th>Quiz</th>
                        <th>Post-Test</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
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

                    <?php 
$no = 1;
while ($siswa = mysqli_fetch_assoc($siswa_result)): 
    $user_id = $siswa['user_id'];

    // Pre-Test
    $pretest = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT nilai FROM nilai_pretest WHERE user_id = $user_id"));

    // Post-Test
    $posttest = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT nilai FROM nilai_posttest WHERE user_id = $user_id"));

    // Interaktif / Quiz
    $interaktif = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT nilai FROM nilai_quiz WHERE user_id = $user_id"));
?>
<tr>
    <td><?= $no++; ?></td>
    <td><strong><?= $siswa['full_name']; ?></strong></td>
    <td><?= $siswa['username']; ?></td>
    <td>
        <span class="badge-score <?= getBadgeClass($pretest['nilai'] ?? null); ?>">
            <?= formatNilai($pretest['nilai'] ?? null); ?>
        </span>
    </td>
    <td>
        <span class="badge-score <?= getBadgeClass($interaktif['nilai'] ?? null); ?>">
            <?= formatNilai($interaktif['nilai'] ?? null); ?>
        </span>
    </td>
    <td>
        <span class="badge-score <?= getBadgeClass($posttest['nilai'] ?? null); ?>">
            <?= formatNilai($posttest['nilai'] ?? null); ?>
        </span>
    </td>
    <td>
        <div class="action-buttons">
            <a href="detail_siswa.php?id=<?= $user_id; ?>" class="btn-detail">Detail</a>
            <button class="btn-reset" onclick="openResetModal(<?= $user_id; ?>, '<?= htmlspecialchars($siswa['full_name']); ?>')">Reset</button>
        </div>
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
    
    <!-- Modal Reset Nilai -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Reset Nilai Siswa</div>
            <div class="modal-body">
                <p id="studentName" style="margin-bottom: 15px; font-weight: 600;"></p>
                <p style="margin-bottom: 15px; color: #666;">Pilih jenis nilai yang ingin direset:</p>
                
                <div class="reset-option">
                    <input type="radio" id="resetPretest" name="resetType" value="pretest" checked>
                    <label for="resetPretest">Reset Pre-Test saja</label>
                </div>
                
                <div class="reset-option">
                    <input type="radio" id="resetQuiz" name="resetType" value="quiz">
                    <label for="resetQuiz">Reset Quiz saja</label>
                </div>
                
                <div class="reset-option">
                    <input type="radio" id="resetPosttest" name="resetType" value="posttest">
                    <label for="resetPosttest">Reset Post-Test saja</label>
                </div>
                
                <div class="reset-option">
                    <input type="radio" id="resetAll" name="resetType" value="all">
                    <label for="resetAll">Reset Semua Nilai (Pre-Test, Quiz, Post-Test)</label>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeResetModal()">Batal</button>
                <button class="btn-confirm" onclick="confirmReset()">Reset Nilai</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentUserId = null;
        
        function openResetModal(userId, studentName) {
            currentUserId = userId;
            document.getElementById('studentName').textContent = 'Siswa: ' + studentName;
            document.getElementById('resetModal').style.display = 'block';
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
            currentUserId = null;
        }
        
        function confirmReset() {
            const resetType = document.querySelector('input[name="resetType"]:checked').value;
            
            const formData = new FormData();
            formData.append('action', 'reset_nilai');
            formData.append('user_id', currentUserId);
            formData.append('tipe_reset', resetType);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeResetModal();
                showAlert(data.message, data.status);
                
                if (data.status === 'success') {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                showAlert('Terjadi kesalahan: ' + error, 'error');
            });
        }
        
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert alert-' + type;
            alertBox.style.display = 'block';
            
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 3000);
        }
        
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resetModal');
            if (event.target == modal) {
                closeResetModal();
            }
        }
    </script>
</body>
</html>