<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Drug;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\ControlQualityBook;
use App\Models\ControlQualityBookDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\LibExtension\LogEx;
use App\LibExtension\Utils;

class NewReportController extends Controller
{
    protected $className = "Backend\NewReportController";
	/**
	 * Sổ kiểm soát chất lượng định ký & đột xuất
	 */
    public function createControlRegularAndIrregularQualityBook(Request $request)
    {
        LogEx::methodName($this->className, 'createControlRegularAndIrregularQualityBook');

    	$user = $request->userInfo;
    	$result['success'] = false;

    	if (empty($request->input('data'))) {
    		$result['message'] = 'Dữ liệu không hợp lệ';
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
    	}

    	if (empty($request->input('charge_person'))) {
    		$result['message'] = 'Người phụ trách là bắt buộc';
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
    	}

    	if (empty($request->input('tracking_staff'))) {
    		$result['message'] = 'Nhân viên theo dõi là bắt buộc';
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
    	}

    	try {
    		$data = $request->input('data');

    		DB::beginTransaction();

    		$book = ControlQualityBook::create([
	    		'drug_store_id' => $user->drug_store_id,
	    		'charge_person' => $request->input('charge_person'),
	    		'tracking_staff' => $request->input('tracking_staff')
	    	]);

	    	if (!empty($book)) {
	    		$now = Carbon::now();
	    		$detailBooks = [];
	    		foreach ($data as $item) {
	    			$detailBooks[] = [
	    				'book_id' => $book->id,
				    	'date' => !empty($item['date']) ? $item['date'] : null,
				    	'drug_id' => !empty($item['drug_id']) ? $item['drug_id'] : null,
				    	'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
				    	'number' => !empty($item['number']) ? $item['number'] : null,
				    	'expire_date' => !empty($item['expire_date']) ? $item['expire_date'] : null,
				    	'quantity' => !empty($item['quantity']) ? $item['quantity'] : null,
				    	'sensory_quality' => !empty($item['sensory_quality']) ? $item['sensory_quality'] : null,
				    	'conclude' => !empty($item['conclude']) ? $item['conclude'] : null,
				    	'reason' => !empty($item['reason']) ? $item['reason'] : null,
	    				'created_at' => $now,
                        'updated_at' => $now
	    			];
	    		}

	    		ControlQualityBookDetail::insert($detailBooks);
	    	}

    		DB::commit();
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $book);
    	} catch (\Exception $e) {
    		DB::rollBack();
            LogEx::try_catch($this->className, $e);
    	}
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /*
     * Get list book
     */
    public function getListControlQualityBook(Request $request)
    {
        LogEx::methodName($this->className, 'getListControlQualityBook');

        $user = $request->userInfo;
        $result['success'] = false;

        $bookDetailFields = [
            'control_quality_book_details.book_id', 'control_quality_book_details.date', 'control_quality_book_details.drug_id', 'control_quality_book_details.unit_id',
            'control_quality_book_details.number', 'control_quality_book_details.expire_date', 'control_quality_book_details.quantity',
            'control_quality_book_details.sensory_quality', 'control_quality_book_details.conclude', 'control_quality_book_details.reason',
            'drug.name as drug_name',
            'unit.name as unit_name'
        ];

        $books = ControlQualityBook::with(['controlQualityBookDetails' => function ($query) use ($bookDetailFields) {
                        $query->leftJoin('drug', 'drug.id', '=', 'control_quality_book_details.drug_id')
                        ->leftJoin('unit', 'unit.id', '=', 'control_quality_book_details.unit_id')
                        // ->where('control_quality_book_details.book_id', $book->id)
                        ->select($bookDetailFields);
                    }])
                    // ->where('id', $id)
                    ->where('drug_store_id', $user->drug_store_id)
                    ->select('id', 'drug_store_id', 'charge_person', 'tracking_staff', 'created_at');

        if (!empty($request->input('from_date'))) {
            $books->where('created_at', '>=', $request->input('from_date'));
        }

        if (!empty($request->input('to_date'))) {
            $books->where('created_at', '<=', $request->input('to_date'));
        }

        $books = $books->orderBy('created_at', 'desc')->get();
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $books);
    }

    /*
     * Get detail book
     */
    public function getDetailControlQualityBook(Request $request, $id)
    {
        LogEx::methodName($this->className, 'getDetailControlQualityBook');

        $user = $request->userInfo;
        $result['success'] = false;

        $book = ControlQualityBook::where('id', $id)
                    ->where('drug_store_id', $user->drug_store_id)
                    ->select('id', 'drug_store_id', 'charge_person', 'tracking_staff', 'created_at')
                    ->first();

        if (empty($book)) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }

        $bookDetailFields = [
            'control_quality_book_details.book_id', 'control_quality_book_details.date', 'control_quality_book_details.drug_id', 'control_quality_book_details.unit_id',
            'control_quality_book_details.number', 'control_quality_book_details.expire_date', 'control_quality_book_details.quantity',
            'control_quality_book_details.sensory_quality', 'control_quality_book_details.conclude', 'control_quality_book_details.reason',
            'drug.name as drug_name',
            'unit.name as unit_name'
        ];

        $bookDetail = ControlQualityBookDetail::leftJoin('drug', 'drug.id', '=', 'control_quality_book_details.drug_id')
                        ->leftJoin('unit', 'unit.id', '=', 'control_quality_book_details.unit_id')
                        ->where('control_quality_book_details.book_id', $book->id)
                        ->select($bookDetailFields)
                        ->get();

        $book->details = $bookDetail;
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $book);
    }

    /*
     * Update book
     */
    public function updateControlQualityBook(Request $request, $id)
    {
        LogEx::methodName($this->className, 'updateControlQualityBook');

        $user = $request->userInfo;
        $result['success'] = false;

        $book = ControlQualityBook::where('id', $id)
                    ->where('drug_store_id', $user->drug_store_id)
                    ->first();

        if (empty($book)) {
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $request->input('data');

            DB::beginTransaction();

            $isDeleteBookDetail = ControlQualityBookDetail::where('book_id', $book->id)->delete();

            if ($isDeleteBookDetail) {
                $book->charge_person = $request->input('charge_person');
                $book->tracking_staff = $request->input('tracking_staff');
                $book->save();

                $now = Carbon::now();
                $detailBooks = [];
                foreach ($data as $item) {
                    $detailBooks[] = [
                        'book_id' => $book->id,
                        'date' => !empty($item['date']) ? $item['date'] : null,
                        'drug_id' => !empty($item['drug_id']) ? $item['drug_id'] : null,
                        'unit_id' => !empty($item['unit_id']) ? $item['unit_id'] : null,
                        'number' => !empty($item['number']) ? $item['number'] : null,
                        'expire_date' => !empty($item['expire_date']) ? $item['expire_date'] : null,
                        'quantity' => !empty($item['quantity']) ? $item['quantity'] : null,
                        'sensory_quality' => !empty($item['sensory_quality']) ? $item['sensory_quality'] : null,
                        'conclude' => !empty($item['conclude']) ? $item['conclude'] : null,
                        'reason' => !empty($item['reason']) ? $item['reason'] : null,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                ControlQualityBookDetail::insert($detailBooks);

                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /*
     * Delete book
     */
    public function deleteControlQualityBook(Request $request, $id)
    {
        LogEx::methodName($this->className, 'deleteControlQualityBook');

        $user = $request->userInfo;
        $result['success'] = false;

        $book = ControlQualityBook::where('id', $id)
                    ->where('drug_store_id', $user->drug_store_id)
                    ->first();

        if (empty($book)) {
            return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
        }

        try {
            DB::beginTransaction();

            $isDeleteBookDetail = ControlQualityBookDetail::where('book_id', $book->id)->delete();

            $isDeleteBook = $book->delete();

            if ($isDeleteBookDetail && $isDeleteBook) {
                DB::commit();
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS);
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            LogEx::try_catch($this->className, $e);
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    /*
     * Phụ lục XVIII thuốc kiểm soát ĐB
     */
    public function getControlDrugAnnexXvii(Request $request)
    {
        LogEx::methodName($this->className, 'getControlDrugAnnexXvii');

        $result['success'] = false;

        if (empty($request->input('drug_id'))) {
            $result['message'] = 'Dữ liệu không hợp lệ';
            return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
        }

        $user = $request->userInfo;
        $drugId = $request->input('drug_id');

        $report = [];

        $drug = Drug::where('id', $drugId)
                    ->where('drug_store_id', $user->drug_store_id)
                    ->where('active', 'yes')
                    ->first();

        if (!empty($drug)) {
            $report['drug'] = $drug;

            $fields = [
                'invoice_detail.number', 'invoice_detail.expiry_date', 'invoice_detail.quantity',
                'invoice_detail.cost',
                'invoice.warehouse_action_id', 'invoice.created_at'
            ];

            $invoiceDetails = InvoiceDetail::join('invoice', 'invoice.id', '=', 'invoice_detail.invoice_id')
                                ->where('invoice_detail.drug_id', $drug->id)
                                ->where('invoice.drug_store_id', $user->drug_store_id);

            if (!empty($request->input('from_date'))) {
                $invoiceDetails->where('created_at', '>=', $request->input('from_date'));
            }

            if (!empty($request->input('to_date'))) {
                $invoiceDetails->where('created_at', '<=', $request->input('to_date'));
            }


            $invoiceDetails = $invoiceDetails->select($fields)
                                ->get();
            $report['invoices'] = $invoiceDetails;
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $report);
    }

    /*
     * Sổ theo dõi bán thuốc theo đơn
     */
    public function getSellingPrescriptionDrugs(Request $request)
    {
        LogEx::methodName($this->className, 'getSellingPrescriptionDrugs');

        $result['success'] = false;

        $user = $request->userInfo;
        $fields = [
            'invoice.id', 'invoice.drug_store_id', 'invoice.invoice_code', 'invoice.amount', 'invoice.description',
            'invoice.created_at',
            'prescription.name_patient', 'prescription.doctor'
        ];

        $invoiceDetailFields = [
            'invoice_detail.id', 'invoice_detail.invoice_id', 'invoice_detail.drug_id', 'invoice_detail.quantity',
            'drug.name', 'drug.drug_code', 'drug.barcode'
        ];

        $invoices = Invoice::with(['invoiceDetails' => function ($query) use ($invoiceDetailFields) {
                            $query->join('drug', 'drug.id', '=', 'invoice_detail.drug_id')
                                ->select($invoiceDetailFields);
                            // $query->select($invoiceDetailFields);
                        }])
                        ->join('prescription', 'prescription.invoice_id', '=', 'invoice.id')
                        ->select($fields)
                        ->where('invoice.drug_store_id', $user->drug_store_id)
                        ->where('invoice.status', 'done');

        if (!empty($request->input('from_date'))) {
            $invoices->where('invoice.created_at', '>=', $request->input('from_date'));
        }

        if (!empty($request->input('to_date'))) {
            $invoices->where('invoice.created_at', '<=', $request->input('to_date'));
        }

        $invoices = $invoices->orderBy('invoice.created_at', 'asc')
                        ->get();

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $invoices);
    }

    public function getQualityControlBookList(Request $request)
    {
        LogEx::methodName($this->className, 'getQualityControlBookList');

        $user = $request->userInfo;
        $requestInput = $request->input();
        $searchStr = $requestInput['search'] ?? null;
        $fromDate = $requestInput['from_date'] ?? null;
        $toDate = $requestInput['to_date'] ?? null;
        $data = Utils::executeRawQuery('select * from f_report_quality_control(?, ?, ?, ?)', [$user->drug_store_id, $searchStr, $fromDate, $toDate], $request->url(), $request->input());

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function qualityControlList(Request $request)
    {
        LogEx::methodName($this->className, 'qualityControlList');

        $requestInput = $request->input();
        $data = Utils::executeRawQuery('select * from v3.f_report_quality_control_list(?)', [Utils::getParams($requestInput)], $request->url(), $requestInput);

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from qualityControlList
    */
    public function qualityControlListV3(Request $request)
    {
        LogEx::methodName($this->className, 'qualityControlList');

        $query = $this->reportQualityControlListV3($request);
        $data = Utils::executeRawQueryV3(
            $query,
            $request->url(),
            $request->input()
        );

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    /**
     * api v3
     * from f_report_quality_control_list on v3 and export
     */
    public function exporQualityControlListV3(Request $request)
    {
        LogEx::methodName($this->className, 'exporQualityControlListV3');

        $requestInput = $request->input();
        $typeExport = $requestInput["type_export"] ?? null;
        $data = null;

        if (isset($typeExport)) {
            switch ($typeExport) {
                case "all":
                    $data = $this->reportQualityControlListV3($request, 1, 35000);
                    break;
                case "current_page":
                    $data = $this->reportQualityControlListV3($request, 1);
                    break;
                case "current_search":
                    $request->request->remove("page");
                    $data = $this->reportQualityControlListV3($request, 1, 35000);
                    break;
            }
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }

    public function reportQualityControlListV3($request, $export = 0, $limit = 10)
    {
        LogEx::methodName($this->className, 'reportQualityControlListV3');

        $p_drug_store_id    =   $request->userInfo->drug_store_id;
        $requestInput       =   $request->input();
        $p_search_str       =   Utils::coalesce($requestInput, 'search', '');
        $p_start_date       =   Utils::coalesce($requestInput, 'from_date', '');
        $p_end_date         =   Utils::coalesce($requestInput, 'to_date', '');

        $resoult = DB::table(DB::raw('control_quality_books c'))
            ->select('c.*')
            ->where('c.drug_store_id', '=', $p_drug_store_id)
            ->when(!empty($p_start_date), function ($query) use ($p_start_date) {
                $query->whereDate('c.created_at', '>=', $p_start_date);
            })
            ->when(!empty($p_end_date), function ($query) use ($p_end_date) {
                $query->whereDate('c.created_at', '<=', $p_end_date);
            })
            ->when(!empty($p_search_str), function ($query) use ($p_search_str) {
                $query->whereExists(function ($query) use ($p_search_str) {
                    $query->select(DB::raw(1))
                        ->from(DB::raw('control_quality_book_details b'))
                        ->join(DB::raw('drug d'),function($join) {
                            $join->on('d.id','=','b.drug_id')
                                ->where('d.active','=','yes');
                        })
                        ->whereRaw('b.book_id = c.id')
                        ->where(
                            (DB::raw('lower(vn_unaccent(c.charge_person))')),
                            'ILIKE',
                            '%' . strtolower(Utils::unaccent($p_search_str)) . '%')
                        ->orWhere(
                            (DB::raw('lower(vn_unaccent(c.tracking_staff))')),
                            'ILIKE',
                            '%' . strtolower(Utils::unaccent($p_search_str)) . '%')
                        ->orWhere(
                            (DB::raw('lower(vn_unaccent(d.name))')),
                            'ILIKE',
                            '%' . strtolower(Utils::unaccent($p_search_str)) . '%')
                        ->orWhere(
                            (DB::raw('lower(vn_unaccent(d.short_name))')),
                            'ILIKE',
                            '%' . strtolower(Utils::unaccent($p_search_str)) . '%')
                        ->orWhere(
                            (DB::raw('lower(vn_unaccent(d.drug_code))')),
                            'ILIKE',
                            '%' . strtolower(Utils::unaccent($p_search_str)) . '%');
                })
                ->get();
            })
            ->orderBy('c.id','DESC');

        if ($export) return $resoult->paginate($limit);

        return $resoult;
    }

    public function qualityControlSave(Request $request)
    {
        LogEx::methodName($this->className, 'qualityControlSave');
        try {
            $data = Utils::executeRawQuery("select v3.f_report_quality_control_save(?) as result", [Utils::getParams($request->input())]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    public function qualityControlDetail(Request $request)
    {
        LogEx::methodName($this->className, 'qualityControlDetail');
        try {
            $data = Utils::executeRawQuery("select v3.f_report_quality_control_detail(?) as result", [Utils::getParams($request->input())]);
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());
            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data[0]->result);
    }

    /**
     * api v3
     * from qualityControlDetailV3
    */
    public function qualityControlDetailV3(Request $request)
    {
        LogEx::methodName($this->className, 'qualityControlDetailV3');

        try {
            $query = $this->reportQualityControlDetailV3($request);

            if (!empty($query['mess'])) {
                return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, $query);
            }

            $data = $query;
        } catch (\Exception $e) {
            LogEx::info($e->getMessage());

            return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
        }

        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data['data']);
    }

    /**
     * api v3
     * from f_report_quality_control_detail on v3
     */
    public function reportQualityControlDetailV3(Request $request)
    {
        LogEx::methodName($this->className, 'reportQualityControlDetailV3');

        $p_id = $request->input('id');
        $datas = [];
        $v_quality_control_book = DB::table(DB::raw('control_quality_books c'))
            ->select(
                DB::raw('c.*')
            )
            ->where('c.id', '=', $p_id)
            ->first();

        if (!$v_quality_control_book) {
            $datas['mess'] = 'Báo cáo không tồn tại';

            return $datas;
        }

        $subQuerys = DB::table(DB::raw('control_quality_book_details bd'))
            ->select(
                DB::raw('to_jsonb(bd.*) ||
                    jsonb_build_object(
                        \'drug_name\', d.name,
                        \'unit_name\', u.name
                    )   as detail')
            )
            ->leftJoin(DB::raw('drug d'),'d.id','=','bd.drug_id')
            ->leftJoin(DB::raw('unit u'),'u.id','=','bd.unit_id')
            ->where('bd.book_id', '=', $p_id)
            ->get();

        $details = [];

        foreach ($subQuerys as $subQuery) {
            $details[] = json_decode($subQuery->detail);
        }

        $v_quality_control_book->detail = $details;
        $datas['data'] = $v_quality_control_book;

        return $datas;
    }
}
