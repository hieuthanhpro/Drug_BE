<?php
namespace App\Repositories\AdminNotification;

use App\Repositories\RepositoryInterface;

interface AdminNotificationRepositoryInterface extends RepositoryInterface
{
    public function getByTopStatus($limit, $status);
}
