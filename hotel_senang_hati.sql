-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2025 at 03:27 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_senang_hati`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckKetersediaanKamar` (IN `p_tanggal_checkin` DATE, IN `p_tanggal_checkout` DATE, IN `p_tipe_kamar` INT)   BEGIN
    SELECT k.nomor_kamar, tk.nama_tipe, tk.harga_per_malam
    FROM kamar k
    JOIN tipe_kamar tk ON k.id_tipe_kamar = tk.id_tipe_kamar
    WHERE k.id_tipe_kamar = p_tipe_kamar
    AND k.status_kamar = 'tersedia'
    AND k.nomor_kamar NOT IN (
        SELECT DISTINCT nomor_kamar 
        FROM pemesanan 
        WHERE (tanggal_checkin <= p_tanggal_checkout 
               AND tanggal_checkout >= p_tanggal_checkin)
        AND status_pemesanan IN ('confirmed', 'checkedin')
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ProsesCheckin` (IN `p_kode_pemesanan` VARCHAR(20), IN `p_id_pegawai` INT, IN `p_deposit` DECIMAL(10,2))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Update status pemesanan
    UPDATE pemesanan 
    SET status_pemesanan = 'checkedin' 
    WHERE kode_pemesanan = p_kode_pemesanan;
    
    -- Update status kamar
    UPDATE kamar k
    JOIN pemesanan p ON k.nomor_kamar = p.nomor_kamar
    SET k.status_kamar = 'terisi'
    WHERE p.kode_pemesanan = p_kode_pemesanan;
    
    -- Insert record check-in
    INSERT INTO check_in_out 
    (kode_pemesanan, nomor_kamar, tanggal_checkin, id_pegawai_checkin, deposit)
    SELECT p.kode_pemesanan, p.nomor_kamar, NOW(), p_id_pegawai, p_deposit
    FROM pemesanan p
    WHERE p.kode_pemesanan = p_kode_pemesanan;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `check_in_out`
--

CREATE TABLE `check_in_out` (
  `id_checkin` int(11) NOT NULL,
  `kode_pemesanan` varchar(20) NOT NULL,
  `nomor_kamar` varchar(10) NOT NULL,
  `tanggal_checkin` datetime DEFAULT NULL,
  `tanggal_checkout` datetime DEFAULT NULL,
  `id_pegawai_checkin` int(11) DEFAULT NULL,
  `id_pegawai_checkout` int(11) DEFAULT NULL,
  `jumlah_tamu_aktual` int(11) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `biaya_tambahan` decimal(10,2) DEFAULT 0.00,
  `keterangan_tambahan` text DEFAULT NULL,
  `status` enum('checkedin','checkedout') DEFAULT 'checkedin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `check_in_out`
--
DELIMITER $$
CREATE TRIGGER `tr_update_status_kamar_after_checkout` AFTER UPDATE ON `check_in_out` FOR EACH ROW BEGIN
    IF NEW.status = 'checkedout' AND OLD.status = 'checkedin' THEN
        UPDATE kamar 
        SET status_kamar = 'tersedia' 
        WHERE nomor_kamar = NEW.nomor_kamar;
        
        UPDATE pemesanan 
        SET status_pemesanan = 'checkedout' 
        WHERE kode_pemesanan = NEW.kode_pemesanan;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kamar`
--

CREATE TABLE `kamar` (
  `nomor_kamar` varchar(10) NOT NULL,
  `id_tipe_kamar` int(11) NOT NULL,
  `lantai` int(11) NOT NULL,
  `status_kamar` enum('tersedia','terisi','maintenance','dibooking') DEFAULT 'tersedia',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `tabel_terkait` varchar(50) NOT NULL,
  `id_record` varchar(50) NOT NULL,
  `jenis_aktivitas` enum('insert','update','delete') NOT NULL,
  `data_lama` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_lama`)),
  `data_baru` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_baru`)),
  `id_pegawai` int(11) DEFAULT NULL,
  `timestamp_aktivitas` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id_pegawai` int(11) NOT NULL,
  `nama_pegawai` varchar(100) NOT NULL,
  `posisi` varchar(50) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tanggal_masuk` date NOT NULL,
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `kode_pemesanan` varchar(20) NOT NULL,
  `metode_pembayaran` enum('tunai','kartu_kredit','kartu_debit','voucher','transfer') NOT NULL,
  `jumlah_bayar` decimal(12,2) NOT NULL,
  `tanggal_pembayaran` datetime DEFAULT current_timestamp(),
  `nomor_referensi` varchar(50) DEFAULT NULL,
  `status_pembayaran` enum('pending','berhasil','gagal','refund') DEFAULT 'pending',
  `id_pegawai` int(11) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan`
--

CREATE TABLE `pemesanan` (
  `kode_pemesanan` varchar(20) NOT NULL,
  `id_tamu` int(11) NOT NULL,
  `nomor_kamar` varchar(10) DEFAULT NULL,
  `tanggal_checkin` date NOT NULL,
  `tanggal_checkout` date NOT NULL,
  `jumlah_malam` int(11) NOT NULL,
  `jumlah_tamu` int(11) NOT NULL,
  `cara_pemesanan` enum('telepon','email','langsung','online') NOT NULL,
  `status_pemesanan` enum('pending','confirmed','checkedin','checkedout','cancelled') DEFAULT 'pending',
  `total_harga` decimal(12,2) NOT NULL,
  `catatan_khusus` text DEFAULT NULL,
  `tanggal_pemesanan` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resto`
--

CREATE TABLE `resto` (
  `id_resto` int(11) NOT NULL,
  `nama_resto` varchar(100) NOT NULL,
  `jenis_masakan` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) NOT NULL,
  `jam_buka` time NOT NULL,
  `jam_tutup` time NOT NULL,
  `status_operasi` enum('buka','tutup','maintenance') DEFAULT 'buka',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tamu`
--

CREATE TABLE `tamu` (
  `id_tamu` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_identitas` varchar(50) NOT NULL,
  `jenis_identitas` enum('KTP','SIM','Passport') NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `kewarganegaraan` varchar(50) DEFAULT 'Indonesia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipe_kamar`
--

CREATE TABLE `tipe_kamar` (
  `id_tipe_kamar` int(11) NOT NULL,
  `nama_tipe` varchar(50) NOT NULL,
  `kapasitas_orang` int(11) NOT NULL,
  `harga_per_malam` decimal(10,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipe_kamar`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_ketersediaan_kamar`
-- (See below for the actual view)
--
CREATE TABLE `v_ketersediaan_kamar` (
`nomor_kamar` varchar(10)
,`nama_tipe` varchar(50)
,`kapasitas_orang` int(11)
,`harga_per_malam` decimal(10,2)
,`status_kamar` enum('tersedia','terisi','maintenance','dibooking')
,`lantai` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_pemesanan_aktif`
-- (See below for the actual view)
--
CREATE TABLE `v_pemesanan_aktif` (
`kode_pemesanan` varchar(20)
,`nama_lengkap` varchar(100)
,`no_telepon` varchar(20)
,`nomor_kamar` varchar(10)
,`nama_tipe` varchar(50)
,`tanggal_checkin` date
,`tanggal_checkout` date
,`status_pemesanan` enum('pending','confirmed','checkedin','checkedout','cancelled')
,`total_harga` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_ketersediaan_kamar`
--
DROP TABLE IF EXISTS `v_ketersediaan_kamar`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_ketersediaan_kamar`  AS SELECT `k`.`nomor_kamar` AS `nomor_kamar`, `tk`.`nama_tipe` AS `nama_tipe`, `tk`.`kapasitas_orang` AS `kapasitas_orang`, `tk`.`harga_per_malam` AS `harga_per_malam`, `k`.`status_kamar` AS `status_kamar`, `k`.`lantai` AS `lantai` FROM (`kamar` `k` join `tipe_kamar` `tk` on(`k`.`id_tipe_kamar` = `tk`.`id_tipe_kamar`)) WHERE `k`.`status_kamar` = 'tersedia' ;

-- --------------------------------------------------------

--
-- Structure for view `v_pemesanan_aktif`
--
DROP TABLE IF EXISTS `v_pemesanan_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pemesanan_aktif`  AS SELECT `p`.`kode_pemesanan` AS `kode_pemesanan`, `t`.`nama_lengkap` AS `nama_lengkap`, `t`.`no_telepon` AS `no_telepon`, `p`.`nomor_kamar` AS `nomor_kamar`, `tk`.`nama_tipe` AS `nama_tipe`, `p`.`tanggal_checkin` AS `tanggal_checkin`, `p`.`tanggal_checkout` AS `tanggal_checkout`, `p`.`status_pemesanan` AS `status_pemesanan`, `p`.`total_harga` AS `total_harga` FROM (((`pemesanan` `p` join `tamu` `t` on(`p`.`id_tamu` = `t`.`id_tamu`)) join `kamar` `k` on(`p`.`nomor_kamar` = `k`.`nomor_kamar`)) join `tipe_kamar` `tk` on(`k`.`id_tipe_kamar` = `tk`.`id_tipe_kamar`)) WHERE `p`.`status_pemesanan` in ('confirmed','checkedin') ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `check_in_out`
--
ALTER TABLE `check_in_out`
  ADD PRIMARY KEY (`id_checkin`),
  ADD KEY `kode_pemesanan` (`kode_pemesanan`),
  ADD KEY `nomor_kamar` (`nomor_kamar`),
  ADD KEY `id_pegawai_checkin` (`id_pegawai_checkin`),
  ADD KEY `id_pegawai_checkout` (`id_pegawai_checkout`);

--
-- Indexes for table `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`nomor_kamar`),
  ADD KEY `id_tipe_kamar` (`id_tipe_kamar`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pegawai` (`id_pegawai`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id_pegawai`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `kode_pemesanan` (`kode_pemesanan`),
  ADD KEY `id_pegawai` (`id_pegawai`);

--
-- Indexes for table `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD PRIMARY KEY (`kode_pemesanan`),
  ADD KEY `id_tamu` (`id_tamu`),
  ADD KEY `nomor_kamar` (`nomor_kamar`);

--
-- Indexes for table `resto`
--
ALTER TABLE `resto`
  ADD PRIMARY KEY (`id_resto`);

--
-- Indexes for table `tamu`
--
ALTER TABLE `tamu`
  ADD PRIMARY KEY (`id_tamu`),
  ADD UNIQUE KEY `no_identitas` (`no_identitas`);

--
-- Indexes for table `tipe_kamar`
--
ALTER TABLE `tipe_kamar`
  ADD PRIMARY KEY (`id_tipe_kamar`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `check_in_out`
--
ALTER TABLE `check_in_out`
  MODIFY `id_checkin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id_pegawai` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resto`
--
ALTER TABLE `resto`
  MODIFY `id_resto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tamu`
--
ALTER TABLE `tamu`
  MODIFY `id_tamu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipe_kamar`
--
ALTER TABLE `tipe_kamar`
  MODIFY `id_tipe_kamar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `check_in_out`
--
ALTER TABLE `check_in_out`
  ADD CONSTRAINT `check_in_out_ibfk_1` FOREIGN KEY (`kode_pemesanan`) REFERENCES `pemesanan` (`kode_pemesanan`),
  ADD CONSTRAINT `check_in_out_ibfk_2` FOREIGN KEY (`nomor_kamar`) REFERENCES `kamar` (`nomor_kamar`),
  ADD CONSTRAINT `check_in_out_ibfk_3` FOREIGN KEY (`id_pegawai_checkin`) REFERENCES `pegawai` (`id_pegawai`),
  ADD CONSTRAINT `check_in_out_ibfk_4` FOREIGN KEY (`id_pegawai_checkout`) REFERENCES `pegawai` (`id_pegawai`);

--
-- Constraints for table `kamar`
--
ALTER TABLE `kamar`
  ADD CONSTRAINT `kamar_ibfk_1` FOREIGN KEY (`id_tipe_kamar`) REFERENCES `tipe_kamar` (`id_tipe_kamar`);

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`kode_pemesanan`) REFERENCES `pemesanan` (`kode_pemesanan`),
  ADD CONSTRAINT `pembayaran_ibfk_2` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`);

--
-- Constraints for table `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD CONSTRAINT `pemesanan_ibfk_1` FOREIGN KEY (`id_tamu`) REFERENCES `tamu` (`id_tamu`),
  ADD CONSTRAINT `pemesanan_ibfk_2` FOREIGN KEY (`nomor_kamar`) REFERENCES `kamar` (`nomor_kamar`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
