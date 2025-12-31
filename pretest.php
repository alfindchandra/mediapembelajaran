<?php
require_once 'config.php';
requireSiswa();

$user_id = $_SESSION['user_id'];

// Check if already completed
$check_query = "SELECT * FROM nilai_pretest WHERE user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);
$already_completed = mysqli_num_rows($check_result) > 0;

// Process submission (TETAP SAMA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $jawaban_benar = 0;
    $total_soal = 0;
    
    // Get all pretest questions
    $soal_query = "SELECT * FROM test_soal WHERE tipe_test = 'pretest' ORDER BY urutan";
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
                             VALUES ($user_id, $soal_id, 'pretest', '$jawaban_user', $is_correct)");
    }
    
    $nilai = hitungNilai($jawaban_benar, $total_soal);
    
    // Save score
    mysqli_query($conn, "INSERT INTO nilai_pretest (user_id, total_soal, jawaban_benar, nilai) 
                         VALUES ($user_id, $total_soal, $jawaban_benar, $nilai)");
    
    // Unlock first materi
    mysqli_query($conn, "INSERT INTO progress_siswa (user_id, materi_id, status) VALUES ($user_id, 1, 'in-progress') 
                         ON DUPLICATE KEY UPDATE status = 'in-progress'");

    
    header("Location: pretest.php?success=1");
    exit();
}

// Get pretest questions
$soal_query = "SELECT * FROM test_soal WHERE tipe_test = 'pretest' ORDER BY urutan";
$soal_result = mysqli_query($conn, $soal_query);
$soal_list = [];
while ($row = mysqli_fetch_assoc($soal_result)) {
    $soal_list[] = $row;
}
$total_soal_count = count($soal_list); 

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
    <title>Pre-Test - LMS Jaringan Komputer</title>
    <style>
        /* CSS yang sudah ada */
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
            color: #667eea;
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
            text-align: left;
        }
        
        .test-header h2 {
            color: #333;
            margin-bottom: 10px;
            
        }
        .test-header p {
            background: #f8f9fa;
            border-left: 5px solid #667eea;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
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
            color: #667eea;
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
        
        /* CSS Tambahan untuk Petunjuk */
        .instruction-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }

        .instruction-card h2 {
            color: #764ba2;
            margin-bottom: 20px;
        }

        .instruction-list {
            list-style: none;
            text-align: left;
            margin: 0 auto 30px auto;
            padding: 0;
            max-width: 600px;
        }
        
        .instruction-list li {
            background: #f8f9fa;
            border-left: 5px solid #667eea;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
        }
        
        .btn-start {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }
        /* END CSS Tambahan */
        
        /* Menyembunyikan tampilan kuis dan timer sebelum dimulai */
        #testContent {
            display: none;
        }
        
        .quiz-slides {
            position: relative;
            overflow: hidden;
        }

        .question-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            width: 100%;
            display: none;
            transition: opacity 0.5s ease-in-out;
        }

        .question-card.active {
            display: block;
        }
        
        .question-number {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            background: #f8f9ff;
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
            color: #667eea;
            font-weight: 600;
        }
        
        .navigation-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding: 20px 0;
            align-items: center;
        }

        .btn-nav {
            background: #f0f0f0;
            color: #333;
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-nav:hover:not(:disabled) {
            background: #e0e0e0;
        }

        .btn-nav:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%); 
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
            color: #667eea;
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
        
        @media (max-width: 768px) {
            .timer-container {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 20px;
            }
            
            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📋 Pre-Test Jaringan Komputer</h1>
        <a href="dashboard_siswa.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <?php if ($already_completed || $show_success): ?>
            <div class="result-card">
                <div class="result-icon">🎉</div>
                <h2 style="color: #333; margin-bottom: 20px;">Pre-Test Selesai!</h2>
                <div class="result-score"><?php echo number_format($nilai_data['nilai'], 2); ?></div>
                <div class="result-text">Skor Anda</div>
                
                <div class="result-details">
                    <p><strong>Jawaban Benar:</strong> <?php echo $nilai_data['jawaban_benar']; ?> dari <?php echo $nilai_data['total_soal']; ?> soal</p>
                    <p><strong>Waktu Selesai:</strong> <?php echo isset($nilai_data['completed_at']) ? formatTanggal($nilai_data['completed_at']) : 'N/A'; ?></p>
                </div>
                
                <a href="dashboard_siswa.php" class="btn-submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">Lanjut ke Materi</a>
            </div>

        <?php else: ?>
            <div id="instructionCard" class="instruction-card">
                <div class="test-header" style="margin-bottom: 0; box-shadow: none;">
                    <h2>Petunjuk: </h2>
                    <p style="color: #666; margin-bottom: 20px;">Kerjakan <?php echo $total_soal_count; ?> Soal pilihan ganda. Waktu pengerjaan total adalah 30 menit. Waktu akan dihitung mundur setelah Anda menekan tombol "Mulai Pre-Test".</p>
                </div>
                
                
                <button id="startTestBtn" class="btn-start">Mulai Pre-Test Sekarang</button>
            </div>
            
            <div id="testContent">
               
                
                <div class="timer-container" id="timerContainer">
                    <div class="timer-label">Sisa Waktu</div>
                    <div class="timer-display" id="timer">30:00</div>
                </div>
                
                <form method="POST" action="" id="testForm">
                    <div class="quiz-slides" id="quizSlides">
                        <?php foreach ($soal_list as $index => $soal): ?>
                        <div class="question-card" data-index="<?php echo $index; ?>">
                            <span class="question-number">Soal <?php echo $index + 1; ?> / <?php echo $total_soal_count; ?></span>
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
                    </div>

                    <div class="navigation-section">
                        <button type="button" id="prevBtn" class="btn-nav">← Sebelumnya</button>
                        <button type="button" id="nextBtn" class="btn-nav">Selanjutnya →</button>
                        <button type="submit" name="submit_test" id="submitBtn" class="btn-submit" style="display: none;">
                            ✓ Submit Pre-Test
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        <?php if (!$already_completed && !$show_success): ?>
        
        const instructionCard = document.getElementById('instructionCard');
        const testContent = document.getElementById('testContent');
        const startTestBtn = document.getElementById('startTestBtn');
        const testForm = document.getElementById('testForm');
        
        let timerInterval; // Variabel untuk menyimpan interval timer

        // --- Timer Functionality ---
        let timeLeft = 30 * 60; // 30 minutes in seconds
        const timerDisplay = document.getElementById('timer');
        
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
                clearInterval(timerInterval);
                alert('Waktu habis! Tes akan di-submit otomatis.');
                testForm.submit();
                return;
            }
            
            timeLeft--;
        }

        function startTest() {
            // 1. Sembunyikan petunjuk
            instructionCard.style.display = 'none';
            // 2. Tampilkan konten soal
            testContent.style.display = 'block';
            // 3. Mulai Timer
            timerInterval = setInterval(updateTimer, 1000);
            // 4. Inisialisasi slide (seperti di kode sebelumnya)
            showSlide(currentSlide);

            // Aktifkan konfirmasi sebelum keluar halaman setelah test dimulai
            window.addEventListener('beforeunload', function (e) {
                e.preventDefault();
                e.returnValue = '';
            });
        }
        
        startTestBtn.addEventListener('click', startTest);


        // --- Slide Navigation Functionality (SAMA) ---
        let currentSlide = 0;
        const slides = document.querySelectorAll('.question-card');
        const totalSlides = slides.length;
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.classList.add('active');
                }
            });
            
            // Update Navigation Buttons
            prevBtn.disabled = index === 0;
            
            if (index === totalSlides - 1) {
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'block';
            } else {
                nextBtn.style.display = 'block';
                submitBtn.style.display = 'none';
            }
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                showSlide(currentSlide);
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                showSlide(currentSlide);
            }
        }

        nextBtn.addEventListener('click', nextSlide);
        prevBtn.addEventListener('click', prevSlide);
        
        // Remove confirmation when submitting
        testForm.addEventListener('submit', function() {
            clearInterval(timerInterval); // Hentikan timer saat submit
            window.removeEventListener('beforeunload', function() {});
        });
        
        // PENTING: Jangan panggil showSlide(currentSlide) di awal, karena kuis disembunyikan
        // Ini akan dipanggil di fungsi startTest()
        
        <?php endif; ?>
    </script>
</body>
</html>