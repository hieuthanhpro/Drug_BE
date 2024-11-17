<?php
/**
 * Created by PhpStorm.
 * User: anhv
 * Date: 12/4/2018
 * Time: 2:48 PM
 */
    Route::group([
    'prefix' => 'thanh-vien'
],function(){
    Route::get('/', 'UserController@index')->name('admin.user.index');
    Route::get('/', 'UserController@create')->name('admin.user.create');
    Route::post('/','UserController@store');
    Route::post('show','UserController@show');
    Route::post('edit','UserController@edit')->name('admin.user.edit');
    Route::get('lock/{id}','UserController@lock')->name('admin.user.lock');
    Route::get('unlock/{id}','UserController@unlock')->name('admin.user.unlock');
    Route::post('change','UserController@changeuser')->name('admin.user.change');
});
?>