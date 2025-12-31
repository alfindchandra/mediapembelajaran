<?php
require_once 'config.php';
requireLogin();

// Get book ID and document from URL
$buku_id = isset($_GET['buku_id']) ? (int)$_GET['buku_id'] : 0;
$doc = isset($_GET['doc']) ? clean_input($_GET['doc']) : '';

// --- LOGIKA TAMBAHAN UNTUK KUIS ---
$soal_list = [];
if (isset($_GET['quiz']) && $_GET['quiz'] === 'esai') {
    // Ambil soal digabung dengan jawaban user (jika sudah pernah jawab sebelumnya)
    // Asumsi: buku_id di URL sama dengan materi_id di tabel soal
    $query_soal = "SELECT s.*, e.jawaban as jawaban_user 
                   FROM kuis_soal s
                   LEFT JOIN kuis_esai e ON s.soal_id = e.soal_id AND e.user_id = ?
                   WHERE s.materi_id = ? 
                   ORDER BY s.urutan ASC";
    
    $stmt_soal = mysqli_prepare($conn, $query_soal);
    mysqli_stmt_bind_param($stmt_soal, "ii", $_SESSION['user_id'], $buku_id);
    mysqli_stmt_execute($stmt_soal);
    $result_soal = mysqli_stmt_get_result($stmt_soal);
    $soal_list = mysqli_fetch_all($result_soal, MYSQLI_ASSOC);
}

if ($buku_id == 0) {
    header("Location: dashboard_siswa.php");
    exit();
}

// Get book information
$query = "SELECT b.*, k.nama_kategori, u.full_name as penulis 
          FROM buku b 
          LEFT JOIN kategori_buku k ON b.kategori_id = k.kategori_id 
          LEFT JOIN users u ON b.created_by = u.user_id 
          WHERE b.buku_id = ? AND b.status = 'aktif'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $buku_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$buku = mysqli_fetch_assoc($result);

if (!$buku) {
    header("Location: dashboard_siswa.php");
    exit();
}

// Get all pages for this book
$query_pages = "SELECT * FROM halaman_buku WHERE buku_id = ? ORDER BY nomor_halaman ASC";
$stmt_pages = mysqli_prepare($conn, $query_pages);
mysqli_stmt_bind_param($stmt_pages, "i", $buku_id);
mysqli_stmt_execute($stmt_pages);
$result_pages = mysqli_stmt_get_result($stmt_pages);
$halaman_list = mysqli_fetch_all($result_pages, MYSQLI_ASSOC);

// Set default doc if empty
if (empty($doc) && !empty($halaman_list)) {
    $doc = $halaman_list[0]['file_pdf'];
}

// Find current page info
$current_page_info = null;
$current_page_number = 1;
foreach ($halaman_list as $index => $page) {
    if ($page['file_pdf'] == $doc) {
        $current_page_info = $page;
        $current_page_number = $index + 1;
        break;
    }
}

// Update progress
$query_progress = "INSERT INTO progress_baca (user_id, buku_id, halaman_terakhir, persentase_selesai, status_baca) 
                   VALUES (?, ?, ?, ?, 'sedang_baca')
                   ON DUPLICATE KEY UPDATE 
                   halaman_terakhir = ?, 
                   persentase_selesai = ?,
                   status_baca = 'sedang_baca',
                   waktu_terakhir_baca = CURRENT_TIMESTAMP";
$persentase = ($current_page_number / count($halaman_list)) * 100;
$stmt_progress = mysqli_prepare($conn, $query_progress);
mysqli_stmt_bind_param($stmt_progress, "iiddid", 
    $_SESSION['user_id'], $buku_id, $current_page_number, $persentase, 
    $current_page_number, $persentase);
mysqli_stmt_execute($stmt_progress);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($buku['judul']); ?> - Pembaca Buku</title>
    
    <script type="text/javascript" src="https://pustaka.ut.ac.id/reader/js/flowpaper.js"></script>
    <script type="text/javascript" src="https://pustaka.ut.ac.id/reader/js/flowpaper_handlers.js"></script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Roboto, sans-serif;
            background: #1a1a1a;
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
            background: linear-gradient(90deg, #002daa, #0073e6);
            padding: 10px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            z-index: 1001;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            font-size: 24px;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .nav-title {
            font-weight: 600;
            font-size: 16px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .logout-btn {
            background: #d32f2f;
            padding: 8px 12px;
            border-radius: 50%;
        }

        .logout-btn:hover {
            background: #b71c1c;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: #0c1c3a;
            padding-top: 70px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .sidebar-header .book-code {
            color: #aaa;
            font-size: 14px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
        }

        .sidebar-nav a {
            color: #e2eaff;
            padding: 12px 20px;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: linear-gradient(90deg, #0073e6, #005baa);
            transform: translateX(5px);
        }

        /* Main Content */
        .book-viewer {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: auto;
            background: #000;
        }

        .book-container {
            max-width: 1200px;
            width: 100%;
            background: #fff;
            box-shadow: 0 5px 30px rgba(0,0,0,0.5);
            border-radius: 8px;
            overflow: hidden;
            
        }

        .pdf-viewer {
            width: 50%;
            height: 80vh;
            border: none;
            
        }
        .pdf-container {
                display: flex;
                
                gap: 10px;
            }

        /* Progress Bar */
        .progress-container {
            background: #222;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #333;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073e6, #00c6ff);
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 14px;
            color: #aaa;
        }

        /* Navigation Controls */
        .nav-controls {
            background: #222;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-controls button {
            background: #0073e6;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .nav-controls button:hover:not(:disabled) {
            background: #005baa;
            transform: translateY(-2px);
        }

        .nav-controls button:disabled {
            background: #333;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .page-info {
            font-size: 16px;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 240px;
            }

            .nav-title {
                font-size: 14px;
            }

            .pdf-viewer {
                height: 70vh;
            }
            
        }
    </style>
</head>
<body>
    <div class="viewer-wrapper">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <h3>BUKU</h3>
               
            </div>
            <nav class="sidebar-nav">
    <?php foreach ($halaman_list as $index => $page): ?>
        <a href="?buku_id=<?php echo $buku_id; ?>&doc=<?php echo urlencode($page['file_pdf']); ?>" 
           class="<?php echo ($page['file_pdf'] == $doc) ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            <?php echo htmlspecialchars($page['judul_halaman']); ?>
        </a>
    <?php endforeach; ?>

    <!-- KUIS ESAI -->
    <a href="?buku_id=<?php echo $buku_id; ?>&quiz=esai"
       class="<?php echo isset($_GET['quiz']) ? 'active' : ''; ?>">
        <i class="bi bi-pencil-square"></i>
        Kuis Akhir (Esai)
    </a>
</nav>

        </aside>

        <!-- Header -->
        <header class="viewer-header">
            <div class="nav-left">
                <button id="toggleSidebar" class="menu-btn" title="Menu">☰</button>
                <div class="nav-title">
                    BUKU Sekolah - 
                    
                </div>
            </div>
            <div class="nav-right">
               
                <a href="dashboard_<?php echo $_SESSION['role']; ?>.php" class="nav-btn" style="text-decoration: none;">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                </a>
                
            </div>
        </header>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo round($persentase, 2); ?>%"></div>
            </div>
            <div class="progress-text">
               Buku
             
            </div>
        </div>

        <!-- Main Content -->
       <main class="book-viewer">
    <div class="book-container">

   <?php if (isset($_GET['quiz']) && $_GET['quiz'] === 'esai'): ?>

        <div style="padding: 30px; color: #000; overflow-y: auto; max-height: 80vh;">
            <div style="border-bottom: 2px solid #0073e6; margin-bottom: 20px; padding-bottom: 10px;">
                <h2>Kuis Akhir (Esai)</h2>
                <p class="text-muted">Jawablah pertanyaan berikut sesuai pemahaman Anda.</p>
            </div>

            <?php if (empty($soal_list)): ?>
                <div class="alert alert-warning" style="background: #fff3cd; padding: 15px; border-radius: 5px;">
                    Belum ada soal untuk materi ini.
                </div>
            <?php else: ?>

                <form method="post" action="simpan_kuis_esai.php">
                    <input type="hidden" name="buku_id" value="<?php echo $buku_id; ?>">

                    <?php foreach ($soal_list as $index => $soal): ?>
                        <div style="margin-bottom: 25px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                            <label style="display: block; margin-bottom: 10px; font-weight: bold; font-size: 1.1em;">
                                <?php echo ($index + 1) . ". " . htmlspecialchars($soal['pertanyaan']); ?>
                            </label>
                            
                            <textarea name="jawaban[<?php echo $soal['soal_id']; ?>]" 
                                      rows="4" 
                                      required 
                                      style="width:100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: sans-serif;"
                                      placeholder="Tulis jawaban Anda di sini..."><?php echo htmlspecialchars($soal['jawaban_user'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>

                    <div style="text-align: right;">
                        <button type="submit" name="submit_quiz" style="
                            background: linear-gradient(90deg, #0073e6, #005baa);
                            color: #fff;
                            padding: 12px 30px;
                            border: none;
                            border-radius: 6px;
                            font-size: 16px;
                            font-weight: bold;
                            cursor: pointer;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
                            <i class="bi bi-send"></i> Kirim Jawaban
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <!-- PDF VIEWER -->
        <?php 
        $file_path = "reader/ADBI421103/" . $doc;
        if (file_exists($file_path)): 
        ?>
        <div class="pdf-container">
            <iframe src="<?php echo htmlspecialchars($file_path); ?>" 
                    class="pdf-viewer"
                    title="<?php echo htmlspecialchars($current_page_info['judul_halaman']); ?>">
            </iframe>
            <iframe src="<?php echo htmlspecialchars($file_path); ?>" 
                    class="pdf-viewer"
                    title="<?php echo htmlspecialchars($current_page_info['judul_halaman']); ?>">
            </iframe>
        </div>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #333;">
                <h2>File tidak ditemukan</h2>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    </div>
</main>


        <!-- Navigation Controls -->
        <div class="nav-controls">
            <?php 
            $prev_index = array_search($current_page_info, $halaman_list) - 1;
            $next_index = array_search($current_page_info, $halaman_list) + 1;
            ?>
            <button 
                <?php if ($prev_index < 0) echo 'disabled'; ?>
                onclick="location.href='?buku_id=<?php echo $buku_id; ?>&doc=<?php echo urlencode($halaman_list[$prev_index]['file_pdf'] ?? ''); ?>'">
                <i class="bi bi-arrow-left"></i> Sebelumnya
            </button>
            
            <div class="page-info">
                Halaman <?php echo $current_page_number; ?> dari <?php echo count($halaman_list); ?>
            </div>
            
            <button 
                <?php if ($next_index >= count($halaman_list)) echo 'disabled'; ?>
                onclick="location.href='?buku_id=<?php echo $buku_id; ?>&doc=<?php echo urlencode($halaman_list[$next_index]['file_pdf'] ?? ''); ?>'">
                Selanjutnya <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        const sidebar = document.getElementById("sidebar");
        const toggleBtn = document.getElementById("toggleSidebar");
        
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener("click", (e) => {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove("active");
                }
            }
        });

        // Keyboard navigation
        document.addEventListener("keydown", (e) => {
            if (e.key === "ArrowLeft") {
                const prevBtn = document.querySelector('.nav-controls button:first-child');
                if (!prevBtn.disabled) prevBtn.click();
            } else if (e.key === "ArrowRight") {
                const nextBtn = document.querySelector('.nav-controls button:last-child');
                if (!nextBtn.disabled) nextBtn.click();
            }
        });

        // Responsive sidebar
        function setSidebarState() {
            if (window.innerWidth >= 768) {
                sidebar.classList.add("active");
            } else {
                sidebar.classList.remove("active");
            }
        }

        window.addEventListener("load", setSidebarState);
        window.addEventListener("resize", setSidebarState);
    </script>
    <script>
// E-Book content for each materi... (sudah benar di atas)

let currentPage = 1;
const totalPages = materiContent[<?php echo $materi_id; ?>].length;

document.getElementById('totalPages').textContent = totalPages;

function loadPage(page) {
    document.getElementById('pageContent').innerHTML = 
        materiContent[<?php echo $materi_id; ?>][page - 1];

    document.getElementById('currentPage').textContent = page;

    document.getElementById('prevPage').disabled = page === 1;
    document.getElementById('nextPage').disabled = page === totalPages;

    // Jika halaman terakhir → tampilkan kuis
    if (page === totalPages) {
        document.getElementById('quizSection').style.display = "block";
    } else {
        document.getElementById('quizSection').style.display = "none";
    }
}

// Tombol navigasi halaman
document.getElementById('prevPage').addEventListener('click', function() {
    if (currentPage > 1) {
        currentPage--;
        loadPage(currentPage);
    }
});

document.getElementById('nextPage').addEventListener('click', function() {
    if (currentPage < totalPages) {
        currentPage++;
        loadPage(currentPage);
    }
});

// Load first page on start
loadPage(currentPage);
</script>


</body>
</html>