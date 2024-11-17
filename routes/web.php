<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/test-barcode', 'Admin\DrugStoreController@testBarcode');

Auth::routes();

Route::group([
    //    'middleware' => 'auth',
    'namespace' => 'Admin',
    'prefix' => 'admin'
], function () {
    Route::get('dashboard', 'HomeController@index')->name('admin.home');

    Route::group([
        'prefix' => 'don-vi'
    ], function () {
        Route::get('/', 'UnitController@index')->name('admin.unit.index');
        Route::get('tao-moi', 'UnitController@create')->name('admin.unit.create');
        Route::get('delete/{id}', 'UnitController@delete')->name('admin.unit.delete');
        Route::post('tao-moi', 'UnitController@store')->name('admin.unit.store');
    });

    Route::group([
        'prefix' => 'ket-noi'
    ], function () {
        Route::get('/', 'ConnectController@showForm')->name('admin.connect.form');
        Route::post('/kiem-tra', 'ConnectController@checkConnectData')->name('admin.connect.check');
    });


    Route::group([
        'prefix' => 'thanh-vien'
    ], function () {
        Route::get('/', 'UserController@index')->name('admin.user.index');
        Route::get('create', 'UserController@create')->name('admin.user.create');
        Route::post('create', 'UserController@store');
        Route::post('show', 'UserController@show');
        Route::post('edit', 'UserController@edit')->name('admin.user.edit');
        Route::get('lock/{id}', 'UserController@lock')->name('admin.user.lock');
        Route::get('unlock/{id}', 'UserController@unlock')->name('admin.user.unlock');
        Route::get('delete/{id}', 'UserController@delete')->name('admin.user.delete');
        Route::post('change', 'UserController@changeuser')->name('admin.user.change');
    });
    Route::group([
        'prefix' => 'hieu-thuoc'
    ], function () {
        Route::get('/', 'DrugStoreController@index')->name('admin.drugstore.index');
        Route::get('tao-moi', 'DrugStoreController@create')->name('admin.drugstore.create');
        Route::post('tao-moi', 'DrugStoreController@store');
        Route::post('show', 'DrugStoreController@show');

        Route::get('edit/{id}', 'DrugStoreController@edit')->name('admin.drugstore.edit');
        Route::post('edit/{id}', 'DrugStoreController@update');
        Route::get('lock/{id}', 'DrugStoreController@lock')->name('admin.drugstore.lock');
        Route::get('unlock/{id}', 'DrugStoreController@unlock')->name('admin.drugstore.unlock');
        Route::get('delete/{id}', 'DrugStoreController@delete')->name('admin.drugstore.delete');
        Route::post('change', 'DrugStoreController@changedrugstore')->name('admin.drugstore.change');

        //Admin chưa phân trang
        Route::get('list', 'DrugStoreController@listStore')->name('admin.drugstore.list');
        Route::get('moveDrug', 'DrugStoreController@moveDrug')->name('admin.drugstore.moveDrug');
        Route::get('xoa', 'DrugStoreController@viewDelete')->name('admin.drugstore.xoa');
        Route::post('sentDrugByDrugstore', 'DrugStoreController@sentDrugByDrugstore')->name('admin.drugstore.sentDrugByDrugstore');


        Route::get('editpw/{id}', 'DrugStoreController@editpw')->name('admin.drugstore.editpw');
        Route::post('editpw/{id}', 'DrugStoreController@updatepw');
    });
    Route::group([
        'prefix' => 'thuoc'
    ], function () {
        Route::get('/', 'DrugController@index')->name('admin.drug.index');
        Route::get('get-list-drug', 'DrugController@getListDrug')->name('admin.drug.get_list');
        Route::post('upload', 'DrugController@upload')->name('admin.drug.upload');
    });
    Route::group([
        'prefix' => 'quyen'
    ], function () {
        Route::get('/', 'PermissionController@index')->name('admin.permission.index');
        Route::get('/tao-moi', 'PermissionController@create')->name('admin.permission.new');
        Route::post('upload', 'DrugController@upload')->name('admin.drug.upload');
    });

    Route::group([
        'prefix' => 'quang-cao'
    ], function () {
        Route::get('/', 'LinkAdsController@index')->name('admin.linkads.index');
        Route::get('/tao-moi', 'LinkAdsController@viewCreate')->name('admin.linkads.viewcreate');

        Route::post('/', 'LinkAdsController@store')->name('admin.linkads.create');

        Route::post('/update/{id}', 'LinkAdsController@update')->name('admin.linkads.update');
        Route::get('/update/{id}', 'LinkAdsController@viewupdate')->name('admin.linkads.viewupdate');

        Route::get('/delete/{id}', 'LinkAdsController@delete')->name('admin.linkads.delete');
    });

    Route::group([
        'prefix' => 'thong-bao'
    ], function () {
        Route::get('/template', 'NotificationController@getNotificationTemplate')->name('admin.notification.template');
        Route::get('/template/{key}', 'NotificationController@editTemplate')->name('admin.notification.editTemplate');
        Route::post('/template/{key}', 'NotificationController@updateTemplate')->name('admin.notification.updateTemplate');
        Route::get('/template-new', 'NotificationController@viewCreateTemplate')->name('admin.notification.viewCreateTemplate');
        Route::post('/template-new', 'NotificationController@createTemplate')->name('admin.notification.createTemplate');
        Route::get('/noti-admin', 'NotificationController@viewAdminNoti')->name('admin.notification.listNoti');
        Route::get('/noti-admin/new', 'NotificationController@viewCreateAdminNoti')->name('admin.notification.createNoti');
        Route::post('/noti-admin/new', 'NotificationController@createAdminNoti')->name('admin.notification.createNotiSubmit');
        Route::get('/noti-admin/{id}', 'NotificationController@detailAdminNoti')->name('admin.notification.detailNoti');
    });

    Route::group([
        'prefix' => 'dat-hang'
    ], function () {
        Route::get('/', 'OrderController@index')->name('admin.order.index');
        Route::get('/tra-hang/{id}', 'OrderController@getOrderForReturn')->name('admin.order.order_return');
        Route::get('/tra-hang/detail/{id}', 'OrderController@getOrderDetailForReturn')->name('admin.order.order_detail_for_return');
        Route::post('/tra-hang', 'OrderController@returnOrder')->name('admin.order.order_return_update');
        Route::get('/da-tra-hang', 'OrderController@getOrdersReturned')->name('admin.order.orders_returned');
        Route::get('/da-tra-hang/{id}', 'OrderController@getOrderDetail')->name('admin.order.order_detail');
    });
});
