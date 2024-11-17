<?php

namespace App\Services;

use App\LibExtension\LogEx;
use App\Repositories\Promotion\PromotionRepositoryInterface;
use App\Repositories\PromotionLogs\PromotionLogsRepositoryInterface;
use Carbon\Carbon;


/**
 * Class PromotionLogsService
 * @package App\Services
 */
class PromotionLogsService
{
    protected $className = "PromotionLogsService";
    protected $promotionLogs;
    protected $promotion;

    public function __construct(PromotionLogsRepositoryInterface $promotionLogs, PromotionRepositoryInterface $promotion)
    {
        LogEx::constructName($this->className, '__construct');
        $this->promotionLogs = $promotionLogs;
        $this->promotion = $promotion;
    }

    public function createPromotionLogByInvoice($requestInput, $invoiceId, $drugStoreId){
        $listPromotion = $this->promotion->getPromotionCondition($drugStoreId, $requestInput["promotion_ids"]);
        $model = array(
            'drug_store_id' => $drugStoreId,
            'invoice_id' => $invoiceId,
            'promotion_json' => json_encode($listPromotion)
        );
        $this->promotionLogs->create($model);
    }

    public function createPromotionLogByOrder($requestInput, $orderId, $drugStoreId){
        $listPromotion = $this->promotion->getPromotionCondition($drugStoreId, $requestInput["promotion_ids"]);
        $model = array(
            'drug_store_id' => $drugStoreId,
            'order_id' => $orderId,
            'promotion_json' => json_encode($listPromotion)
        );
        $this->promotionLogs->create($model);
    }
}
