<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\LibExtension\LogEx;

class UpdateDrugStoreWarehouse extends Command
{
    protected $className = "UpdateDrugStoreWarehouse";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drugstore:update {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update warehouse data of specific drug store';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public const ACTIION_TYPE_ADD = array(
        // 'AC1' => "Bán hàng cho khách",
        'AC2' => "Nhập hàng từ NCC",
        'AC3' => "Khách trả lại thuốc",
        // 'AC4' => "Trả hàng cho NCC",
        // 'AC5' => "xuất cân bằng kho",
        // 'AC6' => "Chuyển kho",
        // 'AC8' => "Xuất Hủy",
        'AC9' => "Nhập Tồn"
    );

    public const ACTIION_TYPE_SUB = array(
        'AC1' => "Bán hàng cho khách",
        // 'AC2' => "Nhập hàng từ NCC",
        // 'AC3' => "Khách trả lại thuốc",
        'AC4' => "Trả hàng cho NCC",
        // 'AC5' => "xuất cân bằng kho",
        // 'AC6' => "Chuyển kho",
        'AC8' => "Xuất Hủy",
    );

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        LogEx::methodName($this->className, 'handle');
        if (empty($this->argument('id'))) {
            $this->line('Khong co drug_store_id');
            return 1;
        }

        $drug_store_id = $this->argument('id');
        $this->line('Scan running.....');

        $flag = false;

        try {
            \DB::beginTransaction();

            $warehouesLog = \App\Models\WarehouseLog::where('drug_store_id', $drug_store_id)
                                ->select(['drug_store_id', 'action_type', 'invoice_id'])
                                ->orderBy('created_at', 'asc')
                                ->get();

            $invoiceIds = $warehouesLog->pluck('invoice_id')->toArray();

            $invoiceDetailFields = [
                'invoice_detail.*',
                'invoice.warehouse_action_id'
            ];

            $invoiceDetails = \App\Models\InvoiceDetail::join('invoice', 'invoice.id', '=', 'invoice_detail.invoice_id')
                                ->where('invoice.drug_store_id', $drug_store_id)
                                ->whereIn('invoice.id', $invoiceIds)
                                ->select($invoiceDetailFields)
                                ->get();

            $arrayData = [];

            foreach ($invoiceDetails as $invoiceDetail) {
                $this->line('Start scanning invoice detail id: ' . $invoiceDetail->id);
                // 1 invoice detail là 1 thuốc tương ứng với 1 unit
                // => tìm bản ghi lưu thông tin cơ bản của thuốc đó (number null) để tìm đc exchange chính xác
                $warehouseBasicInfo = \App\Models\Warehouse::where('drug_store_id', $drug_store_id)
                                ->where('drug_id', $invoiceDetail->drug_id)
                                // ->where('number', $invoiceDetail->number)
                                ->whereNull('number')
                                ->where('unit_id', $invoiceDetail->unit_id)
                                ->first();

                if (empty($warehouseBasicInfo)) {
                    // echo 'Không tìm thấy bản ghi lưu thông tin cơ bản';
                    $this->line('Không tìm thấy bản ghi lưu thông tin cơ bản');
                    $flag = true;
                    break;
                }

                // số lượng update quy ra đơn vị cơ bản
                // bằng số lượng trong hóa đơn chi tiết * exchange của bản ghi lưu thông tin cơ bản
                $basicQuantityUpdate = $invoiceDetail->quantity * $warehouseBasicInfo->exchange;

                // Lưu thuốc của từng lô vào mảng để tính tổng số lượng update (không phân biệt unit_id)
                // $checkExist = array_filter($arrayData, function($element) {
                //     return ($element['drug_id'] == $invoiceDetail->drug_id) && ($element['number'] == $invoiceDetail->number);
                // });
                $checksum = -1;
                foreach ($arrayData as $index => $element) {
                    if (($element['drug_id'] == $invoiceDetail->drug_id) && ($element['number'] == $invoiceDetail->number)) {
                        $checksum = $index;
                        break;
                    }
                }

                if ($checksum > -1) { // nếu thuốc trong lô này đã ở trong mảng
                    if (in_array($invoiceDetail->warehouse_action_id, self::ACTIION_TYPE_ADD)) {
                        $arrayData[$checksum]['quantity'] += $basicQuantityUpdate;
                    }

                    if (in_array($invoiceDetail->warehouse_action_id, self::ACTIION_TYPE_SUB)) {
                        $arrayData[$checksum]['quantity'] -= $basicQuantityUpdate;
                    }
                } else {

                    if (in_array($invoiceDetail->warehouse_action_id, self::ACTIION_TYPE_ADD)) {
                        $arrayData[] = [
                            'drug_id' => $invoiceDetail->drug_id,
                            'number' => $invoiceDetail->number,
                            'quantity' => $basicQuantityUpdate
                        ];
                    }

                    if (in_array($invoiceDetail->warehouse_action_id, self::ACTIION_TYPE_SUB)) {
                        $arrayData[] = [
                            'drug_id' => $invoiceDetail->drug_id,
                            'number' => $invoiceDetail->number,
                            'quantity' => -$basicQuantityUpdate
                        ];
                    }
                }
            }

            foreach ($arrayData as $element) {
                $this->line('Start updating warehouse drug_id: ' . $element['drug_id'] . ' and number: ' . $element['number']);

                // Lấy và Lặp qua update lại số lượng tất cả các đơn vị của thuốc này trong kho
                $warehouseNeedUpdates = \App\Models\Warehouse::where('drug_store_id', $drug_store_id)
                                            ->where('drug_id', $element['drug_id'])
                                            ->where('number', $element['number'])
                                            // ->where('unit_id', $invoiceDetail->unit_id)
                                            ->get();

                foreach ($warehouseNeedUpdates as $warehouseNeedUpdate) {
                    // Lấy bản ghi lưu thông tin cơ bản của từng thuốc (để lấy exchange cho chính xác)
                    $warehouseNeedUpdateBasicInfo = \App\Models\Warehouse::where('drug_store_id', $drug_store_id)
                                                    ->where('drug_id', $warehouseNeedUpdate->drug_id)
                                                    // ->where('number', $invoiceDetail->number)
                                                    ->whereNull('number')
                                                    ->where('unit_id', $warehouseNeedUpdate->unit_id)
                                                    ->first();

                    if (empty($warehouseNeedUpdateBasicInfo)) {
                        // echo 'Không tìm thấy bản ghi lưu thông tin cơ bản needUpdate';
                        $this->line('Không tìm thấy bản ghi lưu thông tin cơ bản needUpdate');
                        $flag = true;
                        break;
                    }


                    $quantityUpdate = $element['quantity'] / $warehouseNeedUpdateBasicInfo->exchange;

                    \App\Models\Warehouse::where('id', $warehouseNeedUpdate->id)
                                    ->update([
                                        'quantity' => $quantityUpdate
                                    ]);

                    // Ghi log
                    $log = new \App\Models\FixWarehouseLog;
                    $oldVal = $warehouseNeedUpdate->quantity;
                    $newVal = $quantityUpdate;
                    $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal}";
                    $log->pushQuantityLog($warehouseNeedUpdate->id, $drug_store_id, $oldVal, $newVal, $desc);
                }
            }

            if ($flag) {
                \DB::rollBack();

                return 1;
            }

            \DB::commit();
            echo 'Success';
        } catch (Exception $e) {
            \DB::rollBack();
            LogEx::try_catch($this->className, $e);
            // echo $e->getMessage();
            $this->line($e->getMessage());

            return 1;
        }
    }
}
