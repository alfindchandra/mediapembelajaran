<?php
require_once 'config.php';
requireSiswa();

// Get all unique materi that have animations
// This query selects the distinct materi_id and its title/description from the 'materi' table 
// where there is at least one entry in the 'animasi' table for that materi.
$materi_animasi_query = "
    SELECT 
        m.materi_id, 
        m.judul, 
        m.deskripsi 
    FROM materi m
    INNER JOIN animasi a ON m.materi_id = a.materi_id
    GROUP BY m.materi_id
    ORDER BY m.urutan
";
$materi_result = mysqli_query($conn, $materi_animasi_query);

$materi_list = [];
while ($row = mysqli_fetch_assoc($materi_result)) {
    // For each materi, get its animations
    $animasi_query = "SELECT * FROM animasi WHERE materi_id = {$row['materi_id']} ORDER BY urutan";
    $animasi_result = mysqli_query($conn, $animasi_query);
    
    $animations = [];
    while ($animasi_row = mysqli_fetch_assoc($animasi_result)) {
        $animations[] = $animasi_row;
    }
    
    $row['animations'] = $animations;
    $materi_list[] = $row;
}

// ===============================================
// Fungsi untuk memformat Link Google Drive
// ===============================================
function formatGoogleDriveEmbed($url) {
    // Cek apakah ini link share biasa: https://drive.google.com/file/d/FILE_ID/view?usp=sharing
    if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)\/view/', $url, $matches)) {
        $file_id = $matches[1];
        return "https://drive.google.com/file/d/{$file_id}/preview";
    }
    // Cek apakah ini link preview/embed yang sudah benar
    if (strpos($url, 'drive.google.com/file/d/') !== false && strpos($url, '/preview') !== false) {
        return $url;
    }
    // Jika ini adalah link edit atau link dokumen lain, kembalikan URL asli (mungkin tidak bisa di-embed)
    return $url;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animasi Pembelajaran - LMS Jaringan Komputer</title>
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
        
        /* ==================== NAV BAR STYLE (DARI PRETEST.PHP) ==================== */
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
        /* =========================================================================== */

        .container {
            max-width: 1200px; /* Diperlebar agar 3 kolom terlihat bagus */
            margin: 40px auto;
            padding: 0 20px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }
        
        /* Grid 3 Kolom Responsif */
        .animasi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); 
            gap: 25px;
            margin-bottom: 50px;
        }

        @media (max-width: 992px) {
            .animasi-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 600px) {
            .animasi-grid {
                grid-template-columns: 1fr;
            }
        }

        /* CARD STYLE BARU */
        .animasi-card {
            background: white;
            padding: 30px; /* Padding lebih besar */
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1); /* Shadow dengan hint warna tema */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s, box-shadow 0.3s;
            min-height: 200px; 
            border: 1px solid #e0e0e0;
        }
        
        .animasi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .card-content h3 {
            color: #764ba2; /* Warna ungu lebih gelap dari gradient */
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 700;
        }
        
        .card-content p {
            color: #666;
            font-size: 15px;
            margin-bottom: 20px;
            height: 45px; 
            overflow: hidden;
        }
        
        .btn-play {
            display: block;
            width: 100%;
            padding: 12px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Warna tema utama */
            color: white;
            text-align: center;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-play:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.5);
        }

        /* MODAL STYLES */
        .animasi-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95); 
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .animasi-modal.active {
            display: flex;
        }
        
        .animasi-modal-content {
            background: #111; /* Background gelap untuk tampilan video */
            border-radius: 15px;
            width: 95%; 
            max-width: 1200px;
            max-height: 95vh;
            overflow: hidden; /* Hilangkan scroll modal */
            position: relative;
        }
        
        .animasi-modal-header {
            background: #333; /* Header gelap */
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; 
            top: 0;
            z-index: 10;
        }
        
        .animasi-modal-body {
            /* Kontainer untuk rasio aspek video */
            position: relative;
            padding-bottom: 56.25%; 
            height: 0;
            overflow: hidden;
            background: black;
        }

        .video-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .video-wrapper iframe,
        .video-wrapper video {
            width: 100%;
            height: 100%;
            border: 0;
        }
        
        .btn-close {
            background: transparent;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }
        
        .btn-close:hover {
            background: rgba(255,255,255,0.1);
        }
        
        /* FOOTER BUTTON */
        .back-button-container {
            text-align: center; /* Pindahkan ke tengah */
            padding: 20px 0;
        }

        .btn-footer-back {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn-footer-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🎬 Animasi Pembelajaran Jaringan Komputer</h1>
        <a href="dashboard_siswa.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div class="animasi-grid">
            <?php 
            $card_count = 0;
            foreach ($materi_list as $materi): 
                foreach ($materi['animations'] as $animasi):
                    $card_count++;
            ?>
                <div class="animasi-card">
                    <div class="card-content">
                        <h3><?php echo $animasi['judul']; ?></h3>
                        <p><?php echo $animasi['deskripsi']; ?></p>
                    </div>
                    <button class="btn-play" onclick="openAnimasi(<?php echo $animasi['animasi_id']; ?>)">
                        ▶ Putar
                    </button>
                </div>

                <div id="modal-<?php echo $animasi['animasi_id']; ?>" class="animasi-modal">
                    <div class="animasi-modal-content">
                        <div class="animasi-modal-header">
                            <h3><?php echo $animasi['judul']; ?></h3>
                            <button class="btn-close" onclick="closeAnimasi(<?php echo $animasi['animasi_id']; ?>)">✕</button>
                        </div>
                        
                        <div class="animasi-modal-body" data-animation-id="<?php echo $animasi['animasi_id']; ?>">
                            <div class="video-wrapper">
                            <?php 
                            $content = $animasi['html_content'];
                            $is_url = filter_var($content, FILTER_VALIDATE_URL);
                            
                            if ($is_url) {
                                
                                // Cek dan format link Google Drive
                                if (strpos($content, 'drive.google.com') !== false) {
                                    $embed_url = formatGoogleDriveEmbed($content);
                                    // Google Drive embed
                                    echo '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>';
                                } 
                                // Cek dan format link YouTube
                                elseif (strpos($content, 'youtube.com') !== false || strpos($content, 'youtu.be') !== false) {
                                    // Sederhana, gunakan URL asli jika sudah berupa embed.
                                    echo '<iframe src="' . $content . '" frameborder="0" allowfullscreen></iframe>';
                                }
                                else {
                                    // URL lain (misalnya MP4 host sendiri)
                                    echo '<iframe src="' . $content . '" frameborder="0" allowfullscreen></iframe>';
                                }
                                
                            } else {
                                // Jika ini adalah kode HTML/Embed (misalnya kode embed dari YouTube/Iframe interaktif)
                                echo $content;
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            endforeach; 
            
            if ($card_count === 0):
            ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #666;">Belum ada Animasi Pembelajaran yang tersedia.</h3>
                <p style="color: #999;">Silakan kembali ke Dashboard untuk melanjutkan.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="back-button-container">
            <a href="dashboard_siswa.php" class="btn-footer-back">
                kembali ke beranda
            </a>
        </div>
    </div>
    
    <script>
        // Variabel untuk melacak elemen iframe/video yang dibuka
        let currentOpenIframe = null;

        function openAnimasi(id) {
            const modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.classList.add('active');
                
                // Cari iframe/video di dalam modal body
                const modalBody = modal.querySelector('.animasi-modal-body');
                const iframe = modalBody.querySelector('iframe, video');
                
                if (iframe) {
                    currentOpenIframe = iframe;
                    
                    let src = iframe.getAttribute('src');
                    // Logic untuk memulai video
                    if (src) {
                        // Tambahkan parameter autoplay HANYA untuk YouTube (karena Drive sudah otomatis)
                        if ((src.includes('youtube.com') || src.includes('youtu.be')) && !src.includes('autoplay=1')) {
                            src += (src.includes('?') ? '&' : '?') + 'autoplay=1';
                            iframe.setAttribute('src', src);
                        } 
                    }
                }
            }
        }
        
        function closeAnimasi(id) {
            const modal = document.getElementById('modal-' + id);
            if (modal) {
                modal.classList.remove('active');
                
                // Hentikan pemutaran video saat modal ditutup
                if (currentOpenIframe) {
                    let src = currentOpenIframe.getAttribute('src');
                    
                    // Hapus parameter autoplay untuk menghentikan video YouTube
                    if (src) {
                        const cleanSrc = src.replace('?autoplay=1', '').replace('&autoplay=1', '');
                        if (src !== cleanSrc) {
                            currentOpenIframe.setAttribute('src', cleanSrc);
                        }
                    }
                    currentOpenIframe = null;
                }
            }
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.animasi-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const id = this.id.replace('modal-', '');
                    closeAnimasi(id);
                }
            });
        });
    </script>
</body>
</html>