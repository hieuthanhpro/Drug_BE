<?php

namespace App\Http\Controllers;

use App\LibExtension\LogEx;

class HomeController extends Controller
{
    protected $className = "HomeController";
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        LogEx::constructName($this->className, '__construct');

        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        LogEx::methodName($this->className, 'index');
        return view('home');
    }
}
