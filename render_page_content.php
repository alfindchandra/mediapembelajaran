<?php
// File untuk render konten halaman
// Dipanggil dari book_viewer_optimized.php

function renderPageContent($page, $soal_list, $buku_id, $kode_buku) {
    if ($page['tipe_konten'] === 'pdf'): ?>
        <!-- PDF Page -->
        <div class="page-content pdf-page">
            <?php if (!empty($page['file_pdf']) && file_exists("reader/buku/" . $page['file_pdf'])): ?>
                <iframe src="reader/buku/<?php echo htmlspecialchars($page['file_pdf']); ?>#toolbar=0&navpanes=0&scrollbar=0"
                        title="<?php echo htmlspecialchars($page['judul_halaman']); ?>"
                        loading="lazy">
                </iframe>
            <?php else: ?>
                <div class="loading-spinner">
                    <i class="bi bi-file-earmark-x"></i>
                    <p>File tidak ditemukan</p>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($page['tipe_konten'] === 'video'): ?>
        <!-- Video Page -->
        <div class="page-content video-page">
            <div class="video-container">
                <?php 
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
                
                $embed_url = getEmbedVideoUrl($page['video_url']);
                if (!empty($embed_url)): 
                ?>
                    <iframe src="<?php echo htmlspecialchars($embed_url); ?>" 
                            title="<?php echo htmlspecialchars($page['judul_halaman']); ?>"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            loading="lazy">
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

    <?php elseif ($page['tipe_konten'] === 'kuis'): ?>
        <!-- Quiz Page -->
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
    <?php endif;
}
?>