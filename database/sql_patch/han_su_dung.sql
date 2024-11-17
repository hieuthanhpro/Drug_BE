ALTER TABLE `drugstores` ADD COLUMN `start_time` DATETIME NULL DEFAULT NULL AFTER `updated_at`;

ALTER TABLE `drugstores` ADD COLUMN `end_time` DATETIME NULL DEFAULT NULL AFTER `start_time`;

ALTER TABLE `drug` MODIFY `name` varchar(500) NULL;

ALTER TABLE `vouchers` ADD COLUMN `recipient_id` INT NULL DEFAULT NULL AFTER `customer_id`;

ALTER TABLE `prescription` ADD COLUMN `month_old` INT NULL DEFAULT NULL AFTER `year_old`;