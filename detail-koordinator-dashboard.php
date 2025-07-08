<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'koordinator') {
    header('Location: login.php');
    exit;
}
$koordinator_id = $_SESSION['user_id'];
$koordinator_nama = $_SESSION['nama_lengkap'] ?? 'Koordinator';

// Helper
function safe($v) { return ($v===null||$v===''||$v===false)?'-':htmlspecialchars($v); }

// Ambil daftar kegiatan yang dikoordinasi
$kegiatan = $conn->query("SELECT k.id, k.nama_kegiatan, k.tanggal
    FROM assignments a
    JOIN kegiatan k ON a.kegiatan_id = k.id
    WHERE a.koordinator_id = $koordinator_id
    ORDER BY k.tanggal DESC")->fetch_all(MYSQLI_ASSOC);

// Proses simpan evaluasi relawan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kegiatan_id'], $_POST['relawan_id'])) {
    $kid = intval($_POST['kegiatan_id']);
    $rid = intval($_POST['relawan_id']);
    $kedisiplinan = intval($_POST['kedisiplinan']);
    $komunikasi = intval($_POST['komunikasi']);
    $kerjasama = intval($_POST['kerjasama']);
    $tanggung_jawab = intval($_POST['tanggung_jawab']);
    $is_present = isset($_POST['is_present']) ? 1 : 0;
    $catatan = $conn->real_escape_string($_POST['catatan']);
    $evaluated_by = $koordinator_id;

    if (!$is_present) {
        $msg = "Harap centang kehadiran sebelum mengisi evaluasi.";
        header("Location: detail-koordinator-dashboard.php?kegiatan_id=$kid&msg=" . urlencode($msg));
        exit;
    }


    // Cek apakah sudah ada evaluasi
    $cek = $conn->query("SELECT id FROM evaluasi WHERE id_kegiatan=$kid AND id_relawan=$rid");
    if ($cek && $cek->num_rows > 0) {
        $conn->query("UPDATE evaluasi SET kedisiplinan=$kedisiplinan, komunikasi=$komunikasi, kerjasama=$kerjasama, tanggung_jawab=$tanggung_jawab, is_present=$is_present, catatan='$catatan', tanggal_evaluasi=CURDATE(), evaluated_by=$evaluated_by WHERE id_kegiatan=$kid AND id_relawan=$rid");
        $msg = "Evaluasi berhasil diperbarui.";
    } else {
        $conn->query("INSERT INTO evaluasi (id_kegiatan, id_relawan, kedisiplinan, komunikasi, kerjasama, tanggung_jawab, is_present, catatan, tanggal_evaluasi, evaluated_by) VALUES ($kid, $rid, $kedisiplinan, $komunikasi, $kerjasama, $tanggung_jawab, $is_present, '$catatan', CURDATE(), $evaluated_by)");
        $msg = "Evaluasi berhasil disimpan.";
    }
    header("Location: dashboard-koordinator.php?kegiatan_id=$kid&msg=" . urlencode($msg));
    exit;
}

// Ambil relawan yang ditugaskan pada kegiatan terpilih
$selected_id = intval($_GET['kegiatan_id'] ?? 0);
$relawan = [];
$evaluasi = [];
if ($selected_id) {
    // Relawan yang ditugaskan
    $q = $conn->query("SELECT ar.relawan_id, ap.name
        FROM assignment_relawan ar
        JOIN applicants ap ON ar.relawan_id = ap.id
        WHERE ar.assignment_id = (SELECT id FROM assignments WHERE kegiatan_id=$selected_id AND koordinator_id=$koordinator_id LIMIT 1)");
    while ($q && $row = $q->fetch_assoc()) {
        $relawan[] = $row;
    }
    // Evaluasi yang sudah ada
    $q2 = $conn->query("SELECT * FROM evaluasi WHERE id_kegiatan=$selected_id AND id_relawan<>0");
    while ($q2 && $row2 = $q2->fetch_assoc()) {
        $evaluasi[$row2['id_relawan']] = $row2;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Koordinator - CivicaCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #6A38C2;
            --primary-light: #F0EBf9;
            --secondary-color: #CC0000;
            --dark-text: #1a202c;
            --light-text: #555;
            --bg-light: #f7fafc;
            --border-color: #e2e8f0;
        }
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background-color: var(--bg-light); color: var(--dark-text); min-height: 100vh; }
        main { flex-grow: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        .content-area { width: 100%; max-width: 1200px; background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .section-header h2 { font-size: 2rem; margin: 0; color: var(--primary-color); }
        .filter-section { background-color: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; gap: 20px; align-items: center; }
        .filter-section label { display: block; margin-bottom: 10px; font-weight: 600; color: var(--dark-text); }
        .filter-section select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { background-color: var(--primary-color); color: #fff; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background-color: #f1f1f1; }
        .empty-table-message { text-align: center; padding: 30px; color: var(--light-text); font-style: italic; }
        .rating-group { display: flex; flex-direction: column; gap: 5px; }
        .rating-group label { font-size: 0.85rem; color: #555; font-weight: 500; }
        .rating-input { width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 0.9rem; box-sizing: border-box; }
        .feedback-textarea { width: calc(100% - 20px); min-height: 40px; padding: 8px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 0.9rem; box-sizing: border-box; resize: vertical; }
        .save-evaluation-button { background-color: var(--primary-color); color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: background-color 0.3s; }
        .save-evaluation-button:hover { background-color: #592EAB; }
        @media (max-width: 768px) {
            main { padding: 20px; }
            .section-header h2 { font-size: 1.5rem; }
            .filter-section { flex-direction: column; align-items: flex-start; }
            .data-table th, .data-table td { padding: 10px; font-size: 0.8rem; }
            .rating-group label, .rating-input, .feedback-textarea, .save-evaluation-button { font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header" style="background:#fff;padding:15px 40px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 4px rgba(0,0,0,0.05);">
        <a href="dashboard-koordinator.php" class="back-button" style="background-color:transparent;color:var(--primary-color);padding:8px 16px;border-radius:8px;text-decoration:none;font-weight:600;border:1px solid var(--primary-color);transition:all 0.3s;display:flex;align-items:center;gap:5px;">
            <i class="fas fa-sign-out-alt"></i> Kembali
        </a>
        <div class="logo" style="display:flex;align-items:center;text-decoration:none;color:var(--dark-text);">
            <img src="logo.png" alt="Logo Icon CivicaCare" class="logo-icon" style="width:32px;height:32px;margin-right:10px;">
            <h1 style="font-family:'Lora',serif;font-size:1.8rem;margin:0;">CivicaCare</h1>
        </div>
    </header>
    <!-- Konten Utama -->
    <main>
        <div class="content-area">
            <div class="section-header">
                <h2>Dashboard Koordinator</h2>
            </div>
            <form method="get" class="filter-section" style="margin-bottom:0;">
                <div style="flex:1;">
                    <label for="kegiatan_id">Pilih Kegiatan yang Anda Koordinasi:</label>
                    <select name="kegiatan_id" id="kegiatan_id" onchange="this.form.submit()">
                        <option value="">-- Pilih Kegiatan --</option>
                        <?php foreach ($kegiatan as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $selected_id==$k['id']?'selected':'' ?>>
                                <?= safe($k['nama_kegiatan']) ?> (<?= date('d-m-Y', strtotime($k['tanggal'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Relawan</th>
                        <th>Kedisiplinan (1-5)</th>
                        <th>Komunikasi (1-5)</th>
                        <th>Kerja Sama (1-5)</th>
                        <th>Tanggung Jawab (1-5)</th>
                        <th>Kehadiran</th>
                        <th>Umpan Balik</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
<?php if ($selected_id && $relawan): ?>
    <?php foreach ($relawan as $r):
        $ev = $evaluasi[$r['relawan_id']] ?? null;
    ?>
    <tr>
        <form method="post">
            <input type="hidden" name="kegiatan_id" value="<?= $selected_id ?>">
            <input type="hidden" name="relawan_id" value="<?= $r['relawan_id'] ?>">
            <td><?= safe($r['name']) ?></td>

            <!-- Kedisiplinan -->
            <td>
                <?php if ($ev): ?>
                    <select disabled>
                        <option><?= $ev['kedisiplinan'] ?></option>
                    </select>
                    <input type="hidden" name="kedisiplinan" value="<?= $ev['kedisiplinan'] ?>">
                <?php else: ?>
                    <select name="kedisiplinan" class="rating-input" required>
                        <option value="">--</option>
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </td>

            <!-- Komunikasi -->
            <td>
                <?php if ($ev): ?>
                    <select disabled>
                        <option><?= $ev['komunikasi'] ?></option>
                    </select>
                    <input type="hidden" name="komunikasi" value="<?= $ev['komunikasi'] ?>">
                <?php else: ?>
                    <select name="komunikasi" class="rating-input" required>
                        <option value="">--</option>
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </td>

            <!-- Kerjasama -->
            <td>
                <?php if ($ev): ?>
                    <select disabled>
                        <option><?= $ev['kerjasama'] ?></option>
                    </select>
                    <input type="hidden" name="kerjasama" value="<?= $ev['kerjasama'] ?>">
                <?php else: ?>
                    <select name="kerjasama" class="rating-input" required>
                        <option value="">--</option>
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </td>

            <!-- Tanggung Jawab -->
            <td>
                <?php if ($ev): ?>
                    <select disabled>
                        <option><?= $ev['tanggung_jawab'] ?></option>
                    </select>
                    <input type="hidden" name="tanggung_jawab" value="<?= $ev['tanggung_jawab'] ?>">
                <?php else: ?>
                    <select name="tanggung_jawab" class="rating-input" required>
                        <option value="">--</option>
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </td>

            <!-- Kehadiran -->
            <td>
                <input type="checkbox"
                    id="is_present_<?= $r['relawan_id'] ?>"
                    name="is_present"
                    value="1"
                    onchange="toggleEvaluationFields(<?= $r['relawan_id'] ?>)"
                    <?= ($ev && $ev['is_present']) ? 'checked disabled' : (!$ev ? '' : 'disabled') ?>>
                <?php if ($ev): ?>
                    <input type="hidden" name="is_present" value="<?= $ev['is_present'] ?>">
                <?php endif; ?>
            </td>

            <!-- Catatan -->
            <td>
                <?php if ($ev): ?>
                    <textarea class="feedback-textarea" readonly><?= safe($ev['catatan']) ?></textarea>
                    <input type="hidden" name="catatan" value="<?= htmlspecialchars($ev['catatan']) ?>">
                <?php else: ?>
                    <textarea name="catatan" class="feedback-textarea" required></textarea>
                <?php endif; ?>
            </td>

            <!-- Tombol -->
            <td>
                <?php if ($ev): ?>
                    <span style="color: #28a745; font-weight: 600;">Sudah Dievaluasi</span>
                <?php else: ?>
                    <button type="submit" class="save-evaluation-button">Simpan</button>
                <?php endif; ?>
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
<?php elseif ($selected_id): ?>
    <tr><td colspan="8" class="empty-table-message">Tidak ada relawan yang ditugaskan untuk kegiatan ini.</td></tr>
<?php else: ?>
    <tr><td colspan="8" class="empty-table-message">Pilih kegiatan untuk menampilkan relawan yang ditugaskan.</td></tr>
<?php endif; ?>
</tbody>

            </table>
            <?php if (isset($_GET['msg'])): ?>
                <script>alert("<?= safe($_GET['msg']) ?>");</script>
            <?php endif; ?>
        </div>
    </main>

    <script>
    <?php foreach ($relawan as $r): ?>
        toggleEvaluationFields(<?= $r['relawan_id'] ?>);
    <?php endforeach; ?>
    </script>

    <script>
        document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function(e) {
        const isPresentCheckbox = form.querySelector("input[type='checkbox'][name='is_present']");
        if (isPresentCheckbox && !isPresentCheckbox.checked) {
            alert("Anda harus mencentang Kehadiran terlebih dahulu sebelum mengisi evaluasi.");
            e.preventDefault(); // hentikan pengiriman form
        }
        });
    });
    </script>

</body>
</html>