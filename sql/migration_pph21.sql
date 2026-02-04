-- EPI Hub — PPh21 Withholding Migration
ALTER TABLE `epi_commission_payout`
  ADD COLUMN `gross_amount` INT UNSIGNED NULL AFTER `amount`,
  ADD COLUMN `tax_percent` DECIMAL(5,2) NULL AFTER `gross_amount`,
  ADD COLUMN `tax_amount` INT UNSIGNED NULL AFTER `tax_percent`,
  ADD COLUMN `net_amount` INT UNSIGNED NULL AFTER `tax_amount`;

-- Backfill existing rows
UPDATE `epi_commission_payout` SET `gross_amount`=`amount`, `net_amount`=`amount` WHERE `gross_amount` IS NULL;

-- Rollback
-- ALTER TABLE `epi_commission_payout` DROP COLUMN `net_amount`;
-- ALTER TABLE `epi_commission_payout` DROP COLUMN `tax_amount`;
-- ALTER TABLE `epi_commission_payout` DROP COLUMN `tax_percent`;
-- ALTER TABLE `epi_commission_payout` DROP COLUMN `gross_amount`;
