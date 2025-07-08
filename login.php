<?php
require_once 'config/db.php';
session_start();

$successMessage = "";
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = "Pendaftaran berhasil! Silakan login.";
}


$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === "" || $password === "") {
        $error = "Username/email dan password wajib diisi.";
    } else {
        // Cek login via username atau email
        $stmt = $conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $user, $email, $hash, $role, $status);
            $stmt->fetch();
            if ($status !== 'aktif') {
                $error = "Akun Anda tidak aktif.";
            } elseif (password_verify($password, $hash)) {
                // Login sukses
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $user;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                // Redirect sesuai role
                if ($role === 'admin') {
                    header("Location: dashboard-admin.php");
                } elseif ($role === 'relawan') {
                    header("Location: dashboard-relawan.php");
                } else {
                    header("Location: dashboard-koordinator.php");
                }
                exit;
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Username/email tidak ditemukan.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* ...gunakan style Anda sebelumnya... */
        body, html { margin:0; padding:0; font-family:'Poppins',sans-serif; height:100%; background-image:url('bg.png'); background-size:cover; background-position:center; background-repeat:no-repeat; display:flex; justify-content:center; align-items:center;}
        .login-container { width:70%; padding:20px;}
        .login-card { display:flex; background-color:#fff; border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,0.1); overflow:hidden; width:100%;}
        .login-image-section { flex-basis:50%; display:block;}
        .login-image-section img { width:100%; height:100%; object-fit:cover; display:block;}
        .login-form-section { flex-basis:50%; padding:40px 50px; display:flex; flex-direction:column; justify-content:center;}
        .logo { display:flex; align-items:center; margin-bottom:20px; justify-content:center;}
        .logo-icon { width:50px; height:50px; margin-right:5px;}
        .logo h1 { font-family:'Lora',serif; font-size:2.5rem; margin:0; color:#1a1a1a;}
        .form-title { text-align:center; font-size:1.4rem; font-weight:500; color:#333; margin-top:0; margin-bottom:20px;}
        .form-group { position:relative; margin-bottom:20px;}
        .form-input { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:10px; font-size:1rem; transition:border-color 0.3s; box-sizing:border-box;}
        .form-input:focus { outline:none; border-color:#6A38C2;}
        .login-button-wrapper { text-align:center; margin-top:10px;}
        .login-button { padding:12px 45px; border:none; background-color:#6A38C2; color:white; font-size:1rem; font-weight:600; border-radius:10px; cursor:pointer; transition:background-color 0.3s; display:inline-block;}
        .login-button:hover { background-color:#592EAB;}
        .register-link { text-align:center; margin-top:20px; font-size:0.9rem; color:#555;}
        .register-link a { color:#6A38C2; font-weight:600; text-decoration:none;}
        .error-message { color:#D83A56; font-size:0.95rem; margin-bottom:10px;}
        @media (max-width:768px){.login-image-section{display:none;}.login-form-section{flex-basis:100%;padding:40px 30px;}}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-image-section">
                <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=2070&auto=format&fit=crop" alt="Relawan bekerja sama">
            </div>
            <div class="login-form-section">
                <div class="logo">
                    <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
                    <h1>CivicaCare</h1>
                </div>
                <h2 class="form-title">Masuk Akun</h2>
                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="form-group">
                        <input type="text" name="username" class="form-input" required placeholder="Username atau Email" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="position:relative;">
                        <input type="password" name="password" id="password" class="form-input" required placeholder="Password">
                        <span class="toggle-password" toggle="#password" style="position:absolute;right:15px;top:50%;transform:translateY(-50%);cursor:pointer;">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                    <div class="login-button-wrapper">
                        <button type="submit" class="login-button">Login</button>
                    </div>
                </form>
                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar di sini</a>
                </div>
            </div>
        </div>
    </div>

<?php if (!empty($successMessage)): ?>
<script>
    alert("<?= $successMessage ?>");
</script>
<?php endif; ?>

</body>
<script>
document.querySelectorAll('.toggle-password').forEach(function(el) {
  el.addEventListener('click', function() {
    var input = document.querySelector(this.getAttribute('toggle'));
    if (input.type === "password") {
      input.type = "text";
      this.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
      input.type = "password";
      this.innerHTML = '<i class="fa fa-eye"></i>';
    }
  });
});
</script>
</html>