<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\LibExtension\CommonConstant;
use App\Repositories\PriceRule\PriceRuleRepositoryInterface;
use App\Repositories\Promotion\PromotionRepositoryInterface;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use App\LibExtension\LogEx;

class PromotionController extends Controller
{
    protected $className = "Backend\PromotionController";
    protected $promotion;
    protected $promotionService;
    protected $priceRule;

    public function __construct(PromotionRepositoryInterface $promotion, PromotionService $promotionService, PriceRuleRepositoryInterface $priceRule)
    {
        LogEx::constructName($this->className, '__construct');
        $this->promotion = $promotion;
        $this->priceRule = $priceRule;
        $this->promotionService = $promotionService;
    }

    public function filter(Request $request)
    {
        LogEx::methodName($this->className, 'filter');
        try {
            $requestInput = $request->input();
            $userInfo = $request->user;
            $data = $this->promotion->filter($requestInput, $userInfo);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function detail($id, Request $request)
    {
        LogEx::methodName($this->className, 'detail');
        try {
            $data = $this->promotion->detail($id, $request->user);
            if (isset($data)) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
            } else {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function save(Request $request)
    {
        LogEx::methodName($this->className, 'save');
        try {
            $requestInput = $request->input();
            $user = $request->userInfo;
            $validation = $this->promotionService->validation($requestInput);
            if ($validation->fails()) {
                return \App\Helper::errorResponse(CommonConstant::UNPROCESSABLE_ENTITY, CommonConstant::MSG_ERROR_UNPROCESSABLE_ENTITY);
            }
            if (isset($requestInput["id"])) {
                $promotion = $this->promotion->detail($requestInput["id"], $user);
                if (isset($promotion)) {
                    $promotion = $this->promotionService->createOrUpdatePromotion($requestInput, $user);
                } else {
                    return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
                }
            } else {
                $promotion = $this->promotionService->createOrUpdatePromotion($requestInput, $user);
            }
            if (isset($promotion)) {
                return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $promotion);
            }
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function delete($id)
    {
        LogEx::methodName($this->className, 'delete');
        try {
            $data = $this->promotion->findOneById($id);
            if (!isset($data)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
            $this->promotion->deleteOneById($id);
            $this->priceRule->deleteManyBy("promotion_id", $id);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function changeStatus($id, Request $request)
    {
        LogEx::methodName($this->className, 'changeStatus');
        $requestInput = $request->input();
        try {
            $data = $this->promotion->findOneById($id);
            if (!isset($data)) {
                return \App\Helper::errorResponse(CommonConstant::BAD_REQUEST, CommonConstant::MSG_NOTFOUND);
            }
            $this->promotion->updateOneById($id, ['status' => $requestInput['status']]);
            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, 1);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }
        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }

    public function getPromotionAvaiable(Request $request)
    {
        LogEx::methodName($this->className, 'getPromotionAvaiable');
        try {
            $requestInput = $request->input();
            $user = $request->userInfo;
            $data = $this->promotionService->getPromotion($requestInput, $user->drug_store_id);

            return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
        } catch (\Exception $e) {
            LogEx::try_catch($this->className, $e->getMessage());
        }

        return \App\Helper::errorResponse(CommonConstant::INTERNAL_SERVER_ERROR, CommonConstant::MSG_ERROR);
    }
}
