-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 06 Jun 2025 pada 23.10
-- Versi server: 10.5.27-MariaDB-log
-- Versi PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `divisi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `divisi`
--

CREATE TABLE `divisi` (
  `id_divisi` int(11) NOT NULL,
  `nama_divisi` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_dibuat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `divisi`
--

INSERT INTO `divisi` (`id_divisi`, `nama_divisi`, `deskripsi`, `tanggal_dibuat`) VALUES
(1, 'Divisi 1: Pendidikan dan Pengembangan SDM', '', '2025-05-11 21:57:23'),
(2, 'Divisi 2: Penelitian dan Pengembangan', '', '2025-05-11 21:57:33'),
(3, 'Divisi 3: Penguatan Jaringan Alumni', '', '2025-05-11 21:57:40'),
(4, 'Divisi 4: Kesejahteraan Sosial', '', '2025-05-11 21:57:48'),
(5, 'Divisi 5: Perencanaan dan Pengembangan Sarpras', '', '2025-05-11 21:57:55'),
(6, 'Divisi 6: Pengembangan Usaha dan Kemitraan', '', '2025-05-11 21:58:01'),
(7, 'Divisi 7: Pengembangan IT, Media dan Publikasi', '', '2025-05-11 21:58:08'),
(8, 'Divisi 8: Pemberdayaan Ekonomi dan Koperasi', '', '2025-05-11 21:14:52'),
(9, 'Divisi 9: Hukum, Advokasi dan Perpajakan', '', '2025-05-11 21:58:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen`
--

CREATE TABLE `dokumen` (
  `id_dokumen` int(11) NOT NULL,
  `id_kegiatan` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `path_file` varchar(255) NOT NULL,
  `tipe_file` varchar(50) DEFAULT NULL,
  `ukuran_file` int(11) DEFAULT NULL,
  `tanggal_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id_kegiatan` int(11) NOT NULL,
  `id_program` int(11) NOT NULL,
  `nama_kegiatan` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `penanggung_jawab` int(11) DEFAULT NULL,
  `status` enum('Belum Dimulai','Berjalan','Selesai','Tertunda') DEFAULT 'Belum Dimulai',
  `persentase` int(11) DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `anggaran` decimal(15,2) DEFAULT NULL,
  `realisasi` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `id_divisi` int(11) DEFAULT NULL,
  `level` enum('admin','manager','staff') DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `username`, `password`, `nama_lengkap`, `email`, `id_divisi`, `level`) VALUES
(1, 'adminyayasan', '@dminyayasan', 'Admin Yayasan', 'yayasantbs@gmail.com', NULL, 'admin'),
(2, 'admin8', 'admin8', 'Admin Divisi 8', 'admin8@tbs.com', 8, 'manager'),
(3, 'staf8', 'staf8', 'staf divisi 8', 'staf8@tbs.com', 8, 'staff');

-- --------------------------------------------------------

--
-- Struktur dari tabel `program_kerja`
--

CREATE TABLE `program_kerja` (
  `id_program` int(11) NOT NULL,
  `id_divisi` int(11) NOT NULL,
  `nama_program` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `anggaran` decimal(15,2) DEFAULT NULL,
  `status` enum('Perencanaan','Berjalan','Selesai','Tertunda') DEFAULT 'Perencanaan',
  `persentase` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `program_kerja`
--

INSERT INTO `program_kerja` (`id_program`, `id_divisi`, `nama_program`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `anggaran`, `status`, `persentase`, `created_at`, `created_by`) VALUES
(2, 1, 'Menyusun SOP dan instrumen verifikasi Kepala', 'Fit and Propertais calon peserta\r\n', '2025-05-20', '2030-05-01', 5000000.00, 'Perencanaan', 0, '2025-05-21 02:39:55', 1),
(3, 1, 'SOP dan Kriteria Recruitmen  ', 'FGD\r\n', '2025-06-01', '2025-06-30', 6500000.00, 'Perencanaan', 0, '2025-05-21 02:57:10', 1),
(4, 1, 'analisa kebutuhan SDM ditingkatan', 'Rapat Kordinasi Kepala tingkatan akhir tahun\r\n', '2025-07-01', '2025-07-31', 6300000.00, 'Perencanaan', 0, '2025-05-21 02:57:48', 1),
(5, 1, 'Membuat program jenjang karir', 'Bea siswa (subsidi) peningkatan SDM\r\n', '2026-01-01', '2026-01-31', 9350000.00, 'Perencanaan', 0, '2025-05-21 02:58:48', 1),
(6, 1, 'analisa SDM pendidik  Tendik', 'FGD\r\n', '2026-03-01', '2026-03-31', 5500000.00, 'Perencanaan', 0, '2025-05-21 02:59:53', 1),
(7, 1, 'Pelatihan pengembangan SDM', 'Membangun kerjama dengan stekholder baik internal dan eksternal\r\n', '2026-05-01', '2026-05-31', 7620000.00, 'Perencanaan', 0, '2025-05-21 03:01:31', 1),
(8, 1, 'menjalin kerjasama divisi jaringan dalam distribusi alumni', 'Rapat Kordinasi dengan Devisi Jaringan\r\n', '2026-07-01', '2026-07-31', 5335000.00, 'Perencanaan', 0, '2025-05-21 03:02:43', 1),
(9, 1, 'Mengadakan koordinasi tentang study kelayakan pendidik dan tendik', 'Pengusulan pengangkatan Guru Tetap\r\n', '2026-08-01', '2026-08-31', 7260000.00, 'Perencanaan', 0, '2025-05-21 03:04:13', 1),
(10, 1, 'Training Manajemen SDM', '1. Guru dan Kepala\r\n2. Pelatihan Manajemen Risiko Organisasi (Kerjasama dev Hukum)\r\n', '2026-11-01', '2027-01-31', 16500000.00, 'Perencanaan', 0, '2025-05-21 03:05:59', 1),
(11, 1, 'Dik learning dan salafiyah lokal ', 'Review Kurikulum (Kerja sama dev litbang)\r\n', '2025-09-01', '2025-09-30', 11192500.00, 'Perencanaan', 0, '2025-05-21 03:07:01', 1),
(12, 1, 'ESQ Power traning All Guru ', 'telaah Visi misi Bersama\r\n', '2025-11-01', '2025-11-30', 46480000.00, 'Perencanaan', 0, '2025-05-21 03:07:55', 1),
(13, 2, 'Penelitian Kurikulum Salaf dan Pengembangan Model Pembelajaran TBS', '1.	Evaluasi kurikulum berbasis kajian salaf dan modern secara integral di semua tingkatan\r\n2.	Penyusunan Struktur Hierarki Kurikulum Muatan Salaf secara integral di semua tingkatan\r\n3.	Integrasi kurikulum salaf dan nasional dengan mendatangkan pakar kurikulum sebagai analis atau hanya sekadar pembanding\r\n', '2025-05-01', '2025-05-31', 0.00, 'Perencanaan', 0, '2025-05-21 03:48:52', 1),
(14, 2, 'Penyusunan Buku Panduan Pembelajaran Berciri Salafiyah', '1.	Penyusunan Buku Panduan Ibadah Sosial (Booklet & pdf),\r\n2.	Inventarisir Kitab-Kitab Karya Masyayikh dan Asatidz TBS\r\n', '2025-05-01', '2025-08-31', 0.00, 'Perencanaan', 0, '2025-05-21 03:50:31', 1),
(15, 2, 'Pemetaan Sejarah & Tokoh TBS', '1.	Pengukuhan/Validasi Sejarah TBS (Tanggal berdiri, Muassis, Masyayikh, Kepala Madrasah dari waktu ke waktu); \r\n2.	Pengadaan Museum TBS\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 03:51:30', 1),
(16, 2, 'Forum Kajian & Diskusi Intelektual Santri dan Guru', 'Diskusi Nilai-Nilai Luhur Masyayikh TBS', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 03:52:20', 1),
(17, 9, 'Legal audit dan legalitas', 'audit legalitas lembaga dan kelengkapan dokumen hukum yayasan\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 03:59:50', 1),
(18, 9, 'review perjanjian kerjasama', '\"telaah dan revisi dokumen MoU dengan mitra (pemerintah/swasta)\r\nYayasan dan lembaga di bawahnya\"\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:00:32', 1),
(19, 9, 'Pendampingan Sengketa', 'pendampingan dan penyelesaian hukum jika terjadi konflik hukum\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:01:40', 1),
(20, 9, 'penyuluhan hukum yayasan dan madrasah', 'edukasi hukum untuk pengurus, tenaga pengajardan siswa/santri\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:02:31', 1),
(21, 9, 'monitoring ketaatan pajak', 'pemeriksaan dan evaluasi pelaporan pajak\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:03:03', 1),
(22, 8, 'Evaluasi Pengelolaan Unit Usaha pada Periode Seblumnya', 'Supervisi kepada Unit Usaha yang telah berjalan\r\nSurvey lokasi usaha baru di lingkup internal Yayasan TBS \r\nBerkomunikasi dengan lembaga internal (sekolah/ madrasah) di lingkup Yayasan TBS yang belum terjamah oleh Divisi Ekonomi\r\n', '2025-05-20', '2025-05-31', 0.00, 'Perencanaan', 0, '2025-05-21 04:14:26', 1),
(23, 8, 'Tindak Lanjut Hasil Temuan', '\"Perencanaan, Penyusunan, dan\r\nPenetapan Program Baru untuk Ekspansi ke Dalam (Lingkup Yayasan TBS)\"\r\n\"Recruitmen Organizing Commitee\r\n(Pelaksana Lapangan)\"\r\nKonsultasi dengan ahli untuk pengembangan potensi Koperasi\r\n', '2025-06-01', '2025-06-30', 0.00, 'Perencanaan', 0, '2025-05-21 04:14:58', 1),
(24, 8, 'Komunikasi Rutin dengan Berbagai Pihak', '\"Mengadakan pertemuan rutin dengan semua anggota Divisi Pemberdayaan Ekonomi dan\r\nKoperasi Yayasan TBS \"\r\nMengadakan pertemuan rutin dengan Ketua II Yayasan TBS\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:15:37', 1),
(25, 8, 'Pelaporan Kegiatan Divisi Pemberdayaan Ekonomi dan Koperasi Yayasan TBS', '\"Penghimpunan dan penyusunan\r\nlaporan semua unit usaha Divisi\r\nPemberdayaan Ekonomi dan Koperasi Yayasan TBS\"\r\nPenyusunan laporan Akhir Periode kepada Pengurus Harian Yayasan TBS\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:16:12', 1),
(26, 6, 'Inventarisasi Data Siswa', 'Pendataan Siswa Kurang Mampu \r\n', '2025-07-01', '2025-07-31', 0.00, 'Perencanaan', 0, '2025-05-21 04:17:00', 1),
(27, 6, 'Sosialisasi/ Penyuluhan', 'Pembinaan, Kemitraan dengan Kepolisisan Terkait Mental dan Spiritual\r\n', '2025-08-01', '2025-08-31', 5000000.00, 'Perencanaan', 0, '2025-05-21 04:17:34', 1),
(28, 6, 'Kemitraan Beasiswa Pendidikan Sarpras dan Sosial', 'Chaneling Kemitraan Universitas, Perusahaan dan Bank\r\n', '2025-08-01', '2025-08-31', 5000000.00, 'Perencanaan', 0, '2025-05-21 04:18:02', 1),
(29, 6, 'Kordinasi Monitoring dengan Divisi Lain', 'Pertemuan dengan Divisi Lain\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:18:40', 1),
(30, 6, 'Memfasilitasi penerimaan Hibah dan Wakaf', 'Menganalisia Hibah atau Wakaf yang akan diterima\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 04:19:16', 1),
(31, 7, 'Kaderisasi calon designer dan programmer', 'Penguatan SDM\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:31:59', 1),
(32, 7, 'Roadshow pelatihan pengelolaan media Masjid di Kabupaten Kudus', 'Branding Istitusi (Penguatan Ahlussunnah An-Nahdliyah & Menyemarakkan 1 Abad Madrasah TBS\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:32:35', 1),
(33, 7, 'Maksimalisasi pengelolaan media sosial Yayasan (IG, Yt dan FB)', 'Optimalisasi website Yayasan\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:33:08', 1),
(34, 7, 'Membuat FAQ', 'Pembuatan sistem komunikasi publik\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:33:56', 1),
(35, 7, 'Bermitra dan sosialisasi Podcast MTs untuk pembuatan konten podcast', 'Sosialisasi ke tiap tingkatan di Yayasan\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:34:27', 1),
(36, 7, 'Pelatihan peningkatan kualitas tenaga kependidikan dibawah naungan Yayasan TBS', 'Pelatihan Input Rapot Digital melalui aplikasi excell\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:35:00', 1),
(37, 7, 'Membuat SIM progres kerja devisi yayasan ', '', '2025-05-12', '2025-05-20', 0.00, 'Selesai', 0, '2025-05-21 07:36:47', 1),
(38, 7, 'Podcast Dialogis Ringan', 'Membuat Video Reels\r\n\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:37:32', 1),
(39, 4, 'PERNIKAHAN	', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 1000000.00, 'Perencanaan', 0, '2025-05-21 07:39:57', 1),
(40, 4, 'PERNIKAHAN', 'MANTU\r\n', '2025-05-20', '2030-05-01', 750000.00, 'Perencanaan', 0, '2025-05-21 07:40:30', 1),
(41, 4, 'HAJAT KHITAN', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 500000.00, 'Perencanaan', 0, '2025-05-21 07:41:02', 1),
(42, 4, 'KELAHIRAN NORMAL', 'PENGURUS/GURU/KARYAWAN/ISTRI \r\n\r\n', '2025-05-20', '2030-05-01', 750000.00, 'Perencanaan', 0, '2025-05-21 07:41:45', 1),
(43, 4, 'KELAHIRAN OPERASI/KEMBAR', 'PENGURUS/GURU/KARYAWAN/ISTRI \r\n\r\n', '2025-05-20', '2030-05-01', 1000000.00, 'Perencanaan', 0, '2025-05-21 07:42:20', 1),
(44, 4, 'PINDAHAN RUMAH BARU', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 500000.00, 'Perencanaan', 0, '2025-05-21 07:42:52', 1),
(45, 4, 'SAKIT PERAWATAN', 'PENGURUS/GURU/KARYAWAN NON BPJS\r\n\r\n', '2025-05-20', '2030-05-01', 500000.00, 'Perencanaan', 0, '2025-05-21 07:43:43', 1),
(46, 4, 'SAKIT PARAH', 'PENGURUS/GURU/KARYAWAN NON BPJS\r\n\r\n', '2025-05-20', '2030-05-01', 1000000.00, 'Perencanaan', 0, '2025-05-21 07:44:18', 1),
(47, 4, 'KEMATIAN', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 1000000.00, 'Perencanaan', 0, '2025-05-21 07:44:57', 1),
(48, 4, 'KEMATIAN KELUARGA', 'ISTRI, SUAMI, ANAK, ORANG TUA\r\n', '2025-05-20', '2030-05-01', 500000.00, 'Perencanaan', 0, '2025-05-21 07:45:45', 1),
(49, 4, 'KEMATIAN KELUARGA', 'KONDISIONAL\r\n', '2025-05-20', '2030-05-01', 750000.00, 'Perencanaan', 0, '2025-05-21 07:46:18', 1),
(50, 4, 'HAJI WAJIB', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 1000000.00, 'Perencanaan', 0, '2025-05-21 07:46:52', 1),
(51, 4, 'UMROH WAJIB', 'PENGURUS/GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 750000.00, 'Perencanaan', 0, '2025-05-21 07:47:21', 1),
(52, 4, 'BEASISWA AKADEMIK', 'SISWA\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:47:56', 1),
(53, 4, 'BEASISWA NON AKADEMIK', 'SISWA\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:48:31', 1),
(54, 4, 'YATAMA&DHU\'AFA', 'SISWA\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:49:01', 1),
(55, 4, 'BAKTI SOSIAL', 'MASYARAKAT\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:49:24', 1),
(56, 4, 'KARANGAN BUNGA', 'PENGURUS\r\n', '2025-05-20', '2030-05-01', 500000.00, 'Perencanaan', 0, '2025-05-21 07:50:54', 1),
(57, 4, 'BPJS KESEHATAN DAN KETENAGA KERJAAN', 'GURU/KARYAWAN\r\n', '2025-05-20', '2030-05-01', 0.00, 'Perencanaan', 0, '2025-05-21 07:51:35', 1),
(59, 5, 'Perencanaan', 'Menilai kebutuhan Gedung dan Sarpras yang ada dan yang dibutuhkan di masa depan, dengan mempertimbangkan pertumbuhan siswa, perubahan kurikulum, dan standar Sarpras yang berlaku.', NULL, NULL, 0.00, 'Perencanaan', 0, '2025-05-22 07:14:58', 1),
(60, 5, 'Pengadaan dan Pembangunan', 'Melakukan pembangunan Gedung baru dan pengadaan Sarpras baru sesuai dengan rencana kebutuhan dan anggaran yang telah disetujui.', NULL, NULL, 0.00, 'Perencanaan', 0, '2025-05-22 07:15:32', 1),
(61, 5, 'Pemeliharaan', 'Melakukan inventarisasi dan evaluasi kondisi Gedung dan Sarpras secara berkala untuk mengetahui kondisi, kerusakan, dan kebutuhan perbaikan.', NULL, NULL, 0.00, 'Perencanaan', 0, '2025-05-22 07:15:54', 1),
(62, 5, 'Pengelolaan', 'Memastikan Gedung dan Sarpras dimanfaatkan secara optimal dan sesuai dengan fungsinya.', NULL, NULL, 0.00, 'Perencanaan', 0, '2025-05-22 07:16:13', 1),
(63, 5, 'Pengembangan', 'Melakukan penelitian dan pengembangan untuk menemukan solusi inovatif dalam pengelolaan Sarpras dan pembangunan Gedung baru.', NULL, NULL, 0.00, 'Perencanaan', 0, '2025-05-22 07:16:32', 1);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id_divisi`);

--
-- Indeks untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_kegiatan` (`id_kegiatan`);

--
-- Indeks untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`),
  ADD KEY `id_program` (`id_program`),
  ADD KEY `penanggung_jawab` (`penanggung_jawab`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_divisi` (`id_divisi`);

--
-- Indeks untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD PRIMARY KEY (`id_program`),
  ADD KEY `id_divisi` (`id_divisi`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id_divisi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id_kegiatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  MODIFY `id_program` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`);

--
-- Ketidakleluasaan untuk tabel `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `kegiatan_ibfk_1` FOREIGN KEY (`id_program`) REFERENCES `program_kerja` (`id_program`),
  ADD CONSTRAINT `kegiatan_ibfk_2` FOREIGN KEY (`penanggung_jawab`) REFERENCES `pengguna` (`id_pengguna`),
  ADD CONSTRAINT `kegiatan_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id_pengguna`);

--
-- Ketidakleluasaan untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `pengguna_ibfk_1` FOREIGN KEY (`id_divisi`) REFERENCES `divisi` (`id_divisi`);

--
-- Ketidakleluasaan untuk tabel `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD CONSTRAINT `program_kerja_ibfk_1` FOREIGN KEY (`id_divisi`) REFERENCES `divisi` (`id_divisi`),
  ADD CONSTRAINT `program_kerja_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id_pengguna`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
