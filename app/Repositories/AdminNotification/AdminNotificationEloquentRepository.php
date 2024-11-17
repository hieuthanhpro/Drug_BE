<?php

namespace App\Repositories\AdminNotification;

use App\Models\AdminNotification;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;
use Illuminate\Support\Facades\DB;

class AdminNotificationEloquentRepository extends AbstractBaseRepository implements AdminNotificationRepositoryInterface
{
    protected $className = "AdminNotificationEloquentRepository";

    public function __construct(AdminNotification $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getByTopStatus($limit, $status)
    {
        LogEx::methodName($this->className, 'getByTopStatus');
        return DB::table("admin_notification")
            ->select('*')
            ->where('status', $status)->limit($limit)->get();
    }
}
