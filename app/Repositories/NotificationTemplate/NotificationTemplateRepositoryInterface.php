<?php
namespace App\Repositories\NotificationTemplate;

use App\Repositories\RepositoryInterface;

interface NotificationTemplateRepositoryInterface extends RepositoryInterface
{
    public function getByKey($key);
}
