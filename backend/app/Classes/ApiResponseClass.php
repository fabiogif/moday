<?php

namespace App\Classes;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiResponseClass
{
    public function __construct()
    {
        //
    }
    public static function rollback($e, $message = "Algo deu errado! Processo não concluído"): JsonResponse
    {
        DB::rollBack();
        Log::error("API Error: " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor'
        ], 500);
    }

    public static function throw($e, $message = "Algo deu errado! Processo não concluído"): JsonResponse
    {
        Log::error("API Exception: " . $e->getMessage(), [
            'exception' => $e,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor'
        ], 500);
    }

    public static function sendResponse($result , $message ,$code=200): JsonResponse
    {
        $response=[
            'success' => true,
            'data'    => $result
        ];
        if(!empty($message)){
            $response['message'] = $message;
        }
        return response()->json($response, $code);
    }

    public static function sendResponsePaginate($class, $result, $code = 200)
    {
        return ($class::collection($result->items())->additional([
           'success' => true,
           'meta' => [
               'total' => $result->total(),
               'is_first_page' => $result->isFirstPage(),
               'is_last_page' => $result->isLastPage(),
               'current_page' => $result->currentPage(),
               'next_page'=> $result->getNumberNextPage(),
               'previous_page' => $result->getNumberPreviousPage(),
           ]
       ]));
    }

    public static function unauthorized($message = 'Token de acesso inválido ou expirado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 401);
    }

    public static function forbidden($message = 'Acesso negado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 403);
    }

    public static function validationError($errors, $message = 'Dados inválidos'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }
}
