<?php

/* use Illuminate\Http\Request; */

use Illuminate\Support\Facades\Route;

Route::group([
    // 'namespace' => 'Backend',
    // 'middleware' => ['auth'],
    'prefix' => 'v2'
], function () {

    Route::group([
        'prefix' => 'dashboard'
    ], function () {
        Route::get('chart', 'DashboardController@getChartData')->name('v2.dashboard.chart');
        Route::get('activities', 'DashboardController@getActivities')->name('v2.dashboard.activities');
        Route::get('statistic', 'DashboardController@getStatistic')->name('v2.dashboard.statistic');
        Route::get('warningdate', 'DashboardController@getWarningDateItems')->name('v2.dashboard.warningdate');
        Route::get('warningquantity', 'DashboardController@getWarningQuantityItems')->name('v2.dashboard.warningquantity');
        Route::get('promotion', 'DashboardController@getPromotionData')->name('v2.dashboard.promotion');
        Route::get('notification', 'NotificationController@getNewestNotifications')->name('v2.notification.getNewestNotification');
        Route::get('notification/read/{id}', 'NotificationController@readNotification')->name('v2.notification.readNotification');
    });

    Route::group([
        'prefix' => 'master'
    ], function () {
        Route::get('drug-group/list', 'DrugGroupController@getList')->name('v2.master.group');
        Route::get('drug-group/list-filter', 'DrugGroupController@getListFilter')->name('v2.master.groupFilter');
        Route::get('drug-category/list', 'DrugCategoryController@getList')->name('v2.master.category');
        Route::get('drug-category/list-filter', 'DrugCategoryController@getListFilter')->name('v2.master.categoryFilter');
        Route::get('customer/list', 'CustomerController@getList')->name('v2.master.customer.list');
        Route::get('customer/history', 'CustomerController@getHistory')->name('v2.master.customer.history');
        Route::get('customer/getlistinvoice', 'CustomerController@getListInvoice')->name('v2.master.customer.getlistinvoice');
        Route::get('supplier/list', 'SupplierController@getList')->name('v2.master.supplier.list');
        Route::get('supplier/history', 'SupplierController@getHistory')->name('v2.master.supplier.history');
        Route::get('supplier/getlistinvoice', 'SupplierController@getListInvoice')->name('v2.master.supplier.getlistinvoice');
        Route::post('user/setting', 'UsersController@setUserSetting')->name('v2.master.usersetting');
        Route::post('drugstore/setting', 'UsersController@setUserSetting')->name('v2.master.usersetting');
    });

    Route::group([
        'prefix' => 'drug'
    ], function () {
        Route::post('uploadzip', 'DrugController@uploadZipDrug')->name('v2.drug.uploadzip');
        Route::post('add', 'DrugController@addDrug')->name('v2.drug.add');
        Route::post('edit/{id}', 'DrugController@editDrug')->name('v2.drug.edit');
        Route::post('updateunit/{id}', 'DrugController@updateUnit')->name('v2.drug.udpateunit');
        Route::post('updatewarning/{id}', 'DrugController@updateWarning')->name('v2.drug.udpatewarning');
        Route::post('updatestatus/{id}/{status}', 'DrugController@updateStatus')->name('v2.drug.udpatestatus');
        Route::get('category/{id}', 'DrugController@drugsInCategory')->name('v2.drug.category');
        Route::get('group/{id}', 'DrugController@drugsInGroup')->name('v2.drug.group');
        Route::get('list', 'DrugController@drugList')->name('v2.drug.list');
        Route::get('scan-code', 'DrugController@drugScanBarcode')->name('v2.drug.scan.barcode');
        Route::get('scan/{drugCode}/{number}', 'DrugController@drugScan')->name('v2.drug.scan');
        Route::get('scan', 'DrugController@drugScanNew')->name('v2.drug.scan.new');
        Route::get('detail/{id}', 'DrugController@getDrugDetail')->name('v2.drug.detail');
        Route::get('search', 'DrugController@search')->name('v2.drug.search');
        Route::post('import', 'DrugController@importDrug')->name('v2.drug.import');
        Route::post('checkimport', 'DrugController@checkImportDrug')->name('v2.drug.checkimport');
        Route::post('stock', 'DrugController@drugStock')->name('v2.drug.stock');
        Route::get('filterDrugByCriteria', 'DrugController@filterDrugByCriteria')->name('v2.drug.filterDrugByCriteria');
        Route::post('clone', 'DrugController@drugClone')->name('v2.drug.clone');
    });

    Route::group([
        'prefix' => 'invoice'
    ], function () {
        Route::post('createIV1', 'InvoiceController@sell')->name('v2.invoice.createiv1');
        Route::post('sell', 'InvoiceController@sell')->name('v2.invoice.sell');
        Route::post('warehousing', 'InvoiceController@warehousing')->name('v2.invoice.warehousing');
        Route::post('warehousingtemp', 'InvoiceController@warehousingTemp')->name('v2.invoice.warehousingtemp');
        Route::get('warehousingstatistic', 'InvoiceController@warehousingStatistic')->name('v2.invoice.warehousingstatistic');
        Route::get('list', 'InvoiceController@getList')->name('v2.invoice.list');
        Route::get('list-tmp', 'InvoiceTmpController@getList')->name('v2.invoice.list-tmp');
        Route::get('detail/{type}/{code}', 'InvoiceController@getInvoiceDetail')->name('v2.invoice.detail');
        Route::get('detail-short/{type}/{code}', 'InvoiceController@getInvoiceDetailShort')->name('v2.invoice.detailshort');
        Route::post('save', 'InvoiceController@saveInvoice')->name('v2.invoice.save');
        Route::post('update-status', 'InvoiceController@updateStatus')->name('v2.invoice.updatestatus');
        Route::delete('delete-sell/{id}', 'InvoiceController@deleteInvoiceSell')->name('v2.invoice.deleteInvoiceSell');
        Route::post('shipping-sell/{id}', 'InvoiceController@updateStatusShipping')->name('v2.invoice.updateStatusShipping');
        Route::post('payment-debt/{id}', 'InvoiceController@paymentDebt')->name('v2.invoice.paymentDebt');
        Route::post('uploadxml', 'InvoiceController@uploadXml')->name('v2.invoice.uploadxml');
        Route::post('checkimport', 'InvoiceController@checkImportInvoice')->name('v2.invoice.checkimport');
        Route::post('import', 'InvoiceController@importInvoice')->name('v2.invoice.import');
        Route::post('payment', 'InvoiceController@paymentInvoice')->name('v2.invoice.payment');
        Route::post('payment-verify', 'InvoiceController@paymentVerify')->name('v2.invoice.paymentVerify');
        Route::get('history-payment/{type}', 'InvoiceController@getHistoryPayment')->name('v2.invoice.historyPayment');
    });

    Route::group([
        'prefix' => 'report'
    ], function () {
        Route::get('quality-control/list', 'NewReportController@qualityControlList')->name('v2.report.qualitycontrol.list');
        Route::post('quality-control/save', 'NewReportController@qualityControlSave')->name('v2.report.qualitycontrol.save');
        Route::get('quality-control/detail', 'NewReportController@qualityControlDetail')->name('v2.report.qualitycontrol.detail');
        Route::get('prescription-statistic', 'ReportController@getPrescriptionStatistic')->name('v2.report.prescriptionstatistic');
        Route::get('special-drug', 'ReportController@getSpecialDrugReport')->name('v2.report.specialdrug');
        Route::post('voucherscheck/check', 'VouchersCheckController@confirmVouchersCheck')->name('v2.report.voucherscheckcheck');
    });

    Route::group([
        'prefix' => 'cashbook'
    ], function () {
        Route::get('list', 'CashbookController@getList')->name('v2.cashbook.list');
        Route::post('save', 'CashbookController@save')->name('v2.cashbook.save');
        Route::post('cash-type', 'CashbookController@addCashType')->name('v2.cashbook.createCashType');
        Route::get('cash-type/{type}', 'CashbookController@getCashType')->name('v2.cashbook.getCashType');
        Route::get('code/{type}', 'CashbookController@getCodeCashbook')->name('v2.cashbook.getCode');
        Route::post('cancel/{id}', 'CashbookController@cancelCashbook')->name('v2.cashbook.cancel');
    });

    Route::group([
        'prefix' => 'order'
    ], function () {
        Route::get('list', 'OrderController@orderList')->name('v2.order.list');
        Route::get('listByDrug', 'OrderController@orderListByDrug')->name('v2.order.listByDrug');
        Route::get('detail', 'OrderController@orderDetail')->name('v2.order.detail');
        Route::post('save', 'OrderController@orderSave')->name('v2.order.save');
        Route::get('reserve', 'OrderController@orderReserve')->name('v2.order.reserve');
    });

    Route::group([
        'prefix' => 'user'
    ], function () {
        Route::get('refresh', 'UsersController@refresh')->name('v2.user.refresh');
        Route::post('refresh', 'UsersController@refresh')->name('v2.user.refresh');
        Route::post('settings', 'UsersController@settings')->name('v2.user.settings');
        Route::post('logout', 'UsersController@logout')->name('v2.user.logout');
        Route::get('list', 'UsersController@getList')->name('v2.user.list');
        Route::get('drugStore', 'UsersController@getDrugStoreInfo')->name('v2.user.drugstoreInfo');
        Route::post('create', 'UsersController@createByStore')->name('v2.user.create');
        Route::post('delete/{id}', 'UsersController@deleteByStore')->name('v2.user.delete');
    });

    Route::group([
        'prefix' => 'admin'
    ], function () {
        Route::get('drugstore/list', 'AdminController@filterDrugStore')->name('v2.admin.drugstore.list');
        Route::post('drugstore/save', 'AdminController@createOrUpdateDrugStore')->name('v2.admin.drugstore.save');
        Route::delete('drugstore/delete', 'AdminController@deleteDrugStore')->name('v2.admin.drugstore.delete');
        Route::post('drugstore/lock', 'AdminController@lockDrugStore')->name('v2.admin.drugstore.lock');
        Route::post('drugstore/copy', 'AdminController@copyDrugFromDrugStore')->name('v2.admin.drugstore.copy');
        Route::get('drugstore/check', 'AdminController@checkDrugStore')->name('v2.admin.drugstore.check');
        Route::get('drugstore/listBySource', 'AdminController@getDrugStoreListBySource')->name('v2.admin.drugstore.listBySource');

        Route::get('announcement/list', 'AdminController@announcementList')->name('v2.admin.announcement.list');
        Route::post('announcement/save', 'AdminController@announcementSave')->name('v2.admin.announcement.save');
        Route::get('user/list', 'AdminController@filterUser')->name('v2.admin.user.list');
        Route::post('user/save', 'AdminController@createOrUpdateUser')->name('v2.admin.user.save');
        Route::post('unit/save', 'AdminController@createOrUpdateUnit')->name('v2.admin.unit.save');
        Route::post('drug/save', 'AdminController@drugDQGSave')->name('v2.admin.drug.save');
        Route::get('bank', 'AdminController@getBanks')->name('v2.admin.bank.getAll');
    });

    Route::group([
        'prefix' => 'invoiceWarehouse'
    ], function () {
        Route::post('checkimport', 'InvoiceWarehouseController@checkImportInvoiceWarehouse')->name('v2.invoicewarehouse.checkimport');
        Route::post('import-invoice', 'InvoiceWarehouseController@importInvoiceWarehouse')->name('v2.invoicewarehouse.import');
        Route::post('{type}', 'InvoiceWarehouseController@invoiceWarehouseSave')->name('v2.invoicewarehouse.save');
        Route::get('filter/{type}', 'InvoiceWarehouseController@filterInvoiceWarehouse')->name('v2.invoicewarehouse.filter');
        Route::post('cancel/{id}', 'InvoiceWarehouseController@cancelInvoiceWarehouse')->name('v2.invoicewarehouse.cancel');
        Route::post('status/{id}', 'InvoiceWarehouseController@changeStatusInvoiceWarehouse')->name('v2.invoicewarehouse.changestatus');
        Route::get('{id}', 'InvoiceWarehouseController@detailInvoiceWarehouse')->name('v2.invoicewarehouse.detail');
    });

    Route::group([
        'prefix' => 'promotion'
    ], function () {
        Route::post('save', 'PromotionController@save')->name('v2.promotion.save');
        Route::get('filter', 'PromotionController@filter')->name('v2.promotion.filter');
        Route::delete('delete/{id}', 'PromotionController@delete')->name('v2.promotion.cancel');
        Route::post('status/{id}', 'PromotionController@changeStatus')->name('v2.promotion.changestatus');
        Route::get('{id}', 'PromotionController@detail')->name('v2.promotion.detail');
        Route::post('avaiable', 'PromotionController@getPromotionAvaiable')->name('v2.promotion.promotionavaiable');
    });

    /**
     * Chỉ để test
     */
    Route::group([
        'prefix' => 'department'
    ], function () {
        Route::get('list', 'DepartmentController@departmentList')->name('v2.department.list');
        Route::get('detail', 'DepartmentController@departmentDetail')->name('v2.department.detail');
        Route::post('save', 'DepartmentController@departmentSave')->name('v2.department.save');
    });
});
