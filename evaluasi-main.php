<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
$admin_name = $_SESSION['nama_lengkap'] ?? 'Admin';
$admin_email = $_SESSION['email'] ?? 'admin@gmail.com';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Jenis Evaluasi - CivicaCare Admin</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
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
        body, html {
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
        .sidebar {
            width: 260px;
            background-color: var(--primary-red);
            color: var(--text-light);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            position: relative;
            z-index: 10;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
            justify-content: flex-start;
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
        }
        .sidebar-footer {
            margin-top: auto;
        }
        .sidebar.collapsed {width: 80px;}
        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-nav span {display: none;}
        /* === Konten Utama === */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .evaluation-options {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        .option-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            min-width: 250px;
            min-height: 150px;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        .option-card i {
            font-size: 3rem;
            color: var(--primary-red);
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
            <!-- Header Konten -->
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

            <!-- Area Konten Dinamis -->
            <section class="content-area">
                <h2>Pilih Jenis Evaluasi</h2>
                <div class="evaluation-options">
                    <a href="evaluasi-per-kegiatan.php" class="option-card">
                        <i class="fas fa-user-check"></i>
                        <span>Evaluasi Relawan per Kegiatan</span>
                    </a>
                    <a href="evaluasi-keseluruhan.php" class="option-card">
                        <i class="fas fa-chart-line"></i>
                        <span>Evaluasi Keseluruhan Kegiatan</span>
                    </a>
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