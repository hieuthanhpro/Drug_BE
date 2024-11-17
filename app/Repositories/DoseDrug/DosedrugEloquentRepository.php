<?php
/**
 * Created by PhpStorm.
 * User: sonmh
 * Date: 12/6/2018
 * Time: 10:43 AM
 */
namespace App\Repositories\DoseDrug;

use App\Models\DoseDrug;
use App\Repositories\AbstractBaseRepository;
use Illuminate\Support\Facades\DB;
use App\LibExtension\LogEx;
class DosedrugEloquentRepository extends AbstractBaseRepository implements DoseDrugRepositoryInterface
{
    protected $className = "DosedrugEloquentRepository";
    public function __construct(DoseDrug $model)
    {
        LogEx::constructName($this->className, '__construct');

        parent::__construct($model);
    }

    public function getListDoseDrug($drug_store_id,$name,$category,$group){
        LogEx::methodName($this->className, 'getListDoseDrug');

        $drug_dose = DoseDrug::leftJoin('dose_group', 'dose_group.id', '=', 'dose_drug.group_id')
            ->leftJoin('dose_category', 'dose_category.id', '=', 'dose_drug.category_id')
            ->select(
                'dose_drug.*',
                'dose_group.name as group_name',
                'dose_category.name as category_name'
            );

        if ($name != null) {
            $drug_dose = $drug_dose->where('dose_drug.name', 'ilike', '%' . $name . '%');
        }

        if ($category != null) {
            $drug_dose = $drug_dose->where('dose_category.id', $category);
        }
        if ($group != null) {
            $drug_dose = $drug_dose->where('dose_group.id', $group);
        }

        $drug_dose = $drug_dose->where('dose_drug.drug_store_id', $drug_store_id)->paginate(20);


        return $drug_dose;
    }

    public function getDetailDose($id){
        LogEx::methodName($this->className, 'getDetailDose');

        $drug_dose = DoseDrug::leftJoin('dose_group', 'dose_group.id', '=', 'dose_drug.group_id')
            ->leftJoin('dose_category', 'dose_category.id', '=', 'dose_drug.category_id')
            ->select(
                'dose_drug.*',
                'dose_group.name as group_name',
                'dose_category.name as category_name'
            );
        $drug_dose = $drug_dose->where('dose_drug.id', $id)->first();
        return $drug_dose;
    }

}
