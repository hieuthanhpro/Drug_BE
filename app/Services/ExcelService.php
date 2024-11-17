<?php
/**
 * Created by PhpStorm.
 * User: hieu
 * Date: 5/24/2019
 * Time: 5:09 PM
 */

namespace App\Services;
use App\Exceptions\RepositoryException;
use Carbon\Carbon;
use Config;
use Excel;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\LibExtension\LogEx;

/**
 * Class Forecast
 *
 * @package app\Services\Forecast
 */
class ExcelService
{
    protected $className = "ExcelService";

    /**
     * Hàm Export file
     * @param $name : Tên file muốn Export
     * @param $title : Tên sheet file excel
     * @param $data : Mảng dữ liệu để Export
     * @param $type : Định dạng file Export, mặc định là excel
     */
    public function Export($name, $title, $data, $type)
    {
        LogEx::methodName($this->className, 'Export');

        if ($name == null and $name == '') {
            $name = 'xuất dữ liệu ngày:-' . Carbon::now()->format('d/m/Y');
        }
        if ($title == null and $title == '') {
            $title = 'Số liệu';
        }
        if ($type == null and $type == '') {
            $type = 'xls';
        }
        if (!empty($data)) {
            Excel::create($name, function ($excel) use ($data, $name, $title) {
                $excel->setTitle($name);
                $excel->sheet($title, function ($sheet) use ($data) {
//                    $i = 0;
//                    foreach ($header as $item){
//                        $sheet->setCellValueByColumnAndRow($i++, 1, $item);
//                    }
//                    quantrac.table_data

                    $sheet->fromArray($data, null, 'A1', true);
//                    $sheet->loadView('quantrac.table_data');

                });
            })->download($type);
        }
    }

    /**
     * Hàm update data file vào DB
     *
     * @param $file : File truyền vào
     * @param $table_name : Tên bảng
     * @param $mapping_column
     * @param $where_clauses : Mảng điều kiện
     * @param $mapping_columns_where : Tham số cần so sánh trong db
     * @param $username
     * @param array $data_column_return
     * @return bool
     */
    public function updateExcelToDb($file, $table_name, $mapping_column, $where_clauses, $mapping_columns_where, $username, $data_column_return = array())
    {
        LogEx::methodName($this->className, 'updateExcelToDb');

        $data_return = array();
        Excel::load($file,
            function ($reader) use (
                $mapping_column,
                $mapping_columns_where,
                $table_name,
                $where_clauses,
                $username,
                $data_column_return,
                &$data_return
            ) {
                // create data for update
                $data_input = $reader->all()->toArray();
                DB::beginTransaction();
                try {
                    if ($where_clauses == null && $mapping_columns_where == null) {
                        foreach ($data_input as $k => $item) {
                            $data = array();
                            foreach ($mapping_column as $key => $value) {
                                $data[$key] = $item[$value];
                                $data['username'] = $username;
                            }
                            foreach ($data_column_return as $vl) {
                                array_push($data_return, $item[$vl]);
                            }
                            // insert database
                            $tmp = DB::table($table_name)->insert($data);
                        }
                    } else {
                        foreach ($data_input as $item) {
                            $data = array();
                            $hasExistValue = false;
                            $ary_where = array();
                            foreach ($mapping_column as $key => $value) {
                                if (isset($item[$value])) {
                                    $hasExistValue = true;
                                }
                                $data[$key] = $item[$value] !== '-' ? $item[$value] : null;
                                $data['username'] = $username;
                            }
                            // update database
                            $tmp = DB::table($table_name)->where($where_clauses);

                            foreach ($mapping_columns_where as $k => $v) {
                                $tmp->where($k, $item[$v]);
                                $ary_where[$k] = $item[$v];
                            }
                            foreach ($data_column_return as $vl) {
                                array_push($data_return, $item[$vl]);
                            }
                            if ($tmp->first() == null) {
                                if ($hasExistValue) {
                                    $tmp->insert(array_merge($data, $where_clauses, $ary_where));
                                }
                            } else {
                                if (isset($data['q_actual'])) {
                                    if ($tmp->first()->q_actual == 0) {
                                        $tmp->update($data);
                                    }
                                } else {
                                    $tmp->update($data);
                                }
                            }
                        }
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    LogEx::try_catch($this->className, $e);
                    Log::error("ImportExcel " . $e->getMessage());
                    throw new RepositoryException("Lỗi định dạng dữ liệu khi update vào database");
                }
            });
        return empty($data_return) ? true : $data_return;
    }

    /**
     * @param $file
     * @param $table_name
     * @param $mapping_column
     * @param $hydro_code
     * @return bool
     */
    public function insertDataPower($file, $table_name, $mapping_column, $hydro_code)
    {
        LogEx::methodName($this->className, 'insertDataPower');

        Excel::load($file,
            function ($reader) use (
                $mapping_column,
                $table_name,
                $hydro_code
            ) {
                // create data for update
                $data_input = $reader->all()->toArray();
                DB::beginTransaction();
                try {
                    foreach ($data_input as $item) {
                        $data = array();
                        foreach ($mapping_column as $key => $value) {
                            if ($value == 'f' && $item[$value] == '') {
                                $data[$key] = null;
                            } else {
                                $data[$key] = $item[$value];
                            }
                        }
                        $data['hydro_code'] = $hydro_code;
                        // insert database
                        DB::table($table_name)->insert($data);
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    LogEx::try_catch($this->className, $e);
                    throw new RepositoryException("Sai định dang");
                }
            });
        return true;
    }


    /**
     * Lấy phần mở rộng của file
     * @param $file : File truyền vào
     * @return mixed
     */
    function get_extension($file)
    {
        LogEx::methodName($this->className, 'get_extension');

        $file = $file->getClientOriginalName();
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * Hàm kiểm tra định dạng file import
     * @param $file : File truyền vào
     * @param $allowed : Mảng định dạng được phép import
     * @return bool
     */
    public function checkFileInput($file, $allowed)
    {
        LogEx::methodName($this->className, 'checkFileInput');

        if ($file == null) {
            return false;
        }

        $extension = $this->get_extension($file);
        if (!in_array($extension, $allowed)) {
            return false;
        } else {
            return true;
        }
    }
}
