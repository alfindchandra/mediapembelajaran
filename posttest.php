<?php
require_once 'config.php';
requireSiswa();

$user_id = $_SESSION['user_id'];

// Check if already completed
$check_query = "SELECT * FROM nilai_posttest WHERE user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);
$already_completed = mysqli_num_rows($check_result) > 0;


// Check if quiz interaktif completed
$quiz_query = "SELECT COUNT(*) AS total FROM nilai_quiz WHERE user_id = $user_id";
$quiz_result = mysqli_query($conn, $quiz_query);
$quiz_data = mysqli_fetch_assoc($quiz_result);
$quiz_completed = $quiz_data['total'] > 0;



// Process submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $jawaban_benar = 0;
    $total_soal = 0;
    
    // Get all posttest questions
    $soal_query = "SELECT * FROM test_soal WHERE tipe_test = 'posttest' ORDER BY urutan";
    $soal_result = mysqli_query($conn, $soal_query);
    
    while ($soal = mysqli_fetch_assoc($soal_result)) {
        $total_soal++;
        $soal_id = $soal['soal_id'];
        $jawaban_user = isset($_POST['jawaban_' . $soal_id]) ? $_POST['jawaban_' . $soal_id] : '';
        $is_correct = ($jawaban_user === $soal['jawaban_benar']) ? 1 : 0;
        
        if ($is_correct) {
            $jawaban_benar++;
        }
        
        // Save answer
        mysqli_query($conn, "INSERT INTO jawaban_siswa (user_id, soal_id, tipe, jawaban, is_correct) 
                            VALUES ($user_id, $soal_id, 'posttest', '$jawaban_user', $is_correct)");
    }
    
    $nilai = hitungNilai($jawaban_benar, $total_soal);
    
    // Save score
    mysqli_query($conn, "INSERT INTO nilai_posttest (user_id, total_soal, jawaban_benar, nilai) 
                        VALUES ($user_id, $total_soal, $jawaban_benar, $nilai)");
    
    header("Location: posttest.php?success=1");
    exit();
}

// Get posttest questions
$soal_query = "SELECT * FROM test_soal WHERE tipe_test = 'posttest' ORDER BY urutan";
$soal_result = mysqli_query($conn, $soal_query);
$soal_list = [];
while ($row = mysqli_fetch_assoc($soal_result)) {
    $soal_list[] = $row;
}

$show_success = isset($_GET['success']);
$nilai_data = null;
if ($already_completed) {
    $nilai_data = mysqli_fetch_assoc($check_result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post-Test - LMS Jaringan Komputer</title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            color: #28a745;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .test-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .test-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .timer-container {
            position: fixed;
            top: 100px;
            right: 40px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
            text-align: center;
            min-width: 150px;
            z-index: 1000;
        }
        
        .timer-label {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .timer-display {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
        }
        
        .timer-display.warning {
            color: #ff9800;
        }
        
        .timer-display.danger {
            color: #f44336;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .question-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .question-number {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .question-text {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option:hover {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .option input[type="radio"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .option label {
            cursor: pointer;
            flex: 1;
            font-size: 16px;
            color: #333;
        }
        
        .option input[type="radio"]:checked + label {
            color: #28a745;
            font-weight: 600;
        }
        
        .submit-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }
        
        .result-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .result-score {
            font-size: 48px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .result-text {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .result-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .result-details p {
            color: #333;
            margin: 5px 0;
        }
        
        .alert {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left: 4px solid #856404;
            color: #856404;
        }
        
        .locked-message {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .locked-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .timer-container {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>✅ Post-Test Jaringan Komputer</h1>
        <a href="dashboard_siswa.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <?php if ($already_completed || $show_success): ?>
            <div class="result-card">
                <div class="result-icon">🎊</div>
                <h2 style="color: #333; margin-bottom: 20px;">Selamat! Post-Test Selesai!</h2>
                <div class="result-score"><?php echo number_format($nilai_data['nilai'], 2); ?></div>
                <div class="result-text">Skor Anda</div>
                
                <div class="result-details">
                    <p><strong>Jawaban Benar:</strong> <?php echo $nilai_data['jawaban_benar']; ?> dari <?php echo $nilai_data['total_soal']; ?> soal</p>
                    <p><strong>Waktu Selesai:</strong> <?php echo formatTanggal($nilai_data['completed_at']); ?></p>
                </div>
                
                <p style="color: #666; margin-bottom: 20px;">
                    Anda telah menyelesaikan semua pembelajaran dalam LMS Jaringan Komputer!
                </p>
                
                <a href="rekap_nilai_siswa.php" class="btn-submit">Lihat Rekap Nilai</a>
            </div>
        <?php elseif (!$quiz_completed): ?>

            <div class="locked-message">
                <div class="locked-icon">🔒</div>
                <h2 style="color: #333; margin-bottom: 20px;">Post-Test Terkunci</h2>
                <p style="color: #666; font-size: 18px;">
                    Anda harus menyelesaikan semua materi dan kuis terlebih dahulu<br>
                    sebelum dapat mengakses Post-Test.
                </p>
                <br>
                <a href="dashboard_siswa.php" class="btn-submit">Kembali ke Dashboard</a>
            </div>
        <?php else: ?>
            <div class="test-header">
                <h2>Post-Test Jaringan Komputer</h2>
                <p style="color: #666;">Evaluasi akhir pembelajaran. Waktu: 30 menit</p>
            </div>
            
            <div class="alert">
                <strong>✨ Selamat!</strong> Anda telah menyelesaikan semua materi. Ini adalah evaluasi akhir untuk mengukur pemahaman Anda.
            </div>
            
            <div class="timer-container" id="timerContainer">
                <div class="timer-label">Sisa Waktu</div>
                <div class="timer-display" id="timer">30:00</div>
            </div>
            
            <form method="POST" action="" id="testForm">
                <?php foreach ($soal_list as $index => $soal): ?>
                <div class="question-card">
                    <span class="question-number">Soal <?php echo $index + 1; ?></span>
                    <div class="question-text"><?php echo $soal['pertanyaan']; ?></div>
                    
                    <div class="options">
                        <div class="option">
                            <input type="radio" id="soal<?php echo $soal['soal_id']; ?>_a" 
                                   name="jawaban_<?php echo $soal['soal_id']; ?>" value="A" required>
                            <label for="soal<?php echo $soal['soal_id']; ?>_a">
                                A. <?php echo $soal['pilihan_a']; ?>
                            </label>
                        </div>
                        
                        <div class="option">
                            <input type="radio" id="soal<?php echo $soal['soal_id']; ?>_b" 
                                   name="jawaban_<?php echo $soal['soal_id']; ?>" value="B">
                            <label for="soal<?php echo $soal['soal_id']; ?>_b">
                                B. <?php echo $soal['pilihan_b']; ?>
                            </label>
                        </div>
                        
                        <div class="option">
                            <input type="radio" id="soal<?php echo $soal['soal_id']; ?>_c" 
                                   name="jawaban_<?php echo $soal['soal_id']; ?>" value="C">
                            <label for="soal<?php echo $soal['soal_id']; ?>_c">
                                C. <?php echo $soal['pilihan_c']; ?>
                            </label>
                        </div>
                        
                        <div class="option">
                            <input type="radio" id="soal<?php echo $soal['soal_id']; ?>_d" 
                                   name="jawaban_<?php echo $soal['soal_id']; ?>" value="D">
                            <label for="soal<?php echo $soal['soal_id']; ?>_d">
                                D. <?php echo $soal['pilihan_d']; ?>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="submit-section">
                    <button type="submit" name="submit_test" class="btn-submit">
                        ✓ Submit Post-Test
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        <?php if (!$already_completed && !$show_success): ?>
        let timeLeft = 30 * 60;
        const timerDisplay = document.getElementById('timer');
        const testForm = document.getElementById('testForm');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 60) {
                timerDisplay.className = 'timer-display danger';
            } else if (timeLeft <= 300) {
                timerDisplay.className = 'timer-display warning';
            }
            
            if (timeLeft <= 0) {
                alert('Waktu habis! Test akan di-submit otomatis.');
                testForm.submit();
            }
            
            timeLeft--;
        }
        
        setInterval(updateTimer, 1000);
        
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
        
        testForm.addEventListener('submit', function() {
            window.removeEventListener('beforeunload', function() {});
        });
        <?php endif; ?>
    </script>
</body>
</html>