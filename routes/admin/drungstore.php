<?php
/**
 * Created by PhpStorm.
 * User: anhv
 * Date: 12/4/2018
 * Time: 2:48 PM
 */
Route::group([
    'prefix' => 'hieu-thuoc'
],function(){
    Route::get('/', 'DrungStoreController@index')->name('admin.drugstore.index');
    Route::get('/', 'DrungStoreController@create')->name('admin.drugstore.create');
    Route::post('/','DrungStoreController@store');
    Route::post('show','DrungStoreController@show');
    Route::post('edit','DrungStoreController@edit')->name('admin.drugstore.edit');
    Route::get('lock/{id}','DrungStoreController@lock')->name('admin.drugstore.lock');
    Route::get('unlock/{id}','DrungStoreController@unlock')->name('admin.drugstore.unlock');
    Route::post('change','DrungStoreController@changedrungstore')->name('admin.drugstore.change');
});
?>
