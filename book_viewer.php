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

$total_pages = count($halaman_list);

// Pastikan page_number ganjil (untuk tampilan 2 halaman)
if ($page_number % 2 == 0) {
    $page_number = $page_number - 1;
}

// Validasi nomor halaman
if ($page_number < 1) $page_number = 1;
if ($page_number > $total_pages) $page_number = $total_pages;

// Ambil hanya 2 halaman yang akan ditampilkan (current dan next)
$current_page_1 = isset($halaman_list[$page_number - 1]) ? $halaman_list[$page_number - 1] : null;
$current_page_2 = isset($halaman_list[$page_number]) ? $halaman_list[$page_number] : null;

// Get ALL kuis soal untuk buku ini (hanya jika salah satu halaman adalah kuis)
$soal_list = [];
$is_quiz_page = ($current_page_1 && $current_page_1['tipe_konten'] === 'kuis') || 
                ($current_page_2 && $current_page_2['tipe_konten'] === 'kuis');

if ($is_quiz_page) {
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
}

function getEmbedVideoUrl($url) {
    if (empty($url)) return '';

    // YOUTUBE
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        if (strpos($url, 'youtube.com/embed/') !== false) {
            return $url . '?rel=0&modestbranding=1';
        }
        
        if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $m)) {
            return "https://www.youtube.com/embed/" . $m[1] . "?rel=0&modestbranding=1";
        }

        if (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $m)) {
            return "https://www.youtube.com/embed/" . $m[1] . "?rel=0&modestbranding=1";
        }
    }

    // GOOGLE DRIVE
    if (strpos($url, 'drive.google.com') !== false) {
        if (preg_match('/\/file\/d\/([^\/]+)/', $url, $m)) {
            return "https://drive.google.com/file/d/" . $m[1] . "/preview";
        }

        if (preg_match('/id=([^&]+)/', $url, $m)) {
            return "https://drive.google.com/file/d/" . $m[1] . "/preview";
        }
    }

    return $url;
}

// Update progress
$persentase = ($page_number / $total_pages) * 100;
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

// Function to render page content
function renderPageContent($halaman, $soal_list, $buku_id, $kode_buku) {
    if (!$halaman) {
        return '<div class="page-content empty-page"><div class="empty-message"><i class="bi bi-file-earmark"></i><p>Halaman kosong</p></div></div>';
    }
    
    if ($halaman['tipe_konten'] === 'pdf') {
        if (!empty($halaman['file_pdf']) && file_exists("reader/buku/" . $halaman['file_pdf'])) {
            return '<div class="page-content pdf-page">
                        <iframe src="reader/buku/' . htmlspecialchars($halaman['file_pdf']) . '#toolbar=0&navpanes=0&scrollbar=0" 
                                title="' . htmlspecialchars($halaman['judul_halaman']) . '" 
                                loading="lazy">
                        </iframe>
                    </div>';
        } else {
            return '<div class="page-content pdf-page">
                        <div class="loading">
                            <i class="bi bi-file-earmark-x"></i>
                            <p>File tidak ditemukan</p>
                        </div>
                    </div>';
        }
    }
    
    if ($halaman['tipe_konten'] === 'video') {
        $embed_url = getEmbedVideoUrl($halaman['video_url']);
        if (!empty($embed_url)) {
            return '<div class="page-content video-page">
                        <div class="video-container">
                            <iframe src="' . htmlspecialchars($embed_url) . '" 
                                    title="' . htmlspecialchars($halaman['judul_halaman']) . '"
                                    frameborder="0"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    </div>';
        } else {
            return '<div class="page-content video-page">
                        <div class="video-container">
                            <div class="video-error">
                                <i class="bi bi-camera-video-off"></i>
                                <p>Video tidak tersedia</p>
                                <small style="margin-top: 10px; opacity: 0.7;">URL video tidak valid</small>
                            </div>
                        </div>
                    </div>';
        }
    }
    
    if ($halaman['tipe_konten'] === 'kuis') {
        $quiz_html = '<div class="page-content quiz-page">
                        <div class="quiz-content">
                            <div class="quiz-header">
                                <h2><i class="bi bi-pencil-square"></i> Kuis Akhir</h2>
                                <p>Jawablah pertanyaan berikut dengan baik dan benar</p>
                            </div>';
        
        if (empty($soal_list)) {
            $quiz_html .= '<div class="no-quiz">
                                <i class="bi bi-inbox"></i>
                                <p>Belum ada soal untuk kuis ini</p>
                            </div>';
        } else {
            $quiz_html .= '<form method="post" action="simpan_kuis_esai.php" id="quizForm">
                                <input type="hidden" name="buku_id" value="' . $buku_id . '">
                                <input type="hidden" name="kode_buku" value="' . htmlspecialchars($kode_buku) . '">';
            
            foreach ($soal_list as $idx => $soal) {
                $jawaban = htmlspecialchars($soal['jawaban_user'] ?? '');
                $quiz_html .= '<div class="question-box">
                                    <label>' . ($idx + 1) . ". " . htmlspecialchars($soal['pertanyaan']) . '</label>
                                    <textarea name="jawaban[' . $soal['soal_id'] . ']" 
                                              rows="3" 
                                              required 
                                              placeholder="Tulis jawaban Anda di sini...">' . $jawaban . '</textarea>
                                </div>';
            }
            
            $quiz_html .= '<button type="submit" name="submit_quiz" class="quiz-submit-btn">
                                <i class="bi bi-send"></i> Kirim Jawaban
                            </button>
                        </form>';
        }
        
        $quiz_html .= '</div></div>';
        return $quiz_html;
    }
    
    return '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($buku['judul']); ?> - Pembaca Buku</title>
    
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
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

        /* Main Content - Book Display */
        .book-viewer {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
            min-height: 0;
        }

        .book-container {
            position: relative;
            width: 90%;
            max-width: 1400px;
            height: 85%;
            max-height: 700px;
            display: flex;
            gap: 10px;
            perspective: 2000px;
        }

        .page {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            overflow: hidden;
            border-radius: 5px;
            transition: transform 0.3s ease;
        }

        .page:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0,0,0,0.4);
        }

        .page-content {
            width: 100%;
            height: 100%;
            padding: 0;
            overflow: hidden;
            color: #333;
        }

        /* Empty Page */
        .empty-page {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }

        .empty-message {
            text-align: center;
            color: #999;
        }

        .empty-message i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 10px;
        }

        .empty-message p {
            font-size: 14px;
            opacity: 0.7;
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

        /* Page Transition */
        .page-transition {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .book-container {
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

            .book-container {
                width: 95%;
                height: 70%;
                flex-direction: column;
            }

            .nav-controls {
                flex-direction: column;
                gap: 15px;
            }

            .nav-controls button {
                width: 100%;
                justify-content: center;
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

     

        <!-- Main Content - Book Pages -->
        <main class="book-viewer">
            <div class="book-container">
                <!-- Left Page -->
                <div class="page page-transition">
                    <?php echo renderPageContent($current_page_1, $soal_list, $buku_id, $kode_buku); ?>
                </div>

                <!-- Right Page -->
                <div class="page page-transition">
                    <?php echo renderPageContent($current_page_2, $soal_list, $buku_id, $kode_buku); ?>
                </div>
            </div>
        </main>

        <!-- Navigation Controls -->
        <div class="nav-controls">
            <button 
                id="prevBtn"
                <?php if ($page_number <= 1) echo 'disabled'; ?>
                onclick="navigatePage(<?php echo max(1, $page_number - 2); ?>)">
                <i class="bi bi-chevron-left"></i> Halaman Sebelumnya
            </button>
            
            <div class="page-info">
                <span>Halaman</span>
                <span class="page-indicator"><?php echo $page_number; ?>-<?php echo min($page_number + 1, $total_pages); ?> / <?php echo $total_pages; ?></span>
            </div>
            
            <button 
                id="nextBtn"
                <?php if ($page_number + 1 >= $total_pages) echo 'disabled'; ?>
                onclick="navigatePage(<?php echo min($total_pages, $page_number + 2); ?>)">
                Halaman Selanjutnya <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>

    <script>
        // Navigation function
        function navigatePage(pageNum) {
            window.location.href = '?book=<?php echo urlencode($kode_buku); ?>&page=' + pageNum;
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.keyCode == 37 && !document.getElementById('prevBtn').disabled) { // Left arrow
                navigatePage(<?php echo max(1, $page_number - 2); ?>);
            } else if (e.keyCode == 39 && !document.getElementById('nextBtn').disabled) { // Right arrow
                navigatePage(<?php echo min($total_pages, $page_number + 2); ?>);
            }
        });

        // Quiz form validation
        const quizForm = document.getElementById('quizForm');
        if (quizForm) {
            quizForm.addEventListener('submit', function(e) {
                const textareas = this.querySelectorAll('textarea[required]');
                let allFilled = true;
                
                textareas.forEach(function(textarea) {
                    if (textarea.value.trim() === '') {
                        allFilled = false;
                        textarea.style.borderColor = '#f44336';
                    } else {
                        textarea.style.borderColor = 'rgba(255,255,255,0.3)';
                    }
                });

                if (!allFilled) {
                    e.preventDefault();
                    alert('❌ Mohon isi semua jawaban sebelum mengirim!');
                    return false;
                }

                return confirm('📝 Apakah Anda yakin ingin mengirim jawaban?\n\nPastikan semua jawaban sudah benar.');
            });
        }

        // Preload next pages for smoother navigation
        const currentPage = <?php echo $page_number; ?>;
        const totalPages = <?php echo $total_pages; ?>;
        
        if (currentPage + 2 < totalPages) {
            const preloadLink = document.createElement('link');
            preloadLink.rel = 'prefetch';
            preloadLink.href = '?book=<?php echo urlencode($kode_buku); ?>&page=' + (currentPage + 2);
            document.head.appendChild(preloadLink);
        }
    </script>
</body>
</html>