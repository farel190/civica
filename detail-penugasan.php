<?php
require_once 'config/db.php';
require_once 'helpers/status-helper.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'relawan') {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['id'] ?? 0);

// Helper function
function safe($v) {
    return ($v === null || $v === '' || $v === false) ? '-' : htmlspecialchars($v);
}

// Ambil data penugasan dan kegiatan
$q = $conn->query("SELECT 
        a.*, 
        k.nama_kegiatan, k.tanggal, k.waktu_mulai, k.waktu_selesai, k.lokasi, k.deskripsi, k.perlengkapan, k.catatan, 
        a.status as status_penugasan,
        r.name as koordinator_nama
    FROM assignment_relawan ar
    JOIN assignments a ON ar.assignment_id = a.id
    JOIN kegiatan k ON a.kegiatan_id = k.id
    LEFT JOIN applicants r ON a.koordinator_id = r.id
    WHERE ar.relawan_id = $user_id AND a.id = $assignment_id
    LIMIT 1");

$penugasan = $q ? $q->fetch_assoc() : null;

if (!$penugasan) {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Detail Penugasan</title></head><body style="font-family:Poppins,sans-serif;text-align:center;padding:60px;">Penugasan tidak ditemukan atau Anda tidak berhak mengaksesnya.<br><a href="dashboard-relawan.php" style="color:#6A38C2;font-weight:600;">Kembali ke Dashboard</a></body></html>';
    exit;
}

$penugasan['status_penugasan'] = getStatusOtomatis(
    $penugasan['tanggal'],
    $penugasan['waktu_mulai'],
    $penugasan['waktu_selesai']
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penugasan - CivicaCare</title>
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
            --bg-page: #f7fafc;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-page);
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--dark-text);
        }

        /* Wrapper utama untuk centering dan padding halaman */
        .main-wrapper {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            /* Rata atas */
            min-height: 100vh;
            padding: 40px 20px;
            /* Padding vertikal dan horizontal */
            box-sizing: border-box;
        }

        /* NEW: Status Badge Styles (copied from dashboard-relawan.html for consistency) */
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

        .status-berlangsung,
        .status-ongoing {
            background-color: #ffc107;
            /* Yellow */
            color: #343a40;
        }

        .status-selesai,
        .status-completed {
            background-color: #28a745;
            /* Green */
            color: #fff;
        }

        .detail-container {
            width: 100%;
            max-width: 1000px;
            /* Lebar maksimal yang ideal untuk konten detail */
        }

        .detail-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px 40px;
            width: 100%;
            box-sizing: border-box;
            /* Penting agar padding tidak menambah lebar */
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--dark-text);
        }

        .back-button {
            background-color: transparent;
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid var(--primary-color);
            transition: all 0.3s;
            white-space: nowrap;
            /* Mencegah teks tombol turun baris */
        }

        .back-button:hover {
            background-color: var(--primary-color);
            color: #fff;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            padding-left: 10px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            /* Grid responsif */
            gap: 25px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .info-item .icon {
            font-size: 1.2rem;
            color: var(--light-text);
            margin-top: 4px;
            width: 20px;
            text-align: center;
        }

        .info-item .text strong {
            display: block;
            color: var(--dark-text);
            font-weight: 600;
        }

        .info-item .text span {
            color: var(--light-text);
        }

        .description-text {
            line-height: 1.8;
            color: var(--light-text);
        }

        .navigation-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .nav-button {
            background-color: var(--primary-color);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .nav-button:hover {
            background-color: #592EAB;
        }

        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <div class="detail-container">
            <div class="detail-card">
                <div class="card-header">
                    <h2><?= safe($penugasan['nama_kegiatan']) ?>
                        <span class="status-badge status-<?= safe(strtolower($penugasan['status_penugasan'])) ?>">
                            <?= ucfirst($penugasan['status_penugasan']) ?>
                        </span>
                    </h2>
                    <a href="dashboard-relawan.php" class="back-button"><i class="fas fa-arrow-left"></i> Kembali</a>
                </div>
                <div class="detail-section">
                    <h3>Informasi Kegiatan</h3>
                    <div class="detail-grid">
                        <div class="info-item">
                            <i class="fas fa-calendar-alt icon"></i>
                            <div class="text">
                                <strong>Tanggal & Waktu:</strong><br>
                                    <?= safe(date('l, d F Y', strtotime($penugasan['tanggal']))) ?>,
                                    <?= safe(substr($penugasan['waktu_mulai'], 0, 5)) ?> - <?= safe(substr($penugasan['waktu_selesai'], 0, 5)) ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt icon"></i>
                            <div class="text">
                                <strong>Lokasi</strong>
                                <span><?= safe($penugasan['lokasi']) ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-tie icon"></i>
                            <div class="text">
                                <strong>Koordinator Lapangan:</strong><br>
                                <?= safe($penugasan['koordinator_nama']) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="info-section">
                    <h3>Deskripsi Tugas</h3>
                        <p class="description-text"><?= safe($penugasan['deskripsi']) ?></p>
                </div>
                <div class="info-section">
                    <h3>Perlengkapan & Catatan</h3>
                        <p class="description-text">
            <strong>Perlengkapan:</strong><br>
            <?= safe($penugasan['perlengkapan']) ?>
        </p>
        <p class="description-text">
            <strong>Catatan:</strong><br>
            <?= safe($penugasan['catatan']) ?>
        </p>
                </div>

    </div>
</body>
</html>