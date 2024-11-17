<?php

namespace App\Repositories\Users;

use App\Repositories\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function adminGetUserWithCompany();
    public function managerGetUserWithCompany();
    public function checkUsername($username);
    public function checkEmail($email);
    public function deleteUsername($username);
    public function deleteEmail($email);
    public function getUsersByActiveAndStoreIds($active, $ids);

    public function filterUser($requestInput, $drugStoreId = 0);
    public function checkAvailableUsername($username, $id);
}
