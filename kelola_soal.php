<?php
require_once 'config.php';
requireGuru();

$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'pretest';

// Handle Delete
if (isset($_GET['delete'])) {
    $soal_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM test_soal WHERE soal_id = $soal_id");
    header("Location: kelola_soal.php?tipe=$tipe&success=delete");
    exit();
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soal_id = isset($_POST['soal_id']) ? (int)$_POST['soal_id'] : 0;
    $pertanyaan = clean_input($_POST['pertanyaan']);
    $pilihan_a = clean_input($_POST['pilihan_a']);
    $pilihan_b = clean_input($_POST['pilihan_b']);
    $pilihan_c = clean_input($_POST['pilihan_c']);
    $pilihan_d = clean_input($_POST['pilihan_d']);
    $jawaban_benar = clean_input($_POST['jawaban_benar']);
    
    if ($soal_id > 0) {
        // Update (Urutan tidak diubah agar posisi tetap)
        $query = "UPDATE test_soal SET 
                  pertanyaan = '$pertanyaan',
                  pilihan_a = '$pilihan_a',
                  pilihan_b = '$pilihan_b',
                  pilihan_c = '$pilihan_c',
                  pilihan_d = '$pilihan_d',
                  jawaban_benar = '$jawaban_benar'
                  WHERE soal_id = $soal_id";
        mysqli_query($conn, $query);
        header("Location: kelola_soal.php?tipe=$tipe&success=edit");
    } else {
        // Otomatis cari urutan terakhir
        $result_urutan = mysqli_query($conn, "SELECT MAX(urutan) as max_u FROM test_soal WHERE tipe_test = '$tipe'");
        $row_urutan = mysqli_fetch_assoc($result_urutan);
        $urutan_baru = $row_urutan['max_u'] + 1;

        // Insert dengan urutan otomatis
        $query = "INSERT INTO test_soal (tipe_test, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, urutan) 
                  VALUES ('$tipe', '$pertanyaan', '$pilihan_a', '$pilihan_b', '$pilihan_c', '$pilihan_d', '$jawaban_benar', $urutan_baru)";
        mysqli_query($conn, $query);
        header("Location: kelola_soal.php?tipe=$tipe&success=add");
    }
    exit();
}

// Get edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM test_soal WHERE soal_id = $edit_id"));
}

// Get all soal
$soal_query = "SELECT * FROM test_soal WHERE tipe_test = '$tipe' ORDER BY urutan ASC";
$soal_result = mysqli_query($conn, $soal_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal - LMS Jaringan Komputer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn-back { background: rgba(255,255,255,0.2); color: white; padding: 8px 20px; border: 1px solid white; border-radius: 5px; text-decoration: none; transition: all 0.3s; }
        .btn-back:hover { background: white; color: #11998e; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; }
        .tab { padding: 12px 30px; background: white; border-radius: 10px; cursor: pointer; font-weight: 600; text-decoration: none; color: #666; transition: 0.3s; }
        .tab.active { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn-submit { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn-cancel { background: #6c757d; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; display: inline-block; margin-left: 10px; }
        .list-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .soal-item { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid #11998e; }
        .soal-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .soal-number { font-weight: bold; color: #11998e; }
        .soal-actions a { text-decoration: none; padding: 5px 12px; border-radius: 4px; font-size: 13px; color: white; margin-left: 5px; }
        .btn-edit { background: #ffc107; }
        .btn-delete { background: #dc3545; }
        .correct-answer { color: #28a745; font-weight: bold; }
        .soal-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📝 Kelola Soal <?php echo ucfirst($tipe); ?></h1>
        <a href="dashboard_guru.php" class="btn-back">← Kembali</a>
    </nav>
    
    <div class="container">
        <div class="tabs">
            <a href="kelola_soal.php?tipe=pretest" class="tab <?php echo $tipe === 'pretest' ? 'active' : ''; ?>">Pre-Test</a>
            <a href="kelola_soal.php?tipe=posttest" class="tab <?php echo $tipe === 'posttest' ? 'active' : ''; ?>">Post-Test</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['success'] === 'add') echo '✓ Soal berhasil ditambahkan ke urutan terakhir!';
            elseif ($_GET['success'] === 'edit') echo '✓ Perubahan soal berhasil disimpan!';
            elseif ($_GET['success'] === 'delete') echo '✓ Soal berhasil dihapus!';
            ?>
        </div>
        <?php endif; ?>
        
        <div class="form-card">
            <h3><?php echo $edit_data ? 'Edit Soal #' . $edit_data['urutan'] : 'Tambah Soal Baru'; ?></h3>
            
            <form method="POST" action="">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="soal_id" value="<?php echo $edit_data['soal_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Pertanyaan</label>
                    <textarea name="pertanyaan" required placeholder="Tuliskan soal di sini..."><?php echo $edit_data ? $edit_data['pertanyaan'] : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilihan A</label>
                        <input type="text" name="pilihan_a" value="<?php echo $edit_data ? $edit_data['pilihan_a'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pilihan B</label>
                        <input type="text" name="pilihan_b" value="<?php echo $edit_data ? $edit_data['pilihan_b'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilihan C</label>
                        <input type="text" name="pilihan_c" value="<?php echo $edit_data ? $edit_data['pilihan_c'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Pilihan D</label>
                        <input type="text" name="pilihan_d" value="<?php echo $edit_data ? $edit_data['pilihan_d'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group" style="max-width: 300px;">
                    <label>Jawaban Benar</label>
                    <select name="jawaban_benar" required>
                        <option value="">-- Pilih Kunci Jawaban --</option>
                        <option value="A" <?php echo ($edit_data && $edit_data['jawaban_benar'] === 'A') ? 'selected' : ''; ?>>Pilihan A</option>
                        <option value="B" <?php echo ($edit_data && $edit_data['jawaban_benar'] === 'B') ? 'selected' : ''; ?>>Pilihan B</option>
                        <option value="C" <?php echo ($edit_data && $edit_data['jawaban_benar'] === 'C') ? 'selected' : ''; ?>>Pilihan C</option>
                        <option value="D" <?php echo ($edit_data && $edit_data['jawaban_benar'] === 'D') ? 'selected' : ''; ?>>Pilihan D</option>
                    </select>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-submit">
                        <?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah Soal'; ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="kelola_soal.php?tipe=<?php echo $tipe; ?>" class="btn-cancel">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="list-card">
            <h3>Daftar Soal Terdaftar (<?php echo mysqli_num_rows($soal_result); ?>)</h3>
            <br>
            <?php if (mysqli_num_rows($soal_result) > 0): ?>
                <?php while ($soal = mysqli_fetch_assoc($soal_result)): ?>
                <div class="soal-item">
                    <div class="soal-header">
                        <span class="soal-number">Soal Nomor <?php echo $soal['urutan']; ?></span>
                        <div class="soal-actions">
                            <a href="kelola_soal.php?tipe=<?php echo $tipe; ?>&edit=<?php echo $soal['soal_id']; ?>" class="btn-edit">Edit</a>
                            <a href="kelola_soal.php?tipe=<?php echo $tipe; ?>&delete=<?php echo $soal['soal_id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Hapus soal nomor <?php echo $soal['urutan']; ?>?')">Hapus</a>
                        </div>
                    </div>
                    <div class="soal-question">
                        <?php echo nl2br($soal['pertanyaan']); ?>
                    </div>
                    <div class="soal-options">
                        <div class="<?php echo $soal['jawaban_benar'] === 'A' ? 'correct-answer' : ''; ?>">A. <?php echo $soal['pilihan_a']; ?></div>
                        <div class="<?php echo $soal['jawaban_benar'] === 'B' ? 'correct-answer' : ''; ?>">B. <?php echo $soal['pilihan_b']; ?></div>
                        <div class="<?php echo $soal['jawaban_benar'] === 'C' ? 'correct-answer' : ''; ?>">C. <?php echo $soal['pilihan_c']; ?></div>
                        <div class="<?php echo $soal['jawaban_benar'] === 'D' ? 'correct-answer' : ''; ?>">D. <?php echo $soal['pilihan_d']; ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">Belum ada soal untuk kategori ini.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>