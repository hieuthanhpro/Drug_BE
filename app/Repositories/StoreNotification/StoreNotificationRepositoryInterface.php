<?php
namespace App\Repositories\StoreNotification;

use App\Repositories\RepositoryInterface;

interface StoreNotificationRepositoryInterface extends RepositoryInterface
{
    public function getTopByIsSent($limit, $is_sent);

    public function getTopNewestByUserId($page, $limit, $id);

    public function getCount($id);
}
