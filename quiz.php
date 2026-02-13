<?php
require_once 'config.php';
requireSiswa();

$user_id = $_SESSION['user_id'];

// CEK PRETEST
$pretest_query = "SELECT * FROM nilai_pretest WHERE user_id = $user_id";
$pretest_result = mysqli_query($conn, $pretest_query);
$pretest_completed = mysqli_num_rows($pretest_result) > 0;

// CEK QUIZ
$quiz_query = "SELECT * FROM nilai_quiz WHERE user_id = $user_id";
$quiz_result = mysqli_query($conn, $quiz_query);
$quiz_completed = mysqli_num_rows($quiz_result) > 0;
$quiz_data = $quiz_completed ? mysqli_fetch_assoc($quiz_result) : null;

// PROSES SIMPAN (HANYA JIKA BELUM PERNAH)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_score') {
    header('Content-Type: application/json');
    
    if ($quiz_completed) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah mengerjakan quiz!']);
        exit();
    }

    $total_soal = (int)($_POST['total_soal'] ?? 0);
    $jawaban_benar = (int)($_POST['jawaban_benar'] ?? 0);
    
    if ($total_soal <= 0) {
        echo json_encode(['success' => false, 'message' => 'Total soal tidak valid.']);
        exit();
    }

    $nilai_persen = ($jawaban_benar / $total_soal) * 100;
    $nilai_persen = number_format($nilai_persen, 2, '.', '');

    $sql = "INSERT INTO nilai_quiz (user_id, total_soal, jawaban_benar, nilai, completed_at)
            VALUES (?, ?, ?, ?, NOW())";
            
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiid", $user_id, $total_soal, $jawaban_benar, $nilai_persen);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Nilai berhasil disimpan!', 'nilai' => $nilai_persen]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . mysqli_error($conn)]);
        }
        mysqli_stmt_close($stmt);
    }
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Evaluasi Interaktif TIK</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
/* ... (SEMUA KODE CSS DARI FILE quiz.html DISINI) ... */
:root {--green:#078065;--dark-green:#045c4f;--accent:#27ae60;}
body {font-family:'Poppins',sans-serif;background:url("img/lab.jpeg") no-repeat center center fixed;background-size:cover;margin:0;padding:0;color:#333;min-height:100vh;}
.container {width:92%;max-width:1000px;margin:28px auto;background:rgba(255,255,255,0.9);border-radius:14px;padding:20px;box-shadow:0 8px 30px rgba(0,0,0,0.35);}
h1,h2,h3 {text-align:center;color:var(--dark-green);margin:6px 0 12px;}
button {background-color:var(--accent);color:#fff;border:none;padding:10px 16px;border-radius:10px;font-size:14px;cursor:pointer;transition:all 0.3s ease;}
button:hover {background-color:var(--dark-green);transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.2);}
.locked-message {background:white;padding:40px;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;}
.locked-icon {font-size:80px;margin-bottom:20px;}
.result-card {background:white;padding:40px;border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;}
.result-icon {font-size:80px;margin-bottom:20px;}
.result-score {font-size:48px;font-weight:bold;color:#27ae60;margin-bottom:10px;}
.result-text {color:#666;font-size:18px;margin-bottom:30px;}
.result-details {background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:30px;}
.result-details p {color:#333;margin:5px 0;}
/* ======== Background pakai gambar seperti materi.html ======== */
body {
  font-family: 'Poppins', sans-serif;
  background: url("img/lab.jpeg") no-repeat center center fixed;
  background-size: cover;
  margin: 0;
  padding: 0;
  color: #333;
  min-height: 100vh;
  overflow-y: auto;
}

/* ======== Kontainer utama ======== */
.container {
  width: 92%;
  max-width: 1000px;
  margin: 28px auto;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(6px);
}

h1, h2, h3 {
  text-align: center;
  color: var(--dark-green);
  margin: 6px 0 12px;
}

p { margin: 6px 0; }

/* ======== Tombol ======== */
button {
  background-color: var(--accent);
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}
button:hover {
  background-color: var(--dark-green);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
button.secondary { background: #e74c3c; }
button.secondary:hover { background: #c0392b; }
.small { padding: 6px 10px; font-size: 13px; }

/* ======== Soal Pilihan Ganda (CSS Tetap ada jaga-jaga, meski HTML dihapus) ======== */
.question {
  margin-bottom: 18px;
  padding: 14px;
  border-radius: 10px;
  background: rgba(236, 249, 241, 0.9);
  transition: all 0.3s ease;
}
.question:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}
.options label {
  display: block;
  margin: 6px 0;
  background: #fff;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
  cursor: pointer;
  transition: all 0.2s ease;
}
.info-btn {
    background: #3498db;
    padding: 8px 16px;
    font-size: 13px;
    margin-left: 8px;
}

.info-btn:hover {
    background: #2980b9;
}
.options label:hover {
  background: #e8f8f5;
  border-color: var(--accent);
  transform: translateX(5px);
}
.options input { margin-right: 8px; }

/* ======== Drag & Drop ======== */
.drag-area {
  border: 2px dashed #2ecc71;
  padding: 10px;
  border-radius: 10px;
  min-height: 60px;
  text-align: center;
  margin: 10px 0;
  transition: all 0.3s ease;
}
.drag-area:hover {
  background: rgba(46, 204, 113, 0.1);
  border-color: var(--dark-green);
}
.kabel-warna {
  width: 140px;
  height: 28px;
  border-radius: 8px;
  margin: 6px auto;
  text-align: center;
  line-height: 28px;
  color: #fff;
  font-weight: 600;
  border: 1px solid #ccc;
  display: inline-block;
  cursor: grab;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.kabel-warna:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.kabel-warna:active { cursor: grabbing; }

.computer {
  background: rgba(234, 250, 241, 0.95);
  padding: 12px;
  border-radius: 10px;
  text-align: center;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}
.computer:hover {
  border-color: var(--accent);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* ======== Skor ======== */
#skorDisplay {
  text-align: center;
  margin-top: 10px;
  font-weight: 700;
  color: #145A32;
  font-size: 18px;
  padding: 10px;
  background: rgba(39, 174, 96, 0.1);
  border-radius: 8px;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

/* ======== Layout Responsif ======== */
.row {
  display: flex;
  gap: 18px;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
}
@media (max-width: 720px) {
  .row { flex-direction: column; }
  .computer { width: 100% !important; }
}

/* ======== Input & Tabel ======== */
input[type="text"], input[type="email"] {
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #bbb;
  transition: all 0.3s ease;
}
input[type="text"]:focus, input[type="email"]:focus {
  border-color: var(--accent);
  outline: none;
  box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}
table {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
}
table th, table td {
  padding: 8px;
  border: 1px solid #ccc;
  text-align: left;
}
thead th {
  background: var(--dark-green);
  color: white;
}
.center { text-align: center; }

/* ======== Efek Tambahan ======== */
.file-item, .device, .device9, .device10, .cable, .connector, .ip-item10 {
  transition: all 0.3s ease;
}
.file-item:hover, .device:hover, .device9:hover, .device10:hover, .cable:hover, .connector:hover, .ip-item10:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.device-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 8px;
}

.match-img {
  width: 90px;
  height: 60px;
  object-fit: contain;
  cursor: pointer;
  border: 2px solid #ccc;
  border-radius: 8px;
  transition: all 0.2s ease;
}
.match-img:hover { transform: scale(1.1); }

.success-msg {
  color: #27ae60;
  font-weight: bold;
  text-align: center;
  margin-top: 10px;
}

.congratulations {
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  margin: 20px 0;
  font-size: 1.5em;
  font-weight: bold;
}
</style>
</head>
<body>

<?php if (!$pretest_completed): ?>
    <div class="container">
        <div class="locked-message">
            <div class="locked-icon">🔒</div>
            <h2>Quiz Terkunci</h2>
            <p style="color:#666;font-size:18px;">
                Anda harus menyelesaikan <strong>Pre-Test</strong> terlebih dahulu<br>
                sebelum dapat mengakses Quiz Interaktif.
            </p>
            <br>
            <a href="pretest.php" style="display:inline-block;background:#27ae60;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
                Kerjakan Pre-Test
            </a>
            <br><br>
            <a href="dashboard_siswa.php" style="display:inline-block;background:#95a5a6;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

<?php elseif ($quiz_completed): ?>
    <div class="container">
        <div class="result-card">
            <div class="result-icon">✅</div>
            <h2>Quiz Sudah Selesai!</h2>
            <div class="result-score"><?php echo number_format($quiz_data['nilai'], 2); ?></div>
            <div class="result-text">Skor Anda</div>
            
            <div class="result-details">
                <p><strong>Jawaban Benar:</strong> <?php echo $quiz_data['jawaban_benar']; ?> dari <?php echo $quiz_data['total_soal']; ?> soal</p>
                <p><strong>Waktu Selesai:</strong> <?php echo formatTanggal($quiz_data['completed_at']); ?></p>
            </div>
            
            <p style="color:#666;margin-bottom:20px;">
                Anda sudah menyelesaikan quiz ini. Nilai yang tercatat adalah nilai pertama Anda.<br>
                Quiz hanya bisa dikerjakan sekali.
            </p>
            
            <a href="posttest.php" style="display:inline-block;background:#27ae60;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;margin:5px;">
                Lanjut ke Post-Test
            </a>
            <a href="dashboard_siswa.php" style="display:inline-block;background:#3498db;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;margin:5px;">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- QUIZ INTERAKTIF AKAN DIMUNCULKAN DI SINI -->
<input type="hidden" id="studentNameHidden" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Siswa'); ?>">

<div id="quizView" class="container">
  <h1>Soal Interaktif (10 Soal)</h1>
  <p style="text-align:center; margin-bottom: 20px;">Lengkapi tantangan di bawah ini dengan benar.</p>
  
  <div id="dnd-questions"></div>

  <div style="text-align:center;margin-top:12px;">
    <button id="finishBtn">Selesai & Lihat Skor</button>
  </div>

  <div id="skorDisplay">Skor sementara: <span id="skor">0</span></div>
</div>

<div id="resultView" class="container" style="display:none;">
  <h2>Hasil Evaluasi Interaktif TIK</h2>
   
  <div id="db-save-status" style="text-align:center; margin:10px 0; font-weight:bold;"></div>

  <table id="infoTable" style="margin:10px auto; width:95%;">
    <thead>
      <tr>
        <th>Nama Siswa</th>
        <th>Jumlah Soal</th>
        <th>Skor</th>
        <th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
      <tr id="infoRow">
        <td id="infoName"></td>
        <td id="infoTotal"></td>
        <td id="infoScore"></td>
        <td id="infoDate"></td>
      </tr>
    </tbody>
  </table>

  <table id="resultTable" style="margin:12px auto; width:95%;">
    <thead>
      <tr>
        <th style="width:6%;">No</th>
        <th>Soal / Aktivitas</th>
        <th style="width:18%;">Hasil</th>
      </tr>
    </thead>
    <tbody id="resultBody"></tbody>
  </table>

  <div style="text-align:center;margin-top:12px;">
    <button id="viewTutorialBtn" class="info-btn" onclick="showTutorialModal()">📚 Lihat Cara Mengerjakan</button>
    <button id="backHomeBtn" class="secondary small">Kembali ke Beranda</button>
</div>
</div>

<script>
// ... (SEMUA KODE JAVASCRIPT DARI FILE quiz.html DISINI) ...

// === BAGIAN PILIHAN GANDA DIHAPUS ===
// Variabel container untuk soal interaktif
const dndContainer = document.getElementById('dnd-questions');

const kabelColors = {
  "Putih Oranye": "linear-gradient(90deg, white 50%, orange 50%)",
  "Oranye": "orange",
  "Putih Hijau": "linear-gradient(90deg, white 50%, green 50%)",
  "Hijau": "green",
  "Biru": "blue",
  "Putih Biru": "linear-gradient(90deg, white 50%, blue 50%)",
  "Coklat": "brown",
  "Putih Coklat": "linear-gradient(90deg, white 50%, brown 50%)"
};

function createCableDrag(title, sequence, index){
  const div = document.createElement('div');
  div.className = 'question';
  div.dataset.qtype = 'cable';
  div.dataset.qindex = index;
  div.innerHTML = `<h3 style="font-weight:600">${title}</h3>`;
   
  const instruction = document.createElement('p');
  instruction.textContent = 'Drag warna kabel ke area di bawah sesuai urutan yang benar';
  instruction.style.fontStyle = 'italic';
  instruction.style.color = '#666';
  div.appendChild(instruction);
   
  const source = document.createElement('div');
  source.className = 'drag-area';
  source.style.minHeight = '80px';
   
  const shuffled = [...sequence].sort(() => Math.random() - 0.5);
   
  shuffled.forEach(s=>{
    const el = document.createElement('div');
    el.className = 'kabel-warna draggable';
    el.textContent = s;
    el.style.background = kabelColors[s] || '#58d68d';
    el.draggable = true;
    el.dataset.item = s;
    source.appendChild(el);
  });
   
  const target = document.createElement('div');
  target.className = 'drag-area drop-area';
  target.dataset.correct = sequence.join(',');
  target.style.minHeight = '80px';
  target.innerHTML = '<p style="color:#999;">Letakkan warna kabel di sini</p>';
   
  div.appendChild(source);
  div.appendChild(target);
  dndContainer.appendChild(div);

  source.querySelectorAll('.draggable').forEach(dragEl=>{
    dragEl.addEventListener('dragstart', (ev)=>{
      ev.dataTransfer.setData('text/plain', dragEl.dataset.item);
      dragEl.style.opacity = '0.5';
    });
    dragEl.addEventListener('dragend', ()=>{
      dragEl.style.opacity = '1';
    });
  });
   
  target.addEventListener('dragover', e=>{
    e.preventDefault();
    target.style.background = 'rgba(46, 204, 113, 0.2)';
  });
   
  target.addEventListener('dragleave', ()=>{
    target.style.background = '';
  });
   
  target.addEventListener('drop', e=>{
    e.preventDefault();
    target.style.background = '';
    const color = e.dataTransfer.getData('text/plain');
    if(!color) return;
    
    if(target.querySelector('p')) target.querySelector('p').remove();
    
    const newEl = document.createElement('div');
    newEl.className = 'kabel-warna';
    newEl.style.background = kabelColors[color] || '#58d68d';
    newEl.textContent = color;
    newEl.style.cursor = 'pointer';
    
    newEl.onclick = () => newEl.remove();
    
    target.appendChild(newEl);
    checkCableOrder(div, target, sequence);
  });
   
  function checkCableOrder(questionDiv, targetArea, correctSequence) {
    const placed = Array.from(targetArea.querySelectorAll('.kabel-warna')).map(el => el.textContent);
    const correct = correctSequence.join(',');
    const current = placed.join(',');
    
    if(current === correct) {
      questionDiv.dataset.correct = 'true';
      targetArea.style.borderColor = '#27ae60';
      
      if(!questionDiv.querySelector('.success-msg')) {
        const msg = document.createElement('p');
        msg.className = 'success-msg';
        msg.textContent = 'Urutan benar!';
        questionDiv.appendChild(msg);
      }
    } else {
      questionDiv.dataset.correct = 'false';
      targetArea.style.borderColor = '#2ecc71';
      const msg = questionDiv.querySelector('.success-msg');
      if(msg) msg.remove();
    }
  }
}

createCableDrag("1. Urutkan Warna Kabel Straight (T568B)", ["Putih Oranye","Oranye","Putih Hijau","Biru","Putih Biru","Hijau","Putih Coklat","Coklat"], 1);
createCableDrag("2. Urutkan Warna Kabel Cross (T568A)", ["Putih Hijau","Hijau","Putih Oranye","Biru","Putih Biru","Oranye","Putih Coklat","Coklat"], 2);

const transferSim = document.createElement('div');
transferSim.className = 'question';
transferSim.dataset.correct = 'false';
transferSim.innerHTML = `
  <h3 style="font-weight:600">3. Simulasi Transfer File</h3>
  <p>Masukkan IP tujuan (Class C) lalu drag file ke PC tujuan</p>
  <div style="text-align:center;margin-bottom:10px;">
    <input
      type="text"
      id="transfer-ip-input"
      placeholder="Contoh: masukan IP PC tujuan"
      style="width:260px;padding:6px;border-radius:6px;border:1px solid #ccc;"
    >
    <button id="check-ip-btn" class="small">Cek IP</button>
    <p id="ip-status" style="font-weight:700;margin-top:6px;"></p>
  </div>

  <div class="row">
    <div class="computer" style="width:45%;">
      <img src="img/pc2.jpeg" style="width:80px;height:80px;margin:10px auto;">
      <h4>Komputer A</h4>
      <div id="computer-a-files">
        <div class="file-item draggable" id="file_doc" draggable="false" style="cursor:not-allowed;margin:6px;">Dokumen.docx</div>
        <div class="file-item draggable" id="file_img" draggable="false" style="cursor:not-allowed;margin:6px;">Gambar.jpg</div>
        <div class="file-item draggable" id="file_xls" draggable="false" style="cursor:not-allowed;margin:6px;">Data.xlsx</div>
      </div>
    </div>

    <div class="computer" style="width:45%;">
      <img src="img/pc3.jpeg" style="width:80px;height:80px;margin:10px auto;">
      <h4>Komputer B</h4>
      <div
        id="computer-b-files"
        class="drop-area"
        style="min-height:100px;color:#999;padding:6px;border:2px dashed #ccc;border-radius:8px;"
      >
        Belum ada file
      </div>
    </div>
  </div>

  <p id="transfer-result" style="font-weight:700;text-align:center;margin-top:8px;"></p>
`;

dndContainer.appendChild(transferSim);

// ===============================
// ELEMENT REFERENCES
// ===============================
const compAFiles = transferSim.querySelector('#computer-a-files');
const compBFiles = transferSim.querySelector('#computer-b-files');
const ipInputTransfer = transferSim.querySelector('#transfer-ip-input');
const ipStatus = transferSim.querySelector('#ip-status');
const checkIPBtn = transferSim.querySelector('#check-ip-btn');
const transferResult = transferSim.querySelector('#transfer-result');

let transferredFiles = 0;

// ===============================
// VALIDASI IP CLASS C (192.168.x.x)
// ===============================
function isValidClassC(ip) {
  const parts = ip.split('.');
  if (parts.length !== 4) return false;

  // Semua harus angka
  for (let p of parts) {
    if (!/^\d+$/.test(p)) return false;
  }

  const [a, b, c, d] = parts.map(Number);

  // Harus 192.168.x.x
  if (a !== 192 || b !== 168) return false;

  // Oktet ke-3 valid
  if (c < 0 || c > 255) return false;

  // Host hanya 2–254 (blok .0, .1, .255)
  if (d <= 1 || d >= 255) return false;

  return true;
}

// ===============================
// CEK IP
// ===============================
checkIPBtn.addEventListener('click', () => {
  const ip = ipInputTransfer.value.trim();

  if (isValidClassC(ip)) {
    ipStatus.textContent = `IP valid (${ip}), file siap ditransfer!`;
    ipStatus.style.color = "#27ae60";

    transferSim.dataset.targetIp = ip;

    Array.from(compAFiles.children).forEach(f => {
      f.draggable = true;
      f.style.cursor = 'grab';
    });
  } else {
    ipStatus.innerHTML =
      "IP tidak valid! Gunakan format Class C (192.168.x.x), host 2–254";
    ipStatus.style.color = "#e74c3c";

    Array.from(compAFiles.children).forEach(f => {
      f.draggable = false;
      f.style.cursor = 'not-allowed';
    });
  }
});

// ===============================
// DRAG FILE
// ===============================
compAFiles.querySelectorAll('.draggable').forEach(f => {
  f.addEventListener('dragstart', e => {
    if (!f.draggable) return;
    e.dataTransfer.setData('text/plain', e.target.id);
    f.style.opacity = '0.5';
  });

  f.addEventListener('dragend', () => {
    f.style.opacity = '1';
  });
});

// ===============================
// DROP FILE
// ===============================
compBFiles.addEventListener('dragover', e => {
  e.preventDefault();
  compBFiles.style.borderColor = '#27ae60';
});

compBFiles.addEventListener('dragleave', () => {
  compBFiles.style.borderColor = '#ccc';
});

compBFiles.addEventListener('drop', e => {
  e.preventDefault();
  compBFiles.style.borderColor = '#ccc';

  const id = e.dataTransfer.getData('text/plain');
  if (!id) return;

  const fileEl = document.getElementById(id);
  if (!fileEl || fileEl.dataset.transferred === 'true') return;

  if (compBFiles.textContent.includes('Belum ada file')) {
    compBFiles.textContent = '';
  }

  const cloneEl = fileEl.cloneNode(true);
  cloneEl.draggable = false;
  cloneEl.style.margin = '6px';
  compBFiles.appendChild(cloneEl);

  fileEl.dataset.transferred = 'true';
  fileEl.style.opacity = '0.3';
  fileEl.draggable = false;

  transferredFiles++;

  transferResult.textContent =
    `${fileEl.textContent} berhasil ditransfer ke ${transferSim.dataset.targetIp}`;
  transferResult.style.color = '#27ae60';

  if (transferredFiles === 3) {
    transferSim.dataset.correct = 'true';
    transferResult.textContent = 'Semua file berhasil ditransfer!';
  }
});
const printerSim = document.createElement('div');
printerSim.className = 'question';
printerSim.dataset.qtype = 'printer';
printerSim.dataset.correct = 'false';

printerSim.innerHTML = `
  <h3 style="font-weight:600">4. Simulasi Sharing Printer</h3>
  <p>Masukkan IP client lalu drag dokumen untuk sharing</p>

  <div style="text-align:center;margin-bottom:10px;">
    <input
      type="text"
      id="printer-ip-input"
      placeholder="192.168.1.3 - 192.168.1.254"
      style="width:260px;padding:6px;border-radius:6px;border:1px solid #ccc;"
    >
    <button id="check-printer-ip-btn" class="small">Cek IP</button>
    <p id="printer-ip-status" style="font-weight:700;margin-top:6px;"></p>
  </div>

  <div class="row">
    <div class="computer" style="width:45%;">
      <img src="img/pc1.jpeg" class="device-img" style="width:80px;height:80px;margin:10px auto;">
      <h4>Komputer Host</h4>

      <img src="img/printer.png" style="width:60px;height:60px;margin:0 auto;display:block;">
      <div id="printer-files">
        <div class="file-item draggable" id="p_testdoc" draggable="false" style="cursor:not-allowed;margin:6px;">
          TestDoc.pdf
        </div>
        <div class="file-item draggable" id="p_report" draggable="false" style="cursor:not-allowed;margin:6px;">
          Laporan.docx
        </div>
      </div>
    </div>

    <div class="computer" style="width:45%;">
      <img src="img/pc2.jpeg" class="device-img" style="width:80px;height:80px;margin:10px auto;">
      <h4>Komputer Client</h4>
      <div
        id="client-printer-files"
        class="drop-area"
        style="min-height:100px;color:#999;padding:6px;border:2px dashed #ccc;border-radius:8px;"
      >
        Belum ada file
      </div>
    </div>
  </div>

  <p id="printer-result" style="font-weight:700;text-align:center;margin-top:8px;"></p>
`;

dndContainer.appendChild(printerSim);

// ===============================
// ELEMENT
// ===============================
const printerFiles = printerSim.querySelector('#printer-files');
const clientPrinterFiles = printerSim.querySelector('#client-printer-files');
const printerIPInput = printerSim.querySelector('#printer-ip-input');
const printerIPStatus = printerSim.querySelector('#printer-ip-status');
const checkPrinterIPBtn = printerSim.querySelector('#check-printer-ip-btn');
const printerResult = printerSim.querySelector('#printer-result');

let printerSentFiles = 0;

// ===============================
// VALIDASI IP KHUSUS
// HANYA 192.168.1.3 - 192.168.1.254
// ===============================
function isValidClientIP(ip) {
  const regex = /^192\.168\.1\.(\d{1,3})$/;
  const match = ip.match(regex);

  if (!match) return false;

  const last = parseInt(match[1], 10);

  if (last <= 2 || last > 254) return false;

  return true;
}

// ===============================
// CEK IP
// ===============================
checkPrinterIPBtn.addEventListener('click', () => {
  const ip = printerIPInput.value.trim();

  if (isValidClientIP(ip)) {
    printerIPStatus.textContent = `IP valid (${ip})`;
    printerIPStatus.style.color = '#27ae60';
    printerSim.dataset.clientIp = ip;

    printerFiles.querySelectorAll('.draggable').forEach(f => {
      f.draggable = true;
      f.style.cursor = 'grab';
    });
  } else {
    printerIPStatus.textContent =
      'IP tidak valid! Gunakan 192.168.1.3 – 192.168.1.254';
    printerIPStatus.style.color = '#e74c3c';

    printerFiles.querySelectorAll('.draggable').forEach(f => {
      f.draggable = false;
      f.style.cursor = 'not-allowed';
    });
  }
});

// ===============================
// DRAG FILE
// ===============================
printerFiles.querySelectorAll('.draggable').forEach(file => {
  file.addEventListener('dragstart', e => {
    if (!file.draggable) return;
    e.dataTransfer.setData('text/plain', file.id);
    file.style.opacity = '0.5';
  });

  file.addEventListener('dragend', () => {
    file.style.opacity = '1';
  });
});

// ===============================
// DROP FILE
// ===============================
clientPrinterFiles.addEventListener('dragover', e => {
  e.preventDefault();
  clientPrinterFiles.style.borderColor = '#27ae60';
});

clientPrinterFiles.addEventListener('dragleave', () => {
  clientPrinterFiles.style.borderColor = '#ccc';
});

clientPrinterFiles.addEventListener('drop', e => {
  e.preventDefault();
  clientPrinterFiles.style.borderColor = '#ccc';

  const id = e.dataTransfer.getData('text/plain');
  if (!id) return;

  const fileEl = document.getElementById(id);
  if (!fileEl || fileEl.dataset.sent === 'true') return;

  if (clientPrinterFiles.textContent.includes('Belum ada file')) {
    clientPrinterFiles.textContent = '';
  }

  const clone = fileEl.cloneNode(true);
  clone.id = id + '_sent';
  clone.draggable = false;
  clone.style.cursor = 'default';

  clientPrinterFiles.appendChild(clone);

  fileEl.dataset.sent = 'true';
  fileEl.style.opacity = '0.3';
  fileEl.draggable = false;

  printerSentFiles++;

  printerResult.textContent =
    `${fileEl.textContent} berhasil dikirim ke ${printerSim.dataset.clientIp}`;
  printerResult.style.color = '#27ae60';

  if (printerSentFiles === 2) {
    printerSim.dataset.correct = 'true';
    printerResult.textContent = 'Semua file berhasil di-share!';
  }
});


const soal5 = document.createElement("div");
soal5.className = "question";
soal5.dataset.qtype = "match";
soal5.dataset.correct = "false";
soal5.style.position = "relative";
soal5.innerHTML = `
  <h3>5. Hubungkan Gambar dengan Fungsinya</h3>
  <p>Klik gambar, lalu klik fungsi yang sesuai. Garis penghubung akan muncul jika benar.</p>
  <div id="wrap5" style="position:relative;padding:20px;border:2px solid #ccc;border-radius:10px;background:white;">
    <canvas id="canvas5" style="position:absolute;top:0;left:0;z-index:1;pointer-events:none;"></canvas>
    <table style="width:100%;text-align:center;z-index:5;position:relative;">
      <tr><th>Gambar</th><th>Fungsi</th></tr>
      <tr>
        <td><img src="img/router.jpeg" alt="Router" id="images_router5" data-match="f_router5" class="match-img"></td>
        <td><div id="f_router5" class="fungsi5" style="border:2px solid #aaa;padding:10px;border-radius:10px;cursor:pointer;transition:all 0.2s;">Menyambungkan jaringan ke internet</div></td>
      </tr>
      <tr>
        <td><img src="img/switch.jpeg" alt="Switch" id="images_switch5" data-match="f_switch5" class="match-img"></td>
        <td><div id="f_switch5" class="fungsi5" style="border:2px solid #aaa;padding:10px;border-radius:10px;cursor:pointer;transition:all 0.2s;">Membagi koneksi antar perangkat</div></td>
      </tr>
      <tr>
        <td><img src="img/kabel.webp" alt="Kabel" id="images_kabel5" data-match="f_kabel5" class="match-img"></td>
        <td><div id="f_kabel5" class="fungsi5" style="border:2px solid #aaa;padding:10px;border-radius:10px;cursor:pointer;transition:all 0.2s;">Menghubungkan dua perangkat jaringan</div></td>
      </tr>
    </table>
  </div>
  <div id="res5" style="text-align:center;margin-top:10px;font-weight:bold;"></div>
`;
dndContainer.appendChild(soal5);

(function () {
  const imgs = soal5.querySelectorAll("img[id^='images_']");
  const funcs = soal5.querySelectorAll(".fungsi5");
  const canvas = soal5.querySelector("#canvas5");
  const ctx = canvas.getContext("2d");
  const wrap = soal5.querySelector("#wrap5");
  let selected = null;
  let connections = [];

  function resizeCanvas() {
    canvas.width = wrap.offsetWidth;
    canvas.height = wrap.offsetHeight;
    drawLines();
  }

  function getPos(el) {
    const r = el.getBoundingClientRect();
    const base = wrap.getBoundingClientRect();
    return { x: r.left - base.left, y: r.top - base.top, w: r.width, h: r.height };
  }

  imgs.forEach(img => {
    img.onclick = () => {
      selected = img;
      imgs.forEach(i => i.style.border = "2px solid #ccc");
      img.style.border = "4px solid #27ae60";
    };
  });

  funcs.forEach(f => {
    f.onclick = () => {
      if (!selected) return;
      const benar = selected.dataset.match === f.id;
      if (benar) {
        connections.push({ from: selected.id, to: f.id });
        drawLines();
        selected.style.opacity = "0.6";
        selected.style.pointerEvents = "none";
        f.style.background = "#d4edda";
        f.style.pointerEvents = "none";
      }
      selected.style.border = "2px solid #ccc";
      selected = null;
      checkResult();
    };
  });

  function drawLines() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    connections.forEach(c => {
      const img = soal5.querySelector(`#${c.from}`);
      const f = soal5.querySelector(`#${c.to}`);
      if (!img || !f) return;
      const a = getPos(img);
      const b = getPos(f);
      ctx.beginPath();
      ctx.moveTo(a.x + a.w, a.y + a.h / 2);
      ctx.lineTo(b.x, b.y + b.h / 2);
      ctx.strokeStyle = "#27ae60";
      ctx.lineWidth = 4;
      ctx.stroke();
    });
  }

  function checkResult() {
    const res = soal5.querySelector("#res5");
    res.innerHTML = `Terhubung: ${connections.length}/${imgs.length}`;
    if (connections.length === imgs.length) {
      res.innerHTML = "Semua benar!";
      res.style.color = "#27ae60";
      soal5.dataset.correct = 'true';
    }
  }

  window.addEventListener("resize", resizeCanvas);
  setTimeout(resizeCanvas, 500);
})();

const soal6 = document.createElement("div");
soal6.className = "question";
soal6.dataset.qtype = "sequence";
soal6.dataset.correct = "false";
soal6.innerHTML = `
  <h3>6. Susun Urutan Proses Jaringan Komputer</h3>
  <p>Drag dan susun langkah di bawah ini sesuai urutan proses komunikasi data!</p>
  <ul id="list6" style="list-style:none;padding:0;max-width:600px;margin:auto;">
    <li draggable="true" data-correct="1" class="drag6">Komputer mengirimkan data</li>
    <li draggable="true" data-correct="2" class="drag6">Data dikirim melalui kabel jaringan</li>
    <li draggable="true" data-correct="3" class="drag6">Switch mengatur arah data</li>
    <li draggable="true" data-correct="4" class="drag6">Komputer tujuan menerima data</li>
  </ul>
  <div id="res6" style="text-align:center;margin-top:10px;font-weight:bold;"></div>
`;
dndContainer.appendChild(soal6);

(function () {
  const items = soal6.querySelectorAll(".drag6");
  const list = soal6.querySelector("#list6");
  const res = soal6.querySelector("#res6");

  list.style.border = "2px dashed #ccc";
  list.style.borderRadius = "10px";
  list.style.padding = "10px";

  const itemsArray = Array.from(items);
  itemsArray.sort(() => Math.random() - 0.5);
  itemsArray.forEach(item => list.appendChild(item));

  items.forEach(i => {
    i.style.border = "1px solid #999";
    i.style.padding = "10px";
    i.style.margin = "6px 0";
    i.style.borderRadius = "8px";
    i.style.background = "#f9f9f9";
    i.style.cursor = "move";
  });

  let dragged = null;

  items.forEach(item => {
    item.addEventListener("dragstart", () => {
      dragged = item;
      item.style.opacity = '0.5';
    });
    item.addEventListener("dragend", () => {
      item.style.opacity = '1';
    });
    item.addEventListener("dragover", e => {
      e.preventDefault();
      item.style.borderColor = '#27ae60';
    });
    item.addEventListener("dragleave", () => {
      item.style.borderColor = '#999';
    });
    item.addEventListener("drop", e => {
      e.preventDefault();
      item.style.borderColor = '#999';
      if (dragged && dragged !== item) {
        const all = [...list.children];
        const draggedPos = all.indexOf(dragged);
        const droppedPos = all.indexOf(item);
        if (draggedPos < droppedPos) item.after(dragged);
        else item.before(dragged);
      }
      checkOrder();
    });
  });

  function checkOrder() {
    const all = list.querySelectorAll("li");
    let benar = 0;
    all.forEach((i, idx) => {
      if (parseInt(i.dataset.correct) === idx + 1) {
        benar++;
        i.style.background = '#d4edda';
      } else {
        i.style.background = '#f9f9f9';
      }
    });
    if (benar === all.length) {
      res.innerHTML = "Urutan sudah benar!";
      res.style.color = "green";
      soal6.dataset.correct = 'true';
    } else {
      res.innerHTML = `Benar ${benar}/${all.length}`;
      res.style.color = "#333";
      soal6.dataset.correct = 'false';
    }
  }
   
  checkOrder();
})();

const soal7 = document.createElement('div');
soal7.className = 'question';
soal7.dataset.qtype = 'puzzle';
soal7.dataset.correct = 'false';
soal7.innerHTML = `
  <h3>7. Puzzle Kabel & Konektor</h3>
  <p>Seret kabel ke konektor yang sesuai. Garis hijau akan muncul jika pasangan benar.</p>
  <div id="wrap7" style="position:relative;width:100%;height:400px;overflow:hidden;border:1px solid #ccc;border-radius:10px;background:linear-gradient(to bottom, #e0f7e0, #ffffff);">
    <canvas id="canvas7" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;pointer-events:none;"></canvas>

    <div id="cable_utp" class="cable" style="width:100px;height:60px;position:absolute;top:30px;left:60px;cursor:grab;z-index:2;background:#ffeaa7;border:2px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">UTP</div>
    <div id="cable_stp" class="cable" style="width:100px;height:60px;position:absolute;top:30px;left:200px;cursor:grab;z-index:2;background:#74b9ff;border:2px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">STP</div>
    <div id="cable_fiber" class="cable" style="width:100px;height:60px;position:absolute;top:30px;left:340px;cursor:grab;z-index:2;background:#a29bfe;border:2px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">Fiber</div>
    <div id="cable_coax" class="cable" style="width:100px;height:60px;position:absolute;top:30px;left:480px;cursor:grab;z-index:2;background:#fd79a8;border:2px solid #ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">Coax</div>

    <div class="connector" data-match="cable_utp" style="width:80px;height:60px;position:absolute;bottom:30px;left:60px;z-index:3;background:#55efc4;border:2px solid #00b894;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">RJ45</div>
    <div class="connector" data-match="cable_stp" style="width:80px;height:60px;position:absolute;bottom:30px;left:200px;z-index:3;background:#81ecec;border:2px solid #00cec9;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">RJ45</div>
    <div class="connector" data-match="cable_fiber" style="width:80px;height:60px;position:absolute;bottom:30px;left:340px;z-index:3;background:#fab1a0;border:2px solid #e17055;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">SC</div>
    <div class="connector" data-match="cable_coax" style="width:80px;height:60px;position:absolute;bottom:30px;left:480px;z-index:3;background:#ff7675;border:2px solid #d63031;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:bold;">BNC</div>
  </div>
  <div id="res7" style="text-align:center;margin-top:10px;font-weight:bold;"></div>
`;
dndContainer.appendChild(soal7);

(function(){
  const wrap = soal7.querySelector('#wrap7');
  const canvas = soal7.querySelector('#canvas7');
  const ctx = canvas.getContext('2d');
  const cables = soal7.querySelectorAll('.cable');
  const connectors = soal7.querySelectorAll('.connector');
  let connections = [];
  let current = null;
  let offsetX = 0, offsetY = 0;

  function resizeCanvas(){
    canvas.width = wrap.offsetWidth;
    canvas.height = wrap.offsetHeight;
    drawLines();
  }
  window.addEventListener('resize', resizeCanvas);
  setTimeout(resizeCanvas, 400);

  cables.forEach(cable=>{
    cable.addEventListener('mousedown', startDrag);
    cable.addEventListener('touchstart', startDrag);
    cable.dataset.left = cable.offsetLeft;
    cable.dataset.top = cable.offsetTop;
  });

  function startDrag(e){
    if(connections.find(c => c.from === e.target)) return;
    e.preventDefault();
    current = e.target;
    const rect = current.getBoundingClientRect();
    const wrapRect = wrap.getBoundingClientRect();
    offsetX = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
    offsetY = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
    current.style.cursor = 'grabbing';
    current.style.transform = 'scale(1.1)';
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('touchmove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
  }

  function onDrag(e){
    if(!current) return;
    const wrapRect = wrap.getBoundingClientRect();
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - wrapRect.left - offsetX;
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - wrapRect.top - offsetY;
    current.style.left = x + 'px';
    current.style.top = y + 'px';
    drawLines();
  }

  function endDrag(e){
    if(!current) return;
    current.style.transform = 'scale(1)';
    let placed = false;
    connectors.forEach(conn=>{
      const connRect = conn.getBoundingClientRect();
      const curRect = current.getBoundingClientRect();
      if(curRect.left+curRect.width/2 > connRect.left &&
         curRect.left+curRect.width/2 < connRect.right &&
         curRect.top+curRect.height/2 > connRect.top &&
         curRect.top+curRect.height/2 < connRect.bottom){
        if(current.id === conn.dataset.match){
          connections.push({from:current, to:conn});
          current.style.left = (conn.offsetLeft + conn.offsetWidth/2 - current.offsetWidth/2) + 'px';
          current.style.top = (conn.offsetTop - current.offsetHeight - 10) + 'px';
          current.style.cursor = 'default';
          placed = true;
        }
      }
    });
    if(!placed){
      current.style.left = current.dataset.left + 'px';
      current.style.top = current.dataset.top + 'px';
      current.style.cursor = 'grab';
    }
    current = null;
    drawLines();
    checkResult();
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchend', endDrag);
  }

  function drawLines(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    connections.forEach(c=>{
      const fromRect = c.from.getBoundingClientRect();
      const toRect = c.to.getBoundingClientRect();
      const wrapRect = wrap.getBoundingClientRect();
      const startX = fromRect.left + fromRect.width/2 - wrapRect.left;
      const startY = fromRect.top + fromRect.height/2 - wrapRect.top;
      const endX = toRect.left + toRect.width/2 - wrapRect.left;
      const endY = toRect.top + toRect.height/2 - wrapRect.top;
      ctx.beginPath();
      ctx.moveTo(startX,startY);
      ctx.lineTo(endX,endY);
      ctx.strokeStyle = 'limegreen';
      ctx.lineWidth = 5;
      ctx.stroke();
    });
  }

  function checkResult(){
    const res = soal7.querySelector('#res7');
    if(connections.length === cables.length){
      res.innerHTML = "Semua kabel berhasil dihubungkan dengan benar!";
      res.style.color = '#27ae60';
      soal7.dataset.correct = 'true';
      cables.forEach(c=>c.style.pointerEvents='none');
    } else {
      res.innerHTML = `Benar ${connections.length}/${cables.length}`;
      res.style.color = '#e67e22';
      soal7.dataset.correct = 'false';
    }
  }

  resizeCanvas();
})();

const soal8 = document.createElement('div');
soal8.className='question';
soal8.dataset.qtype='topo';
soal8.dataset.correct='false';
soal8.innerHTML = `
  <h3 style="font-weight:600">8. Susun Topologi Jaringan</h3>
  <p>Seret perangkat ke posisi yang benar pada topologi.</p>
  <div id="topologi-wrapper" style="width:100%;height:320px;border:1px solid #ddd;position:relative;margin:12px auto;background:#fafafa;">
    <div class="target" data-id="pc1" style="width:80px;height:80px;border:2px dashed #777;position:absolute;top:220px;left:80px;border-radius:8px;"></div>
    <div class="target" data-id="router" style="width:80px;height:80px;border:2px dashed #777;position:absolute;top:40px;left:260px;border-radius:8px;"></div>
    <div class="target" data-id="switch" style="width:80px;height:80px;border:2px dashed #777;position:absolute;top:120px;left:260px;border-radius:8px;"></div>
    <div class="target" data-id="pc2" style="width:80px;height:80px;border:2px dashed #777;position:absolute;top:220px;left:420px;border-radius:8px;"></div>
  </div>
  <div id="devices" style="display:flex;justify-content:center;gap:18px;margin-top:12px;">
    <div class="draggable device" data-id="pc1" draggable="true" style="width:80px;height:80px;cursor:grab;background:#e3f2fd;border:2px solid #2196f3;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
      <img src="img/pc1.jpeg" alt="PC1" class="device-img">
    </div>
    <div class="draggable device" data-id="pc2" draggable="true" style="width:80px;height:80px;cursor:grab;background:#e3f2fd;border:2px solid #2196f3;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
      <img src="img/pc2.jpeg" alt="PC2" class="device-img">
    </div>
    <div class="draggable device" data-id="switch" draggable="true" style="width:80px;height:80px;cursor:grab;background:#fff3e0;border:2px solid #ff9800;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
      <img src="img/switch.jpeg" alt="Switch" class="device-img">
    </div>
    <div class="draggable device" data-id="router" draggable="true" style="width:80px;height:80px;cursor:grab;background:#f3e5f5;border:2px solid #9c27b0;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
      <img src="img/router.jpeg" alt="Router" class="device-img">
    </div>
  </div>
  <div id="soal8-result" style="margin-top:12px;font-weight:700;text-align:center;"></div>
`;
dndContainer.appendChild(soal8);

(function(){
  const draggables8 = Array.from(soal8.querySelectorAll('.draggable'));
  const targets8 = Array.from(soal8.querySelectorAll('.target'));
  const topWrapper = soal8.querySelector('#topologi-wrapper');
  const result8 = soal8.querySelector('#soal8-result');

  draggables8.forEach(d=>{
    d.addEventListener('dragstart', e=>{
      e.dataTransfer.setData('text/plain', d.dataset.id);
      d.style.opacity = '0.5';
    });
    d.addEventListener('dragend', ()=>{
      d.style.opacity = '1';
    });
  });
   
  targets8.forEach(t=>{
    t.addEventListener('dragover', e=>{
      e.preventDefault();
      t.style.background = 'rgba(39, 174, 96, 0.2)';
    });
    t.addEventListener('dragleave', ()=>{
      t.style.background = '';
    });
    t.addEventListener('drop', e=>{
      e.preventDefault();
      t.style.background = '';
      const id = e.dataTransfer.getData('text/plain');
      const dragged = draggables8.find(x=>x.dataset.id===id);
      if(!dragged) return;
      dragged.style.position='absolute';
      const rect = t.getBoundingClientRect();
      const wrapRect = topWrapper.getBoundingClientRect();
      dragged.style.top = (rect.top - wrapRect.top) + 'px';
      dragged.style.left = (rect.left - wrapRect.left) + 'px';
      topWrapper.appendChild(dragged);
      if(id === t.dataset.id) t.style.borderColor = '#27ae60';
      else t.style.borderColor = '#e74c3c';
      checkSoal8();
    });
  });
   
  function checkSoal8(){
    let ok=0;
    targets8.forEach(t=>{
      const placed = topWrapper.querySelector(`[data-id='${t.dataset.id}']`);
      if(placed && placed.dataset.id === t.dataset.id) ok++;
    });
    if(ok === targets8.length){ 
      result8.textContent='Topologi sudah benar!'; 
      result8.style.color='#27ae60'; 
      soal8.dataset.correct='true'; 
    } else { 
      result8.textContent = `Jawaban benar: ${ok}/${targets8.length}`; 
      result8.style.color='#e67e22'; 
      soal8.dataset.correct='false';
    }
  }
})();

const soal9 = document.createElement('div');
soal9.className='question';
soal9.dataset.qtype='simTopo';
soal9.dataset.correct='false';
soal9.innerHTML = `
  <h3 style="font-weight:600">9. Simulasi Topologi - Masukkan IP</h3>
  <p>Masukkan IP untuk tiap perangkat lalu kirim pesan PC1 → PC2</p>
  <div id="topo9-wrapper" style="display:flex;justify-content:center;gap:20px;margin-top:12px;flex-wrap:wrap;">
    <div class="device9" data-id="router" style="text-align:center;border:2px solid #ccc;padding:10px;border-radius:10px;background:#f3e5f5;">
      <img src="img/router.jpeg" alt="Router" class="device-images" style="width:60px;height:60px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">Router</div>
      <input class="ip9" placeholder="IP Router" style="width:120px;margin-top:6px;padding:4px;border-radius:4px;border:1px solid #ccc;">
      <div class="status9" style="margin-top:6px;"></div>
    </div>
    <div class="device9" data-id="pc1" style="text-align:center;border:2px solid #ccc;padding:10px;border-radius:10px;background:#e3f2fd;">
      <img src="img/pc1.jpeg" alt="PC1" class="device-images" style="width:60px;height:60px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">PC1</div>
      <input class="ip9" placeholder="IP PC1" style="width:120px;margin-top:6px;padding:4px;border-radius:4px;border:1px solid #ccc;">
      <div class="status9" style="margin-top:6px;"></div>
    </div>
    <div class="device9" data-id="pc2" style="text-align:center;border:2px solid #ccc;padding:10px;border-radius:10px;background:#e3f2fd;">
      <img src="img/pc2.jpeg" alt="PC2" class="device-images" style="width:60px;height:60px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">PC2</div>
      <input class="ip9" placeholder="IP PC2" style="width:120px;margin-top:6px;padding:4px;border-radius:4px;border:1px solid #ccc;">
      <div class="status9" style="margin-top:6px;"></div>
    </div>
    <div class="device9" data-id="pc3" style="text-align:center;border:2px solid #ccc;padding:10px;border-radius:10px;background:#e3f2fd;">
      <img src="img/pc3.jpeg" alt="PC3" class="device-images" style="width:60px;height:60px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">PC3</div>
      <input class="ip9" placeholder="IP PC3" style="width:120px;margin-top:6px;padding:4px;border-radius:4px;border:1px solid #ccc;">
      <div class="status9" style="margin-top:6px;"></div>
    </div>
  </div>
  <div style="text-align:center;margin-top:8px;"><button id="sendMsg9" class="small">Kirim Pesan PC1 → PC2</button><div id="message9" style="margin-top:8px;font-weight:700;"></div></div>
`;
dndContainer.appendChild(soal9);

(function(){
  const devices9 = Array.from(soal9.querySelectorAll('.device9'));
  const sendMsg9 = soal9.querySelector('#sendMsg9');
  const message9 = soal9.querySelector('#message9');

  function getIps9(){
    const ips = {};
    devices9.forEach(d=>{
      const el = d.querySelector('.ip9');
      ips[d.dataset.id] = el ? el.value.trim() : '';
    });
    return ips;
  }

  // ===============================
  // VALIDASI IPv4
  // ===============================
  function validateIP(ip){
    const regex =
      /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/;
    return regex.test(ip);
  }

  // ===============================
  // AMBIL NETWORK PREFIX (xxx.xxx.xxx)
  // ===============================
  function getNetworkPrefix(ip){
    return ip.split('.').slice(0,3).join('.');
  }

  sendMsg9.addEventListener('click', ()=>{
    const ips = getIps9();
    let ok = true;

    // ===============================
    // VALIDASI FORMAT IP
    // ===============================
    devices9.forEach(d=>{
      const st = d.querySelector('.status9');
      const ip = ips[d.dataset.id];

      if(!validateIP(ip)){
        st.textContent = 'IP salah!';
        st.style.color = '#e74c3c';
        ok = false;
      } else {
        st.textContent = 'Format OK';
        st.style.color = '#27ae60';
      }
    });

    if(!ok){
      message9.textContent = 'Periksa format IP!';
      message9.style.color = '#e74c3c';
      soal9.dataset.correct = 'false';
      return;
    }

    // ===============================
    // VALIDASI SATU NETWORK DENGAN ROUTER
    // ===============================
    const routerNet = getNetworkPrefix(ips.router);

    ['pc1','pc2','pc3'].forEach(id=>{
      const device = soal9.querySelector(`[data-id="${id}"]`);
      const st = device.querySelector('.status9');

      if(getNetworkPrefix(ips[id]) !== routerNet){
        st.textContent = 'Beda network!';
        st.style.color = '#e74c3c';
        ok = false;
      }
    });

    if(!ok){
      message9.textContent =
        'PC harus satu network dengan Router (xxx.xxx.xxx.X)';
      message9.style.color = '#e74c3c';
      soal9.dataset.correct = 'false';
      return;
    }

    // ===============================
    // BERHASIL
    // ===============================
    message9.textContent =
      `Pesan dari ${ips.pc1} ke ${ips.pc2} berhasil terkirim melalui Router ${ips.router}`;
    message9.style.color = '#27ae60';
    soal9.dataset.correct = 'true';
  });
})(); 

const soal10 = document.createElement('div');
soal10.className='question';
soal10.dataset.qtype='ipAssign';
soal10.dataset.correct='false';
soal10.innerHTML = `
  <h3 style="font-weight:600">10. Konfigurasi IP Jaringan</h3>
  <p>Seret IP ke perangkat yang sesuai.</p>
  <div id="soal10-wrapper" style="display:flex;justify-content:center;gap:40px;margin-top:12px;position:relative;min-height:200px;">
    <div class="device10" data-id="pc1" style="text-align:center;border:2px solid #ccc;padding:15px;border-radius:10px;background:#e3f2fd;">
      <img src="img/pc1.jpeg" alt="PC1" class="device-img" style="width:70px;height:70px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">PC1</div>
      <div class="status10" style="width:20px;height:20px;border-radius:50%;background:#ccc;margin:8px auto;"></div>
    </div>
    <div class="device10" data-id="router" style="text-align:center;border:2px solid #ccc;padding:15px;border-radius:10px;background:#f3e5f5;">
      <img src="img/router.jpg" alt="Router" class="device-img" style="width:70px;height:70px;margin:0 auto;">
      <div style="font-weight:bold;margin-top:5px;">Router</div>
      <div class="status10" style="width:20px;height:20px;border-radius:50%;background:#ccc;margin:8px auto;"></div>
    </div>
  </div>
  <div id="ips10" style="display:flex;justify-content:center;gap:18px;margin-top:20px;">
    <div class="ip-item10" data-id="pc1" draggable="true" style="padding:10px 15px;background:#fff;border:2px solid #2196f3;cursor:grab;border-radius:8px;font-weight:bold;box-shadow:0 2px 4px rgba(0,0,0,0.1);">192.168.1.10</div>
    <div class="ip-item10" data-id="router" draggable="true" style="padding:10px 15px;background:#fff;border:2px solid #9c27b0;cursor:grab;border-radius:8px;font-weight:bold;box-shadow:0 2px 4px rgba(0,0,0,0.1);">192.168.1.1</div>
  </div>
  <div id="soal10-result" style="margin-top:12px;font-weight:700;text-align:center;"></div>
`;
dndContainer.appendChild(soal10);

(function(){
  const devices10 = Array.from(soal10.querySelectorAll('.device10'));
  const ips10 = Array.from(soal10.querySelectorAll('.ip-item10'));
  const result10 = soal10.querySelector('#soal10-result');
  const wrapper10 = soal10.querySelector('#soal10-wrapper');
   
  ips10.forEach(ip=>{
    ip.addEventListener('dragstart', e=>{
      e.dataTransfer.setData('text/plain', ip.dataset.id);
      ip.style.opacity = '0.5';
    });
    ip.addEventListener('dragend', ()=>{
      ip.style.opacity = '1';
    });
  });
   
  devices10.forEach(dev=>{
    dev.addEventListener('dragover', e=>{
      e.preventDefault();
      dev.style.borderColor = '#27ae60';
    });
    dev.addEventListener('dragleave', ()=>{
      dev.style.borderColor = '#ccc';
    });
    dev.addEventListener('drop', e=>{
      e.preventDefault();
      dev.style.borderColor = '#ccc';
      const id = e.dataTransfer.getData('text/plain');
      const status = dev.querySelector('.status10');
      if(id === dev.dataset.id) {
        status.style.background = '#27ae60';
        dev.style.background = dev.dataset.id === 'pc1' ? '#c8e6c9' : '#e1bee7';
      } else {
        status.style.background = '#e74c3c';
        dev.style.background = dev.dataset.id === 'pc1' ? '#ffcdd2' : '#f8bbd0';
      }
      
      const ipEl = ips10.find(x=>x.dataset.id===id);
      if(!ipEl) return;
      ipEl.style.position='absolute';
      const rect = dev.getBoundingClientRect();
      const wrapRect = wrapper10.getBoundingClientRect();
      ipEl.style.top = (rect.bottom - wrapRect.top + 10) + 'px';
      ipEl.style.left = (rect.left - wrapRect.left + rect.width/2 - 60) + 'px';
      ipEl.style.cursor = 'default';
      ipEl.draggable = false;
      wrapper10.appendChild(ipEl);
      checkSoal10();
    });
  });
   
  function checkSoal10(){
    const greens = devices10.filter(d=> d.querySelector('.status10').style.background === 'rgb(39, 174, 96)').length;
    if(greens === devices10.length){ 
      result10.textContent='Semua perangkat terkonfigurasi IP dengan benar!'; 
      result10.style.color='#27ae60'; 
      soal10.dataset.correct='true'; 
    } else { 
      result10.textContent=`Jawaban benar: ${greens}/${devices10.length}`; 
      result10.style.color='#e67e22'; 
      soal10.dataset.correct='false';
    }
  }
})();

// === LOGIKA UTAMA ===
const quizView = document.getElementById('quizView');
const resultView = document.getElementById('resultView');
const finishBtn = document.getElementById('finishBtn');
const skorSpan = document.getElementById('skor');
const detailBody = document.getElementById('resultBody');
// Ambil nama dari input hidden
const studentNameInput = document.getElementById('studentNameHidden');
const dbSaveStatus = document.getElementById('db-save-status');

finishBtn.addEventListener('click', ()=>{
  // Hitung Skor Hanya untuk Interaktif
  let dndScore = 0;
  const qNodes = Array.from(dndContainer.querySelectorAll('.question'));
  qNodes.forEach(qn=>{
    if(qn.dataset.correct === 'true') dndScore++;
  });

  const totalPossible = qNodes.length; // Hanya soal interaktif (10)
  const totalScore = dndScore;
  const studentName = studentNameInput.value.trim();

  // Update tampilan hasil
  document.getElementById('infoTotal').innerText = `${totalPossible} soal`;
  document.getElementById('infoName').innerText = studentName;
  document.getElementById('infoScore').innerText = `${totalScore} / ${totalPossible}`;
   
  const now = new Date();
  const dateStr = now.toLocaleDateString('id-ID', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
  document.getElementById('infoDate').innerText = dateStr;

  detailBody.innerHTML = '';

  // Generate Detail Hasil (Hanya Interaktif)
  qNodes.forEach((qn, idx)=>{
    const labelEl = qn.querySelector('h3') || qn.querySelector('p') || qn;
    const label = labelEl ? labelEl.innerText : `Soal Interaktif ${idx+1}`;
    const ok = qn.dataset.correct === 'true';
    const row = document.createElement('tr');
    const no = document.createElement('td'); no.className='center'; no.innerText = idx + 1;
    const soal = document.createElement('td'); soal.innerText = label;
    const res = document.createElement('td'); res.className='center';
    res.innerText = ok ? 'Benar' : 'Salah';
    res.style.color = ok ? '#27ae60' : '#e74c3c';
    res.style.fontWeight = 'bold';
    row.append(no,soal,res);
    detailBody.appendChild(row);
  });

  quizView.style.display='none';
  resultView.style.display='block';
  skorSpan.innerText = totalScore;
  window.scrollTo(0,0);

  const percentage = (totalScore / totalPossible) * 100;
  let message = '';
  if(percentage >= 90) message = 'Luar biasa! Nilai sempurna!';
  else if(percentage >= 80) message = 'Sangat baik! Pertahankan!';
  else if(percentage >= 70) message = 'Bagus! Terus tingkatkan!';
  else if(percentage >= 60) message = 'Cukup baik, terus belajar!';
  else message = 'Perlu belajar lebih giat lagi!';

  const congratsDiv = document.createElement('div');
  congratsDiv.className = 'congratulations';
  congratsDiv.innerHTML = message;
  const firstTable = resultView.querySelector('table');
  let existingCongrats = resultView.querySelector('.congratulations');
  if(existingCongrats) existingCongrats.remove();
  resultView.insertBefore(congratsDiv, firstTable);
   
  // =======================================================
  // Logika kirim skor ke server (PHP/MySQL)
  // =======================================================
   
  dbSaveStatus.textContent = 'Menyimpan hasil ke database...';
  dbSaveStatus.style.color = '#3498db';
   
  const formData = new FormData();
  formData.append('action', 'save_score');
  formData.append('nama_siswa', studentName);
  formData.append('total_soal', totalPossible);
  formData.append('jawaban_benar', totalScore);

  fetch('quiz.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      dbSaveStatus.textContent = data.message;
      dbSaveStatus.style.color = '#27ae60';
    } else {
      dbSaveStatus.textContent = data.message || 'Gagal menyimpan hasil kuis!';
      dbSaveStatus.style.color = '#e74c3c';
    }
  })
  .catch(error => {
    console.error('Error saat menyimpan skor:', error);
    dbSaveStatus.textContent = 'Error koneksi: Gagal berkomunikasi dengan server.';
    dbSaveStatus.style.color = '#e74c3c';
  });
});

function updateLiveScore(){
  const qNodes = Array.from(dndContainer.querySelectorAll('.question'));
  let dndCorrect = qNodes.filter(qn => qn.dataset.correct === 'true').length;
  skorSpan.textContent = dndCorrect;
}
setInterval(updateLiveScore, 500);


// ================= BACK HOME =================
document.getElementById('backHomeBtn').addEventListener('click', ()=>{
  if(confirm('🔄 Yakin ingin kembali ke beranda')) {
    window.location.href = 'dashboard_siswa.php';
  }
});

document.getElementById('viewTutorialBtn').addEventListener('click', ()=>{
  if(!confirm('📖 Yakin ingin melihat tutorial?')) return;
  window.location.href = 'lihat_cara_mengerjakan.html';
});

// ====== CEK GAMBAR YANG GAGAL DIMUAT ======
window.addEventListener('load', () => {
  document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', () => {
      console.warn('⚠️ Gambar tidak ditemukan:', img.src);
      img.style.border = '3px dashed red';
      img.title = '❌ Gambar tidak ditemukan';
    });
  });
});
</script>
<?php endif; ?>
</body>
</html>