<?php
require_once 'config/db.php';
session_start();

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle promosi/demosi koordinator via GET/POST
if (isset($_GET['promote']) && is_numeric($_GET['promote'])) {
    $id = intval($_GET['promote']);
    // Ambil email dari tabel users
    $result = $conn->query("SELECT email FROM users WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $conn->real_escape_string($row['email']); // hindari SQL injection
        // Update role di users
        $conn->query("UPDATE users SET role = 'koordinator' WHERE id = $id");
        // Update role di applicants jika ditemukan
        $conn->query("UPDATE applicants SET role = 'koordinator' WHERE email = '$email'");
    }
    header("Location: daftar-relawan.php");
    exit;
}


if (isset($_GET['demote']) && is_numeric($_GET['demote'])) {
    $id = intval($_GET['demote']);

    // Ambil email dari users
    $result = $conn->query("SELECT email FROM users WHERE id = $id");
    $row = $result->fetch_assoc();
    $email = $row['email'];
    // Update role di users
    $conn->query("UPDATE users SET role = 'relawan' WHERE id = $id");
    // Sinkronisasi role di applicants (jika ada)
    $conn->query("UPDATE applicants SET role = 'relawan' WHERE email = '$email'");
    header("Location: daftar-relawan.php");
    exit;
}


// Ambil data relawan aktif
$search = $_GET['search'] ?? '';
$day = $_GET['day'] ?? '';
$where = "WHERE u.role IN ('relawan','koordinator') AND u.status = 'aktif'";
$params = [];
if ($search) {
    $where .= " AND (a.name LIKE ? OR u.email LIKE ? OR a.phone LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}
if ($day) {
    $where .= " AND (a.availability LIKE ? OR a.availability LIKE ?)";
    $params[] = "%$day%";
    $params[] = "%$day%";
}
$sql = "SELECT u.id, a.name, u.email, a.phone, a.education, a.role, a.nik, a.address, a.availability, a.status,
        a.name AS a_name, a.nik AS a_nik, a.phone AS a_phone, a.address AS a_address, a.education AS a_education, a.availability AS a_availability
        FROM users u
        LEFT JOIN applicants a ON (a.email = u.email AND a.status = 'diterima')
        $where
        ORDER BY a.name ASC";
$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$relawan = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Relawan - CivicaCare Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* ...gunakan style dari file Anda sebelumnya... */
        :root { --primary-red: #C70000; --bg-light: #F8F9FA; --text-dark: #212529; --text-light: #f8f9fa; --border-color: #dee2e6; --primary-color: #6A38C2; --primary-light: #F0EBf9; --secondary-color: #CC0000;}
        body, html { margin:0; padding:0; font-family:'Poppins',sans-serif; background-color:var(--bg-light); color:var(--text-dark); min-height:100vh; display:flex; flex-direction:column;}
        .dashboard-container { display:flex; min-height:100vh;}
        .sidebar { width:260px; background-color:var(--primary-red); color:var(--text-light); padding:20px; display:flex; flex-direction:column; position:relative; z-index:10; transition: width 0.3s ease;}
        .sidebar-header { display:flex; align-items:center; gap:15px; padding-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.2); margin-bottom:20px; justify-content:flex-start;}
        .sidebar-header h2 { margin:0; font-size:1.5rem;}
        .sidebar-nav ul { list-style:none; padding:0; margin:0;}
        .sidebar-nav li a { display:flex; align-items:center; gap:15px; padding:15px 10px; border-radius:8px; color:var(--text-light); text-decoration:none; font-weight:500; transition:background-color 0.3s; position:relative;}
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color:rgba(0,0,0,0.2);}
        .sidebar-nav li a .nav-icon { font-size:1.2rem; width:20px; text-align:center; flex-shrink:0;}
        .sidebar-footer { margin-top:auto;}
        .sidebar-nav .notification-dot { position:absolute; top:12px; right:15px; width:10px; height:10px; background-color:#ffc107; border-radius:50%; border:2px solid var(--primary-red);}
        .sidebar.collapsed {width: 80px;}
        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-nav span {display: none;}
        .main-content { flex-grow:1; display:flex; flex-direction:column;}
        .main-content .main-header { display:flex; justify-content:flex-end; align-items:center; padding:20px 40px; background-color:#fff; position:relative; overflow:hidden;}
        .header-shape { position:absolute; top:0; right:0; width:350px; height:100%; background-color:var(--primary-red); clip-path:polygon(15% 0,100% 0,100% 100%,0% 100%);}
        .user-profile { display:flex; align-items:center; gap:15px; position:relative; z-index:1;}
        .user-avatar { width:45px; height:45px; border-radius:50%; background-color:#e9ecef; display:flex; justify-content:center; align-items:center; color:#495057; font-size:1.5rem;}
        .user-info { color:var(--text-light); text-align:right;}
        .user-info .user-name { font-weight:600;}
        .user-info .user-email { font-size:0.8rem; opacity:0.9;}
        .content-area { padding:40px; flex-grow:1;}
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;}
        .section-header h2 { font-size:2rem; margin:0; color:var(--primary-red);}
        .controls-container { display:flex; flex-direction:column; align-items:center; gap:20px; margin-bottom:30px;}
        .filter-section { display:flex; align-items:center; gap:15px; flex-wrap:wrap;}
        .day-filter-pills { display:flex; gap:10px;}
        .day-pill { background-color:#e9ecef; color:var(--light-text); border:1px solid var(--border-color); padding:8px 15px; border-radius:20px; cursor:pointer; font-weight:500; font-size:0.9rem; transition:all 0.3s;}
        .day-pill.active { background-color:var(--primary-light); color:var(--primary-color); border-color:var(--primary-color); font-weight:600; box-shadow:0 2px 4px rgba(106,56,194,0.1);}
        .search-section { display:flex; justify-content:center;}
        .search-box { display:flex; align-items:center; border:1px solid var(--border-color); border-radius:50px; background-color:#fff; box-shadow:0 2px 4px rgba(0,0,0,0.05); width:100%; max-width:900px; padding:0 15px; box-sizing:border-box; transition:all 0.3s;}
        .search-box:focus-within { border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(106,56,194,0.2);}
        .search-box .search-icon { color:var(--light-text); margin-right:10px;}
        .search-input { flex-grow:1; border:none; padding:10px 0; outline:none; background-color:transparent; font-size:1rem; box-sizing:border-box; color:var(--text-dark);}
        .clear-search-button { background:none; border:none; color:var(--light-text); font-size:1rem; cursor:pointer; padding:5px; margin-left:5px; transition:color 0.2s;}
        .clear-search-button:hover { color:var(--primary-red);}
        .data-table { width:100%; border-collapse:collapse; margin-top:20px; background-color:#fff; box-shadow:0 4px 6px rgba(0,0,0,0.05); border-radius:8px; overflow:hidden;}
        .data-table th, .data-table td { padding:15px; text-align:left; border-bottom:1px solid var(--border-color);}
        .data-table th { background-color:var(--primary-red); color:#fff; font-weight:600; text-transform:uppercase; font-size:0.9rem;}
        .data-table tbody tr:last-child td { border-bottom:none;}
        .data-table tbody tr:hover { background-color:#f1f1f1;}
        .role-badge { display:inline-block; padding:5px 10px; border-radius:5px; font-size:0.85rem; font-weight:600; text-transform:capitalize; background-color:#6c757d; color:#fff;}
        .role-koordinator { background-color:var(--primary-color);}
        .action-buttons-cell { display:flex; gap:8px;}
        .action-button { padding:8px 12px; border:none; border-radius:5px; cursor:pointer; font-size:0.85rem; font-weight:600; transition:background-color 0.2s;}
        .button-detail { background-color:#007bff; color:#fff;}
        .button-detail:hover { background-color:#0056b3;}
        .button-koordinator { background-color:#28a745; color:#fff;}
        .button-koordinator:hover { background-color:#218838;}
        .button-remove-koordinator { background-color:#ffc107; color:#343a40;}
        .button-remove-koordinator:hover { background-color:#e0a800;}
        .empty-table-message { text-align:center; padding:30px; color:#6c757d; font-style:italic;}
        .modal-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); display:flex; justify-content:center; align-items:center; z-index:1000;}
        .modal-content { background-color:#fff; padding:30px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.2); width:90%; max-width:600px; position:relative; max-height:80vh; overflow-y:auto;}
        .modal-header { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; margin-bottom:20px;}
        .modal-header h3 { margin:0; font-size:1.5rem; color:var(--primary-red);}
        .modal-close-button { background:none; border:none; font-size:2rem; color:#aaa; cursor:pointer;}
        .modal-close-button:hover { color:#666;}
        .modal-body p { margin-bottom:10px; line-height:1.6;}
        .modal-body strong { display:inline-block; min-width:120px;}
        .modal-body ul { list-style:none; padding:0; margin:0;}
        .modal-body li { margin-bottom:5px;}
        #menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 10px;
            /* Space between toggle and title */
        }
        @media (max-width:900px){ .content-area{padding:20px;}}
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
                    <li class="active"><a href="daftar-relawan.php"><i class="fa-solid fa-users nav-icon"></i><span>Daftar Relawan</span></a></li>
                    <li><a href="pengajuan.php"><i class="fa-solid fa-file-import nav-icon"></i><span>Pengajuan</span></a></li>
                    <li><a href="kegiatan.php"><i class="fa-solid fa-people-group nav-icon"></i><span>Kegiatan</span></a></li>
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
            <header class="main-header">
                <div class="header-shape"></div>
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                        <div class="user-email"><?= htmlspecialchars($_SESSION['email'] ?? 'admin@civicacare.com') ?></div>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </header>
            <section class="content-area">
                <div class="section-header">
                    <h2>Daftar Relawan Aktif</h2>
                </div>
                <div class="controls-container">
                    <form class="search-section" method="get" style="width:100%;max-width:900px;">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Cari relawan..." value="<?= htmlspecialchars($search) ?>">
                            <?php if ($search): ?>
                                <a href="daftar-relawan.php" class="clear-search-button"><i class="fas fa-times-circle"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="filter-section">
                        <div class="day-filter-pills">
                            <a href="daftar-relawan.php" class="day-pill<?= $day == '' ? ' active' : '' ?>">Semua</a>
                            <?php
                            $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
                            foreach ($days as $d):
                            ?>
                            <a href="daftar-relawan.php?<?= http_build_query(array_merge($_GET, ['day'=>$d])) ?>" class="day-pill<?= $day == $d ? ' active' : '' ?>"><?= ucfirst($d) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Pendidikan</th>
                            <th>Peran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relawan)): ?>
                            <tr><td colspan="6" class="empty-table-message">Tidak ada relawan aktif saat ini.</td></tr>
                        <?php else: foreach ($relawan as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['a_name'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($r['email'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($r['phone'] ?: $r['a_phone'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($r['education'] ?: $r['a_education'] ?: '-') ?></td>
                                <td>
                                    <span class="role-badge<?= $r['role'] === 'koordinator' ? ' role-koordinator' : '' ?>">
                                        <?= htmlspecialchars($r['role'] ?: '-') ?>
                                    </span>
                                </td>
                                <td class="action-buttons-cell">
                                    <button class="action-button button-detail" data-id="<?= $r['id'] ?>">Detail</button>
                                    <?php if ($r['role'] === 'koordinator'): ?>
                                        <a href="?demote=<?= $r['id'] ?>" class="action-button button-remove-koordinator" onclick="return confirm('Lepas koordinator?')">Lepas Koordinator</a>
                                    <?php else: ?>
                                        <a href="?promote=<?= $r['id'] ?>" class="action-button button-koordinator" onclick="return confirm('Jadikan koordinator?')">Jadikan Koordinator</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <!-- Detail Modal -->
    <div id="volunteer-detail-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="detail-modal-title">Detail Relawan</h3>
                <button class="modal-close-button" id="close-detail-modal-button">&times;</button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Detail will be loaded by JS -->
            </div>
        </div>
    </div>
    <script>
        // Modal logic
        document.querySelectorAll('.button-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                fetch('get-relawan-detail.php?id=' + id)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.id) {
                            document.getElementById('detail-modal-title').textContent = 'Detail Relawan: ' + data.nama_lengkap;
                            document.getElementById('modal-body-content').innerHTML = `
                                <p><strong>Nama Lengkap:</strong> ${data.nama_lengkap}</p>
                                <p><strong>NIK:</strong> ${data.nik || '-'}</p>
                                <p><strong>Telepon:</strong> ${data.phone || '-'}</p>
                                <p><strong>Email:</strong> ${data.email || '-'}</p>
                                <p><strong>Alamat:</strong> ${data.address || '-'}</p>
                                <p><strong>Pendidikan:</strong> ${data.education || '-'}</p>
                                <p><strong>Ketersediaan:</strong> ${data.availability ? data.availability : '-'}</p>
                                <p><strong>Status:</strong> ${data.status}</p>
                                <p><strong>Peran:</strong> ${data.role}</p>
                            `;
                            document.getElementById('volunteer-detail-modal').style.display = 'flex';
                        }
                    });
            });
        });
        document.getElementById('close-detail-modal-button').onclick = function() {
            document.getElementById('volunteer-detail-modal').style.display = 'none';
        };
        document.getElementById('volunteer-detail-modal').onclick = function(e) {
            if (e.target === this) this.style.display = 'none';
        };
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