-- Migration SQL untuk Integrasi Mailketing
-- Jalankan script ini di database untuk memastikan tabel yang diperlukan tersedia

-- 1. Tabel konfigurasi Mailketing
CREATE TABLE IF NOT EXISTS `epi_mailketing_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel log email untuk monitoring
CREATE TABLE IF NOT EXISTS `epi_email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_type` varchar(50) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `provider` enum('mailketing','smtp') NOT NULL DEFAULT 'mailketing',
  `response_data` text,
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_type` (`email_type`),
  KEY `idx_status` (`status`),
  KEY `idx_provider` (`provider`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insert konfigurasi default Mailketing (jika belum ada)
INSERT IGNORE INTO `epi_mailketing_config` (`config_key`, `config_value`) VALUES
('api_key', ''),
('api_url', 'https://api.mailketing.co.id'),
('from_email', ''),
('from_name', 'Simple Aff Plus'),
('default_list_id', ''),
('is_enabled', '0'),
('test_mode', '1');

-- 4. Tabel template email (jika belum ada)
CREATE TABLE IF NOT EXISTS `epi_email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_type` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `content` longtext,
  `mailketing_template_id` varchar(100),
  `mailketing_list_id` varchar(100),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_type` (`email_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insert template email default (jika belum ada)
INSERT IGNORE INTO `epi_email_templates` (`email_type`, `name`, `subject`, `description`) VALUES
('registration_member', 'Registrasi Member', 'Selamat Datang di Simple Aff Plus', 'Template untuk notifikasi registrasi member baru'),
('upgrade_member', 'Upgrade Member', 'Upgrade Berhasil', 'Template untuk notifikasi upgrade member'),
('order_member', 'Order Produk', 'Order Diterima', 'Template untuk notifikasi order produk'),
('withdrawal_member', 'Pencairan Komisi', 'Pencairan Diproses', 'Template untuk notifikasi pencairan komisi');

-- 6. Buat index untuk optimasi performa
CREATE INDEX IF NOT EXISTS `idx_epi_email_logs_composite` ON `epi_email_logs` (`email_type`, `status`, `created_at`);

-- Selesai migration
-- Pastikan untuk backup database sebelum menjalankan script ini