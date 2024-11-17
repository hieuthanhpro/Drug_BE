<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;

/**
 * Class InvoiceWarehouse
 *
 *
 * @package App\Models
 */
class ReportRevenue extends Eloquent
{
    protected $className = "Report";
    protected $table = 'invoice_reportRevenue';
    protected $fillable = [
        'drug_store_id',
        'code',
        'type',
        'reason',
        'created_by',
        'status',
        'invoice_type',
        'invoice_id',
        'date',
        'supplier_id',
        'customer_id',
        'ref_code'
    ];

    public $enum_mapping = [
        'type' => ['import', 'export'],
        'status' => ['done', 'pending', 'delivery', 'cancel'],
        'invoice_type' => ['IV1', 'IV2', 'IV3', 'IV4', 'IV5', 'IV7']
    ];
}
