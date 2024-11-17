<?php

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\LibExtension\LogEx;

/**
 * Class Drug
 * @package App\Models
 */
class Drug extends Eloquent
{
    protected $className = "Drug";

    protected $table = 'drug';
    protected $fillable = [
        'drug_store_id',
        'drug_category_id',
        'drug_group_id',
        'drug_code',
        'barcode',
        'name',
        'unit_id',
        'is_master_data',
        'short_name',
        'concentration',
        'substances',
        'country',
        'company',
        'package_form',
        'registry_number',
        'description',
        'expiry_date',
        'usage',
        'image',
        'active',
        'vat',
        'updated_at',
        'created_at',
        'is_monopoly',
        'source'
    ];

    public $enum_mapping = [
        'active' => ['yes', 'no']
    ];
}
