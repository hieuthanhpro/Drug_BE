<?php
namespace App\Repositories\PaymentLogs;

use App\Repositories\RepositoryInterface;

interface PaymentLogsRepositoryInterface extends RepositoryInterface
{
    public function filter($type, $requestInput, $userInfo);
}
