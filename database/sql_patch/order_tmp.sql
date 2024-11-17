CREATE TABLE `order_tmp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `drug_store_id` int(11) DEFAULT NULL,
  `supplier_order_code` varchar(20) DEFAULT NULL,
  `order_code` varchar(15) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `amount` decimal(20,2) DEFAULT NULL COMMENT 'giá chưa vat',
  `vat_amount` decimal(20,2) DEFAULT NULL COMMENT 'số tiền thuế',
  `pay_amount` decimal(20,2) DEFAULT NULL COMMENT 'số tiền trả',
  `created_by` int(11) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` enum('done','cancel', 'modify', 'ordering', 'confirm') DEFAULT 'ordering',
  `delivery_date` timestamp NULL DEFAULT NULL,
  `receipt_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;