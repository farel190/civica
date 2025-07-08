<?php
require_once 'config/db.php';
session_start();

// Cek login dan role relawan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'relawan') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data pengajuan milik user ini
$stmt = $conn->prepare("SELECT status, rejection_reason, name, nik, phone, email, address, education, availability, document_name FROM applicants WHERE id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
$stmt->close();

function safe($val) {
    return ($val === null || $val === '' || $val === false) ? '-' : htmlspecialchars($val);
}

$status_badge = [
    'diajukan' => 'status-diajukan',
    'diseleksi' => 'status-diseleksi',
    'diterima' => 'status-diterima',
    'ditolak' => 'status-ditolak',
    'mengundurkan_diri' => 'status-mengundurkan_diri',
];

// Handle aksi relawan (ajukan ulang jika ditolak)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_ulang']) && $app && $app['status'] === 'ditolak') {
    $stmt = $conn->prepare("UPDATE applicants SET status='diajukan', rejection_reason=NULL WHERE id=?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: pengajuan-relawan.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengajuan Saya - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { font-family:'Poppins',sans-serif; background:#f7fafc; margin:0; }
        .container { max-width:600px; margin:40px auto; background:#fff; border-radius:15px; box-shadow:0 4px 16px rgba(0,0,0,0.07); padding:30px; }
        h2 { color:#6A38C2; margin-top:0; }
        .status-badge { display:inline-block; padding:6px 14px; border-radius:6px; font-weight:600; font-size:0.95rem; margin-bottom:8px; }
        .status-diajukan { background:#ffc107; color:#343a40; }
        .status-diseleksi { background:#17a2b8; color:#fff; }
        .status-diterima { background:#28a745; color:#fff; }
        .status-ditolak { background:#dc3545; color:#fff; }
        .status-mengundurkan_diri { background:#6c757d; color:#fff; }
        .row { margin-bottom:18px; }
        .label { font-weight:600; color:#555; display:block; margin-bottom:3px; }
        .value { font-size:1.05rem; color:#222; }
        .reason-box { background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:8px; padding:12px 16px; margin-top:10px; }
        .doc-link { color:#6A38C2; text-decoration:underline; }
        .doc-link:hover { color:#592EAB; }
        .back-btn { display:inline-block; margin-top:25px; background:#6A38C2; color:#fff; padding:10px 28px; border-radius:8px; text-decoration:none; font-weight:600; transition:background 0.2s; }
        .back-btn:hover { background:#592EAB; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Status Pengajuan Saya</h2>
        <?php if (!$app): ?>
            <div style="color:#dc3545;font-weight:600;">Anda belum pernah mengajukan pendaftaran relawan.</div>
        <?php else: ?>
            <div class="row">
                <span class="label">Status Pengajuan</span>
                <span class="status-badge <?= $status_badge[$app['status']] ?? '' ?>"><?= safe($app['status']) ?></span>
            </div>
            <div class="row">
                <span class="label">Nama Lengkap</span>
                <span class="value"><?= safe($app['name']) ?></span>
            </div>
            <div class="row">
                <span class="label">NIK</span>
                <span class="value"><?= safe($app['nik']) ?></span>
            </div>
            <div class="row">
                <span class="label">Nomor Telepon</span>
                <span class="value"><?= safe($app['phone']) ?></span>
            </div>
            <div class="row">
                <span class="label">Email</span>
                <span class="value"><?= safe($app['email']) ?></span>
            </div>
            <div class="row">
                <span class="label">Alamat</span>
                <span class="value"><?= safe($app['address']) ?></span>
            </div>
            <div class="row">
                <span class="label">Pendidikan Terakhir</span>
                <span class="value"><?= safe($app['education']) ?></span>
            </div>
            <div class="row">
                <span class="label">Jadwal Ketersediaan</span>
                <span class="value"><?= safe($app['availability']) ?></span>
            </div>
            <div class="row">
                <span class="label">Dokumen</span>
                <?php if ($app['document_name']): ?>
                    <a class="doc-link" href="uploads/<?= urlencode($app['document_name']) ?>" target="_blank">Lihat Dokumen</a>
                <?php else: ?>
                    <span class="value">-</span>
                <?php endif; ?>
            </div>
            <?php if ($app['status'] === 'ditolak' && $app['rejection_reason']): ?>
            <div class="reason-box">
                <strong>Alasan Penolakan:</strong><br><?= safe($app['rejection_reason']) ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <a href="dashboard-relawan.php" class="back-btn"><i class="fa fa-arrow-left"></i> Kembali ke Dashboard</a>
        <?php if ($app && $app['status'] === 'ditolak'): ?>
        <form method="post" style="margin-top:20px;">
            <button type="submit" name="ajukan_ulang" class="back-btn" style="background:#ffc107;color:#222;">Ajukan Ulang</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
