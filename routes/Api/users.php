<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/4/2018
 * Time: 8:47 AM
 */
    Route::group ( [
        'prefix' => 'tai-khoan'
    ], function () {
        Route::get('thong-tin', 'UsersController@getUserInfo')->name('taikhoan.getinfo');
    });