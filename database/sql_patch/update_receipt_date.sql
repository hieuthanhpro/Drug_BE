UPDATE invoice a
	INNER JOIN invoice b ON a.id = b.id
SET a.receipt_date = b.created_at
WHERE a.receipt_date IS NULL;