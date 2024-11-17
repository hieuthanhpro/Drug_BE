<?php

namespace App\LibExtension;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Utils
{
    protected $className = "Utils";

    public static function isset($value, $defaultValue)
    {
        if (isset($value))
            return $value;
        return $defaultValue;
    }

    public static function coalesce($data, $key, $defaultValue)
    {
        if (isset($data[$key]))
            return $data[$key];
        return $defaultValue;
    }

    public static function coalesceMapping($data, $coalesceList)
    {
        $retData = array();
        foreach ($coalesceList as $colName => $dataColName) {
            if (gettype($dataColName) == 'string') {
                // Has the same column name
                if ($dataColName == '-')
                    $colVal = $data[$colName];
                else
                    $colVal = $data[$dataColName];
            } else {
                if ($dataColName[0] == '-')
                    $colVal = self::coalesce($data, $colName, $dataColName[1]);
                else
                    $colVal = self::coalesce($data, $dataColName[0], $dataColName[1]);
            }
            $retData[$colName] = $colVal;
        }
        return $retData;
    }


    public static function sqlWhere($query, $columnName, $operator, $searchCond, $searchCol)
    {
        if (!empty($searchCond[$searchCol])) {
            $valSearch = $searchCond[$searchCol];
            if ($operator == 'like' || $operator == 'ilike') {
                $valSearch = '%' . $valSearch . '%';
            }
            $query->where($columnName, $operator, $valSearch);
        }
    }

    public static function getSequenceDB($sequenceType)
    {
        $sequenceName = '';
        switch ($sequenceType) {
            case 'TL':
                $sequenceName = 'dose_drug_tl100001_id_seq';
                break;
            case 'DRUG':
                $sequenceName = 'drug_drug100001_id_seq';
                break;
            case 'KP':
                $sequenceName = 'vouchers_kp100001_id_seq';
                break;
            case 'PT':
                $sequenceName = 'invoice_pt100001_id_seq';
                break;
            case 'PC':
                $sequenceName = 'invoice_pc100001_id_seq';
                break;
            case 'PN':
                $sequenceName = 'invoice_pn100001_id_seq';
                break;
            case 'HD':
                $sequenceName = 'invoice_hd100001_id_seq';
                break;
            case 'HDT':
                $sequenceName = 'invoice_hdt100001_id_seq';
                break;
            case 'PTH':
                $sequenceName = 'invoice_pth100001_id_seq';
                break;
            case 'XK':
                $sequenceName = 'invoice_xk100001_id_seq';
                break;
            case 'NK':
                $sequenceName = 'invoice_nk100001_id_seq';
                break;
            case 'DDH':
                $sequenceName = 'order_ddh100001_id_seq';
                break;
            default:
                break;
        }

        $query = DB::select("SELECT nextval('$sequenceName') as seq_id");

        return $query[0]->seq_id;
    }

    //Remove symbol
    public static function removeSymbolAndUnaccent($str)
    {

        return preg_replace('/[^\p{L}\p{N}\s]/u', '', Utils::unaccent($str));
    }

    //Build search condition for query
    public static function unaccent($str)
    {
        $transliteration = array(
            'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Æ' => 'A', 'Ǽ' => 'A',
            'Á' => 'A', 'À' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
            'Ấ' => 'A', 'Ầ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A', 'Â' => 'A',
            'Ắ' => 'A', 'Ằ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A', 'Ă' => 'A',
            'ä' => 'a', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'æ' => 'a', 'ǽ' => 'a',
            'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a', 'â' => 'a',
            'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a', 'ă' => 'a',
            'Đ' => 'D', 'Ď' => 'D',
            'đ' => 'd', 'ď' => 'd',
            'Ë' => 'E', 'Ĕ' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ė' => 'E',
            'É' => 'E', 'È' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
            'Ế' => 'E', 'Ề' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E', 'Ê' => 'E',
            'ë' => 'e', 'ĕ' => 'e', 'ē' => 'e', 'ę' => 'e', 'ė' => 'e',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e', 'ê' => 'e',
            'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
            'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g',
            'Ĥ' => 'H', 'Ħ' => 'H',
            'ĥ' => 'h', 'ħ' => 'h',
            'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'Ī' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
            'Í' => 'I', 'Ì' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
            'î' => 'i', 'ï' => 'i', 'į' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'ı' => 'i',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k', 'ĸ' => 'k',
            'Ĺ' => 'L', 'Ļ' => 'L', 'Ľ' => 'L', 'Ŀ' => 'L', 'Ł' => 'L',
            'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l',
            'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N',
            'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŋ' => 'n', 'ŉ' => 'n',
            'Ó' => 'O', 'Ò' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
            'Ố' => 'O', 'Ồ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O', 'Ô' => 'O',
            'Ớ' => 'O', 'Ờ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O', 'Ơ' => 'O',
            'Ö' => 'O', 'Ø' => 'O', 'Ō' => 'O', 'Ŏ' => 'O', 'Ő' => 'O', 'Œ' => 'O',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o', 'ô' => 'o',
            'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o', 'ơ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o', 'œ' => 'o', 'ð' => 'o',
            'Ŕ' => 'R', 'Ř' => 'R',
            'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r',
            'Š' => 'S', 'Ŝ' => 'S', 'Ś' => 'S', 'Ş' => 'S',
            'š' => 's', 'ŝ' => 's', 'ś' => 's', 'ş' => 's',
            'Ŧ' => 'T', 'Ţ' => 'T', 'Ť' => 'T',
            'ŧ' => 't', 'ţ' => 't', 'ť' => 't',
            'Û' => 'U', 'Ü' => 'U', 'Ũ' => 'U', 'Ŭ' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ų' => 'U',
            'Ú' => 'U', 'Ù' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
            'Ứ' => 'U', 'Ừ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U', 'Ư' => 'U',
            'û' => 'u', 'ü' => 'u', 'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u', 'ư' => 'u',
            'Ŵ' => 'W', 'Ẁ' => 'W', 'Ẃ' => 'W', 'Ẅ' => 'W',
            'ŵ' => 'w', 'ẁ' => 'w', 'ẃ' => 'w', 'ẅ' => 'w',
            'Ÿ' => 'Y', 'Ŷ' => 'Y',
            'Ý' => 'Y', 'Ỳ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
            'ÿ' => 'y', 'ŷ' => 'y',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'Ž' => 'Z', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z',
            'ž' => 'z', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z'
        );
        return str_replace(
            array_keys($transliteration),
            array_values($transliteration),
            $str
        );
    }

    public static function build_query_AutoCom_search($text, $fieldname = 'full_search')
    {
        LogEx::methodName("Utils", 'build_query_AutoCom_search');

        if ($text) {
            if (trim($text) == '*') {
                return '';
            }
            $text = self::unaccent($text);

            //1. Put all substring with "" to array
            $arrDoubleQuote = array();
            preg_match_all('/(\"[^\"]+\")/', $text, $arrDoubleQuote);

            //2. Replace all substring with "" by space
            $textSplit = preg_replace('/(\"[^\"]+\")/', ' ', $text);

            //3. Split by space and comma
            $arrSplit = preg_split('/[\ \,]+/', $textSplit);

            //Build query search
            $strQuery = '';

            if (count($arrDoubleQuote) > 0) {
                if (count($arrDoubleQuote[0]) > 0) {
                    foreach ($arrDoubleQuote[0] as $key => $value) {
                        $tmpValue = preg_replace('/\"/', '', $value); // remove from string
                        $strQuery = $strQuery . " and " . $fieldname . " ~* '" . $tmpValue . "'";
                    }
                }
            }

            if (count($arrSplit) > 0) {
                foreach ($arrSplit as $key => $value) {
                    if (trim($value == '')) {
                        continue;
                    }

                    if ($strQuery != '') {
                        $strQuery = $strQuery . " and ";
                    }

                    if (is_numeric($value)) {
                        //Build search exactly number
                        $strQuery = $strQuery . " (" . $fieldname . " ~ '\y" . $value . "' or " . $fieldname . " ~ '\D" . $value . "')";
                        $strQuery = $strQuery . " and (" . $fieldname . " ~ '" . $value . "\y' or " . $fieldname . " ~ '" . $value . "\D')";
                    } else {
                        $strQuery = $strQuery . $fieldname . " ~* '" . $value . "'";
                    }
                }
            }
            LogEx::debug("##### Auto search: " . $strQuery);
            return $strQuery;
        }
    }

    public static function createOrderByString($inputText, $orderByColumn)
    {
        $newStr = mb_strtolower($inputText);
        $strArr = array_filter(explode(' ', $newStr));
        $strArrLen = count($strArr);
        $orderByStr = "case when position('{$newStr}' in lower({$orderByColumn})) = 1 then 0 else 1 end ";

        $mapOrderByString = function ($str) use ($orderByColumn) {
            return " - length(replace(lower({$orderByColumn}), '{$str}', ''))";
        };

        $orderByStr .= ",(length({$orderByColumn}) * {$strArrLen}";
        $orderByStr .= join("", array_map($mapOrderByString, $strArr));
        $orderByStr .= ") desc";

        $orderByStr .= ",(length({$orderByColumn}) * {$strArrLen}";
        $orderByStr .= join("", array_map($mapOrderByString, $strArr));
        $orderByStr .= "):: numeric / length({$orderByColumn}) desc";
        return $orderByStr;
    }

    public static function createTempTable($columns, $data, $tableSuffix = '')
    {
        LogEx::methodName("Utils", 'createTempTable');

        $sql = join('" varchar, "', array_map(function ($value) {
            return strtolower($value);
        }, $columns));
        DB::statement('create temp table tmp_inputdata' . $tableSuffix . ' ("' . $sql . '" varchar)');
        for ($i = 0; $i < count($data); $i++) {
            $row = $data[$i];
            $sql = join("','", array_map(function ($value) use ($row) {
                return $row[$value] ?? '';
            }, $columns));
            DB::statement("insert into tmp_inputdata" . $tableSuffix . " values ('" . $sql . "')");
        }
    }

    public static function createTempTableFromRequestInput($requestInput)
    {
        LogEx::methodName("Utils", 'createTempTableFromRequestInput');

        $data = array_diff_key($requestInput, ['page' => '', 'per_page' => '', 'perPage' => '', 'user' => '', 'userInfo' => '']);
        DB::statement('create temp table tmp_inputdata( data jsonb )');
        DB::statement('insert into tmp_inputdata values (?)', [json_encode($data)]);
    }

    public static function generateImageFromBase64($data = '')
    {
        LogEx::methodName('Utils', 'generateImageFromBase64');

        try {
            if (preg_match('/^data:image\/(\w+);(name=[^;]+;)?base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    throw new \Exception('invalid image type');
                }

                $data = base64_decode($data);

                if ($data === false) {
                    throw new \Exception('base64_decode failed');
                }
            } else {
                throw new \Exception('did not match data URI with image data');
            }

            $newImageName = uniqid() . '.' . $type;
            $target_dir = 'upload/images/' . $newImageName;
            file_put_contents($target_dir, $data);

            return $newImageName;
        } catch (\Exception $e) {
            LogEx::try_catch('Utils', $e);
            return $e->getMessage();
        }
    }

    public static function executeRawQuery($query, $params = [], $url = null, $requestInput = null, $export = 0, $limit = 10)
    {
        LogEx::methodName('Utils', 'executeRawQuery');
        $isDetail = false;
        $perPage = 10;
        if (isset($requestInput)) {
            $perPage = $requestInput['per_page'] ?? ($requestInput['perPage'] ?? 10);
            $isDetail = isset($requestInput['detail']);
        }
        if ($export) {
            $perPage = $limit;
            if ($limit > 10) $perPage = $limit;
        }
        $isPaging = false;
        if (isset($perPage)) {
            $isPaging = true;
            $page = $requestInput['page'] ?? 1;
            $offset = ($page - 1) * $perPage;
            DB::statement("create temp table tmp_output as $query", $params);
            $total = DB::select("select count(*) as cnt from tmp_output t");
            $total = $total[0]->cnt;
            $query = "select * from tmp_output limit $perPage offset $offset";
            $params = [];
        }
        $data = DB::select($query, $params);
        if ($isDetail) {
            $data = UniversalDataMapping::hydrate($data);
        }
        if ($isPaging) {
            return new LengthAwarePaginator($data, $total, $perPage, $page, ['path' => $url]);
        }
        return $data;
    }

    /**
     * api v3
     * from executeRawQuery
    */
    public static function executeRawQueryV3($query, $url = null, $requestInput = null, $export = 0, $limit = 10)
    {
        LogEx::methodName('Utils', 'executeRawQuery');
        $isDetail = false;
        $perPage = 10;
        if (isset($requestInput)) {
            $perPage = $requestInput['per_page'] ?? ($requestInput['perPage'] ?? 10);
            $isDetail = isset($requestInput['detail']);
        }
        if ($export) {
            $perPage = $limit;
            if ($limit > 10) $perPage = $limit;
        }
        $isPaging = false;
        if (isset($perPage)) {
            $isPaging = true;
            $page = $requestInput['page'] ?? 1;
            $offset = ($page - 1) * $perPage;
            $total = $query->get()
                ->toArray();
            $total = count($total);
            $querys = $query->offset($offset)
                ->limit($perPage)
                ->get()
                ->toArray();
        }
        $data = $querys;
        if ($isDetail) {
            $data = UniversalDataMapping::hydrate($data);
        }
        if ($isPaging) {
            return new LengthAwarePaginator($data, $total, $perPage, $page, ['path' => $url]);
        }

        return $data;
    }

    public static function getSumData($data, $requestInput, $sql, $sqlParams = [])
    {
        $perPage = null;
        if (isset($requestInput)) {
            $perPage = $requestInput['per_page'] ?? ($requestInput['perPage'] ?? null);
        }
        if (isset($perPage)) {
            $data = $data->toArray();
            $sumData = Utils::executeRawQuery($sql, $sqlParams);
            if (count($sumData) > 0) {
                $data['sum_data'] = $sumData[0];
            }
        }
        return $data;
    }

    /**
     * api v3
     * from getSumData
    */
    public static function getSumDataV3($data, $requestInput, $sumParams = [])
    {
        $perPage = 10;
        if (isset($requestInput)) {
            $perPage = $requestInput['per_page'] ?? ($requestInput['perPage'] ?? 10);
        }
        if (isset($perPage)) {
            $data = $data->toArray();

            if (count($sumParams) > 0) {
                $data['sum_data'] = $sumParams;
            }
        }
        return $data;
    }

    /**
     * api v3
     * conver string
     */
    public static function converString($strings)
    {
        return ( preg_replace('/([^\pL\.\ ]+)/u', '', strip_tags(trim($strings))) );;
    }

    public static function getErrorMessage($dataType, $errorType)
    {
        LogEx::methodName('Utils', 'getErrorMessage');
        $errorList = array(
            'drug' => array(
                -1 => 'Sản phẩm đã tồn tại',
                -2 => 'Vui lòng nhập tên sản phẩm',
                -3 => 'Vui lòng nhập đơn vị tính nhỏ nhất',
                -4 => 'Vui lòng nhập giá bán đơn vị tính nhỏ nhất',
                -5 => 'Sản phẩm không tồn tại',
                -6 => 'Đơn vị quy đổi đã tồn tại'
            ),
            'invoice' => array(
                -1 => 'Hoá đơn không tồn tại',
                -2 => 'Số lượng tồn kho không đủ',
                -3 => 'Chưa chọn sản phẩm',
                -4 => 'Chưa chọn thuốc bán combo',
                -5 => 'Không thể tạo hoá đơn',
                -6 => 'Trùng thuốc và số lô'
            )
        );
        if (isset($errorList[$dataType]) && isset($errorList[$dataType][$errorType])) {
            return $errorList[$dataType][$errorType];
        }
        return "Có lỗi xảy ra [$dataType$errorType]";
    }

    public static function getExceptionMessage($exception)
    {
        LogEx::methodName('Utils', 'getExceptionMessage');
        if (preg_match('/^SQLSTATE\[P0001\]: Raise exception: 7 ERROR:  ([^\n]+)\n/', $exception->getMessage(), $match)) {
            return $match[1];
        }
        return 'Có lỗi xảy ra';
    }

    /**
     * @param $requestInput
     * @param array $params
     * @param $check_store Kiểm tra có sử dụng store của user login không
     * @return false|string
     */
    public static function getParams($requestInput, $params = array(), $check_store = true)
    {
        $data = array_diff_key($requestInput, ['page' => '', 'per_page' => '', 'perPage' => '', 'user' => '', 'userInfo' => '']);
        $data = array_merge($data, $params);
        if (isset($requestInput['userInfo'])) {
            if ($check_store) {
                $data['drug_store_id'] = $requestInput['userInfo']['drug_store_id'];
            }
            $data['user_id'] = $requestInput['userInfo']['id'];
        }
        return json_encode($data);
    }

    /**
     * @param $requestInput
     * @param array $params
     * @param $check_store Kiểm tra có sử dụng store của user login không
     * @return false|string
     */
    public static function getParamsString($requestInput, $params = array())
    {
        $data = array_diff_key($requestInput, ['page' => '', 'per_page' => '', 'perPage' => '', 'user' => '', 'userInfo' => '']);
        $data = array_merge($data, $params);
        return http_build_query($data);
    }

    /**
     * @param $length
     * @param $isOnlyNumber
     * @param $isOnlyAlpha
     * @return string
     */
    public static function quickRandom($length = 16, $isOnly = null)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (isset($isOnly)) {
            $pool = $isOnly == 'number' ? '0123456789' : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * @param $items
     * @return array
     */
    public static function getIds($items)
    {
        $ids = [];
        foreach ($items as $i => $v) {
            if (isset($v['id'])) {
                $ids[] = $v['id'];
            } else {
                break;
            }
        }
        return $ids;
    }

    /**
     * Cộng thêm số tháng tương ứng
     * @param $time
     * @param $plusMonths
     * @return false|int
     */
    public static function addMonths($time, $plusMonths)
    {

        $endTime = strtotime("+{$plusMonths} months", strtotime($time));
        return $endTime;
    }

    /**
     * Multi-array search
     *
     * @param array $array
     * @param array $search
     * @return array
     */
    public static function multiArraySearch($array, $search)
    {
        $result = array();
        foreach ($array as $key => $value) {
            foreach ($search as $k => $v) {
                if (!isset($value[$k]) || $value[$k] != $v) {
                    continue 2;
                }
            }
            $result[] = $key;
        }
        return $result;
    }

    /**
     * @param $id
     * @return string
     */
    public static function buildDrugMasterCode($id): string
    {
        $code = "00000000";
        return substr($code, Str::length($id)) . $id;
    }

    public const MSG_VNPAY = array(
        '01' => 'Không tìm thấy mã đơn hàng',
        '02' => 'Không tìm thấy mã đơn hàng',
        '04' => 'Số tiền không hợp lệ',
        '05' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.',
        '06' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch.',
        '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.',
        '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
        '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.',
        '12' => "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.",
        '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
        '79' => 'Giao dịch không thành công do: Quý khách nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch',
        '65' => 'Giao dịch không thành công do: Quý khách nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch',
        '75' => 'Ngân hàng thanh toán đang bảo trì',
        '99' => 'Đã xảy ra lỗi. Vui lòng thử lại sau!'
    );
}

class UniversalDataMapping extends \App\Models\BaseModel
{
    protected $casts = [
        'units' => 'array',
        'numbers' => 'array',
        'quantity' => 'float'
    ];
}
