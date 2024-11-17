<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LibExtension\LogEx;

class HomeController extends Controller
{
    protected $className = "Admin\HomeController";

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        LogEx::methodName($this->className, 'index');

        return view('admin.home.index');
    }
}
