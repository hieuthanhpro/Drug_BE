<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Exception;
use App\LibExtension\LogEx;

class Controller extends BaseController
{
    protected $className = "Controller";

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function responseApi($code = 0, $message = '', $data = array(), $total_page = false, $header_type = 'json')
    {
        LogEx::methodName($this->className, 'responseApi');

        // No data return []
        if ($data == null) {
            $data = [];
        }

        return [
            'ERR_CODE' => $code,
            'ERR_MSG' => $message,
            'RESULT' => $data
        ];
    }

    public function generateImageFromBase64($data = '')
    {
        LogEx::methodName($this->className, 'generateImageFromBase64');

        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
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
            $targetDir = 'upload/images/' . $newImageName;
            file_put_contents($targetDir, $data);

            return $newImageName;
        } catch (Exception $e) {
            LogEx::try_catch($this->className, $e);
            return $e->getMessage();
        }
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        LogEx::methodName($this->className, 'paginate');

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function saveImageCDN($file, $options)
    {
        LogEx::methodName($this->className, 'saveImageCDN');

        $date = Carbon::now()->format('dmY');
        $extension = $file->getClientOriginalExtension();
        $filename = $date . "_" . substr(md5($file->getClientOriginalName()), 0, 8) . "." . $extension;
        $file_original = "upload/files/";
        switch ($options['type']) {
            case 'drug':
                $file_original = "upload/drug/";
                break;
        }
        $file->move($file_original, $filename);
        return asset('/') . $file_original . $filename;
    }
}
