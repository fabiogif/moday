<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar se há arquivos sendo enviados
        if (!$request->hasFile('image') && !$request->hasFile('logo')) {
            return $next($request);
        }
        
        $errors = [];
        
        // Validar imagem de produto
        if ($request->hasFile('image')) {
            $errors = array_merge($errors, $this->validateImageFile($request->file('image')));
        }
        
        // Validar logo de empresa
        if ($request->hasFile('logo')) {
            $errors = array_merge($errors, $this->validateLogoFile($request->file('logo')));
        }
        
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação de arquivo',
                'errors' => $errors
            ], 422);
        }
        
        return $next($request);
    }
    
    /**
     * Validar arquivo de imagem de produto
     */
    private function validateImageFile($file): array
    {
        $errors = [];
        
        // Verificar se é um arquivo válido
        if (!$file->isValid()) {
            $errors[] = 'Arquivo de imagem inválido';
            return $errors;
        }
        
        // Verificar tamanho (2MB)
        if ($file->getSize() > 2048 * 1024) {
            $errors[] = 'Imagem muito grande. Máximo: 2MB';
        }
        
        // Verificar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP';
        }
        
        // Verificar se é uma imagem válida
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            $errors[] = 'Arquivo não é uma imagem válida';
        } else {
            // Verificar dimensões
            if ($imageInfo[0] > 2048 || $imageInfo[1] > 2048) {
                $errors[] = 'Dimensões da imagem muito grandes. Máximo: 2048x2048px';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar arquivo de logo
     */
    private function validateLogoFile($file): array
    {
        $errors = [];
        
        // Verificar se é um arquivo válido
        if (!$file->isValid()) {
            $errors[] = 'Arquivo de logo inválido';
            return $errors;
        }
        
        // Verificar tamanho (1MB)
        if ($file->getSize() > 1024 * 1024) {
            $errors[] = 'Logo muito grande. Máximo: 1MB';
        }
        
        // Verificar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Tipo de arquivo não permitido para logo. Use: JPG, PNG, GIF ou SVG';
        }
        
        // Verificar se é uma imagem válida (exceto SVG)
        if ($file->getMimeType() !== 'image/svg+xml') {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                $errors[] = 'Arquivo não é uma imagem válida';
            } else {
                // Verificar dimensões
                if ($imageInfo[0] > 1024 || $imageInfo[1] > 1024) {
                    $errors[] = 'Dimensões do logo muito grandes. Máximo: 1024x1024px';
                }
            }
        }
        
        return $errors;
    }
}
