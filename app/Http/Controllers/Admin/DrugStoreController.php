<?php

namespace App\Http\Controllers\Admin;

use App\LibExtension\CommonConstant;
use App\LibExtension\Utils;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDrugStore;
use App\Repositories\DrugStore\DrugStoreEloquentRepository;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Repositories\Users\UserRepositoryInterface;
use App\Repositories\WarehouseLog\WarehouseLogRepositoryInterface;
use App\Services\SMSService;
use App\Models\Drug;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\Validator;
use Milon\Barcode\DNS1D;

class DrugStoreController extends Controller
{
    protected $className = "Admin\DrugStoreController";
    private $drugStore;
    protected $drug;
    protected $warehouse;
    protected $invoice;
    protected $voucher;
    protected $users;
    protected $warehouseLog;
    protected $invoiceDetail;
    private $smsService;
    private $notificationTemplate;

    public function __construct(
        DrugStoreEloquentRepository             $drugStore,
        DrugRepositoryInterface                 $drug,
        WarehouseRepositoryInterface            $warehouse,
        InvoiceRepositoryInterface              $invoice,
        VouchersRepositoryInterface             $voucher,
        UserRepositoryInterface                 $users,
        SMSService                              $smsService,
        NotificationTemplateRepositoryInterface $notificationTemplate,
        WarehouseLogRepositoryInterface         $warehouseLog
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->drugStore = $drugStore;
        $this->drug = $drug;
        $this->warehouse = $warehouse;
        $this->invoice = $invoice;
        $this->voucher = $voucher;
        $this->users = $users;
        $this->smsService = $smsService;
        $this->notificationTemplate = $notificationTemplate;
        $this->warehouseLog = $warehouseLog;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');
        $this->drugStore->orderBy('end_time', 'ASC');
        $data = $this->drugStore->findAll();
        return view('admin.drugstore.index', compact('data'));
    }

    public function create()
    {
        LogEx::methodName($this->className, 'create');

        return view('admin.drugstore.create');
    }

    public function store(Request $request)
    {
        LogEx::methodName($this->className, 'store');

        $data = $request->all();
        $data['status'] = 1;
        unset($data['_token']);

        if (!empty($data['username'])) {
            $username = $this->drugStore->findOneBy('username', $data['username']);
        }

        if (!empty($data['base_code'])) {
            $base_code = $this->drugStore->findOneBy('base_code', $data['base_code']);
        }
        if (!empty($data['name'])) {
            $name = $this->drugStore->findOneBy('name', $data['name']);
        }
        if (!empty($data['reg_number'])) {
            $regNumber = $this->drugStore->findOneBy('reg_number', $data['reg_number']);
        }

        if (!empty($username) || !empty($base_code) || !empty($name) || !empty($regNumber)) {
            return back()->with('errors', 'Trùng tên mã hoặc tài khoản kết nối');
        }
        $create = $this->drugStore->create($data);
        if ($create) {
            return redirect()->route('admin.drugstore.index')->with('success', 'Tạo nhà thuốc thành công');
        }
        return back()->with('errors', 'Tạo nhà thuốc thất bại');
    }

    public function delete($id)
    {
        LogEx::methodName($this->className, 'delete');

        $data = $this->drugStore->findOneById($id);
        $this->drug->deleteManyBy('drug_store_id', $id);
        $this->drugStore->deleteManyBy('drug_store_id', $id);
        $this->users->deleteManyBy('drug_store_id', $id);
        $this->invoice->deleteManyBy('drug_store_id', $id);
        $this->voucher->deleteManyBy('drug_store_id', $id);
        if (!empty($data)) {
            $delete = $this->drugStore->deleteOneById($id);
            if ($delete) {
                return back()->with('success', 'Xóa nhà thuốc thành công');
            } else {
                return back()->with('errors', 'Xóa nhà thuốc thất bại');
            }
        }
        return back()->with('errors', 'Không có thông tin nhà thuốc');
    }

    /**
     * Đang dùng cho trang api ==> Cần viết lại theo hướng phân trang
     * @param Request $request
     * @return false|string
     */
    public function listStore(Request $request)
    {
        LogEx::methodName($this->className, 'listStore');

        $data = $this->drugStore->findAll();
        $dataList['data'] = $data;
        return json_encode($dataList);
    }

    public function edit($id)
    {
        LogEx::methodName($this->className, 'edit');

        $data = $this->drugStore->findOneById($id);
        return view('admin.drugstore.edit', compact('data'));
    }

    public function update(UpdateDrugStore $request, $id)
    {
        LogEx::methodName($this->className, 'update');

        $input = $request->all();
        $validation = Validator::make($input, [
            'phone' => 'required|phone:VN',
        ], [
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'phone.phone' => 'Vui lòng kiểm tra lại số điện thoại',
        ]);

        if ($validation->fails()) {
            return back()->withInput()->withErrors($validation);
        }

        $isSendSms = false;
        if (!empty($input['is-send-sms'])) {
            unset($input['is-send-sms']);
            $isSendSms = true;
        }
        unset($input['_token']);
        $data = $this->drugStore->findOneById($id);
        if (empty($data)) {
            return back()->with('errors', 'Không có thông tin nhà thuốc');
        }

        DB::beginTransaction();
        try {
            $this->drugStore->updateOneById($id, $input);
            DB::commit();

            // Send SMS to user
            if ($isSendSms && !empty($input['username']) && !empty($input['password'])) {
                // Thong bao ket noi voi cuc duoc hoac update thong tin (user info, password ket noi cuc duoc)
                $template = $this->notificationTemplate->getByKey('connect_national_pharmacy');
                $message = str_replace(['{{username}}', '{{password}}'], [$input['username'], $input['password']], $template->content_sms);
                $msg = $this->smsService->sendSMS($message, $input['phone']);
            }

            if (empty($msg) || (gettype($msg) == "boolean" && $msg == true)) {
                return redirect()->route('admin.drugstore.index')->with('success', 'Cập nhật nhà thuốc thành công');
            } else {
                return redirect()->route('admin.drugstore.index')->with('success', 'Cập nhật nhà thuốc thành công')->with('errors', $msg);
            }
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return back()->with('errors', 'Cập nhật nhà thuốc thuốc thất bại');
        }
    }

    public function lock($id)
    {
        LogEx::methodName($this->className, 'lock');

        $data = $this->drugStore->findOneById($id);
        if (empty($data)) {
            return back()->with('errors', 'Không có thông tin nhà thuốc');

        }
        $input['status'] = 0;
        DB::beginTransaction();

        try {
            $this->drugStore->updateOneById($id, $input);
            DB::commit();
            return redirect()->route('admin.drugstore.index')->with('success', 'Khóa nhà thuốc thành công');
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return back()->with('errors', 'Khóa nhà thuốc thuốc thất bại');
        }
    }

    public function unlock($id)
    {
        LogEx::methodName($this->className, 'unlock');

        $data = $this->drugStore->findOneById($id);
        if (empty($data)) {
            return back()->with('errors', 'Không có thông tin nhà thuốc');
        }

        $input['status'] = 1;
        DB::beginTransaction();

        try {
            $this->drugStore->updateOneById($id, $input);
            DB::commit();
            return redirect()->route('admin.drugstore.index')->with('success', 'Mở khóa nhà thuốc thành công');
        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return back()->with('errors', 'Mở khóa nhà thuốc thuốc thất bại');
        }
    }

    public function moveDrug()
    {
        LogEx::methodName($this->className, 'moveDrug');
        // COPY_FROM_DRUG_STORE
        $defaultDrugStoreID = env('COPY_FROM_DRUG_STORE', 945);
        $drugStore = $this->drugStore->findAll();
        return view('admin.movedrug.index', compact('drugStore', 'defaultDrugStoreID'));
    }

    public function viewDelete()
    {
        LogEx::methodName($this->className, 'viewDelete');

        $drugStore = $this->drugStore->findAll();
        return view('admin.drugstore.delete', compact('drugStore'));
    }

    public function sentDrugByDrugstore(Request $request)
    {
        LogEx::methodName($this->className, 'sentDrugByDrugstore');

        $input = $request->input();
        $storeSend = $input['store_send']; // From DrugStore
        $storeGive = $input['store_give']; // To DrugStore
        if ($storeSend == $storeGive) {
            return back()->with('errors', 'Hai nhà thuốc phải khác nhau');
        } else {
            $countDrugGive = Drug::where('drug_store_id', '=', $storeGive)->count();
            $countDrugSend = Drug::where('drug_store_id', '=', $storeSend)->count();

            if ($countDrugSend == 0) {
                return back()->with('errors', 'Nhà thuốc chuyển chưa có thuốc');
            } else if ($countDrugGive > 0) {
                return back()->with('errors', 'Nhà thuốc nhận đã có sẵn thuốc ');
            } else {
                try {
                    DB::beginTransaction();
                    $retResult = DB::statement('SELECT copyDrug4NewDrugStore(?, ?)', [$countDrugSend, $countDrugGive]);
                    LogEx::info("copyDrug4NewDrugStore: " . $retResult);
                    DB::commit();
                    return back()->with('success', 'Chuyển thuốc thành công');
                } catch (\Exception $e) {
                    DB::rollback();
                    LogEx::try_catch($this->className, $e);
                    return back()->with('errors', 'xảy ra lỗi khi chuyển thuốc vui lòng thử lại');
                }
            }
        }
    }

    public function testBarcode()
    {
        LogEx::methodName($this->className, 'testBarcode');
        echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG("232323", "C128") . '" alt="barcode"   />';
    }

    public function updateStoreWarehouse($drugStoreId)
    {
        LogEx::methodName($this->className, 'updateStoreWarehouse');

        $flag = false;

        try {
            DB::beginTransaction();
            $warehouseLog = $this->warehouseLog->findManyBy('drug_store_id', $drugStoreId)
                ->select(['drug_store_id', 'action_type', 'invoice_id'])
                ->orderBy('created_at', 'asc')
                ->get();

            $invoiceIds = $warehouseLog->pluck('invoice_id')->toArray();

            $invoiceDetailFields = [
                'invoice_detail.*',
                'invoice.warehouse_action_id'
            ];

            $invoiceDetails = \App\Models\InvoiceDetail::join('invoice', 'invoice.id', '=', 'invoice_detail.invoice_id')
                ->where('invoice.drug_store_id', $drugStoreId)
                ->whereIn('invoice.id', $invoiceIds)
                ->select($invoiceDetailFields)
                ->get();

            $arrayData = [];

            foreach ($invoiceDetails as $invoiceDetail) {
                // 1 invoice detail là 1 thuốc tương ứng với 1 unit
                // => tìm bản ghi lưu thông tin cơ bản của thuốc đó (number null) để tìm đc exchange chính xác
                $warehouseBasicInfo = \App\Models\Warehouse::where('drug_store_id', $drugStoreId)
                    ->where('drug_id', $invoiceDetail->drug_id)
                    // ->where('number', $invoiceDetail->number)
                    ->whereNull('number')
                    ->where('unit_id', $invoiceDetail->unit_id)
                    ->first();

                if (empty($warehouseBasicInfo)) {
                    echo 'Không tìm thấy bản ghi lưu thông tin cơ bản';
                    $flag = true;
                    break;
                }

                // số lượng update quy ra đơn vị cơ bản
                // bằng số lượng trong hóa đơn chi tiết * exchange của bản ghi lưu thông tin cơ bản
                $basicQuantityUpdate = $invoiceDetail->quantity * $warehouseBasicInfo->exchange;

                $checksum = -1;
                foreach ($arrayData as $index => $element) {
                    if (($element['drug_id'] == $invoiceDetail->drug_id) && ($element['number'] == $invoiceDetail->number)) {
                        $checksum = $index;
                        break;
                    }
                }

                if ($checksum > -1) {
                    // nếu thuốc trong lô này đã ở trong mảng
                    if (in_array($invoiceDetail->warehouse_action_id, CommonConstant::ACTION_TYPE_ADD)) {
                        $arrayData[$checksum]['quantity'] += $basicQuantityUpdate;
                    }

                    if (in_array($invoiceDetail->warehouse_action_id, CommonConstant::ACTION_TYPE_SUB)) {
                        $arrayData[$checksum]['quantity'] -= $basicQuantityUpdate;
                    }
                } else {

                    if (in_array($invoiceDetail->warehouse_action_id, CommonConstant::ACTION_TYPE_ADD)) {
                        $arrayData[] = [
                            'drug_id' => $invoiceDetail->drug_id,
                            'number' => $invoiceDetail->number,
                            'quantity' => $basicQuantityUpdate
                        ];
                    }

                    if (in_array($invoiceDetail->warehouse_action_id, CommonConstant::ACTION_TYPE_SUB)) {
                        $arrayData[] = [
                            'drug_id' => $invoiceDetail->drug_id,
                            'number' => $invoiceDetail->number,
                            'quantity' => -$basicQuantityUpdate
                        ];
                    }
                }
            }

            foreach ($arrayData as $element) {
                // Lấy và Lặp qua update lại số lượng tất cả các đơn vị của thuốc này trong kho
                $warehouseNeedUpdates = $this->warehouse->findManyBy('drug_store_id', $drugStoreId)
                    ->where('drug_id', $element['drug_id'])
                    ->where('number', $element['number'])
                    ->get();

                foreach ($warehouseNeedUpdates as $warehouseNeedUpdate) {
                    // Lấy bản ghi lưu thông tin cơ bản của từng thuốc (để lấy exchange cho chính xác)
                    $warehouseNeedUpdateBasicInfo = $this->warehouse->findManyBy('drug_store_id', $drugStoreId)
                        ->where('drug_id', $warehouseNeedUpdate->drug_id)
                        ->whereNull('number')
                        ->where('unit_id', $warehouseNeedUpdate->unit_id)
                        ->first();

                    if (empty($warehouseNeedUpdateBasicInfo)) {
                        echo 'Không tìm thấy bản ghi lưu thông tin cơ bản needUpdate';
                        $flag = true;
                        break;
                    }

                    $quantityUpdate = $element['quantity'] / $warehouseNeedUpdateBasicInfo->exchange;

                    $this->warehouse->updateOneById($warehouseNeedUpdate->id, [
                        'quantity' => $quantityUpdate
                    ]);
                    // Ghi log
                    $log = new \App\Models\FixWarehouseLog;
                    $oldVal = $warehouseNeedUpdate->quantity;
                    $newVal = $quantityUpdate;
                    $desc = "Thay đổi quantity từ {$oldVal} sang {$newVal}";
                    $log->pushQuantityLog($warehouseNeedUpdate->id, $drugStoreId, $oldVal, $newVal, $desc);
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
            echo $e->getMessage();

            return 1;
        }

    }

    public function setting(Request $request)
    {
        LogEx::methodName($this->className, 'setting');
        $requestInput = $request->input();
        $data = Utils::executeRawQuery("select * from v3.f_drugstore_setting(?)", [Utils::getParams($requestInput)]);
        $resp = $this->responseApi(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        return response()->json($resp);
    }
}
