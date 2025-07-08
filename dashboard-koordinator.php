<?php
require_once 'config/db.php';
require_once 'helpers/status-helper.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'koordinator') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

date_default_timezone_set('Asia/Jakarta');
$now = new DateTime();

$q_auto = $conn->query("SELECT a.id, k.tanggal, k.waktu_mulai, k.waktu_selesai FROM assignment_relawan ar JOIN assignments a ON ar.assignment_id=a.id JOIN kegiatan k ON a.kegiatan_id=k.id WHERE ar.relawan_id=$user_id AND a.status IN ('terjadwal','berlangsung')");
if ($q_auto) {
    while ($row = $q_auto->fetch_assoc()) {
        $statusBaru = getStatusOtomatis($row['tanggal'], $row['waktu_mulai'], $row['waktu_selesai']);
        if ($statusBaru) {
            $conn->query("UPDATE assignments SET status='$statusBaru' WHERE id=" . intval($row['id']));
        }
    }
}
// Helper
function safe($v) { return ($v===null||$v===''||$v===false)?'-':htmlspecialchars($v); }

// Ambil data relawan
$relawan = $conn->query("SELECT * FROM applicants WHERE id=$user_id LIMIT 1")->fetch_assoc();
$status = $relawan['status'] ?? '';
$role = $relawan['role'] ?? 'relawan';

// Notifikasi belum dibaca
$notif_count = $conn->query("SELECT COUNT(*) FROM notifikasi WHERE user_id=$user_id AND status_baca='belum_terbaca'")->fetch_row()[0];

// Penugasan aktif/berikutnya
$penugasan = [];
if ($status === 'diterima') {
    $q = $conn->query("SELECT a.*, k.nama_kegiatan, k.tanggal, k.waktu_mulai, k.waktu_selesai, k.lokasi FROM assignment_relawan ar JOIN assignments a ON ar.assignment_id=a.id JOIN kegiatan k ON a.kegiatan_id=k.id WHERE ar.relawan_id=$user_id AND a.status IN ('terjadwal','berlangsung') ORDER BY k.tanggal ASC, k.waktu_mulai ASC LIMIT 1");
    if ($q) $penugasan = $q->fetch_assoc();
}

// Riwayat evaluasi
$evaluasi = [];
if ($status === 'diterima') {
    $q = $conn->query("SELECT e.*, k.nama_kegiatan, k.tanggal FROM evaluasi e JOIN kegiatan k ON e.id_kegiatan = k.id WHERE e.id_relawan = $user_id ORDER BY e.tanggal_evaluasi DESC");
    while ($row = $q->fetch_assoc()) $evaluasi[] = $row;
}

// Tugas koordinator
$koordinator_kegiatan = [];
if ($status === 'diterima' && $role === 'koordinator') {
    $q = $conn->query("SELECT a.*, k.nama_kegiatan, k.tanggal, k.status as status_kegiatan FROM assignments a JOIN kegiatan k ON a.kegiatan_id=k.id WHERE a.koordinator_id=$user_id AND a.status IN ('terjadwal','berlangsung') ORDER BY k.tanggal ASC, k.waktu_mulai ASC");
    while ($row = $q->fetch_assoc()) $koordinator_kegiatan[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CivicaCare</title>

    <!-- Google Fonts untuk Tipografi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Library Ikon Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <style>
        /* === Pengaturan Dasar === */
        :root {
            --primary-color: #6A38C2;
            /* Ungu */
            --primary-light: #F0EBf9;
            /* Ungu Muda */
            --secondary-color: #CC0000;
            /* Merah */
            --dark-text: #1a202c;
            --light-text: #555;
            --bg-light: #f7fafc;
            --border-color: #e2e8f0;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            /* Fallback color */
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            /* Membuat background tidak ikut scroll */
            color: var(--dark-text);
        }

        /* === Header / Navigation Bar === */
        .main-header {
            background-color: #fff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .bg-image {
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
            height: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--dark-text);
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            margin-right: 10px;
        }

        .logo h1 {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            margin: 0;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-icon {
            font-size: 1.4rem;
            color: var(--light-text);
            cursor: pointer;
            position: relative;
        }

        .nav-icon .notification-dot {
            position: absolute;
            top: 0;
            right: -2px;
            width: 8px;
            height: 8px;
            background-color: var(--secondary-color);
            border-radius: 50%;
            border: 1px solid #fff;
            display: none;
            /* Sembunyikan secara default, JS akan menampilkannya */
        }

        /* Style untuk pesan jika tidak ada penugasan */
        .no-assignment-message {
            text-align: center;
            color: var(--light-text);
            padding: 20px;
            font-style: italic;
            font-size: 0.95rem;
        }

        /* NEW: Status Badge Styles */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            margin-left: 10px;
            /* Space from text */
            vertical-align: middle;
            /* Align with text */
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

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary-color);
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

        .modal-body p {
            font-size: 1rem;
            color: var(--light-text);
            line-height: 1.6;
        }

        /* === Konten Utama === */
        main {
            padding: 40px;
        }

        /* === Banner Ajakan (CTA) === */
        .cta-banner {
            background-color: var(--primary-light);
            border: 1px solid var(--primary-color);
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 40px;
        }

        .cta-banner h2 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5rem;
            color: var(--dark-text);
        }

        .cta-banner .cta-button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .cta-banner .cta-button:hover {
            background-color: #592EAB;
        }

        /* === Kartu Dashboard === */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .card {
            background-color: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            padding: 25px;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-body {
            flex-grow: 1;
            /* Membuat body mengambil sisa ruang */
            color: var(--light-text);
            line-height: 1.7;
        }

        .card-body ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .card-body li {
            padding: 5px 0;
        }

        .card-footer {
            margin-top: 20px;
            text-align: right;
        }

        .card-footer .detail-button {
            background-color: #fff;
            color: var(--primary-color);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid var(--primary-color);
            transition: all 0.3s;
        }

        .card-footer .detail-button:hover {
            background-color: var(--primary-color);
            color: #fff;
        }

        /* === Media Query untuk Responsif === */
        @media (max-width: 992px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                padding: 15px 20px;
            }

            main {
                padding: 20px;
            }

            .logo h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Header / Navigation Bar -->
    <header class="main-header">
        <a href="#" class="logo">
            <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
            <h1>CivicaCare</h1>
        </a>
        <nav class="header-nav">
            <a href="riwayat-notifikasi.php" class="nav-icon">
                <i class="fa-solid fa-bell"></i>
                <?php if ($notif_count > 0): ?><span class="notification-dot" style="display:block;"></span><?php else: ?><span class="notification-dot"></span><?php endif; ?>
            </a>
            <a href="profile.php" class="nav-icon"><i class="fa-solid fa-user-circle"></i></a>
            <a href="logout.php" class="nav-icon" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </nav>
    </header>

    <!-- Konten Utama -->
    <main>
        <!-- Banner Ajakan -->
        <?php if ($status === 'diajukan' || $status === 'diseleksi' || $status === 'diterima'): ?>
            <!-- Tidak tampilkan banner -->
        <?php elseif ($status === 'ditolak'): ?>
            <div class="cta-banner">
                <h2>Perbaiki Pendaftaran Anda</h2>
                <p>Pengajuan Anda sebelumnya ditolak. Silakan periksa kembali data Anda dan ajukan ulang.</p>
                <a href="form-pendaftaran-relawan.php" class="cta-button" id="isi-formulir-button">Perbaiki Formulir</a>
            </div>
        <?php else: ?>
            <div class="cta-banner">
                <h2>Lengkapi Pendaftaran Anda!</h2>
                <p>Hanya satu langkah lagi untuk menjadi bagian dari relawan hebat kami.</p>
                <a href="form-pendaftaran-relawan.php" class="cta-button" id="isi-formulir-button">Isi Formulir</a>
            </div>
        <?php endif; ?>

        <!-- Kartu Koordinator -->
        <?php if ($status === 'diterima' && $role === 'koordinator'): ?>
        <div class="card" id="koordinator-card" style="margin-bottom: 40px;">
            <div class="card-header">
                <i class="fa-solid fa-user-tie card-icon"></i>
                <h3>Tugas Koordinator</h3>
            </div>
            <div class="card-body" id="koordinator-content">
                <p>Anda adalah koordinator untuk kegiatan berikut:</p>
                <ul id="coordinated-activities-list">
                    <?php if (empty($koordinator_kegiatan)): ?>
                        <li class="no-assignment-message">Anda belum ditugaskan sebagai koordinator untuk kegiatan apapun yang akan datang atau sedang berlangsung.</li>
                    <?php else: foreach ($koordinator_kegiatan as $k): ?>
                        <li><strong><?= safe($k['nama_kegiatan']) ?></strong> (<?= safe(date('d M', strtotime($k['tanggal']))) ?>) - <span class="status-badge status-<?= safe($k['status']) ?>"><?= ucfirst($k['status']) ?></span></li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
            <div class="card-footer" id="koordinator-footer" style="display: block;">
                <a href="detail-koordinator-dashboard.php" class="detail-button">Lihat Semua Tugas Koordinator</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Kartu Dashboard -->
        <div class="dashboard-cards">
            <!-- Kartu Penugasan Saya -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-calendar-days card-icon"></i>
                    <h3>Penugasan Saya</h3>
                </div>
                <div class="card-body" id="penugasan-content">
                    <?php if ($status !== 'diterima'): ?>
                        <p class="no-assignment-message">
                            <?php if ($status === 'diajukan' || $status === 'diseleksi'): ?>Penugasan akan ditampilkan setelah pendaftaran Anda disetujui admin.
                            <?php elseif ($status === 'ditolak'): ?>Pendaftaran Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.
                            <?php else: ?>Silakan lengkapi formulir pendaftaran untuk melihat penugasan Anda.
                            <?php endif; ?>
                        </p>
                    <?php elseif (!$penugasan): ?>
                        <p class="no-assignment-message">Belum ada penugasan yang akan datang atau sedang berlangsung untuk Anda.</p>
                    <?php else: ?>
                        <p>
                            <strong>Berikutnya:</strong> <?= safe($penugasan['nama_kegiatan']) ?> <span class="status-badge status-<?= safe($penugasan['status']) ?>"><?= ucfirst($penugasan['status']) ?></span><br>
                            <strong>Tanggal:</strong> <?= safe(date('l, d F Y', strtotime($penugasan['tanggal']))) ?><br>
                            <strong>Waktu:</strong> <?= safe(substr($penugasan['waktu_mulai'],0,5)) ?> - <?= safe(substr($penugasan['waktu_selesai'],0,5)) ?><br>
                            <strong>Lokasi:</strong> <?= safe($penugasan['lokasi']) ?>
                        </p>
                        <div class="card-footer">
                            <a href="detail-penugasan.php?id=<?= $penugasan['id'] ?>" class="detail-button">Lihat Detail</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Kartu Riwayat Evaluasi -->
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-chart-line card-icon"></i>
                    <h3>Riwayat Evaluasi</h3>
                </div>
                <div class="card-body" id="evaluasi-content">
                    <?php if ($status !== 'diterima'): ?>
                        <p class="no-assignment-message">
                            <?php if ($status === 'diajukan' || $status === 'diseleksi'): ?>Riwayat evaluasi akan ditampilkan setelah pendaftaran Anda disetujui admin.
                            <?php elseif ($status === 'ditolak'): ?>Pendaftaran Anda ditolak. Riwayat evaluasi tidak tersedia.
                            <?php else: ?>Silakan lengkapi formulir pendaftaran untuk melihat riwayat evaluasi Anda.
                            <?php endif; ?>
                        </p>
                    <?php elseif (empty($evaluasi)): ?>
                        <p class="no-assignment-message">Belum ada evaluasi yang tersedia untuk Anda.</p>
                    <?php else:
                        $total = $count = 0;
                        foreach ($evaluasi as $e) {
                            $total += $e['kedisiplinan'] + $e['komunikasi'] + $e['kerjasama'] + $e['tanggung_jawab'];
                            $count += 4;
                        }
                        $avg = $count ? number_format($total/$count,1) : 'N/A';
                        $latest = $evaluasi[0];
                    ?>
                        <p>
                            <strong>Rata-rata Skor:</strong> <?= $avg ?> / 5.0<br>
                            <strong>Evaluasi Terbaru:</strong> <?= safe($latest['nama_kegiatan']) ?><br>
                            <strong>Umpan Balik:</strong> <?= safe(mb_strimwidth($latest['umpan_balik'],0,50,'...')) ?>
                        </p>
                        <div class="card-footer">
                            <a href="detail-evaluasi.php" class="detail-button">Lihat Riwayat</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>