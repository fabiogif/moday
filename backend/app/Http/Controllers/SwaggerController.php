<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;

/**
 * @OA\Info(
 *     title="Moday API",
 *     version="1.0.0",
 *     description="API completa para sistema de gestão de restaurantes e delivery",
 *     @OA\Contact(
 *         email="contato@moday.com",
 *         name="Equipe Moday"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost",
 *     description="Servidor de Desenvolvimento"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token JWT para autenticação"
 * )
 * 
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints de autenticação e gerenciamento de usuários"
 * )
 * 
 * @OA\Tag(
 *     name="Health Check",
 *     description="Verificação de saúde da API"
 * )
 * 
 * @OA\Tag(
 *     name="Tenants",
 *     description="Gerenciamento de tenants (empresas)"
 * )
 * 
 * @OA\Tag(
 *     name="Categorias",
 *     description="Gerenciamento de categorias de produtos"
 * )
 * 
 * @OA\Tag(
 *     name="Produtos",
 *     description="Gerenciamento de produtos"
 * )
 * 
 * @OA\Tag(
 *     name="Planos",
 *     description="Gerenciamento de planos de assinatura"
 * )
 * 
 * @OA\Tag(
 *     name="Mesas",
 *     description="Gerenciamento de mesas do restaurante"
 * )
 * 
 * @OA\Tag(
 *     name="Pedidos",
 *     description="Gerenciamento de pedidos"
 * )
 * 
 * @OA\Tag(
 *     name="Clientes",
 *     description="Gerenciamento de clientes"
 * )
 */
class SwaggerController extends Controller
{
    //
}
