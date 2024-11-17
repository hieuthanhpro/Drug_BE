<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/7/2018
 * Time: 8:52 PM
 */

Route::group ( [
    'prefix' => 'nhom-thuoc'
], function () {
    Route::post('/tao-moi', 'DrugGroupController@creatNew')->name('nhomthuoc.taomoi');

    Route::post('/xoa', 'DrugGroupController@deleteGroup')->name('nhomthuoc.xoa');

    Route::post('/cap-nhat', 'DrugGroupController@updateGroup')->name('nhomthuoc.capnhat');

    Route::get('/lay-all', 'DrugGroupController@getAllGroup')->name('nhomthuoc.layall');

});