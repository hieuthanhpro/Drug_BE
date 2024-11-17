<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/8/2018
 * Time: 12:00 AM
 */

Route::group ( [
    'prefix' => 'hang-hoa'
], function () {
    Route::post('/tao-moi', 'DrugController@createNewDrug')->name('hanghoa.taomoi');

    Route::post('/cap-nhat', 'DrugController@updateDrug')->name('hanghoa.capnhat');

    Route::post('/xoa', 'DrugController@deledeDrug')->name('hanghoa.xoa');

    Route::get('/danh-sach', 'DrugController@getListDrug')->name('hanghoa.danhsach');

    Route::post('/lay-don-vi', 'DrugController@getUnitByDrug')->name('hanghoa.donvi');
    
});