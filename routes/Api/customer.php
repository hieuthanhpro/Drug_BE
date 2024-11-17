<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/3/2018
 * Time: 9:19 AM
 */
Route::group ( [
    'prefix' => 'khach-hang'
], function () {
    Route::get('/tao-nhom-khach-hang', 'UsersController@login')->name('taikhoan.dangnhap');
});