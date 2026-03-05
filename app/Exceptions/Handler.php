<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        $context = [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if (!app()->runningInConsole() && request()) {
            $context += [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ];

            if (auth()->check()) {
                $context['user_id'] = auth()->id();
            }

            $requestData = request()->except(['password', 'password_confirmation', 'current_password']);
            if (!empty($requestData)) {
                $context['request_data'] = $requestData;
            }
        }

        Log::error('Error', $context);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
//        if ($request->expectsJson()) {
//            return $this->renderJsonResponse($request, $e);
//        }
//        return parent::render($request, $e);

        return $this->renderJsonResponse($request, $e);
    }

    /**
     * Render exception as JSON response
     */
    protected function renderJsonResponse($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Method not allowed',
            ], 405);
        }

        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();

            if ($statusCode < 500) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Request error',
                ], $statusCode);
            }
        }

        $statusCode = 500;
        $message = 'Internal server error';

        return response()->json([
            'message' => $message,
        ], $statusCode);
    }
}
