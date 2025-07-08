<?php
require_once 'config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$sql = "SELECT u.id, u.nama_lengkap, u.nik, u.email, u.phone, u.address, u.education, u.availability, u.status, u.role,
        a.name AS a_name, a.nik AS a_nik, a.phone AS a_phone, a.address AS a_address, a.education AS a_education, a.availability AS a_availability
        FROM users u
        LEFT JOIN applicants a ON (a.email = u.email AND a.status = 'diterima')
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    // Gunakan data dari applicants jika data users kosong
    $response = [
        'id' => $data['id'],
        'nama_lengkap' => $data['nama_lengkap'] ?: $data['a_name'],
        'nik' => $data['nik'] ?: $data['a_nik'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?: $data['a_phone'],
        'address' => $data['address'] ?: $data['a_address'],
        'education' => $data['education'] ?: $data['a_education'],
        'availability' => $data['availability'] ?: $data['a_availability'],
        'status' => $data['status'],
        'role' => $data['role'],
    ];
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Data tidak ditemukan']);
}