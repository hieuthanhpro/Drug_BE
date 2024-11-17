<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 11/26/2018
 * Time: 3:49 PM
 */

namespace App\Providers;

use App\Repositories\AbstractBaseRepository;
use App\Repositories\AdminNotification\AdminNotificationEloquentRepository;
use App\Repositories\AdminNotification\AdminNotificationRepositoryInterface;
use App\Repositories\AdsTracking\AdsTrackingEloquentRepository;
use App\Repositories\AdsTracking\AdsTrackingRepositoryInterface;
use App\Repositories\Bank\BankEloquentRepository;
use App\Repositories\Bank\BankRepositoryInterface;
use App\Repositories\Cashbook\CashbookEloquentRepository;
use App\Repositories\Cashbook\CashbookRepositoryInterface;
use App\Repositories\CashType\CashTypeEloquentRepository;
use App\Repositories\CashType\CashTypeRepositoryInterface;
use App\Repositories\CheckDetail\CheckDetailEloquentRepository;
use App\Repositories\CheckDetail\CheckDetailRepositoryInterface;
use App\Repositories\Customer\CustomerEloquentRepository;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\Repositories\Debt\DebtEloquentRepository;
use App\Repositories\Debt\DebtRepositoryInterface;
use App\Repositories\DoseCategory\DoseCategoryEloquentRepository;
use App\Repositories\DoseCategory\DoseCategoryRepositoryInterface;
use App\Repositories\DoseDetail\DoseDetailEloquentRepository;
use App\Repositories\DoseDetail\DoseDetailRepositoryInterface;
use App\Repositories\DoseDrug\DosedrugEloquentRepository;
use App\Repositories\DoseDrug\DoseDrugRepositoryInterface;
use App\Repositories\DoseGroup\DoseGroupRepositoryInterface;
use App\Repositories\DoseGroup\DoseGroupEloquentRepository;
use App\Repositories\Drug\DrugEloquentRepository;
use App\Repositories\DrugMaster\DrugMasterEloquentRepository;
use App\Repositories\Drug\DrugRepositoryInterface;
use App\Repositories\DrugMaster\DrugMasterRepositoryInterface;
use App\Repositories\Invoice\InvoiceEloquentRepository;
use App\Repositories\Invoice\InvoiceRepositoryInterface;
use App\Repositories\InvoiceDetail\InvoiceDetailEloquentRepository;
use App\Repositories\InvoiceDetail\InvoiceDetailRepositoryInterface;
use App\Repositories\InvoiceDetailTmp\InvoiceDetailTmpEloquentRepository;
use App\Repositories\InvoiceDetailTmp\InvoiceDetailTmpRepositoryInterface;
use App\Repositories\InvoiceDose\InvoiceDoseEloquentRepository;
use App\Repositories\InvoiceDose\InvoiceDoseRepositoryInterface;
use App\Repositories\InvoiceTmp\InvoiceTmpEloquentRepository;
use App\Repositories\InvoiceTmp\InvoiceTmpRepositoryInterface;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseEloquentRepository;
use App\Repositories\InvoiceWarehouse\InvoiceWarehouseRepositoryInterface;
use App\Repositories\NotificationTemplate\NotificationTemplateEloquentRepository;
use App\Repositories\NotificationTemplate\NotificationTemplateRepositoryInterface;
use App\Repositories\PaymentLogs\PaymentLogsEloquentRepository;
use App\Repositories\PaymentLogs\PaymentLogsRepositoryInterface;
use App\Repositories\PermissionGroup\PermissionGroupEloquentRepository;
use App\Repositories\PermissionGroup\PermissionGroupRepositoryInterface;
use App\Repositories\PermissionRole\PermissionRoleEloquentRepository;
use App\Repositories\PermissionRole\PermissionRoleRepositoryInterface;
use App\Repositories\Permissions\PermissionsEloquentRepository;
use App\Repositories\Permissions\PermissionsRepositoryInterface;
use App\Repositories\Prescription\PrescriptionEloquentRepository;
use App\Repositories\Prescription\PrescriptionRepositoryInterface;
use App\Repositories\PriceRule\PriceRuleEloquentRepository;
use App\Repositories\PriceRule\PriceRuleRepositoryInterface;
use App\Repositories\Promotion\PromotionEloquentRepository;
use App\Repositories\Promotion\PromotionRepositoryInterface;
use App\Repositories\PromotionLogs\PromotionLogsEloquentRepository;
use App\Repositories\PromotionLogs\PromotionLogsRepositoryInterface;
use App\Repositories\Report\ReportEloquentRepository;
use App\Repositories\Report\ReportRepositoryInterface;
use App\Repositories\RepositoryInterface;
use App\Repositories\StoreNotification\StoreNotificationEloquentRepository;
use App\Repositories\StoreNotification\StoreNotificationRepositoryInterface;
use App\Repositories\Supplier\SupplierEloquentRepository;
use App\Repositories\Supplier\SupplierRepositoryInterface;
use App\Repositories\TDrugUnit\TDrugUnitEloquentRepository;
use App\Repositories\TDrugUnit\TDrugUnitRepositoryInterface;
use App\Repositories\TOrder\TOrderEloquentRepository;
use App\Repositories\TOrder\TOrderRepositoryInterface;
use App\Repositories\Unit\UnitEloquentRepository;
use App\Repositories\Unit\UnitRepositoryInterface;
use App\Repositories\Vouchers\VouchersEloquentRepository;
use App\Repositories\Vouchers\VouchersRepositoryInterface;
use App\Repositories\VouchersCheck\VouchersCheckEloquentRepository;
use App\Repositories\VouchersCheck\VouchersCheckRepositoryInterface;
use App\Repositories\Warehouse\WarehouseRepositoryInterface;
use App\Repositories\Warehouse\WarehouseEloquentRepository;
use App\Repositories\DrugCategory\DrugCategoryEloquentRepository;
use App\Repositories\DrugCategory\DrugCategoryRepositoryInterface;
use App\Repositories\DrugGroup\DrugGroupEloquentRepository;
use App\Repositories\DrugGroup\DrugGroupRepositoryInterface;
use App\Repositories\DrugStore\DrugStoreEloquentRepository;
use App\Repositories\DrugStore\DrugStoreRepositoryInterface;
use App\Repositories\Role\RoleEloquentRepository;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\Users\UserEloquentRepository;
use App\Repositories\Users\UserRepositoryInterface;
use App\Repositories\WarehouseLog\WarehouseLogEloquentRepository;
use App\Repositories\WarehouseLog\WarehouseLogRepositoryInterface;
use App\Repositories\LinkAds\LinkAdsEloquentRepository;
use App\Repositories\LinkAds\LinkAdsRepositoryInterface;
use App\Repositories\Order\OrderRepositoryInterface;
use App\Repositories\Order\OrderEloquentRepository;
use App\Repositories\OrderDetail\OrderDetailRepositoryInterface;
use App\Repositories\OrderDetail\OrderDetailEloquentRepository;
use App\Repositories\OrderTmp\OrderTmpRepositoryInterface;
use App\Repositories\OrderTmp\OrderTmpEloquentRepository;
use App\Repositories\OrderDetailTmp\OrderDetailTmpRepositoryInterface;
use App\Repositories\OrderDetailTmp\OrderDetailTmpEloquentRepository;
use App\Repositories\OrderDetailAdmin\OrderDetailAdminEloquentRepository;
use App\Repositories\OrderDetailAdmin\OrderDetailAdminRepositoryInterface;

use Illuminate\Support\ServiceProvider;
use App\LibExtension\LogEx;

/**
 * Class RepositoryServiceProvider
 * @package App\Providers
 */
class RepositoryServiceProvider extends ServiceProvider
{
    protected $className = "RepositoryServiceProvider";
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        LogEx::registerName($this->className, 'register');

        $this->app->bind(RepositoryInterface::class, AbstractBaseRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserEloquentRepository::class);
        $this->app->bind(DrugStoreRepositoryInterface::class, DrugStoreEloquentRepository::class);
        $this->app->bind(DrugGroupRepositoryInterface::class, DrugGroupEloquentRepository::class);
        $this->app->bind(DrugCategoryRepositoryInterface::class, DrugCategoryEloquentRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleEloquentRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseEloquentRepository::class);
        $this->app->bind(DrugRepositoryInterface::class, DrugEloquentRepository::class);
        $this->app->bind(UnitRepositoryInterface::class, UnitEloquentRepository::class);
        $this->app->bind(InvoiceDetailRepositoryInterface::class, InvoiceDetailEloquentRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceEloquentRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerEloquentRepository::class);
        $this->app->bind(WarehouseLogRepositoryInterface::class, WarehouseLogEloquentRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierEloquentRepository::class);
        $this->app->bind(DebtRepositoryInterface::class, DebtEloquentRepository::class);
        $this->app->bind(PrescriptionRepositoryInterface::class, PrescriptionEloquentRepository::class);
        $this->app->bind(DrugMasterRepositoryInterface::class, DrugMasterEloquentRepository::class);
        $this->app->bind(VouchersRepositoryInterface::class, VouchersEloquentRepository::class);
        $this->app->bind(InvoiceTmpRepositoryInterface::class, InvoiceTmpEloquentRepository::class);
        $this->app->bind(InvoiceDetailTmpRepositoryInterface::class, InvoiceDetailTmpEloquentRepository::class);
        $this->app->bind(VouchersCheckRepositoryInterface::class, VouchersCheckEloquentRepository::class);
        $this->app->bind(CheckDetailRepositoryInterface::class, CheckDetailEloquentRepository::class);
        $this->app->bind(PermissionsRepositoryInterface::class, PermissionsEloquentRepository::class);
        $this->app->bind(PermissionGroupRepositoryInterface::class, PermissionGroupEloquentRepository::class);
        $this->app->bind(PermissionRoleRepositoryInterface::class, PermissionRoleEloquentRepository::class);
        $this->app->bind(DoseCategoryRepositoryInterface::class, DoseCategoryEloquentRepository::class);
        $this->app->bind(DoseGroupRepositoryInterface::class, DoseGroupEloquentRepository::class);
        $this->app->bind(DoseDrugRepositoryInterface::class, DosedrugEloquentRepository::class);
        $this->app->bind(DoseDetailRepositoryInterface::class, DoseDetailEloquentRepository::class);
        $this->app->bind(InvoiceDoseRepositoryInterface::class, InvoiceDoseEloquentRepository::class);
        $this->app->bind(LinkAdsRepositoryInterface::class, LinkAdsEloquentRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderEloquentRepository::class);
        $this->app->bind(OrderDetailRepositoryInterface::class, OrderDetailEloquentRepository::class);
        $this->app->bind(OrderTmpRepositoryInterface::class, OrderTmpEloquentRepository::class);
        $this->app->bind(OrderDetailTmpRepositoryInterface::class, OrderDetailTmpEloquentRepository::class);
        $this->app->bind(OrderDetailAdminRepositoryInterface::class, OrderDetailAdminEloquentRepository::class);
        $this->app->bind(NotificationTemplateRepositoryInterface::class, NotificationTemplateEloquentRepository::class);
        $this->app->bind(AdminNotificationRepositoryInterface::class, AdminNotificationEloquentRepository::class);
        $this->app->bind(StoreNotificationRepositoryInterface::class, StoreNotificationEloquentRepository::class);
        $this->app->bind(CashbookRepositoryInterface::class, CashbookEloquentRepository::class);
        $this->app->bind(CashTypeRepositoryInterface::class, CashTypeEloquentRepository::class);
        $this->app->bind(InvoiceWarehouseRepositoryInterface::class, InvoiceWarehouseEloquentRepository::class);
        $this->app->bind(PromotionRepositoryInterface::class, PromotionEloquentRepository::class);
        $this->app->bind(PriceRuleRepositoryInterface::class, PriceRuleEloquentRepository::class);
        $this->app->bind(PromotionLogsRepositoryInterface::class, PromotionLogsEloquentRepository::class);
        $this->app->bind(TDrugUnitRepositoryInterface::class, TDrugUnitEloquentRepository::class);
        $this->app->bind(PaymentLogsRepositoryInterface::class, PaymentLogsEloquentRepository::class);
        $this->app->bind(TOrderRepositoryInterface::class, TOrderEloquentRepository::class);
        $this->app->bind(AdsTrackingRepositoryInterface::class, AdsTrackingEloquentRepository::class);
        $this->app->bind(BankRepositoryInterface::class, BankEloquentRepository::class);
    }
}
