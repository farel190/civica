<?php
require_once 'config/db.php';
session_start();

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle tambah/edit/hapus kegiatan
$error = "";
$success = "";

// Tambah kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    $nama = trim($_POST['nama_kegiatan']);
    $deskripsi = trim($_POST['deskripsi']);
    $lokasi = trim($_POST['lokasi']);
    $tanggal = $_POST['tanggal'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $kategori = trim($_POST['kategori']);
    $kuota = intval($_POST['kuota_relawan']);
    $perlengkapan = trim($_POST['perlengkapan']);
    $catatan = trim($_POST['catatan']);
    if ($nama === '' || $lokasi === '' || $tanggal === '' || $waktu_mulai === '' || $waktu_selesai === '' || $kuota < 1) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $conn->prepare("INSERT INTO kegiatan (nama_kegiatan, deskripsi, lokasi, tanggal, waktu_mulai, waktu_selesai, kategori, kuota_relawan, perlengkapan, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssiss", $nama, $deskripsi, $lokasi, $tanggal, $waktu_mulai, $waktu_selesai, $kategori, $kuota, $perlengkapan, $catatan);
        if ($stmt->execute()) {
            $success = 'Kegiatan berhasil ditambahkan!';
        } else {
            $error = 'Gagal menambah kegiatan.';
        }
        $stmt->close();
    }
}

// Edit kegiatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_activity'])) {
    $id = intval($_POST['activity_id']);
    $nama = trim($_POST['nama_kegiatan']);
    $deskripsi = trim($_POST['deskripsi']);
    $lokasi = trim($_POST['lokasi']);
    $tanggal = $_POST['tanggal'];
    $waktu_mulai = $_POST['waktu_mulai'];
    $waktu_selesai = $_POST['waktu_selesai'];
    $kategori = trim($_POST['kategori']);
    $kuota = intval($_POST['kuota_relawan']);
    $perlengkapan = trim($_POST['perlengkapan']);
    $catatan = trim($_POST['catatan']);
    if ($nama === '' || $lokasi === '' || $tanggal === '' || $waktu_mulai === '' || $waktu_selesai === '' || $kuota < 1) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $conn->prepare("UPDATE kegiatan SET nama_kegiatan=?, deskripsi=?, lokasi=?, tanggal=?, waktu_mulai=?, waktu_selesai=?, kategori=?, kuota_relawan=?, perlengkapan=?, catatan=? WHERE id=?");
        $stmt->bind_param("sssssssissi", $nama, $deskripsi, $lokasi, $tanggal, $waktu_mulai, $waktu_selesai, $kategori, $kuota, $perlengkapan, $catatan, $id);
        if ($stmt->execute()) {
            $success = 'Kegiatan berhasil diupdate!';
        } else {
            $error = 'Gagal mengupdate kegiatan.';
        }
        $stmt->close();
    }
}

// Hapus kegiatan
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM kegiatan WHERE id = $id");
    header("Location: kegiatan.php");
    exit;
}

// Ambil semua kegiatan
$result = $conn->query("SELECT * FROM kegiatan ORDER BY tanggal DESC, waktu_mulai DESC");
$kegiatan = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

function safe($val) {
    return ($val === null || $val === '' || $val === false) ? '-' : htmlspecialchars($val);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kegiatan - CivicaCare Admin</title>

    
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
            position:relative;
            z-index: 10;
            transition: width 0.3s ease;
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

        .sidebar-header { display:flex; align-items:center; gap:15px; padding-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.2); margin-bottom:20px; justify-content:flex-start;}
        .sidebar-header h2 { margin:0; font-size:1.5rem;}
        .sidebar-nav ul { list-style:none; padding:0; margin:0;}
        .sidebar-nav li a { display:flex; align-items:center; gap:15px; padding:15px 10px; border-radius:8px; color:var(--text-light); text-decoration:none; font-weight:500; transition:background-color 0.3s; position:relative;}
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color:rgba(0,0,0,0.2);}
        .sidebar-nav li a .nav-icon { font-size:1.2rem; width:20px; text-align:center; flex-shrink:0;}
        .sidebar-footer { margin-top:auto;}
        .sidebar-nav .notification-dot { position:absolute; top:12px; right:15px; width:10px; height:10px; background-color:#ffc107; border-radius:50%; border:2px solid var(--primary-red);}
        
        /* === Konten Utama === */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        .add-button {
            background-color: var(--primary-red);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .add-button:hover {
            background-color: #a00000;
        }

        /* === Table Styles === */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            /* Ensures rounded corners apply to table */
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

        .button-edit {
            background-color: #007bff;
            /* Blue */
            color: #fff;
        }

        .button-edit:hover {
            background-color: #0056b3;
        }

        .button-delete {
            background-color: #dc3545;
            /* Red */
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

        /* === Modal Styles === */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            position: relative;
            max-height: 80vh;
            /* Batasi tinggi modal untuk mengaktifkan scroll */
            overflow-y: auto;
            /* Aktifkan scroll vertikal */
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary-red);
        }

        .close-button {
            background: none;
            border: none;
            font-size: 2rem;
            color: #aaa;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close-button:hover {
            color: #666;
        }

        /* Custom scrollbar untuk modal (opsional, untuk estetika) */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }

        /* === Form Grid dalam Modal === */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Dua kolom */
            gap: 15px 20px;
            /* Jarak antar elemen form */
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-grid .form-group.span-two-columns {
            grid-column: span 2;
            /* Membuat elemen ini membentang dua kolom */
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 24px);
            /* Adjust for padding */
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .modal-actions .cancel-button {
            background-color: #e9ecef;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .modal-actions .cancel-button:hover {
            background-color: #dee2e6;
        }

        .modal-actions .save-button {
            background-color: var(--primary-red);
            color: #fff;
            border: none;
        }

        .modal-actions .save-button:hover {
            background-color: #a00000;
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
                    <li class="active"><a href="kegiatan.php"><i class="fa-solid fa-people-group nav-icon"></i><span>Kegiatan</span></a></li>
                    <li><a href="penjadwalan.php"><i class="fa-solid fa-calendar-days nav-icon"></i><span>Penjadwalan</span></a></li>
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
                    <h2>Manajemen Kegiatan</h2>
                    <button class="add-button" onclick="openModal()"><i class="fas fa-plus"></i> Tambah Kegiatan</button>
                </div>
                <?php if ($error): ?><div class="error-message" style="color:#C70000; margin-bottom:10px;"> <?= htmlspecialchars($error) ?> </div><?php endif; ?>
                <?php if ($success): ?><div class="success-message" style="color:#28a745; margin-bottom:10px;"> <?= htmlspecialchars($success) ?> </div><?php endif; ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Lokasi</th>
                            <th>Kuota</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($kegiatan)): ?>
                        <tr><td colspan="6" class="empty-table-message">Belum ada kegiatan yang ditambahkan.</td></tr>
                    <?php else: foreach ($kegiatan as $k): ?>
                        <tr>
                            <td><?= safe($k['nama_kegiatan']) ?></td>
                            <td><?= safe(date('d M Y', strtotime($k['tanggal']))) ?></td>
                            <td><?= safe(substr($k['waktu_mulai'],0,5)) ?> - <?= safe(substr($k['waktu_selesai'],0,5)) ?></td>
                            <td><?= safe($k['lokasi']) ?></td>
                            <td><?= safe($k['kuota_relawan']) ?></td>
                            <td class="action-buttons-cell">
                                <button class="action-button button-edit" onclick="editActivity(<?= $k['id'] ?>, <?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                                <a href="kegiatan.php?delete=<?= $k['id'] ?>" class="action-button button-delete" onclick="return confirm('Yakin ingin menghapus kegiatan ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Modal Tambah/Edit Kegiatan -->
    <div id="activity-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Tambah Kegiatan Baru</h3>
                <button class="close-button" onclick="closeModal()">&times;</button>
            </div>
            <form method="post" id="activity-form">
                <input type="hidden" name="activity_id" id="activity-id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama-kegiatan">Nama Kegiatan</label>
                        <input type="text" name="nama_kegiatan" id="nama-kegiatan" required>
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <input type="text" name="lokasi" id="lokasi" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" required>
                    </div>
                    <div class="form-group">
                        <label for="waktu-mulai">Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" id="waktu-mulai" required>
                    </div>
                    <div class="form-group">
                        <label for="waktu-selesai">Waktu Selesai</label>
                        <input type="time" name="waktu_selesai" id="waktu-selesai" required>
                    </div>
                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <input type="text" name="kategori" id="kategori" placeholder="Contoh: Lingkungan" required>
                    </div>
                    <div class="form-group">
                        <label for="kuota-relawan">Kuota Relawan</label>
                        <input type="number" name="kuota_relawan" id="kuota-relawan" min="1" required>
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="perlengkapan">Perlengkapan yang Dibutuhkan</label>
                        <textarea name="perlengkapan" id="perlengkapan" rows="3" placeholder="Contoh: Topi, Sarung tangan, Botol minum pribadi"></textarea>
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="catatan">Catatan Tambahan untuk Relawan</label>
                        <textarea name="catatan" id="catatan" rows="3" placeholder="Contoh: Kenakan pakaian nyaman, Makan siang disediakan"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-button" onclick="closeModal()">Batal</button>
                    <button type="submit" class="save-button" id="modal-save-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openModal(edit = false) {
        document.getElementById('activity-modal').style.display = 'flex';
        document.getElementById('modal-title').textContent = edit ? 'Edit Kegiatan' : 'Tambah Kegiatan Baru';
        document.getElementById('activity-form').reset();
        document.getElementById('activity-id').value = '';
        document.getElementById('modal-save-btn').name = edit ? 'edit_activity' : 'add_activity';
    }
    function closeModal() {
        document.getElementById('activity-modal').style.display = 'none';
    }
    function editActivity(id, data) {
        openModal(true);
        document.getElementById('activity-id').value = id;
        document.getElementById('nama-kegiatan').value = data.nama_kegiatan;
        document.getElementById('deskripsi').value = data.deskripsi;
        document.getElementById('lokasi').value = data.lokasi;
        document.getElementById('tanggal').value = data.tanggal;
        document.getElementById('waktu-mulai').value = data.waktu_mulai.substring(0,5);
        document.getElementById('waktu-selesai').value = data.waktu_selesai.substring(0,5);
        document.getElementById('kategori').value = data.kategori;
        document.getElementById('kuota-relawan').value = data.kuota_relawan;
        document.getElementById('perlengkapan').value = data.perlengkapan;
        document.getElementById('catatan').value = data.catatan;
    }
    document.getElementById('activity-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>

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