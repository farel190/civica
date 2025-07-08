<?php
require_once 'config/db.php';

// Contoh: Ambil jumlah total user/relawan dari database
$sql = "SELECT COUNT(*) as total_relawan FROM users WHERE role = 'relawan'";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : ['total_relawan' => 0];
$total_relawan = $row['total_relawan'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivicaCare - Platform Manajemen Relawan</title>
    <!-- Google Fonts untuk Tipografi -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;600&display=swap"
        rel="stylesheet">

    <style>
        /* ...existing code... */
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            color: #333;
            height: 100%;
            background-color: #fdfdfd;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .bg-image {
            background-image: url('bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
            height: 100%;
        }
        .container {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 60%;
            max-width: 1000px;
            padding: 2rem;
            flex-wrap: wrap;
        }
        .content-left {
            flex: 1;
            min-width: 300px;
            padding-right: 2rem;
        }
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }
        .logo-icon {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        .logo h1 {
            font-family: 'Lora', serif;
            font-size: 3rem;
            margin: 0;
            color: #1a1a1a;
        }
        .tagline {
            font-size: 1.25rem;
            line-height: 1.6;
            color: #555;
            max-width: 450px;
        }
        .stat-relawan {
            margin-top: 1.5rem;
            font-size: 1.1rem;
            color: #6A38C2;
            font-weight: 600;
        }
        .action-right {
            flex-shrink: 0;
        }
        .cta-button {
            background-color: #6A38C2;
            color: white;
            padding: 16px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 5px 20px rgba(106, 56, 194, 0.3);
            display: inline-block;
        }
        .cta-button:hover {
            background-color: #592EAB;
            transform: translateY(-3px);
        }
        @media (max-width: 768px) {
            body {
                align-items: flex-start;
                padding-top: 4rem;
            }
            .container {
                flex-direction: column;
                text-align: center;
                background-color: rgba(255, 255, 255, 0.9);
                border-radius: 15px;
                padding: 2rem 1rem;
                width: 90%;
            }
            .content-left {
                padding-right: 0;
                margin-bottom: 2rem;
            }
            .logo {
                justify-content: center;
            }
            .tagline {
                max-width: 100%;
            }
        }
    </style>
</head>

<body class="bg-image">

    <main class="container">
        <div class="content-left">
            <div class="logo">
                <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon">
                <h1>CivicaCare</h1>
            </div>
            <p class="tagline">
                Platform manajemen relawan untuk program sosial masyarakat yang lebih terorganisir dan berdampak.
            </p>
            <div class="stat-relawan">
                Total Relawan Terdaftar: <strong><?php echo $total_relawan; ?></strong>
            </div>
        </div>
        <div class="action-right">
            <a href="login.php" class="cta-button">Gabung Sekarang</a>
        </div>
    </main>

</body>
</html>