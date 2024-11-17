<?php
/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 5/3/2019
 * Time: 1:37 PM
 */

namespace App\Services;

use App\Models\Drug;
use Config;
use App\Services\CommonConstant;
use App\Models\InvoiceDetail;
use App\Models\Invoice;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;

/**
 * Class Base chứa hàm xử lý common cho API trạm và sensor
 * VD : login, header, footer của một file XML
 *
 * @package App\Services
 */
class BuyDoseService
{
    protected $className = "BuyDoseService";

    protected $drug;
    protected $warehouse;

    public function __construct(
        DrugRepositoryInterface $drug,
        WarehouseRepositoryInterface $warehouse
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drug = $drug;
        $this->warehouse = $warehouse;
    }

    public function getNumberBuy($drug_id, $unit_id, $quatity, $usage)
    {
        LogEx::methodName($this->className, 'getNumberBuy');

        $data_result = array();
        $data = DB::table('warehouse')
            ->select(
                'warehouse.exchange',
                'warehouse.quantity',
                'warehouse.number',
                'warehouse.expiry_date',
                'warehouse.current_cost',
                'warehouse.main_cost'
            )
            ->where('warehouse.drug_id', $drug_id)
            ->where('warehouse.unit_id', $unit_id)
            ->where('warehouse.is_check', 0)
            ->where('warehouse.quantity', '>=', 1)
            ->get();
        foreach ($data as $value) {
            $diff = $quatity - $value->quantity;
            if ($diff <= 0) {
                $tpm = array(
                    'number' => $value->number,
                    'quantity' => $quatity,
                    'drug_id' => $drug_id,
                    'unit_id' => $unit_id,
                    'vat' => 0,
                    'cost' => $value->current_cost,
                    'expiry_date' => $value->expiry_date,
                    'usage' => $usage
                );
                $data_result[] = $tpm;
                break;
            } else {
                $tpm = array(
                    'number' => $value->number,
                    'quantity' => $value->quantity,
                    'drug_id' => $drug_id,
                    'unit_id' => $unit_id,
                    'vat' => 0,
                    'cost' => $value->current_cost,
                    'expiry_date' => $value->expiry_date,
                    'usage' => $usage
                );
                $data_result[] = $tpm;
                $quatity = $diff;
            }
        }
        return $data_result;
    }

}
