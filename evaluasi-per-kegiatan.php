<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_name = $_SESSION['nama_lengkap'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? 'admin@gmail.com';

// Ambil semua kegiatan yang assignment-nya selesai
$kegiatan = $conn->query("SELECT k.id, k.nama_kegiatan, k.tanggal
    FROM kegiatan k
    JOIN assignments a ON a.kegiatan_id = k.id
    WHERE a.status = 'selesai'
    AND (
        SELECT COUNT(*) 
        FROM assignment_relawan ar
        WHERE ar.assignment_id = a.id
    ) > (
        SELECT COUNT(*) 
        FROM evaluasi e
        WHERE e.id_kegiatan = k.id AND e.nilai IS NOT NULL AND e.id_relawan != 0
    )
    GROUP BY k.id
    ORDER BY k.tanggal DESC")->fetch_all(MYSQLI_ASSOC);

// Helper
function safe($v) { return ($v===null||$v===''||$v===false)?'-':htmlspecialchars($v); }

// Proses simpan evaluasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kegiatan_id'], $_POST['nilai'], $_POST['catatan'], $_POST['relawan_id'])) {
    $kid = intval($_POST['kegiatan_id']);
    $rid = intval($_POST['relawan_id']);
    $nilai = intval($_POST['nilai']);
    $catatan = $conn->real_escape_string($_POST['catatan']);
    $cek = $conn->query("SELECT id FROM evaluasi WHERE id_kegiatan=$kid AND id_relawan=$rid");
    if ($cek && $cek->num_rows > 0) {
        $conn->query("UPDATE evaluasi SET nilai=$nilai, catatan='$catatan', tanggal_evaluasi=CURDATE() WHERE id_kegiatan=$kid AND id_relawan=$rid");
        $msg = "Evaluasi berhasil diperbarui.";
    } else {
        $conn->query("INSERT INTO evaluasi (id_kegiatan, id_relawan, nilai, catatan, tanggal_evaluasi) VALUES ($kid, $rid, $nilai, '$catatan', CURDATE())");
        $msg = "Evaluasi berhasil disimpan.";
    }
    header("Location: evaluasi-per-kegiatan.php?kegiatan_id=$kid&msg=" . urlencode($msg));
    exit;
}

// Ambil relawan yang ditugaskan pada kegiatan terpilih
$selected_id = intval($_GET['kegiatan_id'] ?? 0);
$relawan = [];
$evaluasi = [];
if ($selected_id) {
    // Relawan yang ditugaskan
    $q = $conn->query("SELECT ar.relawan_id, ap.name
        FROM assignment_relawan ar
        JOIN applicants ap ON ar.relawan_id = ap.id
        WHERE ar.assignment_id = (SELECT id FROM assignments WHERE kegiatan_id=$selected_id LIMIT 1)");
    while ($q && $row = $q->fetch_assoc()) {
        $relawan[] = $row;
    }
    // Evaluasi yang sudah ada
    $q2 = $conn->query("SELECT * FROM evaluasi WHERE id_kegiatan=$selected_id AND id_relawan<>0");
    while ($q2 && $row2 = $q2->fetch_assoc()) {
        $evaluasi[$row2['id_relawan']] = $row2;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Relawan per Kegiatan - CivicaCare Admin</title>
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
            --border-color: #dee2e6;
            --primary-light: #F0EBf9;
            --primary-color: #6A38C2;
        }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--text-dark); min-height: 100vh; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--primary-red); color: var(--text-light); padding: 20px; display: flex; flex-direction: column; }
        .sidebar-header { display: flex; align-items: center; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 20px; }
        .sidebar-header h2 { margin: 0; font-size: 1.5rem; }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li a { display: flex; align-items: center; gap: 15px; padding: 15px 10px; border-radius: 8px; color: var(--text-light); text-decoration: none; font-weight: 500; transition: background-color 0.3s; }
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color: rgba(0, 0, 0, 0.2); }
        .sidebar-nav li a .nav-icon { font-size: 1.2rem; width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar-footer { margin-top: auto; }
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
        .filter-section { background-color: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); margin-bottom: 30px; display: flex; gap: 20px; align-items: center; }
        .filter-section label { display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); }
        .filter-section select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background-color: var(--primary-red); color: #fff; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background-color: #f1f1f1; }
        .empty-table-message { text-align: center; padding: 30px; color: #6c757d; font-style: italic; }
        .rating-input { width: 60px; padding: 6px 8px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 0.95rem; }
        .feedback-textarea { width: 100%; min-height: 40px; padding: 8px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 0.95rem; box-sizing: border-box; resize: vertical; }
        .save-evaluation-button { background-color: var(--primary-red); color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: background-color 0.3s; }
        .save-evaluation-button:hover { background-color: #a00000; }
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
                    <h2>Evaluasi Relawan per Kegiatan</h2>
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
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Relawan</th>
                            <th>Nilai (1-5)</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($selected_id && $relawan): ?>
                            <?php foreach ($relawan as $r): 
                                $ev = $evaluasi[$r['relawan_id']] ?? null;
                            ?>
                            <tr>
                                <form method="post">
                                    <input type="hidden" name="kegiatan_id" value="<?= $selected_id ?>">
                                    <input type="hidden" name="relawan_id" value="<?= $r['relawan_id'] ?>">
                                    <td><?= safe($r['name']) ?></td>
                                    <td>
                                        <select name="nilai" class="rating-input" required>
                                            <option value="">--</option>
                                            <?php for ($i=1;$i<=5;$i++): ?>
                                                <option value="<?= $i ?>" <?= ($ev && $ev['nilai']==$i)?'selected':'' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="catatan" class="feedback-textarea"><?= $ev ? safe($ev['catatan']) : '' ?></textarea>
                                    </td>
                                    <td>
                                        <button type="submit" class="save-evaluation-button"><?= $ev ? 'Update' : 'Simpan' ?></button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        <?php elseif ($selected_id): ?>
                            <tr><td colspan="4" class="empty-table-message">Tidak ada relawan yang ditugaskan untuk kegiatan ini.</td></tr>
                        <?php else: ?>
                            <tr><td colspan="4" class="empty-table-message">Pilih kegiatan untuk menampilkan relawan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (isset($_GET['msg'])): ?>
                    <script>alert("<?= safe($_GET['msg']) ?>");</script>
                <?php endif; ?>
            </section>
        </main>
    </div>
<script>
    const toggleButton = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('shifted');
    });
</script>
</body>
</html>