<?php
require_once 'config.php';
requireGuru();

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

// 1. Ambil data siswa
$siswa_query = "SELECT * FROM users WHERE role = 'siswa' ORDER BY full_name";
$siswa_result = mysqli_query($conn, $siswa_query);

// 2. Inisialisasi PDF (Landscape, A4)
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set informasi dokumen
$pdf->SetCreator('LMS Jaringan Komputer');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Rekap Nilai Siswa');

// Hilangkan header/footer default
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set Margin
$pdf->SetMargins(13.5, 15, 13.5);
$pdf->AddPage();

// ================= HEADER =================
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'REKAP NILAI SISWA', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'MATA PELAJARAN JARINGAN KOMPUTER', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, 'SMK AL MUHAMMAD CEPU', 0, 1, 'C');
$pdf->Ln(5);

$bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$tanggal_indo = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');

// ================= SET LEBAR KOLOM =================
$w_no = 12;
$w_nama = 88;
$w_score = 35;
$w_total = 35;
$w_naik = 30;

// ================= HEADER TABEL =================
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(17, 153, 142);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell($w_no, 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w_nama, 10, 'Nama Siswa', 1, 0, 'C', true);
$pdf->Cell($w_score, 10, 'Pre-Test', 1, 0, 'C', true);
$pdf->Cell($w_score, 10, 'Quiz', 1, 0, 'C', true);
$pdf->Cell($w_score, 10, 'Post-Test', 1, 0, 'C', true);
$pdf->Cell($w_total, 10, 'Total', 1, 0, 'C', true);
$pdf->Cell($w_naik, 10, 'Peningkatan', 1, 1, 'C', true);

// ================= ISI TABEL =================
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);

$no = 1;
$total_nilai_kelas = 0;
$total_peningkatan_kelas = 0;
$count_peningkatan = 0;

while ($siswa = mysqli_fetch_assoc($siswa_result)) {

    $user_id = $siswa['user_id'];

    $pretest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM nilai_pretest WHERE user_id = $user_id"));
    $pretest_nilai = $pretest ? $pretest['nilai'] : null;

    $quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM nilai_quiz WHERE user_id = $user_id"));
    $quiz_nilai = $quiz ? $quiz['nilai'] : null;

    $posttest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nilai FROM nilai_posttest WHERE user_id = $user_id"));
    $posttest_nilai = $posttest ? $posttest['nilai'] : null;

    // Hitung Total
    $nilai_array = array_filter([$pretest_nilai, $quiz_nilai, $posttest_nilai], function($v) {
        return $v !== null;
    });
    $total_nilai = array_sum($nilai_array);
    $total_nilai_kelas += $total_nilai;

    // Hitung Peningkatan
    if ($pretest_nilai !== null && $posttest_nilai !== null) {
        $peningkatan = $posttest_nilai - $pretest_nilai;
        $total_peningkatan_kelas += $peningkatan;
        $count_peningkatan++;
    } else {
        $peningkatan = null;
    }

    $fill = ($no % 2 == 0);
    $pdf->SetFillColor(245, 245, 245);

    $pdf->Cell($w_no, 8, $no, 1, 0, 'C', $fill);
    $pdf->Cell($w_nama, 8, ' ' . $siswa['full_name'], 1, 0, 'L', $fill);

    $pdf->Cell($w_score, 8, $pretest_nilai !== null ? number_format($pretest_nilai, 2) : '-', 1, 0, 'C', $fill);
    $pdf->Cell($w_score, 8, $quiz_nilai !== null ? number_format($quiz_nilai, 2) : '-', 1, 0, 'C', $fill);
    $pdf->Cell($w_score, 8, $posttest_nilai !== null ? number_format($posttest_nilai, 2) : '-', 1, 0, 'C', $fill);

    $pdf->Cell($w_total, 8, $total_nilai > 0 ? number_format($total_nilai, 2) : '-', 1, 0, 'C', $fill);

    $pdf->Cell(
        $w_naik,
        8,
        $peningkatan !== null ? ($peningkatan > 0 ? '+' : '') . number_format($peningkatan, 2) : '-',
        1,
        1,
        'C',
        $fill
    );

    $no++;
}

// ================= SUMMARY =================
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);

$rata_peningkatan = $count_peningkatan > 0 ? $total_peningkatan_kelas / $count_peningkatan : 0;

$pdf->Cell(50, 6, 'Total Nilai Kelas', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->Cell(30, 6, number_format($total_nilai_kelas, 2), 0, 1, 'L');

$pdf->Cell(50, 6, 'Rata-rata Peningkatan Kelas', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->Cell(30, 6, number_format($rata_peningkatan, 2), 0, 1, 'L');

$pdf->Cell(50, 6, 'Total Siswa', 0, 0, 'L');
$pdf->Cell(5, 6, ':', 0, 0, 'C');
$pdf->Cell(30, 6, mysqli_num_rows($siswa_result) . ' Siswa', 0, 1, 'L');

// ================= TANDA TANGAN =================
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 10);

$w_sign = 135;

$pdf->Cell($w_sign, 5, '', 0, 0, 'C');
$pdf->Cell($w_sign, 5, 'Bojonegoro, ' . $tanggal_indo, 0, 1, 'C');

$pdf->Cell($w_sign, 5, '', 0, 0, 'C');
$pdf->Cell($w_sign, 5, 'Kepala Sekolah,', 0, 1, 'C');

$pdf->Ln(20);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($w_sign, 5, '', 0, 0, 'C');
$pdf->Cell($w_sign, 5, 'Sutrisno, M.Pd', 0, 1, 'C');

// ================= OUTPUT =================
ob_end_clean();
$pdf->Output('Rekap_Nilai_TKJ_' . date('d-m-Y') . '.pdf', 'I');
?>
