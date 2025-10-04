<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\Auth\RegisterRequest;

Route::post('/test-register', function (Request $request) {
    try {
        // Validação manual
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|confirmed|string|min:6',
            'phone' => 'nullable|string|max:20',
            'tenant_id' => 'nullable|exists:tenants,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dados válidos',
            'data' => $request->all()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ], 500);
    }
});
