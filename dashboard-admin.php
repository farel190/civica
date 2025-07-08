<?php
require_once 'config/db.php';
session_start();

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Ambil data ringkasan
// Jumlah relawan
$res = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'relawan'");
$total_relawan = $res ? $res->fetch_row()[0] : 0;

// Jumlah pengajuan
$res = $conn->query("SELECT COUNT(*) FROM pengajuan");
$total_pengajuan = $res ? $res->fetch_row()[0] : 0;

// Jumlah kegiatan
$res = $conn->query("SELECT COUNT(*) FROM kegiatan");
$total_kegiatan = $res ? $res->fetch_row()[0] : 0;

// Jumlah penjadwalan
$res = $conn->query("SELECT COUNT(*) FROM penjadwalan");
$total_penjadwalan = $res ? $res->fetch_row()[0] : 0;

// Jumlah evaluasi
$res = $conn->query("SELECT COUNT(*) FROM evaluasi");
$total_evaluasi = $res ? $res->fetch_row()[0] : 0;

// Notifikasi admin (belum terbaca)
$res = $conn->query("SELECT COUNT(*) FROM notifikasi WHERE id_user = {$_SESSION['user_id']} AND status = 'belum_terbaca'");
$total_notif = $res ? $res->fetch_row()[0] : 0;

// Jumlah pengajuan baru (status 'diajukan')
$res = $conn->query("SELECT COUNT(*) FROM applicants WHERE status = 'diajukan'");
$total_pengajuan_baru = $res ? $res->fetch_row()[0] : 0;

// Data admin
$admin_name = $_SESSION['username'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? 'admin@civicacare.com';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root { --primary-red: #C70000; --bg-light: #F8F9FA; --text-dark: #212529; --text-light: #f8f9fa; --border-color: #dee2e6;}
        body, html { margin:0; padding:0; font-family:'Poppins',sans-serif; background-color:var(--bg-light); color:var(--text-dark); height:100vh; overflow:hidden; }
        .dashboard-container { display:flex; min-height:100vh; height:100vh; }
        .sidebar { width:260px; background-color:var(--primary-red); color:var(--text-light); padding:20px; display:flex; flex-direction:column; position:relative; z-index:10;}
        .sidebar-header { display:flex; align-items:center; gap:15px; padding-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.2); margin-bottom:20px; justify-content:flex-start;}
        .sidebar-header h2 { margin:0; font-size:1.5rem;}
        .sidebar-nav ul { list-style:none; padding:0; margin:0;}
        .sidebar-nav li a { display:flex; align-items:center; gap:15px; padding:15px 10px; border-radius:8px; color:var(--text-light); text-decoration:none; font-weight:500; transition:background-color 0.3s; position:relative;}
        .sidebar-nav li a:hover, .sidebar-nav li.active a { background-color:rgba(0,0,0,0.2);}
        .sidebar-nav li a .nav-icon { font-size:1.2rem; width:20px; text-align:center; flex-shrink:0;}
        .sidebar-footer { margin-top:auto;}
        .sidebar-nav .notification-dot { position:absolute; top:12px; right:15px; width:10px; height:10px; background-color:#ffc107; border-radius:50%; border:2px solid var(--primary-red);}
        .main-content { flex-grow:1; display:flex; flex-direction:column; height:100vh; overflow:auto; }
        .main-header { display:flex; justify-content:flex-end; align-items:center; padding:20px 40px; background-color:#fff; position:relative; overflow:hidden;}
        .header-shape { position:absolute; top:0; right:0; width:350px; height:100%; background-color:var(--primary-red); clip-path:polygon(15% 0,100% 0,100% 100%,0% 100%);}
        .user-profile { display:flex; align-items:center; gap:15px; position:relative; z-index:1;}
        .user-avatar { width:45px; height:45px; border-radius:50%; background-color:#e9ecef; display:flex; justify-content:center; align-items:center; color:#495057; font-size:1.5rem;}
        .user-info { color:var(--text-light); text-align:right;}
        .user-info .user-name { font-weight:600;}
        .user-info .user-email { font-size:0.8rem; opacity:0.9;}
        .content-area { padding:40px; flex-grow:1;}
        .welcome-section { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; height:60vh;}
        .welcome-section .logo { display:flex; align-items:center; margin-bottom:20px;}
        .welcome-section .logo-icon { width:60px; height:60px; margin-right:15px;}
        .welcome-section .logo h1 { font-family:'Lora',serif; font-size:4rem; margin:0; color:#343a40;}
        .welcome-section p { font-size:1.5rem; color:#495057; font-weight:500;}
        .summary-cards { display:flex; gap:30px; margin-top:40px; flex-wrap:wrap; justify-content:center;}
        .summary-card { background:#fff; border-radius:15px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:30px 40px; min-width:180px; text-align:center; flex:1;}
        .summary-card .icon { font-size:2.2rem; margin-bottom:10px; color:var(--primary-red);}
        .summary-card .label { font-size:1.1rem; color:#888;}
        .summary-card .value { font-size:2rem; font-weight:700; color:#212529;}
        @media (max-width:900px){ .summary-cards{flex-direction:column;gap:20px;}.summary-card{min-width:unset;}}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Menu</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="dashboard-admin.php"><i class="fa-solid fa-table-columns nav-icon"></i><span>Dashboard</span></a></li>
                    <li><a href="daftar-relawan.php"><i class="fa-solid fa-users nav-icon"></i><span>Daftar Relawan</span></a></li>
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
                        <div class="user-name"><?= htmlspecialchars($admin_name) ?></div>
                        <div class="user-email"><?= htmlspecialchars($admin_email) ?></div>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
            </header>
            <section class="content-area">
                <div class="welcome-section">
                    <div class="logo">
                        <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
                        <h1>CivicaCare</h1>
                    </div>
                    <p>Selamat datang kembali, <?= htmlspecialchars($admin_name) ?>!</p>
                </div>
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-users"></i></div>
                        <div class="label">Total Relawan</div>
                        <div class="value"><?= $total_relawan ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-file-import"></i></div>
                        <div class="label">Total Pengajuan</div>
                        <div class="value"><?= $total_pengajuan ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-people-group"></i></div>
                        <div class="label">Total Kegiatan</div>
                        <div class="value"><?= $total_kegiatan ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="label">Total Penjadwalan</div>
                        <div class="value"><?= $total_penjadwalan ?></div>
                    </div>
                    <div class="summary-card">
                        <div class="icon"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div class="label">Total Evaluasi</div>
                        <div class="value"><?= $total_evaluasi ?></div>
                    </div>
                </div>
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