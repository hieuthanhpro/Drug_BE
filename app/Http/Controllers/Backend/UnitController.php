<?php

namespace App\Http\Controllers\Backend;

use App\LibExtension\CommonConstant;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Http\Controllers\Controller;
use App\LibExtension\LogEx;

class UnitController extends Controller
{
    protected $className = "Backend\UnitController";

    protected $unit;

    public function __construct(UnitRepositoryInterface $unit)
    {
        LogEx::constructName($this->className, '__construct');

        $this->unit = $unit;
    }

    public function index(){
        LogEx::methodName($this->className, 'index');

        $data = $this->unit->orderBy('name', 'asc')
            ->findManyByCredentials([
                'id' => [0, '>']
            ]);
        return \App\Helper::successResponse(CommonConstant::SUCCESS_CODE, CommonConstant::MSG_SUCCESS, $data);
    }
}
