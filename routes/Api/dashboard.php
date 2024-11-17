<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/3/2018
 * Time: 9:19 AM
 */
    Route::group ( [
        'prefix' => 'tong-quan'
    ], function () {
        Route::get('/doanh-so', 'DashboardController@getInvoiceByTime')->name('doanhso');

        Route::get('/doanh-so-ngay', 'DashboardController@getInvoiceByDay')->name('doanhso.current_day');

        Route::get('/doanh-so-tuan', 'DashboardController@getInvoiceByWeek')->name('doanhso.tuan');

        Route::get('/doanh-so-nam', 'DashboardController@getInvoiceByYear')->name('doanhso.nam');

        Route::get('/nhom-thuoc', 'DashboardController@getDrugByGroup')->name('doanhso.nhomthuoc');

        Route::get('/thuoc-sap-het-hang', 'DashboardController@getListDrugWaring')->name('doanhso.canhbaotonkho');
        Route::get('/thuoc-sap-het-hang-gop-don-vi', 'DashboardController@getListDrugWaringCombineWarning')->name('doanhso.canhbaotonkhogopdonvi');


        Route::get('/thuoc-ban-chay', 'DashboardController@getTopDrug')->name('doanhso.thuocbanchay');

        Route::get('/ton-kho', 'DashboardController@getDrugInventory')->name('doanhso.tonkho');

        Route::get('/thuoc-sap-het-han', 'DashboardController@getDrugExpired')->name('doanhso.thuocsaphethan');

        Route::get('/getlog', 'DashboardController@getLogWarehouse')->name('doanhso.getlog');
    });