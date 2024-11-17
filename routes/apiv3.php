<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'Backend',
], function () {
    Route::get('/v3/invoice/payment-ipn', 'InvoiceController@paymentIPN')
        ->name('v3.invoice.paymentIPN');
    Route::group([
        'prefix' => 'auth'
    ], function () {
        Route::post('login', 'UsersController@login')
            ->name('user.login');
    });
    Route::group(['middleware' => ['auth']], function () {
        Route::group([
            'prefix' => 'auth'
        ], function () {
            Route::post('logout', 'UsersController@logout')->name('user.logout');
        });

        Route::group([
            'prefix' => 'dashboard'
        ], function () {
            Route::get('chart', 'DashboardController@getChartDataV3')
                ->name('v3.dashboard.chart');
            Route::get('activities', 'DashboardController@getActivitiesV3')
                ->name('v3.dashboard.activities');
            Route::get('statistic', 'DashboardController@getStatisticV3')
                ->name('v2.dashboard.statistic');
            Route::get('warningdate', 'DashboardController@getWarningDateItemsV3')
                ->name('v3.dashboard.warningdate');
            Route::get('warningquantity', 'DashboardController@getWarningQuantityItemsV3')
                ->name('v3.dashboard.warningquantity');
            Route::get('warningquantitynew', 'DashboardController@dashboardGetWarningQuantityDrugNewV3')
                ->name('v3.dashboard.warningquantity.new');
            Route::post('showdrugstore', 'DashboardController@dashboardGetDrugStoreByDrugV3')
                ->name('v3.dashboard.drugStore.new');
            Route::get('promotion', 'DashboardController@getPromotionDataV3')
                ->name('v2.dashboard.promotion');
            Route::get('notification', 'NotificationController@getNewestNotifications')
                ->name('v3.notification.getNewestNotification');
            Route::get('notification/read/{id}', 'NotificationController@readNotification')
                ->name('v3.notification.readNotification');
            //user checking
            Route::post('userTracking/views', 'DashboardController@addViews')
                ->name('v3.adsChecking.view');
            Route::get('userTracking/filter', 'DashboardController@getAdsTracking')
                ->name('v3.getAdsChecking.view');
            Route::post('userTracking/export', 'DashboardController@exportAdsTracking')
                ->name('v3.getAdsChecking.export');
            //apiv3
            Route::get('chart/invoice', 'DashboardController@invoiceViews')
                ->name('v3.adsChecking.view');
            Route::get('chart/report-money', 'DashboardController@reportMoney')
                ->name('v3.adsChecking.reportMoney');
            Route::get('chart/top-product', 'DashboardController@topProduct')
                ->name('v3.adsChecking.topProduct');
            Route::get('chart/invoice-today-week', 'DashboardController@invoiceTodayWeek')
                ->name('v3.adsChecking.invoiceTodayWeek');
            Route::get('chart/real-revenue', 'DashboardController@realRevenue')
                ->name('v3.real.revenue');
            Route::get('chart/gum-revenue', 'DashboardController@gumRevenue')
                ->name('v3.gum.revenue');
        });

        Route::group([
            'prefix' => 'master'
        ], function () {
            Route::get('drug-group/list', 'DrugGroupController@getList')
                ->name('v3.master.group');
            Route::get('drug-group/list-filter', 'DrugGroupController@getListFilter')
                ->name('v3.master.groupFilter');
            Route::get('drug-category/list', 'DrugCategoryController@getList')
                ->name('v3.master.category');
            Route::get('drug-category/list-filter', 'DrugCategoryController@getListFilter')
                ->name('v3.master.categoryFilter');
            Route::get('customer/list', 'CustomerController@getListV3')
                ->name('v3.master.customer.list');
            Route::post('customer/list/export', 'CustomerController@exportListV3')
                ->name('v3.master.customer.list.export');
            Route::get('customer/history', 'CustomerController@getHistoryV3')
                ->name('v3.master.customer.history');
            Route::get('customer/getlistinvoice', 'CustomerController@getListInvoice')
                ->name('v2.master.customer.getlistinvoice');
            Route::get('supplier/list', 'SupplierController@getListV3')
                ->name('v3.master.supplier.list');
            Route::post('supplier/list/export', 'SupplierController@exportListV3')
                ->name('v3.master.supplier.list.export');
            Route::get('supplier/history', 'SupplierController@getHistory')
                ->name('v3.master.supplier.history');
            Route::get('supplier/getlistinvoice', 'SupplierController@getListInvoice')
                ->name('v2.master.supplier.getlistinvoice');
            Route::post('user/setting', 'UsersController@setUserSetting')
                ->name('v2.master.usersetting');
            Route::post('drugstore/setting', 'UsersController@setUserSettingV3')
                ->name('v3.master.usersetting');
        });

        Route::group([
            'prefix' => 'drug'
        ], function () {
            Route::get('filter', 'DrugController@filter')
                ->name('v3.drug.filter');
            Route::get('filter-master-data', 'DrugController@filterDrugMaster')
                ->name('v3.drug.filterDrugMaster');
            Route::get('detail/{id}', 'DrugController@detail')
                ->name('v3.drug.detail');
            Route::get('filter-for-sale', 'DrugController@filterForSale')
                ->name('v3.drug.filterForSale');
            Route::post('delete/{id}', 'DrugController@delete')
                ->name('v3.drug.delete');
            Route::post('updateStatus', 'DrugController@updateStatusV3')
                ->name('v3.drug.updateStatus');
            Route::post('save', 'DrugController@save')
                ->name('v3.drug.save');
            Route::post('export', 'DrugController@export')
                ->name('v3.drug.export');
            Route::get('autoListFavorite/{textInput}', 'DrugController@autoListFavoriteV3')
                ->name('v3.drug.autoListFavorite');
            //old drug
            Route::post('uploadzip', 'DrugController@uploadZipDrug')
                ->name('v3.drug.uploadzip');
            Route::post('add', 'DrugController@addDrug')
                ->name('v3.drug.add');//use
            Route::post('edit/{id}', 'DrugController@editDrug')
                ->name('v2.drug.edit');
            Route::post('updateunit/{id}', 'DrugController@updateUnit')
                ->name('v3.drug.udpateunit');//use
            Route::post('updatewarning/{id}', 'DrugController@updateWarning')
                ->name('v2.drug.udpatewarning');
            Route::post('updatestatus/{id}/{status}', 'DrugController@updateStatus')
                ->name('v2.drug.udpatestatus');
            Route::get('category/{id}', 'DrugController@drugsInCategory')->name('v3.drug.category');
            Route::get('group/{id}', 'DrugController@drugsInGroupV3')
                ->name('v3.drug.group');
            Route::get('list', 'DrugController@drugList')
                ->name('v2.drug.list');
            Route::get('scan-code', 'DrugController@drugScanBarcode')
                ->name('v2.drug.scan.barcode');
            Route::get('scan/{drugCode}/{number}', 'DrugController@drugScan')
                ->name('v2.drug.scan');
            Route::get('scan', 'DrugController@drugScanNew')
                ->name('v2.drug.scan.new');
            Route::get('search', 'DrugController@search')
                ->name('v2.drug.search');
            Route::post('import', 'DrugController@importDrugV3')
                ->name('v3.drug.import');
            Route::post('checkimport', 'DrugController@checkImportDrugV3')
                ->name('v3.drug.checkimport');
            Route::post('stock', 'DrugController@drugStock')
                ->name('v2.drug.stock');
            Route::get('filterDrugByCriteria', 'DrugController@filterDrugByCriteriaV3')
                ->name('v3.drug.filterDrugByCriteria');
            Route::post('clone', 'DrugController@drugClone')
                ->name('v2.drug.clone');
        });

        Route::group([
            'prefix' => 'category'
        ], function () {
            Route::get('filter', 'DrugCategoryController@filter')
                ->name('category.filter');
            Route::post('save', 'DrugCategoryController@save')
                ->name('category.save');
            Route::delete('delete/{id}', 'DrugCategoryController@delete')
                ->name('category.delete');
            Route::post('export', 'DrugCategoryController@export')
                ->name('category.export');
        });

        Route::group([
            'prefix' => 'group'
        ], function () {
            Route::get('filter', 'DrugGroupController@filter')
                ->name('group.filter');
            Route::post('save', 'DrugGroupController@save')
                ->name('group.save');
            Route::delete('delete/{id}', 'DrugGroupController@delete')
                ->name('group.delete');
            Route::post('export', 'DrugGroupController@export')
                ->name('group.export');
        });

        Route::group([
            'prefix' => 'unit'
        ], function () {
            Route::get('list', 'UnitController@index')
                ->name('v3.unit.list');
        });

        Route::group([
            'prefix' => 'invoice'
        ], function () {
            Route::get('filter', 'InvoiceController@filter')
                ->name('invoice.filter');//list vs list-tmp
            Route::post('export', 'InvoiceController@export')
                ->name('invoice.export');
            Route::post('createIV1TEST', 'InvoiceController@sellV3')
                ->name('v3.invoice.createiv1');
            //Route::post('sell', 'InvoiceController@sellV3')->name('v3.invoice.sell');
            Route::post('sell', 'InvoiceController@sell')
                ->name('v3.invoice.sell');
            Route::post('createIV1', 'InvoiceController@sellV3')
                ->name('v3.invoice.createiv1');
            Route::post('warehousing', 'InvoiceController@warehousingV3')
                ->name('v3.invoice.warehousingV3');
            //Route::post('warehousing', 'InvoiceController@warehousing')->name('v3.invoice.warehousingV3');
            //Route::post('warehousingtemp', 'InvoiceController@warehousingTemp')->name('v3.invoice.warehousingtemp');
            Route::post('warehousingtemp', 'InvoiceTmpController@store')
                ->name('v3.invoice.warehousingtemp');
            Route::get('warehousingstatistic', 'InvoiceController@warehousingStatisticV3')
                ->name('v3.invoice.warehousingstatistic');
            Route::post('export-warehousingstatistic', 'InvoiceController@exportWarehousingStatisticV3')
                ->name('v3.invoice.export.warehousingstatistic');
            Route::get('list-tmp', 'InvoiceTmpController@getListV3')
                ->name('v3.invoice.list-tmp');
            Route::post('list-tmp/export', 'InvoiceTmpController@exportTMP')
                ->name('v3.invoice.list-tmp');
            Route::get('detail/{type}/{code}', 'InvoiceController@getInvoiceDetailV3')
                ->name('v3.invoice.detail');
            Route::get('detail-new/{type}/{code}', 'InvoiceController@getInvoiceDetailV3New')
                ->name('v3.invoice.detail.new');
            Route::get('detail-short/{type}/{code}', 'InvoiceController@getInvoiceDetailShortV3')
                ->name('v3.invoice.detailshort');
            //Route::post('save', 'InvoiceController@store')->name('v3.invoice.save');
            Route::post('save', 'InvoiceController@saveInvoiceV3')
                ->name('v3.invoice.save');
            Route::post('update-status', 'InvoiceController@updateStatusV3')
                ->name('v3.invoice.updatestatus');
            Route::delete('delete-sell/{id}', 'InvoiceController@deleteInvoiceSell')
                ->name('v3.invoice.deleteInvoiceSell');
            Route::post('shipping-sell/{id}', 'InvoiceController@updateStatusShippingV3')
                ->name('v3.invoice.updateStatusShipping');
            Route::post('payment-debt/{id}', 'InvoiceController@paymentDebtV3')
                ->name('v3.invoice.paymentDebt');
            Route::post('uploadxml', 'InvoiceController@uploadXml')
                ->name('v3.invoice.uploadxml');
            Route::post('checkimport', 'InvoiceController@checkImportInvoiceV3')
                ->name('v3.invoice.checkimport');
            Route::post('import', 'InvoiceController@importInvoiceV3')
                ->name('v3.invoice.import');//pending
            Route::post('import-invoice-excel', 'InvoiceController@importInvoiceExcel')
                ->name('v3.invoice.importInvoice');
            Route::post('payment', 'InvoiceController@paymentInvoice')
                ->name('v3.invoice.payment');
            Route::post('payment-verify', 'InvoiceController@paymentVerify')
                ->name('v3.invoice.paymentVerify');
            Route::get('history-payment/{type}', 'InvoiceController@getHistoryPayment')
                ->name('v3.invoice.historyPayment');
            Route::post('history-payment/{type}/export', 'InvoiceController@exportHistoryPayment')
                ->name('v3.invoice.export.historyPayment');
            Route::get('invoice_return/{id}', 'InvoiceController@getInvoiceReturn')
                ->name('v3.invoice.return');
            Route::get('/detail_invoice/{id}', 'InvoiceController@getDetailInvoice')
                ->name('V3.invoice.detail');
            Route::resource('invoice_tmp', 'InvoiceTmpController');
        });

        Route::group([
            'prefix' => 'user'
        ], function () {
            Route::get('refresh', 'UsersController@refresh')
                ->name('v2.user.refresh');
            Route::post('refresh', 'UsersController@refresh')
                ->name('v2.user.refresh');
            Route::post('settings', 'UsersController@settings')
                ->name('v2.user.settings');
            Route::post('logout', 'UsersController@logout')
                ->name('v2.user.logout');
            Route::get('list', 'UsersController@getList')
                ->name('v3.user.list');
            Route::post('list/export', 'UsersController@exportList')
                ->name('v3.user.export');
            Route::get('drugStore', 'UsersController@getDrugStoreInfo')
                ->name('v3.user.drugstoreInfo');
            Route::post('create', 'UsersController@createByStore')
                ->name('v3.user.create');
            Route::post('delete/{id}', 'UsersController@deleteByStore')
                ->name('v2.user.delete');

            //Route::post('create', 'UsersController@create'); khong dung nua
            Route::post('change-pass', 'UsersController@changePass')
                ->name('v3.user.changePass');
            Route::post('update/{id}', 'UsersController@update')
                ->name('v3.user.update');
            //Route::get('delete/{id}', 'UsersController@delete');khong dung nua

            Route::get('resetpass/{id}', 'UsersController@resetPassword');
            Route::get('notifications', 'UsersController@getNotifications');
            Route::post('notification/mark-as-read/{id}', 'UsersController@markAsReadNotification');
        });

        Route::group([
            'prefix' => 'customer'
        ], function () {
            Route::get('filter', 'CustomerController@filter')
                ->name('v3.customer.filter');
            Route::post('export', 'CustomerController@export')
                ->name('v3.customer.export');
            Route::post('save', 'CustomerController@save')
                ->name('v3.customer.save');
        });

        Route::group([
            'prefix' => 'warehouse'
        ], function () {
            Route::get('/invoices/filter', 'InvoiceWarehouseController@filter')
                ->name('V3.warehouseInvoice.filter');//invoiceWarehouse/filter/{type}
            Route::post('/invoices/export', 'InvoiceWarehouseController@export')
                ->name('V3.warehouseInvoice.export');//invoiceWarehouse/filter/{type}
            Route::get('/filter', 'WarehouseController@filter')
                ->name('warehouse.filter');
            Route::post('/export', 'WarehouseController@export')
                ->name('warehouse.export');
            // ######## Autocomplete: search data ########
            // Sale drug for customer, guest
            Route::get('/autoListWithPackages4SaleFavorite', 'WarehouseController@autoListWithPackages4SaleFavorite')
                ->name('v3.warehouse.autoWithPacks4SaleFavorite');
            Route::get('/getListUnitByDrug4Sale/{drug_id}', 'WarehouseController@getListUnitByDrug4Sale')
                ->name('v3.warehouse.listUnitByDrug4Sale');
            Route::get('/stockList/', 'WarehouseController@getStockListV3')
                ->name('v3.warehouse.stocklist');
            Route::post('/stockList-export/', 'WarehouseController@exportStockListV3')
                ->name('v3.warehouse.export.stocklist');
        });

        Route::group([
            'prefix' => 'report'
        ], function () {
            Route::get('/revenue', 'ReportController@filterRevenue')
                ->name('report.filterRevenue');
            Route::get('/revenue-profit', 'ReportController@filterRevenueProfit')
                ->name('V3.report.filterRevenueProfit');
            Route::get('/sales-person', 'ReportController@filterSalePerson')
                ->name('report.filterSalePerson');
            //Route::get('/warehouse-sell', 'ReportController@filterGoodsInOut')->name('report.filterGoodsInOut');
            Route::get('/goods-in-out', 'ReportController@filterGoodsInOut')
                ->name('v3.report.filterGoodsInOut');
            Route::post('/revenue/export', 'ReportController@exportRevenue')
                ->name('report.exportRevenue');
            Route::post('/revenue-profit/export', 'ReportController@exportRevenueProfit')
                ->name('report.exportRevenueProfit');
            Route::post('/sale-person/export', 'ReportController@exportSalePerson')
                ->name('report.exportSalePerson');
            //Route::post('/warehouse-sell/export', 'ReportController@exportGoodsInOut')->name('report.exportGoodsInOut');goods-in-out
            Route::post('/goods-in-out/export', 'ReportController@exportGoodsInOut')
                ->name('report.exportGoodsInOut');
            /*from api v2*/
            Route::post('quality-control/export', 'NewReportController@exporQualityControlListV3')
                ->name('v3.report.qualitycontrol.export');
            Route::get('quality-control/list', 'NewReportController@qualityControlListV3')
                ->name('v3.report.qualitycontrol.list');
            Route::post('quality-control/save', 'NewReportController@qualityControlSave')
                ->name('v3.report.qualitycontrol.save');
            Route::get('quality-control/detail', 'NewReportController@qualityControlDetailV3')
                ->name('v3.report.qualitycontrol.detail');
            Route::get('prescription-statistic', 'ReportController@getPrescriptionStatisticV3')
                ->name('v3.report.prescriptionstatistic');
            Route::post('prescription-statistic/export', 'ReportController@exporPrescriptionStatisticV3')
                ->name('v3.export.prescriptionstatistic');
            Route::get('special-drug', 'ReportController@getSpecialDrugReport')
                ->name('v3.report.specialdrug');
            Route::post('special-drug/export', 'ReportController@getSpecialDrugReportExport')
                ->name('v3.report.specialdrug.export');
            Route::post('voucherscheck/check', 'VouchersCheckController@confirmVouchersCheckV3')
                ->name('v3.report.voucherscheckcheck');
            /*api báo cáo*/
            Route::get('/inventory', 'ReportController@reportInventory')
                ->name('report.inventory');
            Route::get('/reportdrug', 'ReportController@reportDrugByType')
                ->name('report.reportdrug');
            Route::get('/revenue_money', 'ReportController@reportRevenueMoneyV3')
                ->name('v3.report.revenuemoney');
            Route::post('/revenue_money/export', 'ReportController@reportRevenueMoneyExportV3')
                ->name('v3.report.revenuemoney');
            Route::get('/revenue_invoice', 'ReportController@reportRevenueInvoice')
                ->name('report.revenueinvoice');
            Route::get('/revenue_drug', 'ReportController@reportRevenueDrug')
                ->name('report.revenuedrug');
            Route::get('/export-import', 'ReportController@getExportImport')
                ->name('report.export-import');
            // Route::get('/goods-in-out', 'ReportController@getGoodsInOut')->name('report.good-in-out');
            // Route::get('/sales-person', 'ReportController@getSalesPerson')->name('report.sales-person');
            //Route::get('/revenue-profit', 'ReportController@getRevenueProfit')->name('report.revenueprofit');
        });

        Route::group([
            'prefix' => 'sales'
        ], function () {
            Route::post('save', 'InvoiceController@saveInvoiceSales')
                ->name('InvoiceSales.save');

        });
        //giu nguyen ham cua v2
        Route::group([
            'prefix' => 'cashbook'
        ], function () {
            Route::get('list', 'CashbookController@getList')
                ->name('v3.cashbook.list');
            Route::post('list-export', 'CashbookController@export')
                ->name('v3.export.cashbook.list');
            Route::post('save', 'CashbookController@save')
                ->name('v3.cashbook.save');
            Route::post('cash-type', 'CashbookController@addCashType')
                ->name('v3.cashbook.createCashType');
            Route::get('cash-type/{type}', 'CashbookController@getCashType')
                ->name('v3.cashbook.getCashType');
            Route::get('code/{type}', 'CashbookController@getCodeCashbook')
                ->name('v3.cashbook.getCode');
            Route::post('cancel/{id}', 'CashbookController@cancelCashbook')
                ->name('v3.cashbook.cancel');
        });

        Route::group([
            'prefix' => 'order'
        ], function () {
            Route::get('list', 'OrderController@orderListV3')
                ->name('v3.order.list');
            Route::post('list-export', 'OrderController@orderExportV3')
                ->name('v3.order.export.list');
            Route::get('listByDrug', 'OrderController@orderListByDrugV3')
                ->name('v3.order.listByDrug');
            Route::post('listbydrug-export', 'OrderController@orderExportListByDrugV3')
                ->name('v3.order.export.listByDrug');
            Route::get('detail', 'OrderController@orderDetailV3')
                ->name('v3.order.detail');
            Route::get('detail-new', 'OrderController@orderDetailV3New')
                ->name('v3.order.detail.new');
            Route::post('save', 'OrderController@orderSaveV3')
                ->name('v3.order.save');
            Route::get('reserve', 'OrderController@orderReserveV3')
                ->name('v3.order.reserve');
        });
        //admin
        Route::group([
            'prefix' => 'admin'
        ], function () {
            Route::get('drugstore/list', 'AdminController@filterDrugStore')
                ->name('v3.admin.drugstore.list');
            Route::post('drugstore/save', 'AdminController@createOrUpdateDrugStore')
                ->name('v3.admin.drugstore.save');
            Route::delete('drugstore/delete', 'AdminController@deleteDrugStore')
                ->name('v3.admin.drugstore.delete');
            Route::post('drugstore/lock', 'AdminController@lockDrugStore')
                ->name('v3.admin.drugstore.lock');
            Route::post('drugstore/copy', 'AdminController@copyDrugFromDrugStore')
                ->name('v3.admin.drugstore.copy');
            Route::get('drugstore/check', 'AdminController@checkDrugStore')
                ->name('v3.admin.drugstore.check');
            Route::get('drugstore/listBySource', 'AdminController@getDrugStoreListBySource')
                ->name('v3.admin.drugstore.listBySource');
            Route::get('announcement/list', 'AdminController@announcementList')
                ->name('v2.admin.announcement.list');
            Route::post('announcement/save', 'AdminController@announcementSave')
                ->name('v2.admin.announcement.save');
            Route::get('user/list', 'AdminController@filterUser')
                ->name('v3.admin.user.list');
            Route::post('user/save', 'AdminController@createOrUpdateUser')
                ->name('v3.admin.user.save');
            Route::post('unit/save', 'AdminController@createOrUpdateUnit')
                ->name('v3.admin.unit.save');
            Route::post('drug/save', 'AdminController@createOrUpdateDrugDQG')
                ->name('v3.admin.drug.save');
            Route::get('bank', 'AdminController@getBanks')
                ->name('v3.admin.bank.getAll');
        });
        //invoiceWarehouse
        Route::group([
            'prefix' => 'invoiceWarehouse'
        ], function () {
            Route::post('checkimport', 'InvoiceWarehouseController@checkImportInvoiceWarehouseV3')
                ->name('v2.invoicewarehouse.checkimport');
            Route::post('import-invoice', 'InvoiceWarehouseController@importInvoiceWarehouseV3')
                ->name('v2.invoicewarehouse.import');
            Route::post('{type}', 'InvoiceWarehouseController@invoiceWarehouseSave')
                ->name('v3.invoicewarehouse.save');
            //Route::get('filter/{type}', 'InvoiceWarehouseController@filterInvoiceWarehouse')->name('v2.invoicewarehouse.filter');
            Route::post('cancel/{id}', 'InvoiceWarehouseController@cancelInvoiceWarehouse')
                ->name('v2.invoicewarehouse.cancel');
            Route::post('status/{id}', 'InvoiceWarehouseController@changeStatusInvoiceWarehouse')
                ->name('v3.invoicewarehouse.changestatus');
            Route::get('{id}', 'InvoiceWarehouseController@detailInvoiceWarehouseV3')
                ->name('v3.invoicewarehouse.detail');
        });
        //promotion
        Route::group([
            'prefix' => 'promotion'
        ], function () {
            Route::post('save', 'PromotionController@save')
                ->name('v2.promotion.save');
            Route::get('filter', 'PromotionController@filter')
                ->name('v3.promotion.filter');
            Route::delete('delete/{id}', 'PromotionController@delete')
                ->name('v3.promotion.cancel');
            Route::post('status/{id}', 'PromotionController@changeStatus')
                ->name('v3.promotion.changestatus');
            Route::get('{id}', 'PromotionController@detail')
                ->name('v3.promotion.detail');
            Route::post('avaiable', 'PromotionController@getPromotionAvaiable')
                ->name('v3.promotion.promotionavaiable');
        });
        /*chi tiết phiếu tạm*/
        Route::get('/invoice_tmp/detail/{id}', 'InvoiceTmpController@getDetailByIdV3')
            ->name('v3.invoice_tmp.detail');
        Route::get('/invoice_tmp/detail-new/{id}', 'InvoiceTmpController@getDetailByIdV3New')
            ->name('invoice_tmp.detail.new');
        Route::get('/order_tmp/detail/{id}', 'OrderTmpController@getDetailOrder')
            ->name('order_tmp.detail');
        Route::get('/vouchers_check/{id}', 'VouchersCheckController@getDetail')
            ->name('v3.vouchers_check.detail');
        Route::get('vouchers_check/cancel/{id}', 'VouchersCheckController@cancel')
            ->name('vouchers_check.cancel');
        Route::get('/vouchers_check/detail/list', 'VouchersCheckController@getDetailList')
            ->name('vouchers_check.detail_list');
        /*
         * api v3
         * end api báo cáo
         */
        //drug-category
        Route::resource('drug-category', 'DrugCategoryController');
        //customer
        Route::resource('customer', 'CustomerController');
        //supplier converted v3
        Route::resource('supplier', 'SupplierController');
        //drug-group
        Route::resource('drug-group', 'DrugGroupController');
        //unit
        Route::resource('unit', 'UnitController');
        //vouchers_check
        Route::post('vouchers_check/export', 'VouchersCheckController@export')
            ->name('v3.vouchersCheck.export');
        Route::resource('vouchers_check', 'VouchersCheckController');
        // ######## Autocomplete: search data ########
        // Import drug page
        Route::get('/drug/autoListWithPackages4Import/{textInput}', 'DrugController@autoListWithPackages4ImportShort')
            ->name('drug.autoWithPacks4ImportShort');
        Route::get('/drug/autoListWithPackages4Import/{modeSearch}/{textInput}', 'DrugController@autoListWithPackages4Import')
            ->name('drug.autoWithPacks4Import');
        //Route::get('/drug/autoListFavorite/{textInput}', 'DrugController@autoListFavorite')->name('drug.autoListFavorite');
        Route::get('/warehouse/getListUnitByDrug/{drug_id}', 'WarehouseController@getListUnitByDrug')
            ->name('warehouse.listUnitByDrug');
        // Sale drug for customer, guest
        Route::get('/warehouse/autoListWithPackages4Sale/{textInput}', 'WarehouseController@autoListWithPackages4SaleShort')
            ->name('warehouse.autoWithPacks4SaleShort');
        Route::get('/warehouse/autoListWithPackages4Sale/{modeSearch}/{textInput}', 'WarehouseController@autoListWithPackages4Sale')
            ->name('warehouse.autoWithPacks4Sale');
        //Route::get('/warehouse/getListUnitByDrug4Sale/{drug_id}', 'WarehouseController@getListUnitByDrug4Sale')->name('warehouse.listUnitByDrug4Sale');
        Route::post('/warehouse/getListUnitByDrugIds4Sale', 'WarehouseController@getListUnitByDrugIds4Sale')
            ->name('warehouse.listUnitByDrugIds4Sale');
        //Route::get('/warehouse/autoListWithPackages4SaleFavorite/', 'WarehouseController@autoListWithPackages4SaleFavorite')->name('warehouse.autoWithPacks4SaleFavorite');
        //Route::get('/warehouse/stockList/', 'WarehouseController@getStockList')->name('warehouse.stocklist');
        Route::post('/warehouse/export-in-out/', 'WarehouseController@exportInOut')
            ->name('v3.warehouse.export.inOut');
        Route::get('/warehouse/inOut/', 'WarehouseController@getWarehouseInOutV3')
            ->name('v3.warehouse.inOut');
        //Route::get('/warehouse/inOut/', 'WarehouseController@getWarehouseInOut')->name('v3.warehouse.inOut');
        Route::get('/drugmaster', 'DrugMasterController@getDrugMasterByNameV3')
            ->name('v3.drug.allwithunit');
    });
});
