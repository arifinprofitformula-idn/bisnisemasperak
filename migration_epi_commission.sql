-- Create table for commission payouts tracking
CREATE TABLE IF NOT EXISTS `epi_commission_payout` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lap_id` INT UNSIGNED NULL,
  `order_id` INT UNSIGNED NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `type` ENUM('sponsor','contrib') NOT NULL,
  `amount` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('requested','pending','processed','paid') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NULL,
  `paid_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `processed_by` INT UNSIGNED NULL,
  `paid_by` INT UNSIGNED NULL,
  `note` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lap` (`lap_id`),
  KEY `idx_status` (`status`),
  KEY `idx_receiver` (`receiver_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit trail for payout status changes
CREATE TABLE IF NOT EXISTS `epi_commission_payout_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payout_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `old_status` ENUM('requested','pending','processed','paid') NOT NULL,
  `new_status` ENUM('requested','pending','processed','paid') NOT NULL,
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payout` (`payout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rollback
-- DROP TABLE IF EXISTS `epi_commission_payout_log`;
-- DROP TABLE IF EXISTS `epi_commission_payout`;
-- Upgrade (existing installs):
-- ALTER TABLE `epi_commission_payout` MODIFY `lap_id` INT NULL;
-- ALTER TABLE `epi_commission_payout` MODIFY `order_id` INT NULL;
-- ALTER TABLE `epi_commission_payout` MODIFY `status` ENUM('requested','pending','processed','paid') NOT NULL DEFAULT 'pending';
