<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 1/2/2019
 * Time: 11:23 AM
 */


namespace App\Repositories\Permissions;

use App\Repositories\RepositoryInterface;

interface PermissionsRepositoryInterface extends RepositoryInterface
{
    public function getListPermission();
}