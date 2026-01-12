<?php
require_once 'config.php';
requireLogin();

// Get book code from URL (bukan buku_id)
$kode_buku = isset($_GET['book']) ? clean_input($_GET['book']) : '';
$page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if (empty($kode_buku)) {
    header("Location: dashboard_siswa.php");
    exit();
}

// Get book information by kode_buku
$query = "SELECT b.*, k.nama_kategori, u.full_name as penulis 
          FROM buku b 
          LEFT JOIN kategori_buku k ON b.kategori_id = k.kategori_id 
          LEFT JOIN users u ON b.created_by = u.user_id 
          WHERE b.kode_buku = ? AND b.status = 'aktif'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $kode_buku);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$buku = mysqli_fetch_assoc($result);

if (!$buku) {
    header("Location: dashboard_siswa.php");
    exit();
}

$buku_id = $buku['buku_id'];

// Get all pages for this book
$query_pages = "SELECT * FROM halaman_buku WHERE buku_id = ? ORDER BY nomor_halaman ASC";
$stmt_pages = mysqli_prepare($conn, $query_pages);
mysqli_stmt_bind_param($stmt_pages, "i", $buku_id);
mysqli_stmt_execute($stmt_pages);
$result_pages = mysqli_stmt_get_result($stmt_pages);
$halaman_list = mysqli_fetch_all($result_pages, MYSQLI_ASSOC);

// Validasi nomor halaman
if ($page_number < 1) $page_number = 1;
if ($page_number > count($halaman_list)) $page_number = count($halaman_list);

// Get current page info
$current_page = $halaman_list[$page_number - 1] ?? null;

// Get ALL kuis soal untuk buku ini (bukan per halaman)
$soal_list = [];
$query_soal = "SELECT s.*, e.jawaban as jawaban_user 
               FROM kuis_soal s
               LEFT JOIN kuis_esai e ON s.soal_id = e.soal_id AND e.user_id = ?
               WHERE s.buku_id = ? 
               ORDER BY s.urutan ASC";

$stmt_soal = mysqli_prepare($conn, $query_soal);
mysqli_stmt_bind_param($stmt_soal, "ii", $_SESSION['user_id'], $buku_id);
mysqli_stmt_execute($stmt_soal);
$result_soal = mysqli_stmt_get_result($stmt_soal);
$soal_list = mysqli_fetch_all($result_soal, MYSQLI_ASSOC);

// Function to convert YouTube URL to embed URL
function getYouTubeEmbedUrl($url) {
    if (empty($url)) return '';
    
    // Jika sudah format embed, return as is
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    
    // Parse berbagai format YouTube URL
    $video_id = '';
    
    // Format: https://www.youtube.com/watch?v=VIDEO_ID
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Format: https://youtu.be/VIDEO_ID
    elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Format: https://www.youtube.com/embed/VIDEO_ID
    elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    
    if ($video_id) {
        return "https://www.youtube.com/embed/" . $video_id . "?rel=0&modestbranding=1";
    }
    
    // Jika bukan YouTube, return original URL (misal Vimeo, dll)
    return $url;
}

// Update progress
$persentase = ($page_number / count($halaman_list)) * 100;
$query_progress = "INSERT INTO progress_baca (user_id, buku_id, halaman_terakhir, persentase_selesai, status_baca) 
                   VALUES (?, ?, ?, ?, 'sedang_baca')
                   ON DUPLICATE KEY UPDATE 
                   halaman_terakhir = ?, 
                   persentase_selesai = ?,
                   status_baca = 'sedang_baca',
                   waktu_terakhir_baca = CURRENT_TIMESTAMP";
$stmt_progress = mysqli_prepare($conn, $query_progress);
mysqli_stmt_bind_param($stmt_progress, "iiddid", 
    $_SESSION['user_id'], $buku_id, $page_number, $persentase, 
    $page_number, $persentase);
mysqli_stmt_execute($stmt_progress);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($buku['judul']); ?> - Pembaca Buku</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            overflow: hidden;
        }

        .viewer-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Header */
        .viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            z-index: 1001;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-title {
            font-weight: 600;
            font-size: 18px;
            color: #fff;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Progress Bar */
        .progress-container {
            background: rgba(0, 0, 0, 0.2);
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .progress-bar {
            flex: 1;
            height: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.5s ease;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }

        .progress-text {
            font-size: 14px;
            color: #fff;
            font-weight: 500;
            min-width: 150px;
            text-align: right;
        }

        /* Main Content - Book Flipper */
        .book-viewer {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
            perspective: 2000px;
            min-height: 0; /* Important untuk flex */
        }

        .flipbook-container {
            position: relative;
            width: 90%;
            max-width: 1400px;
            height: 85%;
            max-height: 700px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #flipbook {
            width: 100%;
            height: 100%;
        }

        .page {
            width: 50%;
            height: 100%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .page-content {
            width: 100%;
            height: 100%;
            padding: 0;
            overflow: hidden;
            color: #333;
        }

        /* PDF Page */
        .pdf-page {
            padding: 0;
            background: #525659;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            height: 100%;
            width: 100%;
        }

        .pdf-page iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Video Page */
        .video-page {
            background: #000;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .video-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .video-header h3 {
            margin: 0;
            font-size: 20px;
        }

        .video-container {
            flex: 1;
            position: relative;
            background: #000;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .video-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #fff;
            text-align: center;
            padding: 40px;
        }

        .video-error i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Quiz Page */
        .quiz-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quiz-content {
            width: 100%;
            height: 100%;
            padding: 25px 30px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 15px;
        }

        .quiz-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .quiz-header p {
            opacity: 0.9;
            font-size: 13px;
        }

        .question-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .question-box label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .question-box textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: rgba(255,255,255,0.95);
            color: #333;
            font-family: inherit;
            font-size: 13px;
            resize: vertical;
            min-height: 60px;
            max-height: 100px;
            transition: all 0.3s ease;
        }

        .question-box textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .quiz-submit-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 20px auto 0;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .quiz-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }

        .no-quiz {
            text-align: center;
            padding: 60px 20px;
        }

        .no-quiz i {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 20px;
        }

        .no-quiz p {
            opacity: 0.7;
            font-size: 16px;
        }

        /* Navigation Controls */
        .nav-controls {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-controls button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .nav-controls button:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .nav-controls button:disabled {
            background: rgba(255,255,255,0.1);
            cursor: not-allowed;
            opacity: 0.5;
            transform: none;
        }

        .page-info {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-indicator {
            background: rgba(255,255,255,0.15);
            padding: 8px 20px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Loading Animation */
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
        }

        .loading i {
            font-size: 48px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* Custom Scrollbar for Quiz */
        .quiz-content::-webkit-scrollbar {
            width: 6px;
        }

        .quiz-content::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        .quiz-content::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        .quiz-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Responsive */
        @media (max-width: 1500px) {
            .flipbook-container {
                width: 85%;
                height: 80%;
            }
        }

        @media (max-width: 1200px) {
            .flipbook-container {
                width: 90%;
                height: 75%;
            }

            .quiz-content {
                padding: 20px 25px;
            }

            .question-box {
                padding: 12px;
                margin-bottom: 12px;
            }

            .quiz-header h2 {
                font-size: 20px;
            }

            .question-box label {
                font-size: 13px;
            }

            .question-box textarea {
                font-size: 12px;
                min-height: 50px;
                padding: 8px;
            }
        }

        @media (max-width: 768px) {
            .book-viewer {
                padding: 10px;
            }

            .flipbook-container {
                width: 95%;
                height: 70%;
            }
        }
    </style>
</head>
<body>
    <div class="viewer-wrapper">
        <!-- Header -->
        <header class="viewer-header">
            <div class="nav-left">
                <div class="nav-title">
                    <i class="bi bi-book"></i> <?php echo htmlspecialchars($buku['judul']); ?>
                </div>
            </div>
            <div class="nav-right">
                <a href="dashboard_<?php echo $_SESSION['role']; ?>.php" class="nav-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </header>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo round($persentase, 2); ?>%"></div>
            </div>
            <div class="progress-text">
                <i class="bi bi-bookmark-check"></i> Progress: <?php echo round($persentase); ?>%
            </div>
        </div>

        <!-- Main Content - Flipbook -->
        <main class="book-viewer">
            <div class="flipbook-container">
                <div id="flipbook">
                    <?php foreach ($halaman_list as $index => $halaman): ?>
                        <div class="page">
                            <?php if ($halaman['tipe_konten'] === 'pdf'): ?>
                                <!-- PDF Page -->
                                <div class="page-content pdf-page">
                                    <?php if (!empty($halaman['file_pdf']) && file_exists("reader/ADBI421103/" . $halaman['file_pdf'])): ?>
                                        <iframe src="reader/ADBI421103/<?php echo htmlspecialchars($halaman['file_pdf']); ?>#toolbar=0&navpanes=0&scrollbar=0"
                                                title="<?php echo htmlspecialchars($halaman['judul_halaman']); ?>">
                                        </iframe>
                                    <?php else: ?>
                                        <div class="loading">
                                            <i class="bi bi-file-earmark-x"></i>
                                            <p>File tidak ditemukan</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($halaman['tipe_konten'] === 'video'): ?>
                                <!-- Video Page -->
                                <div class="page-content video-page">
                                   
                                    <div class="video-container">
                                        <?php 
                                        $embed_url = getYouTubeEmbedUrl($halaman['video_url']);
                                        if (!empty($embed_url)): 
                                        ?>
                                            <iframe src="<?php echo htmlspecialchars($embed_url); ?>" 
                                                    title="<?php echo htmlspecialchars($halaman['judul_halaman']); ?>"
                                                    frameborder="0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                    allowfullscreen>
                                            </iframe>
                                        <?php else: ?>
                                            <div class="video-error">
                                                <i class="bi bi-camera-video-off"></i>
                                                <p>Video tidak tersedia</p>
                                                <small style="margin-top: 10px; opacity: 0.7;">URL video tidak valid</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php elseif ($halaman['tipe_konten'] === 'kuis'): ?>
                                <!-- Quiz Page - Menampilkan SEMUA soal untuk buku ini -->
                                <div class="page-content quiz-page">
                                    <div class="quiz-content">
                                        <div class="quiz-header">
                                            <h2><i class="bi bi-pencil-square"></i> Kuis Akhir</h2>
                                            <p>Jawablah pertanyaan berikut dengan baik dan benar</p>
                                        </div>

                                        <?php if (empty($soal_list)): ?>
                                            <div class="no-quiz">
                                                <i class="bi bi-inbox"></i>
                                                <p>Belum ada soal untuk kuis ini</p>
                                            </div>
                                        <?php else: ?>
                                            <form method="post" action="simpan_kuis_esai.php" id="quizForm">
                                                <input type="hidden" name="buku_id" value="<?php echo $buku_id; ?>">
                                                <input type="hidden" name="kode_buku" value="<?php echo htmlspecialchars($kode_buku); ?>">

                                                <?php foreach ($soal_list as $idx => $soal): ?>
                                                    <div class="question-box">
                                                        <label>
                                                            <?php echo ($idx + 1) . ". " . htmlspecialchars($soal['pertanyaan']); ?>
                                                        </label>
                                                        <textarea name="jawaban[<?php echo $soal['soal_id']; ?>]" 
                                                                  rows="3" 
                                                                  required 
                                                                  placeholder="Tulis jawaban Anda di sini..."><?php echo htmlspecialchars($soal['jawaban_user'] ?? ''); ?></textarea>
                                                    </div>
                                                <?php endforeach; ?>

                                                <button type="submit" name="submit_quiz" class="quiz-submit-btn">
                                                    <i class="bi bi-send"></i> Kirim Jawaban
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>

        <!-- Navigation Controls -->
        <div class="nav-controls">
            <button 
                id="prevBtn"
                <?php if ($page_number <= 2) echo 'disabled'; ?>
                onclick="location.href='?book=<?php echo urlencode($kode_buku); ?>&page=<?php echo ($page_number - 2); ?>'">
                <i class="bi bi-chevron-left"></i> Halaman Sebelumnya
            </button>
            
            <div class="page-info">
                <span>Halaman</span>
                <span class="page-indicator"><?php echo $page_number; ?> / <?php echo count($halaman_list); ?></span>
            </div>
            
            <button 
                id="nextBtn"
                <?php if ($page_number >= count($halaman_list)) echo 'disabled'; ?>
                onclick="location.href='?book=<?php echo urlencode($kode_buku); ?>&page=<?php echo ($page_number + 2); ?>'">
                Halaman Selanjutnya <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
    <script>
        $(document).ready(function() {
            // Get viewport dimensions
            function getFlipbookDimensions() {
                const viewportWidth = $(window).width();
                const viewportHeight = $(window).height();
                
                // Calculate available space (minus header, progress, controls)
                const availableHeight = viewportHeight - 200; // 200px for header, progress, controls
                const availableWidth = viewportWidth - 100; // 100px for padding
                
                // Aspect ratio 2:1 (double page spread)
                let width = Math.min(availableWidth, 1400);
                let height = Math.min(availableHeight, 700);
                
                // Maintain aspect ratio
                if (width / 2 > height) {
                    width = height * 2;
                } else {
                    height = width / 2;
                }
                
                return {
                    width: Math.floor(width),
                    height: Math.floor(height)
                };
            }

            const dimensions = getFlipbookDimensions();
            
            $("#flipbook").turn({
                width: dimensions.width,
                height: dimensions.height,
                autoCenter: true,
                display: 'double',
                acceleration: true,
                gradients: true,
                elevation: 50,
                page: <?php echo $page_number; ?>,
                when: {
                    turned: function(event, page) {
                        const newUrl = '?book=<?php echo urlencode($kode_buku); ?>&page=' + page;
                        window.history.pushState({page: page}, '', newUrl);
                    
                        updateNavButtons(page, <?php echo count($halaman_list); ?>);
                        
                        // Update progress
                        updateProgress(page, <?php echo count($halaman_list); ?>);
                    }
                }
            });

            // Keyboard navigation
            $(document).keydown(function(e) {
                if (e.keyCode == 37) { // Left arrow
                    $("#flipbook").turn("previous");
                } else if (e.keyCode == 39) { // Right arrow
                    $("#flipbook").turn("next");
                }
            });

            // Button navigation
            $("#prevBtn").click(function(e) {
                e.preventDefault();
                $("#flipbook").turn("previous");
            });

            $("#nextBtn").click(function(e) {
                e.preventDefault();
                $("#flipbook").turn("next");
            });

            // Responsive resize
            let resizeTimeout;
            $(window).resize(function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    const newDimensions = getFlipbookDimensions();
                    $("#flipbook").turn("size", newDimensions.width, newDimensions.height);
                }, 250);
            });
        });

        function updateNavButtons(currentPage, totalPages) {
            $("#prevBtn").prop('disabled', currentPage <= 1);
            $("#nextBtn").prop('disabled', currentPage >= totalPages);
            $(".page-indicator").text(currentPage + " / " + totalPages);
        }

        function updateProgress(currentPage, totalPages) {
            const percentage = (currentPage / totalPages) * 100;
            $(".progress-fill").css('width', percentage + '%');
            $(".progress-text").html('<i class="bi bi-bookmark-check"></i> Progress: ' + Math.round(percentage) + '%');
        }

        // Quiz form validation
        $("#quizForm").submit(function(e) {
            const textareas = $(this).find('textarea[required]');
            let allFilled = true;
            
            textareas.each(function() {
                if ($(this).val().trim() === '') {
                    allFilled = false;
                    $(this).css('border-color', '#f44336');
                } else {
                    $(this).css('border-color', 'rgba(255,255,255,0.3)');
                }
            });

            if (!allFilled) {
                e.preventDefault();
                alert('❌ Mohon isi semua jawaban sebelum mengirim!');
                return false;
            }

            return confirm('📝 Apakah Anda yakin ingin mengirim jawaban?\n\nPastikan semua jawaban sudah benar.');
        });
    </script>
</body>
</html>