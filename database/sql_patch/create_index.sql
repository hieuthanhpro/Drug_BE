-- Created by dthquan on 27/03/2020

-- Create index for invoice table to improve query performance
CREATE INDEX invoice_drug_store_id_IDX USING BTREE ON invoice (drug_store_id,receipt_date);
