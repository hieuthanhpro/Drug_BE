CREATE VIEW `invoice_return` AS SELECT
`invoice`.`refer_id` AS `refer_id`,
`invoice_detail`.`drug_id` AS `drug_id`,
`invoice_detail`.`unit_id` AS `unit_id`,
`invoice_detail`.`number` AS `number`,
sum( `invoice_detail`.`quantity` ) AS `return_quantity` 
FROM
	( `invoice` LEFT JOIN `invoice_detail` ON ( `invoice_detail`.`invoice_id` = `invoice`.`id` ) ) 
WHERE
	`invoice`.`refer_id` IS NOT NULL 
GROUP BY
	`invoice_detail`.`drug_id`,
	`invoice_detail`.`unit_id`,
	`invoice_detail`.`number`