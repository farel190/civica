<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'relawan') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Helper
function safe($v) { return ($v===null||$v===''||$v===false)?'-':htmlspecialchars($v); }

// Ambil daftar kegiatan yang pernah diikuti relawan
$kegiatan = $conn->query("SELECT k.id, k.nama_kegiatan, k.tanggal
    FROM kegiatan k
    JOIN assignments a ON a.kegiatan_id = k.id
    JOIN assignment_relawan ar ON ar.assignment_id = a.id
    WHERE ar.relawan_id = $user_id
    GROUP BY k.id
    ORDER BY k.tanggal DESC")->fetch_all(MYSQLI_ASSOC);

// Ambil evaluasi pribadi relawan
$evaluasi = $conn->query("SELECT e.*, k.nama_kegiatan, k.tanggal, u.username AS evaluator_name, u.role AS evaluator_role
    FROM evaluasi e
    JOIN kegiatan k ON e.id_kegiatan = k.id
    LEFT JOIN users u ON e.evaluated_by = u.id
    WHERE e.id_relawan = $user_id
    ORDER BY k.tanggal DESC, e.tanggal_evaluasi DESC")->fetch_all(MYSQLI_ASSOC);

// Ambil evaluasi keseluruhan kegiatan yang diikuti relawan (id_relawan = 0)
$evaluasi_kegiatan = $conn->query("
    SELECT ek.id_kegiatan, ek.catatan_umum, ek.nilai_akhir, ek.tanggal_rekap, k.nama_kegiatan, k.tanggal
    FROM evaluasi_keseluruhan ek
    JOIN kegiatan k ON ek.id_kegiatan = k.id
    WHERE k.id IN (
        SELECT k2.id FROM kegiatan k2
        JOIN assignments a2 ON a2.kegiatan_id = k2.id
        JOIN assignment_relawan ar2 ON ar2.assignment_id = a2.id
        WHERE ar2.relawan_id = $user_id
    )
    ORDER BY k.tanggal DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Saya - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #6A38C2;
            --dark-text: #1a202c;
            --light-text: #555;
            --border-color: #e2e8f0;
            --success-color: #28a745;
            --medium-color: #ffc107;
            --fail-color: #dc3545;
        }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh; background-image: url('bg.png'); background-attachment: fixed; }
        main { flex-grow: 1; padding: 40px; }
        .detail-container { width: 100%; max-width: 900px; margin: 0 auto; padding: 0 20px; margin-top: 30px; }
        .detail-card { background-color: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); padding: 30px 40px; }
        .main-header { background-color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); position: sticky; top: 0; z-index: 1000; }
        .logo { display: flex; align-items: center; text-decoration: none; color: var(--dark-text); }
        .logo-icon { width: 32px; height: 32px; margin-right: 10px; }
        .logo h1 { font-family: 'Lora', serif; font-size: 1.8rem; margin: 0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 15px; }
        .card-header h2 { margin: 0; font-size: 1.8rem; color: var(--dark-text); }
        .filter-section { background-color: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); margin-bottom: 30px; display: flex; gap: 20px; align-items: center; }
        .filter-section label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--dark-text); }
        .filter-section select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background-color: var(--primary-color); color: #fff; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background-color: #f1f1f1; }
        .empty-table-message { text-align: center; padding: 30px; color: #6c757d; font-style: italic; }
        .score-display { font-weight: 600; color: var(--dark-text); }
        .overall-success-badge { padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-align: center; min-width: 80px; display: inline-block; }
        .badge-success { background-color: #d4edda; color: #155724; border: 1px solid var(--success-color); }
        .badge-medium { background-color: #fff3cd; color: #856404; border: 1px solid var(--medium-color); }
        .badge-fail { background-color: #f8d7da; color: #721c24; border: 1px solid var(--fail-color); }
        .back-button { background-color: transparent; color: var(--primary-color); padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid var(--primary-color); transition: all 0.3s; white-space: nowrap; }
        .overall-success-badge {
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
    min-width: 80px;
    display: inline-block;
}
.badge-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #28a745;
}
.badge-medium {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
}
.badge-fail {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #dc3545;
}

    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <a href="dashboard-relawan.php" class="back-button"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="logo">
            <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
            <h1>CivicaCare</h1>
        </div>
    </header>
    <!-- Konten Utama -->
    <main class="detail-container">
        <div class="detail-card">
            <div class="card-header">
                <h2>Riwayat Evaluasi</h2>
            </div>
            <!-- Filter Section -->
            <div class="filter-section">
                <label for="activity-filter-select">Filter berdasarkan Kegiatan:</label>
                <select id="activity-filter-select" onchange="filterEvaluasi()">
                    <option value="all">Semua Kegiatan</option>
                    <?php foreach ($kegiatan as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= safe($k['nama_kegiatan']) ?> (<?= date('d-m-Y', strtotime($k['tanggal'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Evaluasi Pribadi Table -->
            <h3>Evaluasi Pribadi</h3>
            <table class="data-table" id="evaluasi-pribadi-table">
                <thead>
                    <tr>
                        <th>Kegiatan</th>
                        <th>Kedisiplinan</th>
                        <th>Komunikasi</th>
                        <th>Kerja Sama</th>
                        <th>Tanggung Jawab</th>
                        <th>Rata-rata Skor</th>
                        <th>Dievaluasi Oleh</th>
                        <th>Umpan Balik</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($evaluasi): foreach ($evaluasi as $e): ?>
                        <tr data-kegiatan="<?= $e['id_kegiatan'] ?>">
                            <td><?= safe($e['nama_kegiatan']) ?> <br><small><?= date('d-m-Y', strtotime($e['tanggal'])) ?></small></td>
                            <td><?= safe($e['kedisiplinan'] ?? '-') ?></td>
                            <td><?= safe($e['komunikasi'] ?? '-') ?></td>
                            <td><?= safe($e['kerjasama'] ?? '-') ?></td>
                            <td><?= safe($e['tanggung_jawab'] ?? '-') ?></td>
                            <td>
                                <?php
                                $avg = ($e['kedisiplinan'] + $e['komunikasi'] + $e['kerjasama'] + $e['tanggung_jawab']) / 4;
                                echo number_format($avg, 2);
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($e['evaluator_role'] === 'koordinator') {
                                    echo 'Koordinator (' . safe($e['evaluator_name']) . ')';
                                } else {
                                    echo 'Admin';
                                }
                                ?>
                            </td>
                            <td><?= safe($e['catatan']) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="8" class="empty-table-message">Tidak ada evaluasi pribadi yang tersedia untuk Anda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Evaluasi Kegiatan Table -->
            <h3 style="margin-top:40px;">Evaluasi Keseluruhan Kegiatan</h3>
<table class="data-table" id="evaluasi-kegiatan-table">
    <thead>
        <tr>
            <th>Kegiatan</th>
            <th>Tanggal</th>
            <th>Nilai Akhir</th>
            <th>Indikator</th>
            <th>Catatan Umum</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($evaluasi_kegiatan): foreach ($evaluasi_kegiatan as $ek): ?>
            <?php
                $nilai = (float)$ek['nilai_akhir'];
                if ($nilai >= 75) {
                    $badge = 'badge-success';
                    $label = 'Berhasil';
                } elseif ($nilai >= 50) {
                    $badge = 'badge-medium';
                    $label = 'Normal';
                } else {
                    $badge = 'badge-fail';
                    $label = 'Gagal';
                }
            ?>
            <tr data-kegiatan="<?= $ek['id_kegiatan'] ?>">
                <td><?= safe($ek['nama_kegiatan']) ?></td>
                <td><?= date('d-m-Y', strtotime($ek['tanggal'])) ?></td>
                <td class="score-display"><?= round($nilai, 2) ?>%</td>
                <td><span class="overall-success-badge <?= $badge ?>"><?= $label ?></span></td>
                <td><?= safe($ek['catatan_umum']) ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="5" class="empty-table-message">Tidak ada evaluasi kegiatan yang tersedia untuk Anda.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

        </div>
    </main>
    <script>
        function filterEvaluasi() {
            var kegiatanId = document.getElementById('activity-filter-select').value;
            var rows = document.querySelectorAll('#evaluasi-pribadi-table tbody tr');
            rows.forEach(function(row) {
                if (kegiatanId === 'all' || row.getAttribute('data-kegiatan') === kegiatanId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            var rows2 = document.querySelectorAll('#evaluasi-kegiatan-table tbody tr');
            rows2.forEach(function(row) {
                if (kegiatanId === 'all' || row.getAttribute('data-kegiatan') === kegiatanId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>