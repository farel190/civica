<?php
function getStatusOtomatis($tanggal, $waktu_mulai, $waktu_selesai) {
    date_default_timezone_set('Asia/Jakarta');

    $now = date('Y-m-d H:i:s');
    $start_time = $tanggal . ' ' . $waktu_mulai;
    $end_time = $tanggal . ' ' . $waktu_selesai;

    if ($now < $start_time) {
        return 'terjadwal';
    } elseif ($now >= $start_time && $now <= $end_time) {
        return 'berlangsung';
    } else {
        return 'selesai';
    }
}
?>
