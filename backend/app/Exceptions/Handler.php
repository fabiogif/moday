<?php

namespace App\Exceptions;

use App\Classes\ApiResponseClass;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TokenExpiredException $e, $request) {
            return ApiResponseClass::unauthorized('Token expirado, realize login novamente');
        });

        $this->renderable(function (TokenInvalidException|JWTException $e, $request) {
            return ApiResponseClass::unauthorized('Token inválido');
        });

        $this->renderable(function (UnauthorizedHttpException $e, $request) {
            $message = strtolower($e->getMessage());

            if (str_contains($message, 'token not provided')) {
                return ApiResponseClass::unauthorized('Token não fornecido');
            }

            if (str_contains($message, 'token has expired')) {
                return ApiResponseClass::unauthorized('Token expirado, realize login novamente');
            }

            if (str_contains($message, 'token invalid')) {
                return ApiResponseClass::unauthorized('Token inválido');
            }

            return ApiResponseClass::unauthorized('Token inválido');
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        $response = parent::render($request, $e);

        if ($response instanceof JsonResponse && $response->getStatusCode() === 401) {
            $data = $response->getData(true);
            $message = $data['message'] ?? null;

            if ($message === 'Unauthenticated.') {
                return ApiResponseClass::unauthorized('Não autorizado');
            }
        }

        return $response;
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return ApiResponseClass::unauthorized('Não autorizado');
    }
}
