<?php
require_once 'config/db.php';
session_start();

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle aksi (seleksi, terima, tolak, hapus)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'seleksi') {
        $conn->query("UPDATE applicants SET status = 'diseleksi' WHERE id = $id");
    } elseif ($_GET['action'] === 'terima') {
        $conn->query("UPDATE applicants SET status = 'diterima' WHERE id = $id");
    } elseif ($_GET['action'] === 'tolak' && isset($_POST['reason'])) {
        $reason = $conn->real_escape_string($_POST['reason']);
        $conn->query("UPDATE applicants SET status = 'ditolak', rejection_reason = '$reason' WHERE id = $id");
    } elseif ($_GET['action'] === 'hapus') {
        $conn->query("DELETE FROM applicants WHERE id = $id");
    }
    header("Location: pengajuan.php" . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// Filter status
$status_filter = $_GET['filter'] ?? 'all';
$where = '';
if ($status_filter !== 'all') {
    $where = "WHERE status = '" . $conn->real_escape_string($status_filter) . "'";
}
$sql = "SELECT * FROM applicants $where ORDER BY id DESC";
$result = $conn->query($sql);
$applicants = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Relawan - CivicaCare Admin</title>

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

        .sidebar { width:260px; background-color:var(--primary-red); color:var(--text-light); padding:20px; display:flex; flex-direction:column; position:relative; z-index:10;}
        .sidebar-header { display:flex; align-items:center; gap:15px; padding-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.2); margin-bottom:20px; justify-content:flex-start;}
        .sidebar-header h2 { margin:0; font-size:1.5rem;}
        .sidebar-nav ul { list-style:none; padding:0; margin:0;}
        .sidebar-nav li a { display:flex; align-items:center; gap:15px; padding:15px 10px; border-radius:8px; color:var(--text-light); text-decoration:none; font-weight:500; transition:background-color 0.3s; position:relative;}
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color:rgba(0,0,0,0.2);}
        .sidebar-nav li a .nav-icon { font-size:1.2rem; width:20px; text-align:center; flex-shrink:0;}
        .sidebar-footer { margin-top:auto;}
        .sidebar-nav .notification-dot { position:absolute; top:12px; right:15px; width:10px; height:10px; background-color:#ffc107; border-radius:50%; border:2px solid var(--primary-red);}

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

        /* === Filter Buttons === */
        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .filter-button {
            background-color: #e9ecef;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            padding: 5px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.3s;
        }

        .filter-button:hover {
            background-color: #dee2e6;
        }

        .filter-button.active {
            background-color: var(--primary-red);
            color: #fff;
            border-color: var(--primary-red);
        }

        /* [NEW] Style for highlighting new applicants */
        .new-applicant-row {
            background-color: var(--primary-light) !important;
            /* Light purple from root */
        }

        .new-applicant-row td {
            font-weight: 600;
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

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-diajukan {
            background-color: #ffc107;
            /* Yellow */
            color: #343a40;
        }

        .status-diseleksi {
            background-color: #17a2b8;
            /* Cyan */
            color: #fff;
        }

        .status-diterima {
            background-color: #28a745;
            /* Green */
            color: #fff;
        }

        .status-ditolak {
            background-color: #dc3545;
            /* Red */
            color: #fff;
        }

        .status-mengundurkan_diri {
            background-color: #6c757d;
            /* Gray */
            color: #fff;
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

        .button-seleksi {
            background-color: #007bff;
            /* Blue */
            color: #fff;
        }

        .button-seleksi:hover {
            background-color: #0056b3;
        }

        .button-terima {
            background-color: #28a745;
            /* Green */
            color: #fff;
        }

        .button-terima:hover {
            background-color: #218838;
        }

        .button-tolak {
            background-color: #dc3545;
            /* Red */
            color: #fff;
        }

        .button-tolak:hover {
            background-color: #c82333;
        }

        .button-detail {
            background-color: #6A38C2;
            /* Primary color for detail button */
            color: #fff;
        }

        .button-detail:hover {
            background-color: #592EAB;
        }

        .empty-table-message {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }

        /* Style for permanent delete button */
        .button-delete-permanent {
            background-color: #dc3545;
            /* Red */
            color: #fff;
        }

        .button-delete-permanent:hover {
            background-color: #c82333;
        }

        /* Modal Styles */
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
            overflow-y: auto;
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

        .modal-close-button {
            background: none;
            border: none;
            font-size: 2rem;
            color: #aaa;
            cursor: pointer;
        }

        .modal-close-button:hover {
            color: #666;
        }

        .modal-body p {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .modal-body strong {
            display: inline-block;
            min-width: 120px;
        }

        .modal-body ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .modal-body li {
            margin-bottom: 5px;
        }

        .modal-body .document-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .modal-body .document-link:hover {
            text-decoration: underline;
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
                    <li class="active"><a href="pengajuan.php"><i class="fa-solid fa-file-import nav-icon"></i><span>Pengajuan</span></a>
                    </li>
                    <li><a href="kegiatan.php"><i class="fa-solid fa-people-group nav-icon"></i><span>Kegiatan</span></a></li>
                    <li><a href="penjadwalan.php"><i class="fa-solid fa-calendar-days nav-icon"></i><span>Penjadwalan</span></a></li>
                    <li><a href="evaluasi-main.php"><i class="fa-solid fa-pen-to-square nav-icon"></i><span>Evaluasi</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket nav-icon"></i><span>Logout</span></a>
                        </li>
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
                    <h2>Pengajuan Relawan</h2>
                    <div class="filter-buttons">
                        <a href="pengajuan.php?filter=all" class="filter-button<?= $status_filter=='all'?' active':'' ?>">Semua</a>
                        <a href="pengajuan.php?filter=diajukan" class="filter-button<?= $status_filter=='diajukan'?' active':'' ?>">Diajukan</a>
                        <a href="pengajuan.php?filter=diseleksi" class="filter-button<?= $status_filter=='diseleksi'?' active':'' ?>">Diseleksi</a>
                        <a href="pengajuan.php?filter=diterima" class="filter-button<?= $status_filter=='diterima'?' active':'' ?>">Diterima</a>
                        <a href="pengajuan.php?filter=ditolak" class="filter-button<?= $status_filter=='ditolak'?' active':'' ?>">Ditolak</a>
                        <a href="pengajuan.php?filter=mengundurkan_diri" class="filter-button<?= $status_filter=='mengundurkan_diri'?' active':'' ?>">Mengundurkan Diri</a>
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>NIK</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applicants)): ?>
                            <tr><td colspan="7" class="empty-table-message">Tidak ada data pendaftar dengan status ini.</td></tr>
                        <?php else: foreach ($applicants as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['name']) ?></td>
                                <td><?= htmlspecialchars($a['nik']) ?></td>
                                <td><?= htmlspecialchars($a['phone']) ?></td>
                                <td><?= htmlspecialchars($a['email']) ?></td>
                                <td><span class="status-badge status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                                <td><button class="action-button button-detail" onclick="showDetail(<?= $a['id'] ?>)">Lihat Detail</button></td>
                                <td class="action-buttons-cell">
                                    <?php if ($a['status'] === 'diajukan'): ?>
                                        <a href="?action=seleksi&id=<?= $a['id'] ?>&filter=<?= urlencode($status_filter) ?>" class="action-button button-seleksi" style="min-width:80px;">Seleksi</a>
                                        <button class="action-button button-tolak" onclick="openTolakModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['name'])) ?>')">Tolak</button>
                                    <?php elseif ($a['status'] === 'diseleksi'): ?>
                                        <a href="?action=terima&id=<?= $a['id'] ?>&filter=<?= urlencode($status_filter) ?>" class="action-button button-terima" style="min-width:80px;">Terima</a>
                                        <button class="action-button button-tolak" onclick="openTolakModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['name'])) ?>')">Tolak</button>
                                    <?php elseif ($a['status'] === 'diterima'): ?>
                                        <!-- Tidak ada tombol seleksi/terima/tolak -->
                                    <?php elseif ($a['status'] === 'ditolak'): ?>
                                        <!-- Tidak ada tombol seleksi/terima/tolak -->
                                    <?php endif; ?>
                                    <a href="?action=hapus&id=<?= $a['id'] ?>&filter=<?= urlencode($status_filter) ?>" class="action-button button-delete-permanent" onclick="return confirm('Hapus permanen?')">Hapus Permanen</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Modal Detail -->
    <div id="applicant-detail-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="detail-modal-title">Detail Pendaftar</h3>
                <button class="modal-close-button" id="close-detail-modal-button">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Detail akan diisi oleh JS -->
            </div>
        </div>
    </div>

    <!-- Modal Tolak -->
    <div id="rejection-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="rejection-modal-title">Alasan Penolakan</h3>
                <button class="modal-close-button" id="close-rejection-modal-button">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rejection-form" method="post">
                    <input type="hidden" name="id" id="rejection-applicant-id">
                    <textarea name="reason" id="rejection-reason" rows="5" style="width:100%;padding:10px;border-radius:8px;border:1px solid #dee2e6;font-family:'Poppins',sans-serif;font-size:1rem;box-sizing:border-box;" placeholder="Contoh: Data NIK tidak valid, mohon periksa kembali." required></textarea>
                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                        <button type="button" class="action-button" id="cancel-rejection-button" style="background-color:#6c757d;color:white;">Batal</button>
                        <button type="submit" class="action-button button-tolak">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
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
    // Modal logic
    function showDetail(id) {
        const data = <?php echo json_encode($applicants); ?>;
        const a = data.find(x => x.id == id);
        if (!a) return;
        let html = '';
        html += `<p><strong>Nama Lengkap:</strong> ${a.name}</p>`;
        html += `<p><strong>NIK:</strong> ${a.nik}</p>`;
        html += `<p><strong>Telepon:</strong> ${a.phone}</p>`;
        html += `<p><strong>Email:</strong> ${a.email}</p>`;
        html += `<p><strong>Alamat:</strong> ${a.address || '-'}</p>`;
        html += `<p><strong>Pendidikan:</strong> ${a.education || '-'}</p>`;
        html += `<p><strong>Ketersediaan:</strong> ${a.availability || '-'}</p>`;
        html += `<p><strong>Status:</strong> ${a.status}</p>`;
        html += `<p><strong>Peran:</strong> ${a.role || 'relawan'}</p>`;
        if (a.status === 'ditolak' && a.rejection_reason) {
            html += `<p><strong>Alasan Penolakan:</strong> ${a.rejection_reason}</p>`;
        }
        // Tambahan: Preview dokumen
        if (a.document_name) {
            const file = 'uploads/' + a.document_name;
            const ext = a.document_name.split('.').pop().toLowerCase();
            html += `<p><strong>Dokumen:</strong><br>`;
            if (["jpg","jpeg","png"].includes(ext)) {
                html += `<img src='${file}' alt='Dokumen' style='max-width:300px;max-height:400px;display:block;margin-bottom:10px;'>`;
                html += `<a href='${file}' download class='btn btn-sm btn-primary'>Unduh Gambar</a>`;
            } else if (ext === 'pdf') {
                html += `<embed src='${file}' type='application/pdf' width='100%' height='400px'/><br>`;
                html += `<a href='${file}' download class='btn btn-sm btn-primary'>Unduh PDF</a>`;
            } else {
                html += `<a href='${file}' download class='btn btn-sm btn-primary'>Unduh Dokumen</a>`;
            }
            html += `</p>`;
        } else {
            html += `<p><strong>Dokumen:</strong> -</p>`;
        }
        document.getElementById('modal-body-content').innerHTML = html;
        document.getElementById('applicant-detail-modal').style.display = 'flex';
    }
    document.getElementById('close-detail-modal-button').onclick = function() {
        document.getElementById('applicant-detail-modal').style.display = 'none';
    };
    document.getElementById('applicant-detail-modal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };
    // Modal tolak
    function openTolakModal(id, name) {
        document.getElementById('rejection-applicant-id').value = id;
        document.getElementById('rejection-reason').value = '';
        document.getElementById('rejection-modal').style.display = 'flex';
    }
    document.getElementById('close-rejection-modal-button').onclick = function() {
        document.getElementById('rejection-modal').style.display = 'none';
    };
    document.getElementById('cancel-rejection-button').onclick = function() {
        document.getElementById('rejection-modal').style.display = 'none';
    };
    // Submit form tolak
    document.getElementById('rejection-form').onsubmit = function(e) {
        e.preventDefault();
        var id = document.getElementById('rejection-applicant-id').value;
        var reason = document.getElementById('rejection-reason').value;
        if (!reason) return alert('Mohon isi alasan penolakan.');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'pengajuan.php?action=tolak&id=' + id + '&filter=<?= urlencode($status_filter) ?>';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'reason';
        input.value = reason;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    };
    </script>
</body>

</html>