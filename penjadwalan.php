<?php
require_once 'config/db.php';
session_start();
// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Helper: konversi hari ke string
function hariIndo($date) {
    $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $d = date('w', strtotime($date));
    return strtolower($days[$d]);
}

// Handle tambah penjadwalan
$error = $success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $kegiatan_id = intval($_POST['kegiatan_id']);
    $koordinator_id = intval($_POST['koordinator_id']);
    $relawan_ids = isset($_POST['relawan_ids']) ? $_POST['relawan_ids'] : [];
    // Ambil tanggal, waktu_mulai, waktu_selesai dari DB berdasarkan kegiatan_id
    $tanggal = $waktu_mulai = $waktu_selesai = '';
    if ($kegiatan_id) {
        $stmt_keg = $conn->prepare("SELECT tanggal, waktu_mulai, waktu_selesai FROM kegiatan WHERE id = ? LIMIT 1");
        $stmt_keg->bind_param("i", $kegiatan_id);
        $stmt_keg->execute();
        $stmt_keg->bind_result($tanggal, $waktu_mulai, $waktu_selesai);
        $stmt_keg->fetch();
        $stmt_keg->close();
    }
    if (!$kegiatan_id || !$koordinator_id || !$tanggal || !$waktu_mulai || !$waktu_selesai || count($relawan_ids) < 1) {
    $error = 'Semua field wajib diisi dan minimal 1 relawan.';
} else {
    // ✅ VALIDASI JUMLAH RELAWAN
    $stmt_keg = $conn->prepare("SELECT kuota_relawan FROM kegiatan WHERE id = ?");
    $stmt_keg->bind_param("i", $kegiatan_id);
    $stmt_keg->execute();
    $stmt_keg->bind_result($kuota);
    $stmt_keg->fetch();
    $stmt_keg->close();

    $max_relawan = max(0, $kuota - 1); // 1 untuk koordinator
    if (count($relawan_ids) > $max_relawan) {
        $error = "Jumlah relawan melebihi kuota! Maksimal $max_relawan relawan karena 1 sudah dijadikan koordinator.";
    } else {
        $stmt = $conn->prepare("INSERT INTO assignments (kegiatan_id, koordinator_id, tanggal, waktu_mulai, waktu_selesai, status) VALUES (?, ?, ?, ?, ?, 'terjadwal')");
        $stmt->bind_param("iisss", $kegiatan_id, $koordinator_id, $tanggal, $waktu_mulai, $waktu_selesai);
        if ($stmt->execute()) {
            $assignment_id = $stmt->insert_id;
            $stmt->close();

            // Insert relawan
            $stmt2 = $conn->prepare("INSERT INTO assignment_relawan (assignment_id, relawan_id) VALUES (?, ?)");
            foreach ($relawan_ids as $rid) {
                $rid = intval($rid);
                $stmt2->bind_param("ii", $assignment_id, $rid);
                $stmt2->execute();
            }
            $stmt2->close();

            // Notifikasi ke koordinator & relawan
            // Ambil nama kegiatan dari ID
            $stmt_keg = $conn->prepare("SELECT nama_kegiatan FROM kegiatan WHERE id = ?");
            $stmt_keg->bind_param("i", $kegiatan_id);
            $stmt_keg->execute();
            $stmt_keg->bind_result($nama_kegiatan);
            $stmt_keg->fetch();
            $stmt_keg->close();

            $user_ids = array_merge([$koordinator_id], $relawan_ids);
            foreach ($user_ids as $uid) {
            $judul = "Penugasan Baru";
            $pesan = "Anda mendapatkan penugasan baru pada kegiatan \"$nama_kegiatan\" tanggal $tanggal.";
            $conn->query("INSERT INTO notifikasi (user_id, judul, pesan, status_baca, created_at) VALUES ($uid, '$judul', '$pesan', 'belum_terbaca', NOW())");
        }


            $success = 'Penjadwalan berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambah penjadwalan.';
        }
    }
}

}
// Handle hapus assignment
if (isset($_GET['delete'])) {
    $aid = intval($_GET['delete']);
    $conn->query("DELETE FROM assignment_relawan WHERE assignment_id = $aid");
    $conn->query("DELETE FROM assignments WHERE id = $aid");
    header("Location: penjadwalan.php");
    exit;
}
// Handle selesai assignment
if (isset($_GET['complete'])) {
    $aid = intval($_GET['complete']);
    $conn->query("UPDATE assignments SET status='selesai' WHERE id = $aid");
    header("Location: penjadwalan.php");
    exit;
}
// Update otomatis status assignment ke 'selesai' jika sudah lewat waktu selesai + 30 menit
$conn->query("UPDATE assignments SET status='selesai' WHERE status!='selesai' AND TIMESTAMP(DATE(tanggal), waktu_selesai) < (NOW() - INTERVAL 30 MINUTE)");
// Ambil data kegiatan aktif
$kegiatan = $conn->query("SELECT * FROM kegiatan WHERE status='aktif' ORDER BY tanggal DESC")->fetch_all(MYSQLI_ASSOC);
// Ambil semua koordinator diterima
$koordinator = $conn->query("SELECT * FROM applicants WHERE status='diterima' AND role='koordinator'")->fetch_all(MYSQLI_ASSOC);
// Ambil semua relawan diterima
$relawan = $conn->query("SELECT * FROM applicants WHERE status='diterima' AND (role IS NULL OR role='relawan')")->fetch_all(MYSQLI_ASSOC);
// Ambil semua assignment
$assignments = $conn->query("SELECT a.*, k.nama_kegiatan, k.tanggal as tgl_kegiatan, k.waktu_mulai, k.waktu_selesai, ak.name as koordinator_nama FROM assignments a JOIN kegiatan k ON a.kegiatan_id=k.id JOIN applicants ak ON a.koordinator_id=ak.id ORDER BY a.tanggal DESC, a.waktu_mulai DESC")->fetch_all(MYSQLI_ASSOC);
// Helper: relawan per assignment
function getRelawan($conn, $assignment_id) {
    $q = $conn->query("SELECT ap.name FROM assignment_relawan ar JOIN applicants ap ON ar.relawan_id=ap.id WHERE ar.assignment_id=$assignment_id");
    $arr = [];
    while ($r = $q->fetch_assoc()) $arr[] = $r['name'];
    return $arr;
}
function safe($v) { return ($v===null||$v===''||$v===false)?'-':htmlspecialchars($v); }
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjadwalan Kegiatan - CivicaCare Admin</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <style>
        /* === Pengaturan Dasar === */
        :root {
            --primary-red: #C70000;
            --bg-light: #F8F9FA;
            --text-dark: #212529;
            --text-light: #f8f9fa;
            --border-color: #dee2e6;
            --primary-color: #6A38C2;
            /* Added for consistency */
            --primary-light: #F0EBf9;
            /* Ungu Muda */
            --secondary-color: #CC0000;
            /* Merah */
        }

        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* === Sidebar (Kiri) === */
        .sidebar {
            width: 260px;
            background-color: var(--primary-red);
            color: var(--text-light);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            position: relative;
            /* Ensure z-index works */
            z-index: 10;
            /* Make sure it's above main content */
        }

        .sidebar.collapsed {
            width: 80px;
            /* Lebar saat disembunyikan */
        }

        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-nav li a span,
        .sidebar.collapsed .sidebar-footer span {
            display: none;
            /* Sembunyikan teks */
        }

        .sidebar.collapsed .sidebar-header {
            padding-bottom: 10px;
            /* Make header more compact when collapsed */
            margin-bottom: 10px;
            /* Reduce space below header when collapsed */
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
            justify-content: flex-start;
            /* Align items to start */
        }

        #menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 10px;
            /* Space between toggle and title */
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 10px;
            border-radius: 8px;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            position: relative;
            /* Diperlukan untuk positioning dot dan tooltip */
        }

        .sidebar-nav li a:hover,
        .sidebar-nav li.active a {
            background-color: rgba(0, 0, 0, 0.2);
        }

        .sidebar-nav li a .nav-icon {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
            /* Prevent icon from shrinking */
        }

        .sidebar-footer {
            margin-top: auto;
            /* Mendorong logout ke bawah */
        }

        .sidebar-nav .notification-dot {
            position: absolute;
            top: 12px;
            right: 15px;
            width: 10px;
            height: 10px;
            background-color: #ffc107;
            /* Kuning untuk perhatian */
            border-radius: 50%;
            border: 2px solid var(--primary-red);
        }

        /* Tooltip styles for collapsed sidebar */
        .sidebar.collapsed .sidebar-nav li a::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 75px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #212529;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            white-space: nowrap;
            z-index: 20;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .sidebar.collapsed .sidebar-nav li a:hover::after {
            opacity: 1;
            visibility: visible;
            transition-delay: 0.3s;
        }

        /* === Konten Utama === */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        .main-content.shifted {
            /* margin-left: -180px; */
            /* Removed: flex-grow handles the shift */
        }

        .main-content .main-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 20px 40px;
            background-color: #fff;
            position: relative;
            overflow: hidden;
        }

        .header-shape {
            position: absolute;
            top: 0;
            right: 0;
            width: 350px;
            height: 100%;
            background-color: var(--primary-red);
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #495057;
            font-size: 1.5rem;
        }

        .user-info {
            color: var(--text-light);
            text-align: right;
        }

        .user-info .user-name {
            font-weight: 600;
        }

        .user-info .user-email {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .content-area {
            padding: 40px;
            flex-grow: 1;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-size: 2rem;
            margin: 0;
            color: var(--primary-red);
        }

        /* === Form Penjadwalan === */
        .scheduling-form-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 40px;
        }

        .scheduling-form-card h3 {
            font-size: 1.5rem;
            color: var(--primary-red);
            margin-top: 0;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
        }

        .form-group {
            margin-bottom: 0;
            /* Adjusted for grid gap */
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group.span-two-columns {
            grid-column: span 2;
        }

        /* Multi-select for volunteers */
        .multi-select-container {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            min-height: 100px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background-color: #fff;
        }

        .multi-select-container label {
            display: block;
            margin-bottom: 5px;
            cursor: pointer;
            padding: 5px 0;
            font-weight: normal;
            /* Override form-group label */
        }

        .multi-select-container label:hover {
            background-color: #f1f1f1;
        }

        .multi-select-container input[type="checkbox"] {
            margin-right: 8px;
        }

        .multi-select-container input[type="checkbox"]:disabled+span {
            color: #aaa;
            cursor: not-allowed;
            text-decoration: line-through;
        }

        .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .assign-button {
            background-color: var(--primary-red);
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .assign-button:hover {
            background-color: #a00000;
        }

        /* === Table Styles === */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
            /* More space from form */
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background-color: var(--primary-red);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .action-buttons-cell {
            display: flex;
            gap: 8px;
        }

        .action-button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .button-delete {
            background-color: #dc3545;
            color: #fff;
        }

        .button-delete:hover {
            background-color: #c82333;
        }

        .empty-table-message {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }

        /* === NEW: Status Badges === */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-terjadwal {
            background-color: #007bff;
            /* Blue */
            color: #fff;
        }

        .status-ongoing {
            background-color: #ffc107;
            /* Yellow */
            color: #343a40;
        }

        .status-completed {
            background-color: #28a745;
            /* Green */
            color: #fff;
        }

        /* === NEW: Styling for completed rows === */
        .assignment-completed {
            opacity: 0.7;
            background-color: #f8f9fa;
            /* Lighter background for completed rows */
        }

        .assignment-completed td {
            color: #6c757d;
            /* Gray out text */
        }

        /* === NEW: Button Complete === */
        .button-complete {
            background-color: #007bff;
            /* Blue */
            color: #fff;
        }

        .button-complete:hover {
            background-color: #0056b3;
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
                    <li><a href="dashboard-admin.php"><i class="fa-solid fa-table-columns nav-icon"></i><span>Dashboard</span></a></li>
                    <li><a href="daftar-relawan.php"><i class="fa-solid fa-users nav-icon"></i><span>Daftar Relawan</span></a></li>
                    <li><a href="pengajuan.php"><i class="fa-solid fa-file-import nav-icon"></i><span>Pengajuan</span></a></li>
                    <li><a href="kegiatan.php"><i class="fa-solid fa-people-group nav-icon"></i><span>Kegiatan</span></a></li>
                    <li class="active"><a href="penjadwalan.php"><i class="fa-solid fa-calendar-days nav-icon"></i><span>Penjadwalan</span></a></li>
                    <li><a href="evaluasi-main.php"><i class="fa-solid fa-pen-to-square nav-icon"></i><span>Evaluasi</span></a></li>
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
            <!-- Header Konten -->
            <header class="main-header">
                <div class="header-shape"></div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></div>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </header>

            <!-- Area Konten Dinamis -->
            <section class="content-area">
                <div class="section-header">
                    <h2>Penjadwalan Kegiatan Relawan</h2>
                </div>
                <?php if ($error): ?><div class="error-message" style="color:#C70000; margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div><?php endif; ?>
                <?php if ($success): ?><div class="success-message" style="color:#28a745; margin-bottom:10px;"> <?= htmlspecialchars($success) ?> </div><?php endif; ?>
                <div class="scheduling-form-card">
                    <h3>Tetapkan Penugasan Baru</h3>
                    <form method="post">
                        <input type="hidden" name="add_assignment" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="kegiatan_id">Pilih Kegiatan</label>
                                <select name="kegiatan_id" id="kegiatan_id" required onchange="this.form.submit()">
                                    <option value="">-- Pilih Kegiatan --</option>
                                    <?php foreach ($kegiatan as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= (isset($_POST['kegiatan_id']) && $_POST['kegiatan_id']==$k['id'])?'selected':'' ?>><?= safe($k['nama_kegiatan']) ?> (<?= safe($k['tanggal']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="koordinator_id">Pilih Koordinator</label>
                                <select name="koordinator_id" id="koordinator_id" required onchange="this.form.submit()">
                                    <option value="">-- Pilih Koordinator --</option>
                                    <?php 
                                    $selected_tgl = '';
                                    if (isset($_POST['kegiatan_id'])) {
                                        foreach ($kegiatan as $k) if ($k['id']==$_POST['kegiatan_id']) $selected_tgl = $k['tanggal'];
                                    }
                                    foreach ($koordinator as $koor): 
                                        $avail = explode(',', $koor['availability'] ?? '');
                                        if ($selected_tgl && !in_array(hariIndo($selected_tgl), $avail)) continue;
                                    ?>
                                        <option value="<?= $koor['id'] ?>" <?= (isset($_POST['koordinator_id']) && $_POST['koordinator_id']==$koor['id'])?'selected':'' ?>><?= safe($koor['name']) ?> (<?= safe($koor['availability']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group span-two-columns">
                                <label>Pilih Relawan</label>
                                <div class="multi-select-container">
                                    <?php 
                                    $kuota = 0;
                                    if (isset($_POST['kegiatan_id'])) {
                                        foreach ($kegiatan as $k) if ($k['id']==$_POST['kegiatan_id']) $kuota = $k['kuota_relawan'];
                                    }
                                    $max_relawan = $kuota > 1 ? $kuota-1 : 1;
                                    $selected_tgl = '';
                                    if (isset($_POST['kegiatan_id'])) {
                                        foreach ($kegiatan as $k) if ($k['id']==$_POST['kegiatan_id']) $selected_tgl = $k['tanggal'];
                                    }
                                    $count = 0;
                                    $can_select_relawan = isset($_POST['kegiatan_id']) && $_POST['kegiatan_id'] && isset($_POST['koordinator_id']) && $_POST['koordinator_id'];
                                    if (!$can_select_relawan) {
                                        echo '<p class="empty-table-message">Pilih kegiatan dan koordinator terlebih dahulu untuk memilih relawan.</p>';
                                    }
                                    foreach ($relawan as $rel) {
                                        $avail = explode(',', $rel['availability'] ?? '');
                                        if ($selected_tgl && !in_array(hariIndo($selected_tgl), $avail)) continue;
                                        $count++;
                                        $disabled = !$can_select_relawan ? 'disabled' : '';
                                    ?>
                                        <label><input type="checkbox" name="relawan_ids[]" value="<?= $rel['id'] ?>" <?= (isset($_POST['relawan_ids']) && in_array($rel['id'], $_POST['relawan_ids']))?'checked':'' ?> <?= (isset($_POST['relawan_ids']) && count($_POST['relawan_ids'])>=$max_relawan && (!isset($_POST['relawan_ids']) || !in_array($rel['id'], $_POST['relawan_ids'])))?'disabled':'' ?> <?= $disabled ?>> <?= safe($rel['name']) ?> (<?= safe($rel['availability']) ?>)</label>
                                    <?php }
                                    if ($count==0 && $can_select_relawan) echo '<p class="empty-table-message">Tidak ada relawan tersedia untuk hari ini.</p>';
                                    ?>
                                    <div style="font-size:0.9em; color:#888; margin-top:5px;">Maksimal <?= $max_relawan ?> relawan dari kuota <?= $kuota ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions" style="margin-top:20px;">
                            <button type="submit" class="assign-button"><i class="fas fa-check-circle"></i> Tetapkan Penugasan</button>
                        </div>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Koordinator</th>
                            <th>Relawan Ditugaskan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr><td colspan="7" class="empty-table-message">Belum ada penugasan yang dibuat.</td></tr>
                    <?php else: foreach ($assignments as $a):
                        // Hitung status dinamis berdasarkan waktu
                        $now = new DateTime();
                        $start = new DateTime($a['tgl_kegiatan'].' '.$a['waktu_mulai']);
                        $end = new DateTime($a['tgl_kegiatan'].' '.$a['waktu_selesai']);
                        $start_minus_30 = clone $start; $start_minus_30->modify('-30 minutes');
                        $end_plus_30 = clone $end; $end_plus_30->modify('+30 minutes');
                        $status = $a['status'];
                        if ($a['status']!=='selesai') {
                            if ($now >= $start_minus_30 && $now <= $end_plus_30) {
                                if ($now < $start) {
                                    $status = 'berlangsung'; // 30 menit sebelum mulai sampai selesai
                                } elseif ($now >= $start && $now <= $end) {
                                    $status = 'berlangsung';
                                } elseif ($now > $end && $now <= $end_plus_30) {
                                    $status = 'berlangsung';
                                }
                            }
                            if ($now > $end_plus_30) {
                                $status = 'selesai';
                            }
                        }
                    ?>
                    <tr<?= $status==='selesai'?' class="assignment-completed"':'' ?>>
                        <td><?= safe($a['nama_kegiatan']) ?></td>
                        <td><?= safe(date('d M Y', strtotime($a['tgl_kegiatan']))) ?></td>
                        <td><?= safe(substr($a['waktu_mulai'],0,5)) ?> - <?= safe(substr($a['waktu_selesai'],0,5)) ?></td>
                        <td><?= safe($a['koordinator_nama']) ?></td>
                        <td><?= implode(', ', getRelawan($conn, $a['id'])) ?></td>
                        <td><?php
                            if ($status==='selesai') echo '<span class="status-badge status-completed">Selesai</span>';
                            elseif ($status==='berlangsung') echo '<span class="status-badge status-ongoing">Berlangsung</span>';
                            else echo '<span class="status-badge status-terjadwal">Terjadwal</span>';
                        ?></td>
                        <td class="action-buttons-cell">
                            <?php if ($a['status']!=='selesai'): ?>
                                <a href="penjadwalan.php?complete=<?= $a['id'] ?>" class="action-button button-complete" onclick="return confirm('Tandai penugasan selesai?')">Selesai</a>
                            <?php endif; ?>
                            <a href="penjadwalan.php?delete=<?= $a['id'] ?>" class="action-button button-delete" onclick="return confirm('Hapus penugasan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const maxRelawan = <?= $kuota ?? 0 ?>;
    const checkboxSelector = 'input[name="relawan_ids[]"]';
    const checkboxes = document.querySelectorAll(checkboxSelector);
    const koordinatorSelected = document.querySelector('#koordinator_id').value ? 1 : 0;
    const maxAllowed = Math.max(0, maxRelawan - koordinatorSelected);

    function updateCheckboxState() {
        const checkedCount = document.querySelectorAll(`${checkboxSelector}:checked`).length;

        checkboxes.forEach(cb => {
            if (!cb.checked) {
                cb.disabled = checkedCount >= maxAllowed;
            }
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCheckboxState);
    });

    updateCheckboxState(); // initial state
});
</script>


</body>

</html>