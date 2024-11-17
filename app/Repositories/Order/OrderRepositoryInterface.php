<?php
namespace App\Repositories\Order;

use App\Repositories\RepositoryInterface;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function getOrderByCodition($drug_store_id,$form_date = null,$to_date = null,$order_code = null, $addition = []);
    public function getDetailById($id);
    public function getDetailByIdFromAdmin($id);
    public function cancelOrder($id);
    public function getOrdersForAdmin();
    public function getOrdersReturned();
}