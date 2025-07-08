<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_name = $_SESSION['nama_lengkap'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? 'admin@gmail.com';

function safe($v) {
    return ($v===null||$v===''||$v===false) ? '-' : htmlspecialchars($v);
}

// Ambil semua kegiatan selesai
$kegiatan = $conn->query("SELECT k.id, k.nama_kegiatan, k.tanggal
    FROM kegiatan k
    JOIN assignments a ON a.kegiatan_id = k.id
    WHERE a.status = 'selesai'
    GROUP BY k.id
    ORDER BY k.tanggal DESC")->fetch_all(MYSQLI_ASSOC);

// Proses simpan/update catatan umum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kegiatan_id'], $_POST['catatan_umum'])) {
    $kid = intval($_POST['kegiatan_id']);
    $catatan_umum = $conn->real_escape_string($_POST['catatan_umum']);
    $admin_id = $_SESSION['user_id'];

    // Hitung ulang nilai akhir
    $nilai_total = 0; $count = 0;
    $relawan = $conn->query("SELECT e.kedisiplinan, e.komunikasi, e.kerjasama, e.tanggung_jawab, e.nilai AS nilai_admin
        FROM evaluasi e
        WHERE e.id_kegiatan = $kid AND (e.kedisiplinan IS NOT NULL OR e.nilai IS NOT NULL)");
    while ($relawan && $r = $relawan->fetch_assoc()) {
        $kedisiplinan = (int)$r['kedisiplinan'];
        $komunikasi = (int)$r['komunikasi'];
        $kerjasama = (int)$r['kerjasama'];
        $tanggung_jawab = (int)$r['tanggung_jawab'];
        $nilai_admin = (float)$r['nilai_admin'];

        if ($kedisiplinan || $komunikasi || $kerjasama || $tanggung_jawab || $nilai_admin) {
            $skor = $kedisiplinan + $komunikasi + $kerjasama + $tanggung_jawab + $nilai_admin;
            $final_score = ($skor / 25) * 100;
            $nilai_total += $final_score;
            $count++;
        }
    }
    $nilai_akhir = $count ? round($nilai_total / $count, 2) : 0;

    // Cek dan simpan/update
    $cek = $conn->query("SELECT id FROM evaluasi_keseluruhan WHERE id_kegiatan=$kid");
    if ($cek && $cek->num_rows > 0) {
        $conn->query("UPDATE evaluasi_keseluruhan 
            SET catatan_umum='$catatan_umum', tanggal_rekap=CURDATE(), nilai_akhir=$nilai_akhir 
            WHERE id_kegiatan=$kid");
        $msg = "Catatan umum dan nilai akhir berhasil diperbarui.";
    } else {
        $conn->query("INSERT INTO evaluasi_keseluruhan (id_kegiatan, catatan_umum, tanggal_rekap, nilai_akhir) 
            VALUES ($kid, '$catatan_umum', CURDATE(), $nilai_akhir)");
        $msg = "Catatan umum dan nilai akhir berhasil disimpan.";
    }

    header("Location: evaluasi-keseluruhan.php?kegiatan_id=$kid&msg=" . urlencode($msg));
    exit;
}


// Data evaluasi
$selected_id = intval($_GET['kegiatan_id'] ?? 0);
$stat = [
    'total_relawan' => 0,
    'avg_nilai' => 0,
    'volunteers' => [],
    'catatan_umum' => '',
];

if ($selected_id) {
    $relawan = $conn->query("SELECT a.id AS id_relawan, a.name,
    e.kedisiplinan, e.komunikasi, e.kerjasama, e.tanggung_jawab,
    e.nilai AS nilai_admin, e.catatan
    FROM assignment_relawan ar
    JOIN assignments ass ON ass.id = ar.assignment_id
    JOIN applicants a ON ar.relawan_id = a.id
    LEFT JOIN evaluasi e ON e.id_kegiatan = ass.kegiatan_id AND e.id_relawan = a.id
    WHERE ass.kegiatan_id = $selected_id");



    $nilai_total = 0; $count = 0;

    while ($relawan && $row = $relawan->fetch_assoc()) {
        $kedisiplinan = (int)$row['kedisiplinan'];
        $komunikasi = (int)$row['komunikasi'];
        $kerjasama = (int)$row['kerjasama'];
        $tanggung_jawab = (int)$row['tanggung_jawab'];
        $nilai_admin = (float)$row['nilai_admin'];

        if ($nilai_admin > 0 || $kedisiplinan > 0 || $komunikasi > 0 || $kerjasama > 0 || $tanggung_jawab > 0) {
            $skor = $kedisiplinan + $komunikasi + $kerjasama + $tanggung_jawab + $nilai_admin;
            $final_score = ($skor / 25) * 100;

            $stat['volunteers'][] = [
                'name' => $row['name'],
                'nilai' => round($final_score, 2),
                'catatan' => $row['catatan'] ?? ''
            ];

            $nilai_total += $final_score;
            $count++;
        }
    }

    $stat['total_relawan'] = $count;
    $stat['avg_nilai'] = $count ? round($nilai_total / $count, 2) : 0;

    $cat = $conn->query("SELECT catatan_umum FROM evaluasi_keseluruhan WHERE id_kegiatan=$selected_id");
    $stat['catatan_umum'] = ($cat && $row = $cat->fetch_assoc()) ? $row['catatan_umum'] : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Keseluruhan Kegiatan - CivicaCare Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-red: #C70000;
            --bg-light: #F8F9FA;
            --text-dark: #212529;
            --text-light: #f8f9fa;
            --primary-color: #6A38C2;
            --primary-light: #F0EBf9;
            --border-color: #dee2e6;
        }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); min-height: 100vh; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--primary-red); color: var(--text-light); padding: 20px; display: flex; flex-direction: column; transition: width 0.3s ease;}
        .sidebar-header { display: flex; align-items: center; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 20px; }
        .sidebar-header h2 { margin: 0; font-size: 1.5rem; }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li a { display: flex; align-items: center; gap: 15px; padding: 15px 10px; border-radius: 8px; color: var(--text-light); text-decoration: none; font-weight: 500; transition: background-color 0.3s; }
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color: rgba(0, 0, 0, 0.2); }
        .sidebar-nav li a .nav-icon { font-size: 1.2rem; width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar-footer { margin-top: auto; }
        .sidebar.collapsed {width: 80px;}
        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-nav span {display: none;}
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .main-content .main-header { display: flex; justify-content: flex-end; align-items: center; padding: 20px 40px; background-color: #fff; position: relative; overflow: hidden; }
        .header-shape { position: absolute; top: 0; right: 0; width: 350px; height: 100%; background-color: var(--primary-red); clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%); }
        .user-profile { display: flex; align-items: center; gap: 15px; position: relative; z-index: 1; }
        .user-avatar { width: 45px; height: 45px; border-radius: 50%; background-color: #e9ecef; display: flex; justify-content: center; align-items: center; color: #495057; font-size: 1.5rem; }
        .user-info { color: var(--text-light); text-align: right; }
        .user-info .user-name { font-weight: 600; }
        .user-info .user-email { font-size: 0.8rem; opacity: 0.9; }
        .content-area { padding: 40px; flex-grow: 1; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .section-header h2 { font-size: 2rem; margin: 0; color: var(--primary-red); }
        .filter-section { background-color: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .filter-section label { display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); flex-shrink: 0; }
        .filter-section select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; flex-grow: 1; min-width: 200px; }
        .evaluation-details-card { background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 30px; }
        .evaluation-details-card h3 { font-size: 1.5rem; color: var(--primary-red); margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .detail-item strong { display: block; color: var(--text-dark); margin-bottom: 5px; }
        .detail-item span { color: #555; }
        .detail-item ul { list-style: none; padding: 0; margin: 0; color: #555; }
        .detail-item li { margin-bottom: 5px; }
        .success-chart { width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(var(--chart-color, #e9ecef) calc(var(--percentage) * 1%), #e9ecef calc(var(--percentage) * 1%)); display: flex; justify-content: center; align-items: center; position: relative; transition: background 0.5s ease; margin: 0 auto 15px auto;}
        .success-chart::before { content: ''; position: absolute; width: 80%; height: 80%; background: #fff; border-radius: 50%; }
        .chart-percentage { position: relative; font-size: 1.8rem; font-weight: 600; color: var(--text-dark); }
        .overall-success-badge { padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 1rem; text-align: center; min-width: 120px; }
        .badge-success { background-color: #d4edda; color: #155724; border: 1px solid #28a745; }
        .badge-medium { background-color: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .badge-fail { background-color: #f8d7da; color: #721c24; border: 1px solid #dc3545; }
        textarea.conclusion-textarea { width: calc(100% - 24px); padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; min-height: 100px; resize: vertical; margin-top: 10px; }
        .save-overall-evaluation-button { background-color: var(--primary-red); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background-color 0.3s; margin-top: 20px; }
        .save-overall-evaluation-button:hover { background-color: #a00000; }
        .empty-state-message { text-align: center; padding: 30px; color: #6c757d; font-style: italic; }
        #menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 10px;
            /* Space between toggle and title */
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <button id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
                <h2>Menu</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard-admin.php"><i class="fa-solid fa-house nav-icon"></i><span>Dashboard</span></a></li>
                    <li><a href="daftar-relawan.php"><i class="fa-solid fa-table-columns nav-icon"></i><span>Daftar Relawan</span></a></li>
                    <li><a href="pengajuan.php"><i class="fa-solid fa-file-import nav-icon"></i><span>Pengajuan</span></a></li>
                    <li><a href="kegiatan.php"><i class="fa-solid fa-people-group nav-icon"></i><span>Kegiatan</span></a></li>
                    <li><a href="penjadwalan.php"><i class="fa-solid fa-calendar-days nav-icon"></i><span>Penjadwalan</span></a></li>
                    <li class="active"><a href="evaluasi-main.php"><i class="fa-solid fa-pen-to-square nav-icon"></i><span>Evaluasi</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket nav-icon"></i><span>Logout</span></a></li>
                    </ul>
                </nav>
            </div>
        </aside>
        <!-- Konten Utama -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-shape"></div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?= safe($admin_name) ?></div>
                        <div class="user-email"><?= safe($admin_email) ?></div>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </header>
            <section class="content-area">
                <div class="section-header">
                    <h2>Evaluasi Keseluruhan Kegiatan</h2>
                </div>
                <form method="get" class="filter-section" style="margin-bottom:0;">
                    <div style="flex:1;">
                        <label for="kegiatan_id">Pilih Kegiatan:</label>
                        <select name="kegiatan_id" id="kegiatan_id" onchange="this.form.submit()">
                            <option value="">-- Pilih Kegiatan --</option>
                            <?php foreach ($kegiatan as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $selected_id==$k['id']?'selected':'' ?>>
                                    <?= safe($k['nama_kegiatan']) ?> (<?= date('d-m-Y', strtotime($k['tanggal'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <?php if ($selected_id): ?>
                <div class="evaluation-details-card">
                    <h3>Detail Evaluasi Kegiatan <span><?= safe($kegiatan[array_search($selected_id, array_column($kegiatan, 'id'))]['nama_kegiatan'] ?? '') ?></span></h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <strong>Relawan Terlibat & Nilai:</strong>
                            <ul>
                                <?php foreach ($stat['volunteers'] as $v): ?>
                                    <li><?= safe($v['name']) ?> (Nilai: <?= safe($v['nilai']) ?><?= $v['catatan'] ? ', Catatan: '.safe($v['catatan']) : '' ?>)</li>
                                <?php endforeach; if (!$stat['volunteers']) echo '<li>-</li>'; ?>
                            </ul>
                        </div>
                        <div class="detail-item">
                            <strong>Total Relawan Dievaluasi:</strong>
                            <span><?= $stat['total_relawan'] ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Performa Rata-rata Relawan:</strong>
                            <span>
                                <?php
                                if ($stat['avg_nilai']) {
                                    $percent = round($stat['avg_nilai']);
                                    echo "$percent% ($stat[avg_nilai]/100)";
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php
                    // Hitung persentase keberhasilan
                    $success_percent = $stat['avg_nilai'] ? round($stat['avg_nilai']) : 0;
                    $badge = 'badge-fail'; $indicator = 'Gagal';
                    if ($success_percent >= 75) { $badge = 'badge-success'; $indicator = 'Berhasil'; }
                    else if ($success_percent >= 50) { $badge = 'badge-medium'; $indicator = 'Normal'; }
                    ?>
                    <div style="display:flex;align-items:center;gap:40px;margin:30px 0;">
                        <div>
                            <div class="success-chart" style="--percentage:<?= $success_percent ?>;--chart-color:<?= $badge=='badge-success'?'#28a745':($badge=='badge-medium'?'#ffc107':'#dc3545') ?>;">
                                <span class="chart-percentage"><?= $success_percent ?>%</span>
                            </div>
                            <div class="overall-success-badge <?= $badge ?>"><?= $indicator ?></div>
                        </div>
                        <div style="flex:1;">
                            <form method="post">
                                <input type="hidden" name="kegiatan_id" value="<?= $selected_id ?>">
                                <label for="catatan_umum">Catatan Umum Evaluasi:</label>
                                <textarea name="catatan_umum" class="conclusion-textarea" required><?= safe($stat['catatan_umum']) ?></textarea>
                                <br>
                                <button type="submit" class="save-overall-evaluation-button">
                                    Simpan Catatan Umum
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($_GET['kegiatan_id'])): ?>
                    <div class="empty-state-message">Kegiatan tidak ditemukan atau belum dievaluasi.</div>
                <?php else: ?>
                    <div class="empty-state-message">Pilih kegiatan untuk melihat detail evaluasi.</div>
                <?php endif; ?>
                <?php if (isset($_GET['msg'])): ?>
                    <script>alert("<?= safe($_GET['msg']) ?>");</script>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>