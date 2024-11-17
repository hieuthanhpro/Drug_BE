<?php

namespace App\Repositories\StoreNotification;

use App\Models\StoreNotification;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;
use Illuminate\Support\Facades\DB;

class StoreNotificationEloquentRepository extends AbstractBaseRepository implements StoreNotificationRepositoryInterface
{
    protected $className = "StoreNotificationEloquentRepository";

    public function __construct(StoreNotification $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getTopByIsSent($limit, $is_sent)
    {
        LogEx::methodName($this->className, 'getTopByIsSent');
        return DB::table("store_notification")
            ->select('*')
            ->where('is_sent', $is_sent)->whereNotNull('user_phone')->whereNotNull('content_sms')->limit($limit)->get();
    }

    public function getTopNewestByUserId($page = 1, $limit = 10, $id)
    {
        LogEx::methodName($this->className, 'getTopNewestByUserId');
        $offset = ($page - 1) * $limit;
        return DB::table("store_notification")
            ->select('*')
            ->whereNotNull('content')->where('user_id', $id)->orderBy('created_at', 'desc')->limit($limit)->offset($offset)->get();
    }

    public function getCount($userId)
    {
        LogEx::methodName($this->className, 'getCount');
        return DB::table("store_notification")
            ->select(DB::raw('count(is_read = true) as total, count(case is_read when false then 1 else null end) as unread'))
            ->whereNotNull('content')->where('user_id', $userId)->get();
    }

    public function readNoti($id)
    {
        LogEx::methodName($this->className, 'readNoti');
        DB::statement("update store_notification set is_read = true, updated_at = NOW() where id = ".$id);
    }
}
