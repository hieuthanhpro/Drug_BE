<?php

namespace App\Exceptions;

use App\LibExtension\LogEx;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof AuthenticationException) {
            return \App\Helper::errorResponse(401, 'Bạn chưa đăng nhập');
        }
        if ($e instanceof AccessDeniedHttpException) {
            return \App\Helper::errorResponse(403, 'Bạn không có quyền thực hiện thao tác này');
        }
        if ($e instanceof NotFoundHttpException) {
            return \App\Helper::errorResponse(404, 'Không tìm thấy API');
        }
        if ($e instanceof ValidationException) {
            return \App\Helper::errorResponse(422, 'Dữ liệu không hợp lệ', $e->errors());
        }
        if ($e instanceof \ErrorException) {
            return \App\Helper::errorResponse(500, 'Hệ thống đang bảo trì. Vui lòng thử lại sau');
        }
        return parent::render($request, $e);
    }

    protected function prepareResponse($request, Exception $e)
    {
        if ($e instanceof AccessDeniedHttpException) {
            // $this->unauthorized is a custom & local function that I created
            // you can try doing dd('yes it works over here');
            return response()->json(['error' => 'Data is unauthorized'], $e->getStatusCode());
        }
    }
}
