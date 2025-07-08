-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 08 Jul 2025 pada 04.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `civicacare`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `education` varchar(50) NOT NULL,
  `availability` text NOT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `status` enum('diajukan','diseleksi','diterima','ditolak','mengundurkan_diri') NOT NULL DEFAULT 'diajukan',
  `role` enum('relawan','admin','koordinator') NOT NULL DEFAULT 'relawan',
  `rejection_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `applicants`
--

INSERT INTO `applicants` (`id`, `name`, `nik`, `email`, `address`, `phone`, `education`, `availability`, `document_name`, `status`, `role`, `rejection_reason`, `created_at`) VALUES
(1, 'Arisa Naruse', '5345452341231231', 'arisanaru4@gmail.com', 'SKH', '089653453452', 'D3', 'senin,selasa,rabu,kamis,jumat', 'doc_686bd2c221334.png', 'diterima', 'relawan', NULL, '2025-07-07 20:59:30'),
(2, 'Choji Yakiniku', '1231231342453456', 'chojitulangbesar@gmail.com', 'KRA', '085121095388', 'S2', 'senin,selasa,sabtu,minggu', 'doc_686bd328ba372.png', 'diterima', 'relawan', NULL, '2025-07-07 21:01:12'),
(3, 'Farelli Faith', '4234132313534009', 'farelliexmple@gmail.com', 'SKA', '083563563112', 'S1/D4', 'senin,selasa,kamis,minggu', 'doc_686bd38e9adf0.png', 'diterima', 'koordinator', NULL, '2025-07-07 21:02:54'),
(5, 'Haidar Rahman', '9569940564596301', 'haidarr@gmail.com', 'SOLO', '082434234210', 'S1/D4', 'rabu,kamis,sabtu,minggu', '', 'diterima', 'relawan', NULL, '2025-07-07 21:24:41'),
(6, 'Awan Eka Dana', '8876875546540064', 'awaneka@gmail.com', 'SKH', '085436634261', 'S1/D4', 'selasa,kamis,jumat,sabtu', 'doc_686c7c10673f7.png', 'diterima', 'relawan', NULL, '2025-07-08 09:01:52'),
(7, 'Fahrudin Wahyu', '6574653543430876', 'wahyuudb5@gmail.com', 'Sukoharjo', '08766465354', 'S1/D4', 'selasa,rabu,jumat,sabtu', 'doc_686c81bfe62b8.png', 'diterima', 'relawan', NULL, '2025-07-08 09:26:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `koordinator_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `status` enum('terjadwal','berlangsung','selesai') DEFAULT 'terjadwal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `assignments`
--

INSERT INTO `assignments` (`id`, `kegiatan_id`, `user_id`, `koordinator_id`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `status`) VALUES
(3, 1, NULL, 3, '2025-07-07', '21:30:00', '22:00:00', 'selesai'),
(4, 2, NULL, 3, '2025-07-07', '22:45:00', '23:00:00', 'selesai'),
(10, 5, NULL, 3, '2025-07-08', '00:25:00', '00:39:00', 'selesai'),
(11, 7, NULL, 3, '2025-07-08', '09:10:00', '09:15:00', 'terjadwal'),
(12, 8, NULL, 3, '2025-07-08', '09:30:00', '09:35:00', 'berlangsung');

-- --------------------------------------------------------

--
-- Struktur dari tabel `assignment_relawan`
--

CREATE TABLE `assignment_relawan` (
  `id` int(11) NOT NULL,
  `relawan_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `assignment_relawan`
--

INSERT INTO `assignment_relawan` (`id`, `relawan_id`, `assignment_id`) VALUES
(5, 1, 3),
(6, 2, 3),
(7, 1, 4),
(8, 2, 4),
(15, 1, 10),
(16, 6, 11),
(17, 7, 12);

-- --------------------------------------------------------

--
-- Struktur dari tabel `evaluasi`
--

CREATE TABLE `evaluasi` (
  `id` int(11) NOT NULL,
  `id_relawan` int(11) NOT NULL,
  `id_kegiatan` int(11) NOT NULL,
  `tanggal_evaluasi` date DEFAULT NULL,
  `kedisiplinan` tinyint(4) DEFAULT NULL,
  `komunikasi` tinyint(4) DEFAULT NULL,
  `kerjasama` tinyint(4) DEFAULT NULL,
  `tanggung_jawab` tinyint(4) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `is_present` tinyint(1) DEFAULT 0,
  `evaluated_by` int(11) DEFAULT NULL,
  `nilai` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `evaluasi`
--

INSERT INTO `evaluasi` (`id`, `id_relawan`, `id_kegiatan`, `tanggal_evaluasi`, `kedisiplinan`, `komunikasi`, `kerjasama`, `tanggung_jawab`, `catatan`, `is_present`, `evaluated_by`, `nilai`) VALUES
(2, 1, 1, '2025-07-07', 4, 5, 5, 4, 'sangat baik', 1, 3, 4),
(4, 2, 1, '2025-07-07', 4, 4, 3, 3, 'baik, tingkatkan', 1, 3, 4),
(5, 1, 2, '2025-07-08', 4, 4, 4, 4, 'bagus', 1, 3, 4),
(6, 2, 2, '2025-07-08', 4, 3, 3, 3, 'bagus', 1, 3, 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `evaluasi_keseluruhan`
--

CREATE TABLE `evaluasi_keseluruhan` (
  `id` int(11) NOT NULL,
  `id_kegiatan` int(11) NOT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `catatan_umum` text DEFAULT NULL,
  `tanggal_rekap` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `evaluasi_keseluruhan`
--

INSERT INTO `evaluasi_keseluruhan` (`id`, `id_kegiatan`, `nilai_akhir`, `catatan_umum`, `tanggal_rekap`) VALUES
(1, 1, 80.00, 'Kegiatan berhasil, tingkatkan', '2025-07-07'),
(2, 2, 74.00, 'Tingkatkan performamu', '2025-07-08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id` int(11) NOT NULL,
  `nama_kegiatan` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `kuota_relawan` int(11) NOT NULL,
  `perlengkapan` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kegiatan`
--

INSERT INTO `kegiatan` (`id`, `nama_kegiatan`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `lokasi`, `deskripsi`, `kategori`, `kuota_relawan`, `perlengkapan`, `catatan`, `status`) VALUES
(1, 'Bersih Desa', '2025-07-07', '21:30:00', '22:00:00', 'SKA', 'Bersih-bersih', 'Lingkungan', 3, 'Topi', 'Note', 'aktif'),
(2, 'Tumpengan Solo', '2025-07-07', '22:45:00', '23:00:00', 'SKA', 'Anggap saja acara adat', 'Adat', 3, 'Jaket', 'Catatan', 'aktif'),
(5, 'Javanese Midnight Shop', '2025-07-08', '00:25:00', '00:39:00', 'SKA', 'Kegiatan Obral Pakaian Tradisional Tengah Malam', 'Sosial', 2, 'Jaket', 'Awas masuk angin', 'aktif'),
(6, 'Midnight Fashion Market', '2025-07-08', '06:38:00', '07:38:00', 'SKA', 'market fashion tengah malam', 'Fashion', 2, 'Jaket', 'Pagi', 'aktif'),
(7, 'Jalan Sehat Balaikota', '2025-07-08', '09:10:00', '09:15:00', 'Surakarta', 'jalan sehat', 'Olahraga', 2, 'sepatu jogging', 'jangan nyampah', 'aktif'),
(8, 'Bersih Kota', '2025-07-08', '09:30:00', '09:35:00', 'Surakarta', 'Bersih-bersih surakarta', 'Lingkungan', 2, 'Sapu', 'Jangan nyampah', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `tanggal_kirim` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('belum_terbaca','terbaca') NOT NULL DEFAULT 'belum_terbaca',
  `user_id` int(11) DEFAULT NULL,
  `status_baca` varchar(20) DEFAULT 'belum_terbaca',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `id_user`, `judul`, `pesan`, `tanggal_kirim`, `status`, `user_id`, `status_baca`, `created_at`) VALUES
(18, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Javanese Midnight Shop\" tanggal 2025-07-08.', '2025-07-08 00:39:41', 'terbaca', 3, 'belum_terbaca', '2025-07-08 00:39:41'),
(19, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Javanese Midnight Shop\" tanggal 2025-07-08.', '2025-07-08 00:39:41', 'terbaca', 1, 'belum_terbaca', '2025-07-08 00:39:41'),
(20, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Jalan Sehat Balaikota\" tanggal 2025-07-08.', '2025-07-08 09:05:31', 'terbaca', 3, 'belum_terbaca', '2025-07-08 09:05:31'),
(21, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Jalan Sehat Balaikota\" tanggal 2025-07-08.', '2025-07-08 09:05:31', 'terbaca', 6, 'belum_terbaca', '2025-07-08 09:05:31'),
(22, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Bersih Kota\" tanggal 2025-07-08.', '2025-07-08 09:30:28', 'belum_terbaca', 3, 'belum_terbaca', '2025-07-08 09:30:28'),
(23, 0, 'Penugasan Baru', 'Anda mendapatkan penugasan baru pada kegiatan \"Bersih Kota\" tanggal 2025-07-08.', '2025-07-08 09:30:28', 'belum_terbaca', 7, 'belum_terbaca', '2025-07-08 09:30:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjadwalan`
--

CREATE TABLE `penjadwalan` (
  `id` int(11) NOT NULL,
  `kegiatan_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `role` enum('admin','koordinator','relawan') NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `education` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `no_hp`, `role`, `status`, `created_at`, `education`) VALUES
(1, 'Arisa', '$2y$10$6HG27G2NmGA49gythSxFpOOKjVTM10wYysJd9JvwrKonG0YxqsLJO', 'arisanaru4@gmail.com', NULL, 'relawan', 'aktif', '2025-07-07 08:09:58', NULL),
(2, 'Choji', '$2y$10$1RLImC7FANOM.l8YUIYNQe0ujnRLSYrhwwThcG4lT2XIl9gm5UuUC', 'chojitulangbesar@gmail.com', NULL, 'relawan', 'aktif', '2025-07-07 08:16:33', NULL),
(3, 'farelli', '$2y$10$k7jbnvD4wPdhf9k9g7XugO.aoVo.SRx3zY03mFVeS8rwCW4IxSD.W', 'farelliexmple@gmail.com', NULL, 'koordinator', 'aktif', '2025-07-07 08:19:58', NULL),
(4, 'admin', '$2y$10$glb1.GEPtTBkjTwA6F3lWuGLSV2BCyy/TaScxhY0GY47N0Lq35Nja', 'admin', '081234234234', 'admin', 'aktif', '2025-07-07 08:53:20', NULL),
(5, 'Haidar', '$2y$10$QgQo.GEMjXJOk4uTWCRZd.yKHCEeKBRUf0jU9CfKvZARTWu73oLrS', 'haidarr@gmail.com', NULL, 'relawan', 'aktif', '2025-07-07 14:11:59', NULL),
(6, 'Awan', '$2y$10$02PktYEVdIyGTkhubVUzXuIAaCseUbVJ7ogXvDMoBPM0wNuwRDQDG', 'awaneka@gmail.com', NULL, 'relawan', 'aktif', '2025-07-08 02:00:31', NULL),
(7, 'Wahyu', '$2y$10$MnAKWLL.rRtjryLc0ntGJOE3JMtOQ.9gEAb4HLnFtxYCeW2hEY.Qe', 'wahyuudb5@gmail.com', NULL, 'relawan', 'aktif', '2025-07-08 02:24:11', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kegiatan_id` (`kegiatan_id`);

--
-- Indeks untuk tabel `assignment_relawan`
--
ALTER TABLE `assignment_relawan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `relawan_id` (`relawan_id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indeks untuk tabel `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_relawan` (`id_relawan`),
  ADD KEY `id_kegiatan` (`id_kegiatan`),
  ADD KEY `fk_evaluated_by` (`evaluated_by`);

--
-- Indeks untuk tabel `evaluasi_keseluruhan`
--
ALTER TABLE `evaluasi_keseluruhan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kegiatan` (`id_kegiatan`);

--
-- Indeks untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifikasi_user` (`user_id`);

--
-- Indeks untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penjadwalan`
--
ALTER TABLE `penjadwalan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `assignment_relawan`
--
ALTER TABLE `assignment_relawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `evaluasi`
--
ALTER TABLE `evaluasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `evaluasi_keseluruhan`
--
ALTER TABLE `evaluasi_keseluruhan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penjadwalan`
--
ALTER TABLE `penjadwalan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`kegiatan_id`) REFERENCES `kegiatan` (`id`);

--
-- Ketidakleluasaan untuk tabel `assignment_relawan`
--
ALTER TABLE `assignment_relawan`
  ADD CONSTRAINT `assignment_relawan_ibfk_1` FOREIGN KEY (`relawan_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `assignment_relawan_ibfk_2` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`);

--
-- Ketidakleluasaan untuk tabel `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD CONSTRAINT `evaluasi_ibfk_1` FOREIGN KEY (`id_relawan`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `evaluasi_ibfk_2` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id`),
  ADD CONSTRAINT `fk_evaluated_by` FOREIGN KEY (`evaluated_by`) REFERENCES `applicants` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `evaluasi_keseluruhan`
--
ALTER TABLE `evaluasi_keseluruhan`
  ADD CONSTRAINT `evaluasi_keseluruhan_ibfk_1` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id`);

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notifikasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
