<?php
namespace App\Repositories\Promotion;

use App\Repositories\RepositoryInterface;

interface PromotionRepositoryInterface extends RepositoryInterface
{
    public function filter($requestInput, $userInfo);
    public function detail($id, $userInfo);
    public function getPromotionCondition($storeId, $promotionIds);
}
