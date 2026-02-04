-- EPI Hub — Cleanup Komisi Penjualan (Backup + Hapus Data)
-- Tujuan: Backup semua data terkait penjualan & komisi, lalu bersihkan dengan aman.
-- Lingkup tabel: sa_order, sa_laporan (kode 1/2/3), epi_commission_payout, epi_commission_payout_log,
--                 epi_payment_confirm (jika ada), epi_admin_finance_log (opsional, log admin).
-- Catatan: Struktur produk (sa_page) TIDAK dihapus. Mapping kontributor (epi_product_contrib) TIDAK dihapus.

SET SESSION sql_notes=0;
SET FOREIGN_KEY_CHECKS=0;

-- =========================
-- 1) BACKUP (di dalam DB)
-- =========================
-- Hapus backup lama jika ada
DROP TABLE IF EXISTS `backup_sa_order`;
DROP TABLE IF EXISTS `backup_sa_laporan`;
DROP TABLE IF EXISTS `backup_epi_commission_payout`;
DROP TABLE IF EXISTS `backup_epi_commission_payout_log`;
DROP TABLE IF EXISTS `backup_epi_payment_confirm`;
DROP TABLE IF EXISTS `backup_epi_admin_finance_log`;

-- Buat tabel backup (struktur sama)
CREATE TABLE `backup_sa_order` LIKE `sa_order`;
CREATE TABLE `backup_sa_laporan` LIKE `sa_laporan`;
CREATE TABLE `backup_epi_commission_payout` LIKE `epi_commission_payout`;
CREATE TABLE `backup_epi_commission_payout_log` LIKE `epi_commission_payout_log`;
-- Tabel opsional: hanya jika ada
SET @tbl_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_payment_confirm');
SET @sql := IF(@tbl_exists>0, 'CREATE TABLE `backup_epi_payment_confirm` LIKE `epi_payment_confirm`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @tbl_exists2 := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_admin_finance_log');
SET @sql2 := IF(@tbl_exists2>0, 'CREATE TABLE `backup_epi_admin_finance_log` LIKE `epi_admin_finance_log`', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Salin data ke backup
INSERT INTO `backup_sa_order` SELECT * FROM `sa_order`;
INSERT INTO `backup_sa_laporan` SELECT * FROM `sa_laporan`;
INSERT INTO `backup_epi_commission_payout` SELECT * FROM `epi_commission_payout`;
INSERT INTO `backup_epi_commission_payout_log` SELECT * FROM `epi_commission_payout_log`;
SET @tbl_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_payment_confirm');
SET @sql := IF(@tbl_exists>0, 'INSERT INTO `backup_epi_payment_confirm` SELECT * FROM `epi_payment_confirm`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @tbl_exists2 := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_admin_finance_log');
SET @sql2 := IF(@tbl_exists2>0, 'INSERT INTO `backup_epi_admin_finance_log` SELECT * FROM `epi_admin_finance_log`', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- =========================
-- 2) CLEANUP (hapus aman)
-- =========================
-- Hapus riwayat payout dan log
TRUNCATE TABLE `epi_commission_payout_log`;
TRUNCATE TABLE `epi_commission_payout`;

-- Hapus pencatatan komisi & pembayaran admin dari ledger
DELETE FROM `sa_laporan` WHERE `lap_code` IN (1,2,3);

-- Hapus transaksi penjualan (invoice/orders)
TRUNCATE TABLE `sa_order`;

-- Opsional: bersihkan tabel konfirmasi pembayaran (jika ada)
SET @tbl_exists := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_payment_confirm');
SET @sql := IF(@tbl_exists>0, 'TRUNCATE TABLE `epi_payment_confirm`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Opsional: bersihkan log admin finance (jika perlu)
-- SET @tbl_exists2 := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epi_admin_finance_log');
-- SET @sql2 := IF(@tbl_exists2>0, 'TRUNCATE TABLE `epi_admin_finance_log`', 'SELECT 1');
-- PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SET FOREIGN_KEY_CHECKS=1;
SET SESSION sql_notes=1;

-- =========================
-- 3) VERIFIKASI (manual run)
-- =========================
-- SELECT COUNT(*) AS n FROM sa_order;
-- SELECT COUNT(*) AS n FROM epi_commission_payout;
-- SELECT COUNT(*) AS n FROM epi_commission_payout_log;
-- SELECT COUNT(*) AS n FROM sa_laporan WHERE lap_code IN (1,2,3);
-- (opsional) SELECT COUNT(*) AS n FROM epi_payment_confirm;

-- =========================
-- 4) ROLLBACK (restore dari backup)
-- =========================
-- TRUNCATE TABLE sa_order; INSERT INTO sa_order SELECT * FROM backup_sa_order;
-- TRUNCATE TABLE sa_laporan; INSERT INTO sa_laporan SELECT * FROM backup_sa_laporan;
-- TRUNCATE TABLE epi_commission_payout; INSERT INTO epi_commission_payout SELECT * FROM backup_epi_commission_payout;
-- TRUNCATE TABLE epi_commission_payout_log; INSERT INTO epi_commission_payout_log SELECT * FROM backup_epi_commission_payout_log;
-- (opsional) TRUNCATE epi_payment_confirm; INSERT INTO epi_payment_confirm SELECT * FROM backup_epi_payment_confirm;
-- (opsional) TRUNCATE epi_admin_finance_log; INSERT INTO epi_admin_finance_log SELECT * FROM backup_epi_admin_finance_log;

