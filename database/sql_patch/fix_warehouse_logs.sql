CREATE TABLE `fix_warehouse_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) DEFAULT '0',
  `drug_store_id` int(11) DEFAULT '0',
  `old_value` mediumtext COLLATE utf8mb4_unicode_ci,
  `new_value` mediumtext COLLATE utf8mb4_unicode_ci,
  `desc` text COLLATE utf8mb4_unicode_ci,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`created`,`id`),
  KEY `IDX_ID` (`id`),
  KEY `IDX_WH` (`wh_id`),
  KEY `IDX_STORE` (`drug_store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci