<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\OrderDetailAdmin\OrderDetailAdminRepositoryInterface;
use App\Http\Requests\ReturnOrderRequest;
use App\Models\Drug;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\SMSService;
use libphonenumber\PhoneNumberUtil;
use App\LibExtension\LogEx;

class OrderController extends Controller
{
    protected $className = "Admin\OrderController";
    private $order;
    protected $orderDetailAdmin;
    private $smsService;
    private $drugStore;

    public function __construct(
        OrderRepositoryInterface $order,
        OrderDetailAdminRepositoryInterface $orderDetailAdmin,
        SMSService $smsService,
        DrugStoreRepositoryInterface $drugStore
    )
    {
        LogEx::constructName($this->className, '__construct');

        $this->order = $order;
        $this->orderDetailAdmin = $orderDetailAdmin;
        $this->smsService = $smsService;
        $this->drugStore = $drugStore;
    }

    public function index()
    {
        LogEx::methodName($this->className, 'index');

        $data = $this->order->getOrdersForAdmin();
        return view('admin.order.index', compact('data'));
    }

    public function getOrderForReturn($id)
    {
        LogEx::methodName($this->className, 'getOrderForReturn');

        $data = $this->order->getDetailById($id);
        if (empty($data)) {
            return $this->index();
        }
        $data_detail = $data['order_detail'];
        $drugs_id = array();
        foreach($data_detail as $value) {
            $drugs_id[] = $value->drug_id;
        }

        $units_tmp = Drug::with(['units' => function ($q) {
            $q->join('unit', 'unit.id', 'warehouse.unit_id');
            $q->where('is_check', true); //1
        }])->select('drug.id')
            ->whereIn('drug.id', $drugs_id)
            ->get()->toArray();

        $units = array();
        foreach($drugs_id as $drug_id) {
            $units[] =$units_tmp[array_search($drug_id, array_column($units_tmp, 'id'))];
        }

        return view('admin.order.order_return', compact('data', 'units'));
    }

    public function returnOrder(ReturnOrderRequest $request)
    {
        LogEx::methodName($this->className, 'returnOrder');

        $data_return_order = $request->input();
        $time = Carbon::createFromFormat("Y-m-d H:i", $data_return_order['time']);
        $order_code = $data_return_order['order_code'];
        $drug_store_id = $data_return_order['drug_store_id'];

        unset($data_return_order['_token']);
        $id = (int)$data_return_order['id'];

        $order_current = $this->order->findOneById($id);

        if ($order_current->status == 'confirm') {
            return redirect()->route('admin.order.index')->with('errors', 'Đơn hàng đã được trả trước đó');
        }

        $data_order = $this->order->getDetailById($id);
        $data_order_detail = $data_order["order_detail"];

        $order_insert = array(
            'status' => 'confirm',
            'amount' => $data_return_order['amount'],
            'vat_amount' => $data_return_order['vat_amount'] ?? null,
            'pay_amount' => $data_return_order['pay_amount'],
            'return_date' => Carbon::now(),
        );

        $list_number = $data_return_order['number'];
        DB::beginTransaction();
        try {
            $this->order->updateOneById($id, $order_insert);
            $this->orderDetailAdmin->deleteAllByCredentials(['order_id' => $id]);
            foreach ($list_number as $key => $value) {
                $old_data = $data_order_detail[$key];
                $item_order = array(
                    'order_id' => $id,
                    'drug_id' => $old_data->drug_id,
                    'package_form' => $old_data->package_form,
                    'concentration' => $old_data->concentration,
                    'manufacturer' => $old_data->manufacturer,
                    'number' => $value,
                    'unit_id' => $data_return_order['unit'][$key],
                    'quantity' => $data_return_order['quantity'][$key],
                    'expiry_date' => $data_return_order['expiry_date'][$key],
                    'cost' => $data_return_order['cost'][$key],
                    'vat' => isset($data_return_order['vat'][$key]) ? $data_return_order['vat'][$key] : 0,
                );

                $this->orderDetailAdmin->create($item_order);
            }
            DB::commit();
            $error = $this->sendNoti($drugStoreId, $order_code, $dataReturnOrder['time'], $data_return_order['pay_amount']);
            if ($error) {
                return redirect()->route('admin.order.orders_returned')->with('success', 'Trả hàng thành công')->with('errors', $error);
            }

            return redirect()->route('admin.order.orders_returned')->with('success', 'Trả hàng thành công');

        } catch (\Exception $e) {
            DB::rollback();
            LogEx::try_catch($this->className, $e);
            return back()->with('errors', 'Trả hàng thất bại');
        }
    }

    public function getOrdersReturned()
    {
        LogEx::methodName($this->className, 'getOrdersReturned');

        $data = $this->order->getOrdersReturned();
        return view('admin.order.orders_returned', compact('data'));
    }

    public function getOrderDetail($id)
    {
        LogEx::methodName($this->className, 'getOrderDetail');

        $data = $this->order->getDetailByIdFromAdmin($id);
        return view('admin.order.order_detail', compact('data'));
    }

    public function getOrderDetailForReturn($id)
    {
        LogEx::methodName($this->className, 'getOrderDetailForReturn');

        $data = $this->order->getDetailById($id);
        return view('admin.order.order_detail_for_return', compact('data'));
    }

    public function sendNoti($drug_store_id, $order_code, $time, $pay_amount)
    {
        LogEx::methodName($this->className, 'sendNoti');

        $error = null;
        $cur_drug_store = $this->drugStore->findOneById($drug_store_id);
        $number = $cur_drug_store->phone;
        $lib = PhoneNumberUtil::getInstance();
        $number_formated = $lib->parse($number, 'VN');
        if ($lib->isValidNumber($number_formated)) {
            $msg = $this->smsService->notiReturnOrderSuccess($cur_drug_store->name, $pay_amount, $time, $order_code, $number);
            if (!(gettype($msg) == "boolean" && $msg == true)) {
                $error = $msg;
            }
        } else {
            $error = "Số điện thoại của nhà thuốc ". $cur_drug_store->name ." bị sai";
        }

        return $error;
    }
}
