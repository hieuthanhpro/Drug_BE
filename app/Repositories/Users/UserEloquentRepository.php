<?php

namespace App\Repositories\Users;

use app\libextension\logex;
use App\LibExtension\Utils;
use App\Models\User;
use App\Repositories\AbstractBaseRepository;
use App\Services\CommonConstant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserEloquentRepository extends AbstractBaseRepository implements UserRepositoryInterface
{
    protected $className = "UserEloquentRepository";

    public function __construct(User $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    private function username($username)
    {
        LogEx::methodName($this->className, 'username');

        $User = DB::table('users')->where('username', $username);
        return $User;
    }

    private function email($email)
    {
        LogEx::methodName($this->className, 'email');

        $User = DB::table('users')->where('email', $email);
        return $User;
    }

    public function checkUsername($username)
    {
        LogEx::methodName($this->className, 'checkUsername');

        $User = $this->username($username)->first();
        return $User;
    }

    public function checkEmail($email)
    {
        LogEx::methodName($this->className, 'checkEmail');

        $User = $this->email($email)->first();
        return $User;
    }

    public function deleteUsername($username)
    {
        LogEx::methodName($this->className, 'deleteUsername');

        $this->username($username)->delete();
    }

    public function deleteEmail($email)
    {
        LogEx::methodName($this->className, 'deleteEmail');

        $this->email($email)->delete();
    }

    public function adminGetUserWithCompany()
    {
        LogEx::methodName($this->className, 'adminGetUserWithCompany');

        $User = User::select('users.*', 'company.company_name')
            ->leftjoin('company', 'users.company_id', 'company.id')
            ->get();
        return $User;
    }

    public function managerGetUserWithCompany()
    {
        LogEx::methodName($this->className, 'managerGetUserWithCompany');

        $User = User::select('users.*', 'company.company_name')
            ->leftjoin('company', 'users.company_id', 'company.id')
            ->where('company_id', Auth::user()->company_id)
            ->where(function ($query) {
                $query->where('role_type', CommonConstant::MANAGER_ROLE)
                    ->orWhere('role_type', CommonConstant::MEMBER_ROLE);
            })
            ->get();
        return $User;
    }

    public function getUsersByActiveAndStoreIds($active, $ids)
    {
        LogEx::methodName($this->className, 'getUsersByStatus');
        $users = User::select('*')->where('active', '=', $active)->whereIn('drug_store_id', $ids)->get();
        return $users;
    }

    public function filterUser($requestInput, $drugStoreId = 0)
    {
        $page = $requestInput['page'] ?? 1;
        $per_page = $requestInput['per_page'] ?? 10;
        $offset = ($page - 1) * $per_page;

        $select = "select  u.id,
            u.drug_store_id,
            s.name                      as drug_store_name,
            to_jsonb(s.*)               as drugstore,
            u.name,
            u.username,
            u.full_name,
            u.email,
            u.number_phone,
            u.avatar,
            u.active::varchar,
            u.user_role,
            u.permission,
            u.settings,
            u.created_at,
            u.updated_at
            from    users u
                inner join drugstores s
                on  s.id = u.drug_store_id";
        $selectCount = "SELECT count(u.*) as total FROM users u inner join drugstores s on s.id = u.drug_store_id";
        $where = " where 1 = 1";
        if($drugStoreId > 0){
            $where = $where . " AND u.drug_store_id = $drugStoreId";
        }
        if (!empty($requestInput['query'])) {
            $keySearch = trim($requestInput['query']);
            $where = $where . " AND (u.name ~* '" . $keySearch
                . "' or u.full_name  ~* '" . $keySearch
                . "' or u.username  ~* '" . $keySearch
                . "' or u.number_phone  ~* '" . $keySearch
                . "' or u.email  ~* '" . $keySearch
                . "' or s.name  ~* '" . $keySearch
                ."')";
        }

        $orderLimit = " order by u.id desc limit " . $per_page . " offset " . $offset;
        $data = DB::select($select . $where . $orderLimit);
        $dataCount = DB::select($selectCount . $where);

        return new LengthAwarePaginator($data, $dataCount[0]->total, $per_page, $page);
    }

    public function checkAvailableUsername($username, $id){
        $query = "select count(*) as total from users where username = '$username'";
        if(isset($id)){
            $query = $query . " AND id != $id";
        }
        $data = DB::select($query);
        return $data[0]->total;
    }
}
