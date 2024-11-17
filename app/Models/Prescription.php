<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:16 AM
 */

namespace App\Models;

use App\Models\BaseModel as Eloquent;
use App\Models\Warehouse;

/**
 * Class User
 *
 * @property int $id
 * @property string invoice_id
 * @property string patient_code
 * @property string name_patient
 * @property string year_old
 * @property string id_card
 * @property int $company_id
 * @property bool doctor
 * @property string clinic
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class Prescription extends Eloquent
{
    protected $table = 'prescription';
    protected $fillable = [
        'invoice_id',
        'patient_code',
        'name_patient',
        'year_old',
        'month_old',
        'weight',
        'id_card',
        'bhyt',
        'code_invoice',
        'doctor',
        'clinic',
        'caregiver',
        'address',
        'patient_address',
        'height'
    ];

}
