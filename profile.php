<?php
require_once 'config/db.php';
session_start();

// Cek login dan role
if (!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = "";
$success = "";

// Inisialisasi variabel default untuk mencegah warning "Undefined variable"
$nama_lengkap = '-';
$nik = '-';
$address = '-';
$email = '-';
$phone = '-';
$education = '-';
$availability = '';
$status_user = '-';

$role = $_SESSION['role'] ?? 'relawan';
if ($role === 'relawan') {
    // Tampilkan form profil relawan
} elseif ($role === 'koordinator') {
    // Tampilkan form profil koordinator
} elseif ($role === 'admin') {
    // Tampilkan data profil admin
}

// Proses update ketersediaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
    $availability_str = implode(',', $availability);

    $stmt = $conn->prepare("UPDATE users SET availability = ? WHERE id = ?");
    $stmt->bind_param("si", $availability_str, $user_id);
    if ($stmt->execute()) {
        $success = "Ketersediaan berhasil diperbarui.";
    } else {
        $error = "Gagal memperbarui ketersediaan.";
    }
    $stmt->close();
}

// Proses pengunduran diri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resign'])) {
    $stmt = $conn->prepare("UPDATE users SET status = 'nonaktif' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        session_destroy();
        header("Location: login.php?resign=1");
        exit;
    } else {
        $error = "Gagal mengajukan pengunduran diri.";
    }
    $stmt->close();
}

// Proses update profil (semua field editable kecuali email & nik)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $availability = isset($_POST['availability']) ? $_POST['availability'] : [];
    $availability_str = implode(',', $availability);
    if ($nama_lengkap === '' || $phone === '' || $address === '' || $education === '' || empty($availability)) {
        $error = 'Semua field wajib diisi.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, phone=?, address=?, education=?, availability=? WHERE id=?");
        $stmt->bind_param("sssssi", $nama_lengkap, $phone, $address, $education, $availability_str, $user_id);
        if ($stmt->execute()) {
            // Redirect ke dashboard setelah update sukses
            $dashboard = 'dashboard-relawan.php';
                if ($_SESSION['role'] === 'koordinator') {
            $dashboard = 'dashboard-koordinator.php';
            } elseif ($_SESSION['role'] === 'admin') {
            $dashboard = 'dashboard-admin.php';
            }
        echo "<script>alert('Profil berhasil diperbarui!');window.location.href='$dashboard';</script>";
        } else {
            $error = 'Gagal memperbarui profil.';
        }
        $stmt->close();
    }
}

// Ambil status pendaftaran dari tabel applicants
$stmt = $conn->prepare("SELECT status FROM applicants WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($status_pendaftaran);
$stmt->fetch();
$stmt->close();

// Ambil data user dari users
$stmt = $conn->prepare("SELECT username, email, no_hp, education, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email, $phone, $education, $status_user);
$stmt->fetch();
$stmt->close();

$availability = ''; // default kosong
$stmt = $conn->prepare("SELECT availability FROM applicants WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($availability);
$stmt->fetch();
$stmt->close();

// Jika sudah diterima, ambil data lengkap dari applicants (override data users jika ada)
if ($status_pendaftaran === 'diterima') {
    $stmt = $conn->prepare("SELECT name, nik, phone, address, education, availability FROM applicants WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $nik, $phone, $address, $education, $availability);
    $stmt->fetch();
    $stmt->close();
    $nama_lengkap = $name;
    // email tetap dari tabel users
}


function safe($val) {
    return ($val === null || $val === '' || $val === false) ? '-' : htmlspecialchars($val);
}
$availability_arr = $availability ? explode(',', $availability) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root { --primary-color: #6A38C2; --primary-light: #F0EBf9; --secondary-color: #CC0000; --dark-text: #1a202c; --light-text: #555; --bg-light: #f7fafc; --border-color: #e2e8f0;}
        body, html { margin:0; padding:0; font-family:'Poppins',sans-serif; background-color:var(--bg-light); background-image:url('bg.png'); background-size:cover; background-position:center; background-attachment:fixed; color:var(--dark-text); min-height:100vh; display:flex; flex-direction:column;}
        .main-header { background-color:#fff; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 4px rgba(0,0,0,0.05); position:sticky; top:0; z-index:1000;}
        .logo { display:flex; align-items:center; text-decoration:none; color:var(--dark-text);}
        .logo-icon { width:32px; height:32px; margin-right:10px;}
        .logo h1 { font-family:'Lora',serif; font-size:1.8rem; margin:0;}
        .back-button { background-color:transparent; color:var(--primary-color); padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:600; border:1px solid var(--primary-color); transition:all 0.3s; display:flex; align-items:center; gap:5px;}
        .back-button:hover { background-color:var(--primary-color); color:#fff;}
        main { flex-grow:1; padding:40px; display:flex; justify-content:center; align-items:flex-start;}
        .profile-container { width:100%; max-width:800px; background-color:#fff; padding:10px; border-radius:15px; box-shadow:0 4px 6px rgba(0,0,0,0.05);}
        .profile-container h2 { font-size:2rem; color:var(--primary-color); margin-top:0; margin-bottom:5px; border-bottom:1px solid var(--border-color); padding-bottom:5px;}
        .profile-actions { display:flex; justify-content:space-between; align-items:center; margin-top:10px;}
        .toggle-resign-button { background-color:transparent; color:var(--secondary-color); padding:12px 25px; border-radius:8px; border:1px solid var(--secondary-color); font-weight:600; cursor:pointer; transition:all 0.3s;}
        .toggle-resign-button:hover { background-color:var(--secondary-color); color:white;}
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px 30px; margin-bottom:30px;}
        .form-group { margin-bottom:0;}
        .form-group.span-two-columns { grid-column:span 2;}
        .form-label { display:block; font-size:0.9rem; color:var(--light-text); margin-bottom:5px; font-weight:600;}
        .form-input, .form-textarea, .form-select { width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:8px; font-size:1rem; box-sizing:border-box; background-color:#fdfdfd;}
        .form-input:focus, .form-textarea:focus, .form-select:focus { outline:none; border-color:var(--primary-color);}
        .form-input[readonly] { background-color:#e9ecef; cursor:not-allowed;}
        .checkbox-group { display:flex; flex-wrap:wrap; gap:15px; margin-top:10px;}
        .checkbox-group label { display:flex; align-items:center; font-size:0.95rem; color:var(--dark-text); cursor:pointer; font-weight:normal;}
        .checkbox-group input[type="checkbox"] { margin-right:8px; transform:scale(1.1);}
        .save-button { background-color:var(--primary-color); color:white; padding:12px 25px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:background-color 0.3s; margin-top:20px;}
        .save-button:hover { background-color:#592EAB;}
        .resignation-section { margin-top:40px; padding-top:30px; border-top:1px solid var(--border-color); text-align:center;}
        .resignation-section h3 { font-size:1.5rem; color:var(--secondary-color); margin-bottom:20px;}
        .resignation-section p { color:var(--light-text); margin-bottom:20px;}
        .resign-button { background-color:var(--secondary-color); color:white; padding:12px 30px; border-radius:8px; border:none; font-weight:600; cursor:pointer; transition:background-color 0.3s;}
        .resign-button:hover { background-color:#a00000;}
        .error-message { color:var(--secondary-color); font-size:0.85rem; margin-top:5px;}
        .success-message { color:#28a745; font-size:0.95rem; margin-bottom:10px;}
        @media (max-width:768px){ main{padding:20px;}.profile-container{padding:20px;}.form-grid{grid-template-columns:1fr;gap:15px;}.form-group.span-two-columns{grid-column:span 1;}}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <a href="<?= $role === 'relawan' ? 'dashboard-relawan.php' : ($role === 'koordinator' ? 'dashboard-koordinator.php' : 'dashboard-admin.php') ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <a href="#" class="logo">
            <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
            <h1>CivicaCare</h1>
        </a>
    </header>
    <main>
        <div class="profile-container">
            <h2>Profil Saya</h2>
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post" id="profile-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-input" name="nama_lengkap" value="<?= safe($nama_lengkap) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIK</label>
                        <input type="text" class="form-input" value="<?= safe($nik) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-input" name="phone" value="<?= safe($phone) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" value="<?= safe($email) ?>" readonly>
                    </div>
                    <div class="form-group span-two-columns">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-textarea" name="address" rows="3" required><?= safe($address) ?></textarea>
                    </div>
                    <div class="form-group span-two-columns">
                        <label class="form-label">Pendidikan Terakhir</label>
                        <input type="text" class="form-input" name="education" value="<?= safe($education) ?>" required>
                    </div>
                    <div class="form-group span-two-columns">
                        <label class="form-label">Jadwal Ketersediaan (Dapat Diubah)</label>
                        <div class="checkbox-group">
                            <?php
                            $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
                            foreach ($days as $day):
                            ?>
                            <label>
                                <input type="checkbox" name="availability[]" value="<?= $day ?>" <?= in_array($day, $availability_arr) ? 'checked' : '' ?>> <?= ucfirst($day) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="profile-actions">
                    <button type="submit" class="save-button" name="update_profile">Simpan Perubahan Profil</button>
                    <button type="button" class="toggle-resign-button" id="toggle-resign-button">Opsi Pengunduran Diri</button>
                </div>
            </form>
            <div class="resignation-section" id="resignation-section" style="display:none;">
                <h3>Mengundurkan Diri sebagai Relawan</h3>
                <p>Jika Anda ingin mengundurkan diri dari CivicaCare, klik tombol di bawah ini.</p>
                <form method="post">
                    <button type="submit" class="resign-button" name="resign" onclick="return confirm('Yakin ingin mengundurkan diri?')">Ajukan Pengunduran Diri</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        // Toggle resignation section
        document.getElementById('toggle-resign-button').addEventListener('click', function() {
            var section = document.getElementById('resignation-section');
            if (section.style.display === 'none') {
                section.style.display = 'block';
                this.textContent = 'Batalkan Pengunduran Diri';
            } else {
                section.style.display = 'none';
                this.textContent = 'Opsi Pengunduran Diri';
            }
        });
    </script>
</body>
</html>