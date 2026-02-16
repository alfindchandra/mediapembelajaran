<?php
require_once 'config.php';
requireSiswa();

$materi_animasi_query = "
    SELECT m.materi_id, m.judul, m.deskripsi 
    FROM materi m
    INNER JOIN animasi a ON m.materi_id = a.materi_id
    GROUP BY m.materi_id
    ORDER BY m.urutan
";
$materi_result = mysqli_query($conn, $materi_animasi_query);

$materi_list = [];
while ($row = mysqli_fetch_assoc($materi_result)) {
    $animasi_query = "SELECT * FROM animasi WHERE materi_id = {$row['materi_id']} ORDER BY urutan";
    $animasi_result = mysqli_query($conn, $animasi_query);
    $animations = [];
    while ($animasi_row = mysqli_fetch_assoc($animasi_result)) {
        $animations[] = $animasi_row;
    }
    $row['animations'] = $animations;
    $materi_list[] = $row;
}

function formatGoogleDriveEmbed($url) {
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)\/view/', $url, $matches)) {
        return "https://drive.google.com/file/d/{$matches[1]}/preview";
    }
    return $url;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animasi Pembelajaran - Modern LMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
            --dark: #1e1b4b;
            --light: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Modern Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);            backdrop-filter: blur(10px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .navbar h1 {
            font-size: 1.25rem;
            background: var(--light);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .btn-back {
            text-decoration: none;
            color: var(--light);
            background: var(--dark);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-back:hover { 
            background: var(--light);
            color: var(--primary); 
        }

        .container {
            max-width: 1100px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .section-title p { color: #64748b; }

        /* Grid & Cards */
        .animasi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .animasi-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .animasi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            opacity: 0; transition: 0.3s;
        }

        .animasi-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .animasi-card:hover::before { opacity: 1; }

        .card-icon {
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .animasi-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: #1e293b;
        }

        .animasi-card p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            flex-grow: 1;
        }

        .btn-play {
            background: #f1f5f9;
            color: var(--dark);
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-play:hover {
            background: var(--primary);
            color: white;
        }

        /* Improved Modal */
        .animasi-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            z-index: 2000;
            padding: 20px;
            place-items: center;
        }

        .animasi-modal.active { display: grid; }

        .modal-content {
            background: #000;
            width: 100%;
            max-width: 1000px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }

        .modal-header {
            background: #1e293b;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
        }

        .video-container iframe {
            position: absolute;
            top:0; left:0; width:100%; height:100%;
            border: none;
        }

        .close-modal {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .close-modal:hover { background: #ef4444; }

        .footer-nav {
            margin-top: 4rem;
            text-align: center;
        }

        .btn-home {
            background: var(--dark);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-home:hover { opacity: 0.9; transform: scale(1.05); }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>LMS Networking</h1>
        <a href="dashboard_siswa.php" class="btn-back">Dashboard</a>
    </nav>

    <div class="container">
        <div class="section-title">
            <h2>🎬 Animasi Pembelajaran</h2>
            <p>Visualisasikan konsep jaringan komputer dengan video interaktif.</p>
        </div>

        <div class="animasi-grid">
            <?php 
            $count = 0;
            foreach ($materi_list as $materi): 
                foreach ($materi['animations'] as $animasi):
                    $count++;
                    $embed_url = $animasi['html_content'];
                    if (filter_var($embed_url, FILTER_VALIDATE_URL)) {
                        if (strpos($embed_url, 'drive.google.com') !== false) {
                            $embed_url = formatGoogleDriveEmbed($embed_url);
                        }
                    }
            ?>
                <div class="animasi-card">
                    <div class="card-icon">📁</div>
                    <h3><?php echo htmlspecialchars($animasi['judul'] ?? ''); ?></h3>
                    <p><?php echo htmlspecialchars($animasi['deskripsi'] ?? ''); ?></p>
                    <button class="btn-play" onclick="toggleModal('modal-<?php echo $animasi['animasi_id']; ?>', true)">
                        <span>▶</span> Putar Animasi
                    </button>
                </div>

                <div id="modal-<?php echo $animasi['animasi_id']; ?>" class="animasi-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span style="font-weight:600;"><?php echo htmlspecialchars($animasi['judul']); ?></span>
                            <button class="close-modal" onclick="toggleModal('modal-<?php echo $animasi['animasi_id']; ?>', false)">✕</button>
                        </div>
                        <div class="video-container">
                            <?php if (filter_var($animasi['html_content'], FILTER_VALIDATE_URL)): ?>
                                <iframe id="iframe-<?php echo $animasi['animasi_id']; ?>" 
                                        data-src="<?php echo $embed_url; ?>" 
                                        src="" 
                                        allow="autoplay; fullscreen"></iframe>
                            <?php else: ?>
                                <div class="custom-content"><?php echo $animasi['html_content']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            endforeach; 
            ?>
        </div>

        <?php if ($count === 0): ?>
            <div style="text-align:center; padding: 4rem; background:white; border-radius:20px;">
                <p>Belum ada materi animasi tersedia.</p>
            </div>
        <?php endif; ?>

        <div class="footer-nav">
            <a href="dashboard_siswa.php" class="btn-home">Kembali ke Beranda</a>
        </div>
    </div>

    <script>
        function toggleModal(modalId, show) {
            const modal = document.getElementById(modalId);
            const iframe = modal.querySelector('iframe');
            
            if (show) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Stop scroll body
                // Load src hanya saat dibuka agar tidak berat & autoplay jalan
                if(iframe) {
                    const originalSrc = iframe.getAttribute('data-src');
                    // Tambahkan autoplay parameter jika YouTube
                    const finalSrc = (originalSrc.includes('youtube.com')) 
                        ? originalSrc + (originalSrc.includes('?') ? '&' : '?') + 'autoplay=1'
                        : originalSrc;
                    iframe.setAttribute('src', finalSrc);
                }
            } else {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
                // Penting: Hapus SRC agar video berhenti total saat modal tutup
                if(iframe) iframe.setAttribute('src', '');
            }
        }

        // Close on backdrop click
        window.onclick = function(event) {
            if (event.target.classList.contains('animasi-modal')) {
                toggleModal(event.target.id, false);
            }
        }
    </script>
</body>
</html>