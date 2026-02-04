-- Create role catalog
CREATE TABLE IF NOT EXISTS `epi_roles` (
  `role_code` VARCHAR(1) NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `level` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`role_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `epi_roles` (`role_code`,`name`,`level`) VALUES
  ('1','Member',1),
  ('6','Finance Manager',6),
  ('7','Operasional Manager',7),
  ('8','Writer Manager',8),
  ('9','Administrator',9)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`level`=VALUES(`level`);

-- Define menu permissions per role
CREATE TABLE IF NOT EXISTS `epi_role_permissions` (
  `role_code` VARCHAR(1) NOT NULL,
  `menu_key` VARCHAR(64) NOT NULL,
  `allowed` TINYINT(1) NOT NULL DEFAULT 1,
  `version` INT NOT NULL DEFAULT 1,
  `updated_by` BIGINT(20) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_code`,`menu_key`),
  KEY `idx_menu` (`menu_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Audit log for role changes
CREATE TABLE IF NOT EXISTS `epi_audit_log` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `actor_id` BIGINT(20) NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `target` VARCHAR(64) DEFAULT NULL,
  `detail` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actor` (`actor_id`),
  KEY `idx_action` (`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- Rollback
-- DROP TABLE `epi_role_permissions`;
-- DROP TABLE `epi_roles`;
-- DROP TABLE `epi_audit_log`;

