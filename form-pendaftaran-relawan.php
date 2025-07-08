<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'relawan') {
    header("Location: login.php");
    exit;
}
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$error = '';
$success = '';

// Ambil data lama jika sudah pernah isi
$stmt = $conn->prepare("SELECT name, nik, email, address, phone, education, availability, document_name, status FROM applicants WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
$form = [
    'name' => '', 'nik' => '', 'email' => '', 'address' => '', 'phone' => '', 'education' => '', 'availability' => '', 'document_name' => '', 'status' => ''
];
if ($stmt->num_rows > 0) {
    $stmt->bind_result($form['name'], $form['nik'], $form['email'], $form['address'], $form['phone'], $form['education'], $form['availability'], $form['document_name'], $form['status']);
    $stmt->fetch();
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $availability = isset($_POST['availability']) ? implode(',', $_POST['availability']) : '';
    $document_name = '';
    // Pastikan folder uploads ada
    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    // Validasi sederhana
    if ($name === '' || $nik === '' || $phone === '' || $address === '' || $education === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (empty($_POST['availability'])) {
        $error = 'Pilih minimal satu hari ketersediaan.';
    } elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = 'NIK harus 16 digit angka dan hanya angka.';
    } elseif (!preg_match('/^[0-9]{11,15}$/', $phone)) {
        $error = 'Nomor telepon harus 11-15 digit angka dan hanya angka.';
    } else {
        // Handle upload dokumen (opsional)
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['document']['size'] <= 2*1024*1024) {
                $document_name = uniqid('doc_').'.'.$ext;
                move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . '/' . $document_name);
            } else {
                $error = 'Format/tidak valid atau file terlalu besar (max 2MB).';
            }
        } else {
            $document_name = $form['document_name'] ?? '';
        }
        if (!$error) {
            // Insert/update ke database
            $stmt = $conn->prepare("REPLACE INTO applicants (id, name, nik, email, address, phone, education, availability, document_name, status, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'diajukan', 'relawan')");
            $stmt->bind_param('issssssss', $user_id, $name, $nik, $email, $address, $phone, $education, $availability, $document_name);
            if ($stmt->execute()) {
                echo "<script>alert('Pendaftaran berhasil dikirim! Data Anda akan diverifikasi admin.');window.location.href='dashboard-relawan.php';</script>";
                exit;
                // $success = 'Pendaftaran berhasil diajukan!';
                // Refresh data form
                // $form = compact('name','nik','email','address','phone','education');
                // $form['availability'] = $availability;
                // $form['document_name'] = $document_name;
            } else {
                $error = 'Gagal menyimpan data. Coba lagi.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran Relawan - CivicaCare</title>

    <!-- Google Fonts untuk Tipografi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap"
        rel="stylesheet">

    <!-- Library Ikon Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <style>
        /* === Pengaturan Dasar === */
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            /* [PERUBAHAN] Menambahkan padding vertikal agar form tidak menempel di ujung layar */
            padding: 20px 0;
        }

        .bg-image {
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
            height: 100%;
        }

        /* === Kontainer Formulir === */
        .form-container {
            width: 200%;
            max-width: 800px;
            /* Lebar maksimal kartu formulir */
            padding: 10px;
            /* [PERUBAHAN] Menyesuaikan tinggi kontainer agar fleksibel */
            height: 80%;
            max-height: 100vh;
        }

        /* === Kartu Formulir === */
        .form-card {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            padding: 50px 50px;
            box-sizing: border-box;
            /* Memastikan padding tidak menambah ukuran */
            /* [PERUBAHAN] Membuat kartu bisa di-scroll secara internal */
            height: 100%;
            overflow-y: auto;
        }

        /* Gaya untuk input yang valid */
        .form-input.is-valid,
        .form-textarea.is-valid,
        .form-select.is-valid {
            border-color: #28a745;
            /* Hijau */
        }

        /* Gaya untuk input yang tidak valid */
        .form-input.is-invalid,
        .form-textarea.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
            /* Merah */
        }

        /* Gaya untuk pesan error inline */
        .error-message {
            color: #dc3545;
            /* Merah */
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
            /* Sembunyikan secara default */
        }

        /* Custom scrollbar (Opsional, untuk estetika) */
        .form-card::-webkit-scrollbar {
            width: 8px;
        }

        .form-card::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .form-card::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .form-card::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }


        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            justify-content: center;
        }

        .logo-icon {
            width: 35px;
            height: 35px;
            margin-right: 12px;
        }

        .logo h1 {
            font-family: 'Lora', serif;
            font-size: 2.2rem;
            margin: 0;
            color: #1a1a1a;
        }

        .form-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
        }

        /* === Grid untuk Form === */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 30px;
            /* Jarak vertikal dikurangi */
        }

        .form-grid .form-group.span-two-columns {
            grid-column: span 2;
        }

        .form-group {
            /* [PERUBAHAN] Margin dipindahkan ke grid gap */
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
            /* Tambahkan ini untuk konsistensi */
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #6A38C2;
        }

        .form-textarea {
            resize: vertical;
            min-height: 20px;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            color: #444;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            transform: scale(1.1);
        }

        .submit-button-wrapper {
            text-align: center;
            margin-top: 10px;
        }

        .submit-button {
            width: 70%;
            padding: 15px;
            border: none;
            background-color: #6A38C2;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .submit-button:hover {
            background-color: #592EAB;
        }

        /* === Media Query untuk Responsif === */
        @media (max-width: 768px) {

            .form-container,
            .form-card {
                height: auto;
                max-height: none;
            }

            .form-card {
                padding: 30px 25px;
                overflow-y: visible;
                /* Nonaktifkan scroll internal di mobile */
            }

            .form-title {
                font-size: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="bg-image">

    <div class="form-container">
        <div class="form-card">
            <div class="logo">
                <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
                <h1>CivicaCare</h1>
            </div>
            <h2 class="form-title">Formulir Pendaftaran Relawan</h2>
            <?php if ($error): ?><div class="error-message" style="display:block;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div style="color:#28a745;font-weight:600;margin-bottom:15px;">Pendaftaran berhasil diajukan! Silakan tunggu konfirmasi admin.</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" autocomplete="on">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama-lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama-lengkap" name="name" class="form-input" required value="<?= htmlspecialchars($form['name']) ?>" autocomplete="nama_lengkap">
                    </div>
                    <div class="form-group">
                        <label for="nik" class="form-label">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" id="nik" name="nik" class="form-input" pattern="[0-9]{16}" minlength="16" maxlength="16" required value="<?= htmlspecialchars($form['nik']) ?>" title="NIK harus 16 digit angka" autocomplete="NIK">
                    </div>
                    <div class="form-group">
                        <label for="no-hp" class="form-label">Nomor Telepon (WhatsApp)</label>
                        <input type="tel" id="no-hp" name="phone" class="form-input" pattern="[0-9]{11,15}" minlength="11" maxlength="15" required value="<?= htmlspecialchars($form['phone']) ?>" title="Nomor telepon harus 11-15 digit angka" autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" class="form-input" value="<?= htmlspecialchars($email) ?>" readonly autocomplete="email">
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                        <textarea id="alamat" name="address" class="form-textarea" rows="3" required><?= htmlspecialchars($form['address']) ?></textarea>
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="pendidikan" class="form-label">Pendidikan Terakhir</label>
                        <select id="pendidikan" name="education" class="form-select" required>
                            <option value="">Pilih Pendidikan</option>
                            <option value="SD" <?= $form['education']==='SD'?'selected':'' ?>>SD</option>
                            <option value="SMP" <?= $form['education']==='SMP'?'selected':'' ?>>SMP</option>
                            <option value="SMA/SMK" <?= $form['education']==='SMA/SMK'?'selected':'' ?>>SMA/SMK</option>
                            <option value="D1/D2" <?= $form['education']==='D1/D2'?'selected':'' ?>>D1/D2</option>
                            <option value="D3" <?= $form['education']==='D3'?'selected':'' ?>>D3</option>
                            <option value="S1/D4" <?= $form['education']==='S1/D4'?'selected':'' ?>>S1/D4</option>
                            <option value="S2" <?= $form['education']==='S2'?'selected':'' ?>>S2</option>
                            <option value="S3" <?= $form['education']==='S3'?'selected':'' ?>>S3</option>
                        </select>
                    </div>
                    <div class="form-group span-two-columns">
                        <label class="form-label">Jadwal Ketersediaan (Pilih Hari)</label>
                        <div class="checkbox-group">
                            <?php $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
                            $avail = explode(',', $form['availability'] ?? '');
                            foreach ($days as $d): ?>
                            <label><input type="checkbox" name="availability[]" value="<?= $d ?>" <?= in_array($d, $avail)?'checked':'' ?>> <?= ucfirst($d) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group span-two-columns">
                        <label for="dokumen" class="form-label">Upload Dokumen (Contoh: CV, Scan KTP, dll.)</label>
                        <input type="file" id="dokumen" name="document" class="form-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <?php if ($form['document_name']): ?>
                        <small style="color:#555;display:block;margin-top:5px;">File sebelumnya: <?= htmlspecialchars($form['document_name']) ?></small>
                        <?php endif; ?>
                        <small style="color: #777; display: block; margin-top: 5px;">Format: PDF, DOCX, JPG, PNG (Max 2MB)</small>
                    </div>
                </div>
                <div class="submit-button-wrapper">
                    <button type="submit" class="submit-button">Ajukan Pendaftaran</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    // Validasi minimal satu hari dipilih (client-side)
    document.querySelector('form').onsubmit = function(e) {
        var checked = document.querySelectorAll('input[name="availability[]"]:checked').length;
        if (checked < 1) {
            alert('Pilih minimal satu hari ketersediaan.');
            e.preventDefault();
            return false;
        }
    };
    </script>
</body>

</html>