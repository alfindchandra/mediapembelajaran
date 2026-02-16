<?php
require_once 'config.php';
requireLogin();

$kode_buku   = isset($_GET['book']) ? clean_input($_GET['book']) : '';
$page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if (empty($kode_buku)) { header("Location: dashboard_siswa.php"); exit(); }

$query = "SELECT b.*, k.nama_kategori, u.full_name as penulis 
          FROM buku b 
          LEFT JOIN kategori_buku k ON b.kategori_id = k.kategori_id 
          LEFT JOIN users u ON b.created_by = u.user_id 
          WHERE b.kode_buku = ? AND b.status = 'aktif'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $kode_buku);
mysqli_stmt_execute($stmt);
$buku = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$buku) { header("Location: dashboard_siswa.php"); exit(); }

$buku_id     = $buku['buku_id'];
$query_pages = "SELECT * FROM halaman_buku WHERE buku_id = ? ORDER BY nomor_halaman ASC";
$stmt_pages  = mysqli_prepare($conn, $query_pages);
mysqli_stmt_bind_param($stmt_pages, "i", $buku_id);
mysqli_stmt_execute($stmt_pages);
$halaman_list = mysqli_fetch_all(mysqli_stmt_get_result($stmt_pages), MYSQLI_ASSOC);
$total_pages  = count($halaman_list);

if ($page_number % 2 == 0) $page_number--;
if ($page_number < 1) $page_number = 1;
if ($page_number > $total_pages) $page_number = $total_pages;

$current_page_1 = $halaman_list[$page_number - 1] ?? null;
$current_page_2 = $halaman_list[$page_number]     ?? null;

$soal_list    = [];
$is_quiz_page = ($current_page_1 && $current_page_1['tipe_konten'] === 'kuis') ||
                ($current_page_2 && $current_page_2['tipe_konten'] === 'kuis');
if ($is_quiz_page) {
    $stmt_soal = mysqli_prepare($conn,
        "SELECT s.*, e.jawaban as jawaban_user FROM kuis_soal s
         LEFT JOIN kuis_esai e ON s.soal_id = e.soal_id AND e.user_id = ?
         WHERE s.buku_id = ? ORDER BY s.urutan ASC");
    mysqli_stmt_bind_param($stmt_soal, "ii", $_SESSION['user_id'], $buku_id);
    mysqli_stmt_execute($stmt_soal);
    $soal_list = mysqli_fetch_all(mysqli_stmt_get_result($stmt_soal), MYSQLI_ASSOC);
}

function getEmbedVideoUrl($url) {
    if (empty($url)) return '';
    if (strpos($url,'youtube.com')!==false||strpos($url,'youtu.be')!==false) {
        if (strpos($url,'youtube.com/embed/')!==false) return $url.'?rel=0&modestbranding=1';
        if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/',$url,$m)) return "https://www.youtube.com/embed/".$m[1]."?rel=0&modestbranding=1";
        if (preg_match('/youtu\.be\/([^\&\?\/]+)/',$url,$m))              return "https://www.youtube.com/embed/".$m[1]."?rel=0&modestbranding=1";
    }
    if (strpos($url,'drive.google.com')!==false) {
        if (preg_match('/\/file\/d\/([^\/]+)/',$url,$m)) return "https://drive.google.com/file/d/".$m[1]."/preview";
        if (preg_match('/id=([^&]+)/',$url,$m))          return "https://drive.google.com/file/d/".$m[1]."/preview";
    }
    return $url;
}

$persentase = ($page_number / $total_pages) * 100;
$stmt_prog  = mysqli_prepare($conn,
    "INSERT INTO progress_baca (user_id,buku_id,halaman_terakhir,persentase_selesai,status_baca)
     VALUES(?,?,?,?,'sedang_baca')
     ON DUPLICATE KEY UPDATE halaman_terakhir=?,persentase_selesai=?,status_baca='sedang_baca',waktu_terakhir_baca=CURRENT_TIMESTAMP");
mysqli_stmt_bind_param($stmt_prog,"iiddid",$_SESSION['user_id'],$buku_id,$page_number,$persentase,$page_number,$persentase);
mysqli_stmt_execute($stmt_prog);

function renderPageContent($halaman, $soal_list, $buku_id, $kode_buku) {
    if (!$halaman) return '<div class="pc empty-pg"><div class="empty-msg"><i class="bi bi-file-earmark"></i><p>Halaman Kosong</p></div></div>';

    if ($halaman['tipe_konten'] === 'pdf') {
        if (!empty($halaman['file_pdf']) && file_exists("reader/buku/".$halaman['file_pdf']))
            return '<div class="pc pdf-pg"><iframe src="reader/buku/'.htmlspecialchars($halaman['file_pdf']).'#toolbar=0&navpanes=0&scrollbar=0" loading="lazy"></iframe></div>';
        return '<div class="pc pdf-pg"><div class="err-st"><i class="bi bi-file-earmark-x"></i><p>File tidak ditemukan</p></div></div>';
    }

    if ($halaman['tipe_konten'] === 'video') {
        $eu = getEmbedVideoUrl($halaman['video_url']);
        if (!empty($eu))
            return '<div class="pc vid-pg"><div class="vid-box"><iframe src="'.htmlspecialchars($eu).'" frameborder="0" loading="lazy" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture" allowfullscreen></iframe></div></div>';
        return '<div class="pc vid-pg"><div class="err-st"><i class="bi bi-camera-video-off"></i><p>Video tidak tersedia</p></div></div>';
    }

    if ($halaman['tipe_konten'] === 'kuis') {
        $h  = '<div class="pc quiz-pg"><div class="quiz-scroll">
                 <div class="quiz-hd">
                   <div class="q-ico"><i class="bi bi-pencil-square"></i></div>
                   <h2>Kuis Akhir</h2><p>Jawablah pertanyaan berikut dengan benar</p>
                 </div>';
        if (empty($soal_list)) {
            $h .= '<div class="no-quiz"><i class="bi bi-inbox"></i><p>Belum ada soal</p></div>';
        } else {
            $h .= '<form method="post" action="simpan_kuis_esai.php" id="quizForm">
                   <input type="hidden" name="buku_id" value="'.$buku_id.'">
                   <input type="hidden" name="kode_buku" value="'.htmlspecialchars($kode_buku).'">';
            foreach ($soal_list as $i => $s) {
                $j  = htmlspecialchars($s['jawaban_user']??'');
                $h .= '<div class="q-box">
                         <label><span class="qn">'.($i+1).'</span>'.htmlspecialchars($s['pertanyaan']).'</label>
                         <textarea name="jawaban['.$s['soal_id'].']" rows="3" required placeholder="Tulis jawaban Anda...">'.$j.'</textarea>
                       </div>';
            }
            $h .= '<button type="submit" name="submit_quiz" class="q-submit"><i class="bi bi-send-fill"></i> Kirim Jawaban</button></form>';
        }
        $h .= '</div></div>';
        return $h;
    }
    return '';
}

$prev_page = max(1, $page_number - 2);
$next_page = min($total_pages, $page_number + 2);
$has_prev  = $page_number > 1;
$has_next  = ($page_number + 1) < $total_pages;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($buku['judul']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
/* ═══════════════════════════════════════
   RESET & VARIABLES
═══════════════════════════════════════ */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

:root {
    --bg:      #0d1117;
    --gold:    #d4a843;
    --gold2:   #b87830;
    --text:    #e8dcc8;
    --muted:   #6a6458;
    --page-r:  #fdf8f0;   /* right page warm white */
    --page-l:  #f6f0e4;   /* left page slightly warmer */
    --spine:   #1e0e02;
    --hdr:     50px;
    --ftr:     52px;
}

html, body {
    width:100%; height:100%;
    overflow:hidden;
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    user-select:none;
}

/* atmospheric glow */
body::before {
    content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
    background:
        radial-gradient(ellipse 65% 45% at 12% 8%,  rgba(212,168,67,.08) 0%, transparent 55%),
        radial-gradient(ellipse 50% 40% at 88% 92%, rgba(184,120,48,.07) 0%, transparent 55%);
}

/* ═══════════════════════════════════════
   LAYOUT
═══════════════════════════════════════ */
.shell {
    position:relative; z-index:1;
    display:flex; flex-direction:column;
    width:100vw; height:100vh;
    overflow:hidden;
}

/* ═══════════════════════════════════════
   HEADER  (50 px)
═══════════════════════════════════════ */
.hdr {
    height:var(--hdr); min-height:var(--hdr); flex-shrink:0;
    display:flex; align-items:center; justify-content:space-between;
    padding:0 16px;
    background:rgba(10,13,20,.94);
    border-bottom:1px solid rgba(212,168,67,.2);
    backdrop-filter:blur(18px);
    z-index:300;
}
.hdr-l { display:flex; align-items:center; gap:9px; min-width:0; }
.hdr-r { flex-shrink:0; }

.hdr-badge {
    width:28px; height:28px; border-radius:6px; flex-shrink:0;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    display:flex; align-items:center; justify-content:center;
    font-size:13px; color:#1a0900;
    box-shadow:0 2px 10px rgba(212,168,67,.4);
}
.hdr-title {
    font-family:'Playfair Display',serif;
    font-size:13px; font-weight:500; color:var(--text);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    max-width:400px;
}
.btn-back {
    display:flex; align-items:center; gap:5px;
    padding:5px 13px;
    background:rgba(255,255,255,.05);
    border:1px solid rgba(255,255,255,.1);
    border-radius:6px; color:var(--text);
    font-size:12px; font-weight:500;
    text-decoration:none; cursor:pointer;
    transition:.2s; font-family:'DM Sans',sans-serif;
}
.btn-back:hover { background:rgba(212,168,67,.13); border-color:rgba(212,168,67,.35); color:var(--gold); }

/* ═══════════════════════════════════════
   BOOK AREA  (flex:1 — fills everything between header/footer)
═══════════════════════════════════════ */
.book-area {
    flex:1; min-height:0;
    display:flex; align-items:center; justify-content:center;
    padding:10px 18px;
    position:relative; overflow:hidden;
}

/* perspective wrapper */
.scene {
    width:100%; height:100%;
    max-width:1380px;
    display:flex; align-items:center; justify-content:center;
    perspective:3200px;
    perspective-origin:50% 47%;
}

/* ═══════════════════════════════════════
   BOOK  — fills scene height, aspect locked
═══════════════════════════════════════ */
.book {
    position:relative;
    height:100%;
    aspect-ratio: 2 / 1.38;   /* double-spread proportion */
    max-height:100%;
    transform-style:preserve-3d;
    cursor:pointer;
    /* subtle ambient shadow */
    filter:drop-shadow(0 28px 55px rgba(0,0,0,.75)) drop-shadow(0 5px 18px rgba(0,0,0,.5));
}

/* ─── BASE PAGES (always visible underneath) ─── */
.pg-base {
    position:absolute; top:0; bottom:0; overflow:hidden;
}
.pg-base-l {
    left:0; width:50%;
    border-radius:8px 0 0 8px;
    background:var(--page-l);
    box-shadow: inset -8px 0 20px rgba(0,0,0,.13);
}
.pg-base-r {
    right:0; width:50%;
    border-radius:0 8px 8px 0;
    background:var(--page-r);
    box-shadow: inset 8px 0 20px rgba(0,0,0,.08);
}

/* paper line texture */
.pg-base::after {
    content:''; position:absolute; inset:0; pointer-events:none;
    background:repeating-linear-gradient(
        180deg, transparent, transparent 28px,
        rgba(0,0,0,.022) 28px, rgba(0,0,0,.022) 29px
    );
}

/* ─── SPINE ─── */
.spine {
    position:absolute;
    left:calc(50% - 7px); top:-2%; bottom:-2%;
    width:14px; z-index:40;
    background:linear-gradient(to right,
        #050200 0%, #1e0e02 22%, #3a1e06 50%, #1e0e02 78%, #050200 100%
    );
    box-shadow:
        0 0 22px rgba(0,0,0,.95),
        inset 2px 0 4px rgba(255,180,80,.06),
        inset -2px 0 4px rgba(0,0,0,.7);
    border-radius:2px;
}

/* ═══════════════════════════════════════
   FLIP LEAF
   A single-sided element that rotates around the spine.

   NEXT: placed over the RIGHT half, origin = left edge (spine),
         rotates 0 → -180°  (right-to-left curl)
   PREV: placed over the LEFT half, origin = right edge (spine),
         rotates 0 → +180°  (left-to-right curl)

   It has a FRONT face (the lifting page) and a BACK face
   (the underside seen mid-flip), achieved with backface-visibility.
═══════════════════════════════════════ */
.flip-leaf {
    position:absolute; top:0; bottom:0; width:50%;
    transform-style:preserve-3d;
    z-index:50;
    display:none;
    pointer-events:none;
    will-change:transform;
    box-shadow:0 18px 40px rgba(0,0,0,.75);
}

/* front face */
.f-front {
    position:absolute; inset:0;
    overflow:hidden;
    backface-visibility:hidden;
    -webkit-backface-visibility:hidden;
}
.leaf-going {
    box-shadow:
        -20px 15px 45px rgba(0,0,0,.6);
}
/* back face — rotated 180° so it shows when leaf is mid-flip */
.f-back {
    position:absolute; inset:0;
    overflow:hidden;
    backface-visibility:hidden;
    -webkit-backface-visibility:hidden;
    transform:rotateY(180deg);
}

/* Gradient shade swept across face during flip (gives curl depth) */
.f-front::after, .f-back::after {
    content:''; position:absolute; inset:0; z-index:5;
    pointer-events:none; opacity:0;
}
.leaf-going .f-front::after {
    background:linear-gradient(to left,
        rgba(0,0,0,0) 0%, rgba(0,0,0,.12) 55%, rgba(0,0,0,.5) 100%);
    animation:shadeF .7s ease forwards;
}
.leaf-going .f-back::after {
    background:linear-gradient(to right,
        rgba(0,0,0,0) 0%, rgba(0,0,0,.10) 55%, rgba(0,0,0,.42) 100%);
    animation:shadeB .7s ease forwards;
}
@keyframes shadeF { 0%{opacity:0} 45%{opacity:1} 100%{opacity:0} }
@keyframes shadeB { 0%{opacity:0} 60%{opacity:1} 100%{opacity:.15} }

/* thin highlight at fold edge — gives paper thickness illusion */
.f-front::before {
    content:''; position:absolute; right:0; top:0; bottom:0; width:4px; z-index:6;
    background:linear-gradient(to right, transparent, rgba(255,255,255,.22));
    pointer-events:none;
}
.f-back::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:4px; z-index:6;
    background:linear-gradient(to left, transparent, rgba(255,255,255,.18));
    pointer-events:none;
}

/* ═══════════════════════════════════════
   FLIP KEYFRAMES
   NEXT: right → left  (rotateY 0 → -180)
   PREV: left  → right (rotateY 0 → +180, mirrored leaf)
═══════════════════════════════════════ */
@keyframes flipNext {
    0%   { transform: rotateY(0deg) translateZ(0); }
    40%  { transform: rotateY(-80deg) translateZ(30px); }
    70%  { transform: rotateY(-150deg) translateZ(15px); }
    100% { transform: rotateY(-180deg) translateZ(0); }
}

@keyframes flipPrev {
    0%   { transform:rotateY(0deg);    }
    20%  { transform:rotateY(28deg);   }
    50%  { transform:rotateY(90deg);   }
    80%  { transform:rotateY(155deg);  }
    100% { transform:rotateY(180deg);  }
}

.anim-next { animation:flipNext .72s cubic-bezier(.38,0,.22,1) forwards; }
.anim-prev { animation:flipPrev .72s cubic-bezier(.38,0,.22,1) forwards; }

/* ═══════════════════════════════════════
   FOOTER NAV  (52 px)
═══════════════════════════════════════ */
.ftr {
    height:var(--ftr); min-height:var(--ftr); flex-shrink:0;
    display:flex; align-items:center; justify-content:space-between;
    padding:0 16px; gap:12px;
    background:rgba(10,13,20,.94);
    border-top:1px solid rgba(212,168,67,.16);
    backdrop-filter:blur(18px);
    z-index:300;
}
.nav-btn {
    display:flex; align-items:center; gap:6px;
    padding:7px 16px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.09);
    border-radius:7px; color:var(--text);
    font-size:12px; font-weight:600; cursor:pointer;
    font-family:'DM Sans',sans-serif; white-space:nowrap;
    transition:.2s;
}
.nav-btn:hover:not(:disabled) {
    background:rgba(212,168,67,.12);
    border-color:rgba(212,168,67,.35);
    color:var(--gold); transform:translateY(-1px);
}
.nav-btn:disabled { opacity:.2; cursor:not-allowed; }

.ftr-mid {
    flex:1; min-width:0;
    display:flex; flex-direction:column; align-items:center; gap:5px;
}
.pg-lbl { font-size:11px; color:var(--muted); font-weight:500; letter-spacing:.05em; }
.prog-bar { width:100%; max-width:220px; height:3px; background:rgba(255,255,255,.06); border-radius:3px; overflow:hidden; }
.prog-fill { height:100%; background:linear-gradient(90deg,var(--gold),var(--gold2)); border-radius:3px; }

/* ═══════════════════════════════════════
   PAGE CONTENT TYPES
═══════════════════════════════════════ */
.pc { width:100%; height:100%; overflow:hidden; display:flex; flex-direction:column; }

/* empty */
.empty-pg { align-items:center; justify-content:center; background:linear-gradient(160deg,#faf5eb,#ede4cf); }
.empty-msg { text-align:center; color:#b09a70; }
.empty-msg i { font-size:34px; display:block; margin-bottom:8px; opacity:.3; }
.empty-msg p { font-size:12px; opacity:.5; font-family:'Playfair Display',serif; }

/* pdf */
.pdf-pg { padding:0; background:#3c3c3c; }
.pdf-pg iframe { width:100%; height:100%; border:none; display:block; }

/* video */
.vid-pg { background:#000; padding:0; }
.vid-box { position:relative; width:100%; height:100%; }
.vid-box iframe { position:absolute; inset:0; width:100%; height:100%; border:none; }

/* error */
.err-st {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    height:100%; color:#999; text-align:center; padding:24px;
    background:linear-gradient(160deg,#faf5eb,#ede4cf);
}
.err-st i { font-size:40px; margin-bottom:10px; opacity:.3; }
.err-st p { font-size:12px; opacity:.5; }

/* quiz */
.quiz-pg { background:linear-gradient(160deg,#0f1724,#091320); color:#fff; }
.quiz-scroll {
    width:100%; height:100%; padding:14px 16px;
    overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column;
    scrollbar-width:none; -ms-overflow-style:none;
}
.quiz-scroll::-webkit-scrollbar { display:none; }

.quiz-hd { text-align:center; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid rgba(212,168,67,.2); }
.q-ico {
    width:36px; height:36px; margin:0 auto 7px;
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    border-radius:8px; display:flex; align-items:center; justify-content:center;
    font-size:16px; color:#1a0800;
    box-shadow:0 3px 10px rgba(212,168,67,.3);
}
.quiz-hd h2 { font-family:'Playfair Display',serif; font-size:16px; color:var(--gold); margin-bottom:3px; }
.quiz-hd p  { font-size:11px; opacity:.7; }

.q-box { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.09); border-radius:7px; padding:9px 11px; margin-bottom:9px; }
.q-box label { display:flex; align-items:flex-start; gap:7px; font-size:12px; font-weight:500; margin-bottom:6px; line-height:1.5; }
.qn {
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#1a0800; font-weight:700; font-size:10px;
    min-width:18px; height:18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px;
}
.q-box textarea {
    width:100%; padding:6px 9px;
    border:1.5px solid rgba(255,255,255,.15); border-radius:5px;
    background:rgba(255,255,255,.93); color:#333;
    font-family:'DM Sans',sans-serif; font-size:12px;
    resize:vertical; min-height:46px; max-height:78px;
    transition:border-color .2s;
}
.q-box textarea:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 2px rgba(212,168,67,.2); }
.q-submit {
    background:linear-gradient(135deg,var(--gold),var(--gold2));
    color:#1a0800; border:none; padding:9px 22px; border-radius:7px;
    font-size:12px; font-weight:700; font-family:'DM Sans',sans-serif;
    cursor:pointer; display:flex; align-items:center; gap:5px;
    margin:10px auto 0; transition:all .25s; box-shadow:0 3px 12px rgba(212,168,67,.3);
}
.q-submit:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(212,168,67,.4); }
.no-quiz { text-align:center; padding:36px 16px; }
.no-quiz i { font-size:40px; opacity:.2; display:block; margin-bottom:10px; }
.no-quiz p { font-size:12px; opacity:.4; }

/* ═══════════════════════════════════════
   OPENING CURTAIN
═══════════════════════════════════════ */
#curtain {
    position:fixed; inset:0; z-index:999;
    display:flex; align-items:center; justify-content:center;
    background:radial-gradient(ellipse at 50% 38%, #1c1005 0%, #0d1117 100%);
    transition:opacity .5s ease;
}
.ct-inner { text-align:center; }
.ct-icon { font-size:60px; color:var(--gold); display:block; margin-bottom:12px; animation:ctBob 2s ease-in-out infinite; }
@keyframes ctBob { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-9px)} }
.ct-inner h2 { font-family:'Playfair Display',serif; font-size:19px; color:var(--text); margin-bottom:3px; }
.ct-inner p  { font-size:12px; color:var(--muted); margin-bottom:18px; }

/* mini animated book in curtain */
.ct-book { width:150px; height:105px; margin:0 auto; position:relative; perspective:480px; }
.ct-l, .ct-r {
    position:absolute; top:0; height:105px; width:72px;
    background:var(--page-r); border-radius:3px;
    box-shadow:0 8px 24px rgba(0,0,0,.65);
}
.ct-l  { left:3px;  transform-origin:right center; animation:ctOpenL 1.1s .5s cubic-bezier(.4,0,.2,1) forwards; }
.ct-r  { right:3px; }
.ct-sp { position:absolute; left:calc(50% - 4px); top:0; width:8px; height:105px; background:var(--spine); border-radius:2px; box-shadow:0 0 10px rgba(0,0,0,.8); }
@keyframes ctOpenL { to { transform:rotateY(-165deg); } }

/* ═══════════════════════════════════════
   CLICK HINT ARROWS (fade in/out)
═══════════════════════════════════════ */
.click-hint {
    position:absolute; top:50%; transform:translateY(-50%);
    font-size:28px; color:rgba(212,168,67,.25);
    pointer-events:none;
    animation:hintFade 2.5s 2.5s ease forwards;
    z-index:30;
}
.hint-l { left:6px;  }
.hint-r { right:6px; }
@keyframes hintFade { 0%{opacity:1} 100%{opacity:0} }
</style>
</head>
<body>

<!-- ═══ OPENING CURTAIN ═══ -->
<?php if ($page_number === 1): ?>
<div id="curtain">
    <div class="ct-inner">
        <i class="bi bi-book-fill ct-icon"></i>
        <h2><?= htmlspecialchars($buku['judul']) ?></h2>
        <p>Membuka buku...</p>
        <div class="ct-book">
            <div class="ct-l"></div>
            <div class="ct-sp"></div>
            <div class="ct-r"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="shell">

    <!-- HEADER -->
    <header class="hdr">
        <div class="hdr-l">
            <div class="hdr-badge"><i class="bi bi-book-fill"></i></div>
            <span class="hdr-title"><?= htmlspecialchars($buku['judul']) ?></span>
        </div>
        <div class="hdr-r">
            <a href="dashboard_<?= $_SESSION['role'] ?>.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </header>

    <!-- BOOK AREA -->
    <main class="book-area">
        <div class="scene">
            <div class="book" id="BOOK">

                <!-- Left base page -->
                <div class="pg-base pg-base-l" id="baseLeft">
                    <?= renderPageContent($current_page_1, $soal_list, $buku_id, $kode_buku) ?>
                </div>

                <!-- Right base page -->
                <div class="pg-base pg-base-r" id="baseRight">
                    <?= renderPageContent($current_page_2, $soal_list, $buku_id, $kode_buku) ?>
                </div>

                <!-- Spine -->
                <div class="spine"></div>

                <!--
                    THE FLIP LEAF:
                    JS will set its position (left:50% for next, left:0 for prev),
                    transform-origin, and content before animating.
                -->
                <div class="flip-leaf" id="LEAF">
                    <div class="f-front" id="LFRONT"></div>
                    <div class="f-back"  id="LBACK"></div>
                </div>

                <!-- Click hint arrows -->
                <?php if ($has_prev): ?>
                <span class="click-hint hint-l"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>
                <?php if ($has_next): ?>
                <span class="click-hint hint-r"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- FOOTER NAV -->
    <nav class="ftr">
        <button class="nav-btn" id="prevBtn"
            <?= !$has_prev ? 'disabled' : '' ?>
            onclick="doFlip(<?= $prev_page ?>,'prev')">
            <i class="bi bi-chevron-double-left"></i> Sebelumnya
        </button>

        <div class="ftr-mid">
            <span class="pg-lbl">
                Hal. <?= $page_number ?>–<?= min($page_number+1,$total_pages) ?> / <?= $total_pages ?>
            </span>
            <div class="prog-bar">
                <div class="prog-fill" style="width:<?= round($persentase) ?>%"></div>
            </div>
        </div>

        <button class="nav-btn" id="nextBtn"
            <?= !$has_next ? 'disabled' : '' ?>
            onclick="doFlip(<?= $next_page ?>,'next')">
            Selanjutnya <i class="bi bi-chevron-double-right"></i>
        </button>
    </nav>

</div><!-- .shell -->

<script>
/* ════════════════════════════════════════════════════
   FLIP ENGINE

   NEXT (right → left):
     • Leaf placed over right half (left:50%)
     • transform-origin: left center  (hinge = spine)
     • f-front: clone of current right page content
     • f-back:  blank/empty (next left page will load on navigate)
     • Animation: rotateY(0) → rotateY(-180)
     • While flipping, base right page hidden (opacity 0)

   PREV (left → right):
     • Leaf placed over left half (left:0)
     • transform-origin: right center (hinge = spine)
     • f-front: clone of current left page content
     • f-back:  blank/empty
     • Animation: rotateY(0) → rotateY(+180)
     • While flipping, base left page hidden

   After animation (700 ms) → navigate to new URL.
════════════════════════════════════════════════════ */
let busy = false;
const DUR = 720; // match CSS animation duration

const BOOK   = document.getElementById('BOOK');
const LEAF   = document.getElementById('LEAF');
const LFRONT = document.getElementById('LFRONT');
const LBACK  = document.getElementById('LBACK');
const bLeft  = document.getElementById('baseLeft');
const bRight = document.getElementById('baseRight');

function doFlip(targetPage, dir) {
    if (busy) return;
    busy = true;

    // ── 1. Copy current page content into leaf faces ──
    if (dir === 'next') {
        // Front shows what was on the right page (it "lifts off")
        LFRONT.innerHTML = bRight.innerHTML;
        // Back is empty — the new page loads after navigation
        LBACK.innerHTML  = '';

        // Place leaf over the RIGHT half, hinge at LEFT (spine)
        LEAF.style.left          = '50%';
        LEAF.style.transformOrigin = 'left center';

        // Front face: same border-radius as right page
        LFRONT.style.borderRadius = '0 8px 8px 0';
        LBACK.style.borderRadius  = '8px 0 0 8px';

        // Hide the base right page (leaf is now on top)
        bRight.style.visibility = 'hidden';

    } else { // prev
        LFRONT.innerHTML = bLeft.innerHTML;
        LBACK.innerHTML  = '';

        // Place leaf over the LEFT half, hinge at RIGHT (spine)
        LEAF.style.left          = '0';
        LEAF.style.transformOrigin = 'right center';

        LFRONT.style.borderRadius = '8px 0 0 8px';
        LBACK.style.borderRadius  = '0 8px 8px 0';

        bLeft.style.visibility = 'hidden';
    }

    // Reset transform (no previous animation state)
    LEAF.style.transform = 'rotateY(0deg)';
    LEAF.style.display   = 'block';

    // Force style flush before adding animation class
    LEAF.getBoundingClientRect();

    // ── 2. Trigger animation ──
    LEAF.classList.add('leaf-going');
    LEAF.classList.add(dir === 'next' ? 'anim-next' : 'anim-prev');

    // ── 3. Navigate after animation completes ──
    setTimeout(() => {
        window.location.href =
            '?book=<?= urlencode($kode_buku) ?>&page=' + targetPage;
    }, DUR);
}

/* ── Curtain ── */
const curtain = document.getElementById('curtain');
if (curtain) {
    setTimeout(() => {
        curtain.style.opacity = '0';
        setTimeout(() => curtain.remove(), 500);
    }, 1950);
}

/* ── Keyboard ── */
document.addEventListener('keydown', e => {
    if (busy) return;
    const p = document.getElementById('prevBtn');
    const n = document.getElementById('nextBtn');
    if ((e.key === 'ArrowLeft'  || e.key === 'PageUp')   && !p.disabled) doFlip(<?= $prev_page ?>, 'prev');
    if ((e.key === 'ArrowRight' || e.key === 'PageDown')  && !n.disabled) doFlip(<?= $next_page ?>, 'next');
});

/* ── Touch / swipe ── */
let tx = 0;
document.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, {passive:true});
document.addEventListener('touchend', e => {
    if (busy) return;
    const d  = tx - e.changedTouches[0].clientX;
    const p  = document.getElementById('prevBtn');
    const n  = document.getElementById('nextBtn');
    if (d >  60 && !n.disabled) doFlip(<?= $next_page ?>, 'next');
    if (d < -60 && !p.disabled) doFlip(<?= $prev_page ?>, 'prev');
}, {passive:true});

/* ── Click left/right half of book ── */
BOOK.addEventListener('click', e => {
    if (busy) return;
    // Ignore clicks on buttons inside pages (quiz, etc.)
    if (e.target.closest('button, a, textarea, input, select')) return;
    const mid = BOOK.getBoundingClientRect().left + BOOK.offsetWidth / 2;
    const p   = document.getElementById('prevBtn');
    const n   = document.getElementById('nextBtn');
    if (e.clientX >= mid && !n.disabled) doFlip(<?= $next_page ?>, 'next');
    if (e.clientX <  mid && !p.disabled) doFlip(<?= $prev_page ?>, 'prev');
});

/* ── Quiz form ── */
const qf = document.getElementById('quizForm');
if (qf) {
    qf.addEventListener('submit', function(e) {
        const areas = this.querySelectorAll('textarea[required]');
        let ok = true;
        areas.forEach(t => {
            if (!t.value.trim()) { ok=false; t.style.borderColor='#f44336'; }
            else t.style.borderColor='rgba(255,255,255,.15)';
        });
        if (!ok) { e.preventDefault(); alert('❌ Isi semua jawaban terlebih dahulu!'); return false; }
        return confirm('📝 Kirim jawaban sekarang?\nPastikan semua jawaban sudah benar.');
    });
    const qs = document.querySelector('.quiz-scroll');
    if (qs) qs.addEventListener('wheel', e => { e.preventDefault(); qs.scrollTop += e.deltaY; }, {passive:false});
}

/* ── Prefetch next spread ── */
const cur = <?= $page_number ?>, tot = <?= $total_pages ?>;
if (cur + 2 <= tot) {
    const lk = document.createElement('link');
    lk.rel = 'prefetch';
    lk.href = '?book=<?= urlencode($kode_buku) ?>&page=' + (cur + 2);
    document.head.appendChild(lk);
}
</script>
</body>
</html>