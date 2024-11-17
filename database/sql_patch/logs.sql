CREATE TABLE `new_warehouse_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wh_id` int(11) DEFAULT '0',
  `drug_store_id` int(11) DEFAULT '0',
  `ref` varchar(1000) CHARACTER SET ascii DEFAULT NULL COMMENT 'URL phát sinh record log này',
  `action` varchar(150) CHARACTER SET ascii NOT NULL,
  `old_value` mediumtext COLLATE utf8mb4_unicode_ci,
  `new_value` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_user_id` varchar(36) CHARACTER SET ascii DEFAULT '',
  `created_username` varchar(150) CHARACTER SET ascii DEFAULT '',
  `desc` text COLLATE utf8mb4_unicode_ci,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`created`,`id`),
  KEY `IDX_ID` (`id`),
  KEY `IDX_WH` (`wh_id`),
  KEY `IDX_STORE_ACT` (`drug_store_id`,`action`),
  KEY `IDX_ACTION` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci