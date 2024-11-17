<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/7/2018
 * Time: 11:47 PM
 */

Route::group ( [
    'prefix' => 'danh-muc-thuoc'
], function () {
    Route::post('/tao-moi', 'DrugCategoryController@creatNew')->name('danhmucthuoc.taomoi');

    Route::post('/xoa', 'DrugCategoryController@deleteGroup')->name('danhmucthuoc.xoa');

    Route::post('/cap-nhat', 'DrugCategoryController@updateGroup')->name('danhmucthuoc.capnhat');

    Route::get('/lay-all', 'DrugCategoryController@getAllGroup')->name('danhmucthuoc.layall');

});