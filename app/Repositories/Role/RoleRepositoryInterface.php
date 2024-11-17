<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 11:04 AM
 */

namespace App\Repositories\Role;

use App\Repositories\RepositoryInterface;

interface RoleRepositoryInterface extends RepositoryInterface
{
    public function getDetail($id);
}