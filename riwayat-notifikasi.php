<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'guest';

switch ($role) {
    case 'relawan':
        $back_url = 'dashboard-relawan.php';
        break;
    case 'koordinator':
        $back_url = 'dashboard-koordinator.php';
        break;
}
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Ambil notifikasi user
$stmt = $conn->prepare("SELECT id, judul, pesan, created_at, status_baca FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung total untuk pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_notifications);
$stmt->fetch();
$stmt->close();

// Tandai semua notifikasi sebagai terbaca
$conn->query("UPDATE notifikasi SET status = 'terbaca' WHERE user_id = $user_id AND status = 'belum_terbaca'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Notifikasi - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root { --primary-color: #6A38C2; --dark-text: #1a202c; --light-text: #555; --border-color: #e2e8f0; --unread-bg: #f0ebf9;}
        body, html { margin:0; padding:0; font-family:'Poppins',sans-serif; background-size:cover; background-position:center; background-repeat:no-repeat; display:flex; justify-content:center; align-items:flex-start; min-height:100vh; padding:20px 0;}
        .bg-image { background-image:url('bg.png'); background-size:cover; background-position:center; background-repeat:no-repeat; width:100%; height:100%;}
        .detail-container { width:100%; max-width:800px; padding:20px;}
        .detail-card { background-color:#fff; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); padding:30px 40px;}
        .card-header { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:20px; margin-bottom:25px;}
        .card-header h2 { margin:0; font-size:1.8rem; color:var(--dark-text);}
        .back-button { background-color:transparent; color:var(--primary-color); padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:600; border:1px solid var(--primary-color); transition:all 0.3s;}
        .back-button:hover { background-color:var(--primary-color); color:#fff;}
        .notification-list { display:flex; flex-direction:column; gap:15px;}
        .notification-item { display:flex; align-items:flex-start; gap:10px; padding:12px 15px; border:1px solid var(--border-color); border-radius:10px;}
        .notification-item.unread { background-color:var(--unread-bg); border-left:5px solid var(--primary-color); font-weight:500;}
        .notification-icon { font-size:1.2rem; color:var(--primary-color); margin-top:5px;}
        .notification-content h3 { margin:0 0 5px 0; font-size:1rem; color:var(--dark-text);}
        .notification-content p { margin:0; color:var(--light-text); font-size:0.85rem;}
        .notification-date { font-size:0.8rem; color:#888; margin-top:2px;}
        .empty-state { text-align:center; padding:50px; color:var(--light-text);}
        .empty-state i { font-size:3rem; margin-bottom:15px; color:#ccc;}
        .pagination-controls { display:flex; justify-content:center; align-items:center; gap:10px; margin-top:30px;}
        .pagination-button { background-color:#fff; border:1px solid var(--border-color); color:var(--light-text); padding:8px 15px; border-radius:8px; cursor:pointer; transition:all 0.3s; font-weight:500;}
        .pagination-button:hover:not(:disabled) { background-color:#f0ebf9; border-color:var(--primary-color);}
        .pagination-button.active { background-color:var(--primary-color); color:#fff; border-color:var(--primary-color);}
        .pagination-button:disabled { background-color:#f8f9fa; color:#ccc; cursor:not-allowed;}
        .action-buttons { display:flex; justify-content:flex-end; gap:10px; margin-top:20px;}
        .action-button { background-color:#fff; border:1px solid var(--border-color); color:var(--light-text); padding:8px 15px; border-radius:8px; cursor:pointer; transition:background-color 0.3s;}
        .action-button:hover { background-color:#f0ebf9;}
    </style>
</head>
<body class="bg-image">
    <div class="detail-container">
        <div class="detail-card">
            <div class="card-header">
                <h2>Riwayat Notifikasi</h2>
                <a href="<?= $back_url ?>" class="back-button"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            <div class="notification-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>Tidak ada notifikasi saat ini.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item<?= $notif['status_baca'] === 'belum_terbaca' ? ' unread' : '' ?>">
                            <div class="notification-icon">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div class="notification-content">
                                <h3><?= htmlspecialchars($notif['judul']) ?></h3>
                                <p><?= htmlspecialchars($notif['pesan']) ?></p>
                                <div class="notification-date"><?= date('d M Y H:i', strtotime($notif['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Pagination controls -->
            <?php if ($total_notifications > $per_page): ?>
                <div class="pagination-controls">
                    <?php
                    $total_pages = ceil($total_notifications / $per_page);
                    for ($i = 1; $i <= $total_pages; $i++):
                    ?>
                        <a href="?page=<?= $i ?>" class="pagination-button<?= $i == $page ? ' active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            <!-- Action buttons -->
            <?php if (!empty($notifications)): ?>
                <div class="action-buttons">
                    <form method="post" style="display:inline;">
                        <button type="submit" name="delete_all" class="action-button" onclick="return confirm('Hapus semua notifikasi?')">Hapus Semua</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php
            // Hapus semua notifikasi jika diminta
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
                $conn->query("DELETE FROM notifikasi WHERE id_user = $user_id");
                echo "<script>window.location.href='riwayat-notifikasi.php';</script>";
                exit;
            }
            ?>
        </div>
    </div>
</body>
</html>