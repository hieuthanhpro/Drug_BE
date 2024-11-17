<?php

namespace App\Repositories\NotificationTemplate;

use App\Exceptions\RepositoryException;
use App\Models\NotificationTemplate;
use App\Repositories\AbstractBaseRepository;
use app\libextension\logex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationTemplateEloquentRepository extends AbstractBaseRepository implements NotificationTemplateRepositoryInterface
{
    protected $className = "NotificationTemplateEloquentRepository";

    public function __construct(NotificationTemplate $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getByKey($key)
    {
        LogEx::methodName($this->className, 'getByKey');
        return DB::table("notification_template")
            ->select('*')
            ->where('key', '=', $key)->first();
    }
}
