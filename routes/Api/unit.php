<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/9/2018
 * Time: 8:50 PM
 */

Route::group ( [
    'prefix' => 'unit'
], function () {
    Route::get('/list', 'UnitController@getListUnit')->name('unit.list');

});