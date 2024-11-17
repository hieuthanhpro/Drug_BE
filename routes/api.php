<?php

use Illuminate\Http\Request;
use function Composer\Autoload\includeFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\LibExtension\LogEx;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'namespace' => 'Backend',
], function () {
    Route::post('/dang-nhap', 'UsersController@login')->name('taikhoan.dangnhap');
    Route::get('/promotion', 'DashboardController@getPromotionData')->name('dashboard.promotion.public');
    Route::post('/forgotPassword', 'UsersController@forgotPassword')->name('taikhoan.forgotPassword');
});

// Route::get('/drug/get-barcode', 'Backend\DrugController@getBarcode');

Route::group([
    'namespace' => 'Backend',
], function () {
    Route::get('test-call', 'InvoiceController@testCall')->name('v3.test.call');
    Route::get('/v2/invoice/payment-ipn', 'InvoiceController@paymentIPN')->name('v2.invoice.paymentIPN');
    Route::group(['middleware' => ['auth']], function () {

        /*user*/
        Route::get('users/list', 'UsersController@getListUser')->name('users.list');


        Route::get('/drug/lay-don-vi', 'DrugController@getUnitByDrug')->name('drug.laydonvi'); // như này
        Route::post('/drug/setcost', 'DrugController@setupCost')->name('drug.setcost');
        Route::post('/drug/masterdata', 'DrugController@insertByMasterData')->name('drug.masterdata');
        Route::get('/drug/getbyname', 'DrugController@getDrugMasterByName')->name('drug.getbyname');

        Route::get('/drug/detail/{id}', 'DrugController@getDetailDrug')->name('drug.detail');
        Route::post('/drug/delete_many', 'DrugController@deleteManyById')->name('drug.delete_many');
        Route::get('/drug/getall', 'DrugController@getAllDrug')->name('drug.getall');

        Route::post('/drug/updateamount', 'DrugController@updateAmout')->name('drug.updateamount');

        /*bán hàng*/
        Route::get('/drug/allwithunit', 'DrugController@getAllWithUnit')->name('drug.allwithunit');

        /*để nhập hàng*/
        Route::get('/drug/getunitbase', 'DrugController@show')->name('drug.getunitbase');


        Route::get('/drug/bynumber/{id}', 'DrugController@getDrugByNumber')->name('drug.bynumber');
        Route::get('/drug/number/{id}', 'DrugController@getAllNumberDrug')->name('drug.bynumber');

        Route::post('/drug/check_master', 'DrugController@checkDrugMaster')->name('drug.check_master');

        Route::get('/drugmaster', 'DrugMasterController@getDrugMasterByName')->name('drug.allwithunit');

        Route::get('/drug-category/detail/{id}', 'DrugCategoryController@getDetailCategory')->name('drugcategory.detail');
        Route::get('/drug-group/detail/{id}', 'DrugGroupController@getDetailGroup')->name('drug-group.detail');

        Route::get('/invoice/detail_invoice/{id}', 'InvoiceController@getDetailInvoice')->name('invoice.detail');

        // Split invoice: IV1,...
        Route::get('invoiceIv1', 'InvoiceController@indexIV1')->name('invoice.invoiceIv1');

        // Split invoice: IV2,...
        Route::get('invoiceIv2', 'InvoiceController@indexIV2')->name('invoice.invoiceIv2');

        // Split invoice: IV7,...
        Route::get('invoiceIv7', 'InvoiceController@indexIV7')->name('invoice.invoiceIv7');
        Route::get('/invoiceIv7/detail_invoice/{id}', 'InvoiceController@getInvoiceIV7Detail')->name('invoice.iv7Detail');

        Route::get('/invoice/history_warehouse', 'InvoiceController@getHistoryWareHouse')->name('invoice.history');
        Route::get('/invoice/cancel/{id}', 'InvoiceController@cancelInvoice')->name('invoice.cancel');

        Route::post('/invoice/excel', 'InvoiceController@importInvoiceExcel')->name('invoice.excel');

        Route::get('/order/detail/{id}', 'OrderController@getDetailOrder')->name('order.detail');
        Route::get('/order/detail_admin/{id}', 'OrderController@getDetailOrderAdmin')->name('order.detail_admin');
        Route::get('/order/cancel/{id}', 'OrderController@cancelOrder')->name('order.cancel');

        Route::get('/supplier/getlist', 'SupplierController@getListSupplier')->name('supplier.list');
        Route::get('/supplier/getdetail/{id}', 'SupplierController@getDetailSupplier')->name('supplier.getdetail');
        Route::get('/supplier/getlistinvoice/{id}', 'SupplierController@getListInvoice')->name('supplier.getlistinvoice');

        Route::get('/invoice/invoice_return/{id}', 'InvoiceController@getInvoiceReturn')->name('invoice.return');
        Route::get('/invoice/drug_remain/{id}', 'InvoiceController@getDrugRemain')->name('invoice.drugremain');


        Route::get('/vouchers/getdetail/{id}', 'VouchersController@getDetailVouchers')->name('vouchers.getdetail');
        Route::get('/vouchers/getlist/{id}', 'VouchersController@getListVouchers')->name('vouchers.getlist');
        Route::get('/vouchers/cancel/{id}', 'VouchersController@cancelVouchers')->name('vouchers.cancel');
        Route::get('/vouchers/statics', 'VouchersController@staticsVouchers')->name('vouchers.statics');
        Route::get('/customer/list', 'CustomerController@getListCustomer')->name('customer.list');
        Route::get('/customer/listinvoice/{id}', 'CustomerController@getListInvoice')->name('customer.listinvoice');


        /*api báo cáo*/
        Route::get('/report/inventory', 'ReportController@reportInventory')->name('report.inventory');
        Route::get('/report/reportdrug', 'ReportController@reportDrugByType')->name('report.reportdrug');
        Route::get('/report/revenue_money', 'ReportController@reportRevenueMoney')->name('report.revenuemoney');
        Route::get('/report/revenue_invoice', 'ReportController@reportRevenueInvoice')->name('report.revenueinvoice');
        Route::get('/report/revenue_drug', 'ReportController@reportRevenueDrug')->name('report.revenuedrug');
        Route::get('/report/export-import', 'ReportController@getExportImport')->name('report.export-import');
        Route::get('/report/goods-in-out', 'ReportController@getGoodsInOut')->name('report.good-in-out');
        Route::get('/report/sales-person', 'ReportController@getSalesPerson')->name('report.sales-person');
        Route::get('/report/revenue-profit', 'ReportController@getRevenueProfit')->name('report.revenueprofit');

        // api barcode
        Route::get('/drug/get-barcode', 'DrugController@getBarcode');
        Route::get('/drug/decode-barcode', 'DrugController@decodeBarcode');

        // api báo cáo mới
        Route::post('/new-report/create-control-regular-and-irregular-quality-book', 'NewReportController@createControlRegularAndIrregularQualityBook');
        Route::get('/new-report/control-quality-book/list', 'NewReportController@getListControlQualityBook');
        Route::get('/new-report/control-quality-book/{id}', 'NewReportController@getDetailControlQualityBook');
        Route::post('/new-report/control-quality-book/{id}/update', 'NewReportController@updateControlQualityBook');
        Route::post('/new-report/control-quality-book/{id}/delete', 'NewReportController@deleteControlQualityBook');
        Route::get('/new-report/control-drug-annex-xviii', 'NewReportController@getControlDrugAnnexXvii');
        Route::get('/new-report/selling-prescription-drugs', 'NewReportController@getSellingPrescriptionDrugs');
        Route::get('/user/get-employee-store', 'UsersController@getEmployeeStore');
        /**
         * api Quảng cáo
         */
        Route::get('/link-ads', 'LinkAdsController@index');


        /*set date warining*/

        Route::post('/set-date', 'UsersController@setWarningDate')->name('set.date');

        /*chi tiết phiếu tạm*/

        Route::get('/invoice_tmp/detail/{id}', 'InvoiceTmpController@getDetailById')->name('invoice_tmp.detail');
        Route::get('/order_tmp/detail/{id}', 'OrderTmpController@getDetailOrder')->name('order_tmp.detail');

        Route::get('/vouchers_check/{id}', 'VouchersCheckController@getDetail')->name('vouchers_check.detail');
        Route::get('vouchers_check/cancel/{id}', 'VouchersCheckController@cancel')->name('vouchers_check.cancel');
        Route::get('/vouchers_check/detail/list', 'VouchersCheckController@getDetailList')->name('vouchers_check.detail_list');

        /*end api báo cáo*/
        require(__DIR__ . '/Api/dashboard.php');
        Route::resource('drug', 'DrugController');
        Route::resource('drug-group', 'DrugGroupController');
        Route::resource('unit', 'UnitController');
        Route::resource('drug-category', 'DrugCategoryController');
        Route::resource('customer', 'CustomerController');
        Route::resource('invoice', 'InvoiceController');
        Route::post('invoice/invoiceIv1', 'InvoiceController@invoiceIV1')->name('invoice.invoiceIV1_create');
        Route::post('invoice/invoiceIv2', 'InvoiceController@invoiceIV2')->name('invoice.invoiceIV2_create');
        Route::post('invoice/invoiceIv7', 'InvoiceController@invoiceIV7')->name('invoice.invoiceIV7_create');


        Route::resource('order', 'OrderController');

        Route::resource('invoice_tmp', 'InvoiceTmpController');
        Route::resource('order_tmp', 'OrderTmpController');
        Route::resource('supplier', 'SupplierController');

        Route::resource('report', 'ReportController');

        Route::resource('vouchers', 'VouchersController');

        Route::resource('vouchers_check', 'VouchersCheckController');

        Route::resource('permission', 'PermissionsController');

        Route::resource('role', 'RoleController');


        Route::resource('dose_group', 'DoseGroupController');

        Route::resource('dose_drug', 'DoseDrugController');

        Route::get('dose_drug/detail/{id}', 'DoseDrugController@getDetailDose')->name('dose_drug.detail');

        Route::get('dose_drug/invoice', 'DoseDrugController@show')->name('dose_drug.invoice');

        Route::get('dose_drug/invoice/{id}', 'DoseDrugController@getDetailInvoiceDose')->name('dose_drug.invoice_detail');


        Route::get('dose_drug/listdrug/{id}', 'DoseDrugController@getListDrugBuy')->name('dose_drug.listdrug');

        Route::post('dose_drug/buydose', 'DoseDrugController@buyDose')->name('dose_drug.buy');


        Route::get('dose_group/detail/{id}', 'DoseGroupController@getDetailGroup')->name('dose_group.detail');

        Route::resource('dose_category', 'DoseCategoryController');

        Route::get('dose_category/detail/{id}', 'DoseCategoryController@getDetailCategory')->name('dose_category.detail');

        Route::post('role/addpermission', 'RoleController@addPermissionRole')->name('role.add');

        Route::post('role/editpermission', 'RoleController@editPermissionRole')->name('role.edit');
        Route::get('role/detail/{id}', 'RoleController@getRoleDetail')->name('role.detail');

        // ######## Autocomplete: search data ########
        // Import drug page
        Route::get('/drug/autoListWithPackages4Import/{textInput}', 'DrugController@autoListWithPackages4ImportShort')->name('drug.autoWithPacks4ImportShort');
        Route::get('/drug/autoListWithPackages4Import/{modeSearch}/{textInput}', 'DrugController@autoListWithPackages4Import')->name('drug.autoWithPacks4Import');
        Route::get('/drug/autoListFavorite/{textInput}', 'DrugController@autoListFavorite')->name('drug.autoListFavorite');
        Route::get('/warehouse/getListUnitByDrug/{drug_id}', 'WarehouseController@getListUnitByDrug')->name('warehouse.listUnitByDrug');

        // Sale drug for customer, guest
        Route::get('/warehouse/autoListWithPackages4Sale/{textInput}', 'WarehouseController@autoListWithPackages4SaleShort')->name('warehouse.autoWithPacks4SaleShort');
        Route::get('/warehouse/autoListWithPackages4Sale/{modeSearch}/{textInput}', 'WarehouseController@autoListWithPackages4Sale')->name('warehouse.autoWithPacks4Sale');

        Route::get('/warehouse/getListUnitByDrug4Sale/{drug_id}', 'WarehouseController@getListUnitByDrug4Sale')->name('warehouse.listUnitByDrug4Sale');
        Route::post('/warehouse/getListUnitByDrugIds4Sale', 'WarehouseController@getListUnitByDrugIds4Sale')->name('warehouse.listUnitByDrugIds4Sale');
        Route::get('/warehouse/autoListWithPackages4SaleFavorite/', 'WarehouseController@autoListWithPackages4SaleFavorite')->name('warehouse.autoWithPacks4SaleFavorite');

        Route::get('/warehouse/stockList/', 'WarehouseController@getStockList')->name('warehouse.stocklist');
        Route::get('/warehouse/inOut/', 'WarehouseController@getWarehouseInOut')->name('warehouse.inOut');

        // User
        Route::group([
            'prefix' => 'user'
        ], function () {
            Route::post('create', 'UsersController@create');
            Route::post('change-pass', 'UsersController@changePass');
            Route::post('update/{id}', 'UsersController@update');
            Route::get('delete/{id}', 'UsersController@delete');

            Route::get('resetpass/{id}', 'UsersController@resetPassword');
            Route::get('notifications', 'UsersController@getNotifications');
            Route::post('notification/mark-as-read/{id}', 'UsersController@markAsReadNotification');
        });

        /**
         * Api export pdf
         */
        Route::post('/export-pdf', 'ExportPdfController@export');

        // v2
        require(__DIR__ . '/Api/apiv2.php');
    });
});

DB::listen(function ($sql) {
    LogEx::logSQL($sql->sql, $sql->bindings, $sql->time);
});
