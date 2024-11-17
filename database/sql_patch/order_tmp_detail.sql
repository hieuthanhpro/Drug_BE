CREATE TABLE `order_detail_tmp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `drug_id` int(11) DEFAULT NULL,
  `concentration` varchar(100) DEFAULT NULL COMMENT 'Ham luong',
  `package_form` varchar(100) DEFAULT NULL COMMENT 'quy cach dong goi',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT 'nha san xuat',
  `unit_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `cost` decimal(11,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;