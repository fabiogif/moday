<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PublicStoreOrderRequest;
use App\Http\Resources\{PublicProductResource, PublicStoreInfoResource, PublicPaymentMethodResource, CouponResource};
use App\Services\{PublicOrderService, PublicStoreService, StoreHourService, OrderService, CouponService, LoyaltyProgramService, PublicOrderCalculationService, PublicClientService};
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicStoreController extends Controller
{
    public function __construct(
        private readonly PublicStoreService $publicStoreService,
        private readonly PublicOrderService $publicOrderService,
        private readonly StoreHourService $storeHourService,
        private readonly OrderService $orderService,
        private readonly CouponService $couponService,
        private readonly LoyaltyProgramService $loyaltyProgramService,
        private readonly PublicOrderCalculationService $publicOrderCalculationService,
        private readonly PublicClientService $publicClientService,
    ) {}

    /**
     * Get store info by tenant slug
     */
    public function getStoreInfo(string $slug): JsonResponse
    {
        try {
            $storeInfo = $this->publicStoreService->getStoreInfo($slug);

            if (!$storeInfo) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada ou inativa',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                new PublicStoreInfoResource($storeInfo),
                'Informações da loja carregadas com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar informações da loja');
        }
    }

    /**
     * Get products by tenant slug
     */
    public function getProducts(string $slug): JsonResponse
    {
        try {
            $products = $this->publicStoreService->getStoreProducts($slug);

            if ($products === null) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                PublicProductResource::collection($products),
                'Produtos carregados com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar produtos');
        }
    }

    /**
     * Get payment methods by tenant slug
     */
    public function getPaymentMethods(string $slug): JsonResponse
    {
        try {
            $paymentMethods = $this->publicStoreService->getStorePaymentMethods($slug);

            if ($paymentMethods === null) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                PublicPaymentMethodResource::collection($paymentMethods),
                'Formas de pagamento carregadas com sucesso',
                200
            );
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Erro ao buscar formas de pagamento');
        }
    }

    /**
     * Create public order (without authentication)
     */
    public function createOrder(PublicStoreOrderRequest $request, string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada',
                    404
                );
            }

            $validated = $request->validated();
            $result = $this->publicOrderService->createOrder($validated, $tenant);

            $responseData = [
                'order_id' => $result['order']->identify,
                'subtotal' => number_format((float) $result['subtotal'], 2, ',', '.'),
                'discount_amount' => number_format((float) $result['discount_amount'], 2, ',', '.'),
                'total' => number_format((float)$result['total'], 2, ',', '.'),
                'coupon_code' => $result['coupon_code'],
                'whatsapp_message' => $result['whatsapp_message'],
                'whatsapp_link' => $result['whatsapp_link'],
            ];

            return ApiResponseClass::sendResponse(
                $responseData,
                'Pedido criado com sucesso',
                201
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseClass::sendResponse(
                $e->errors(),
                'Dados inválidos',
                422
            );
        } catch (DomainException $e) {
            return ApiResponseClass::sendResponse(
                '',
                $e->getMessage(),
                422
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Tratar erros específicos de banco de dados
            if (strpos($e->getMessage(), 'duplicate key') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                
                $message = 'Já existe um cadastro com estes dados. ';
                
                if (strpos($e->getMessage(), 'email') !== false) {
                    $message .= 'O email informado já está cadastrado.';
                } elseif (strpos($e->getMessage(), 'cpf') !== false) {
                    $message .= 'O CPF informado já está cadastrado.';
                } else {
                    $message .= 'Por favor, verifique seus dados e tente novamente.';
                }
                
                return ApiResponseClass::sendResponse(
                    ['field' => 'client'],
                    $message,
                    422
                );
            }
            
            Log::error('Database error creating public order', [
                'error' => $e->getMessage(),
                'slug' => $slug
            ]);
            
            return response()->json([
                'success' => false,
                'data' => '',
                'message' => 'Erro ao processar seu pedido. Por favor, tente novamente.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error creating public order', [
                'error' => $e->getMessage(),
                'slug' => $slug
            ]);
            
            return ApiResponseClass::rollback($e, 'Erro ao criar pedido');
        }
    }

    /**
     * Validate coupon for the public store checkout.
     */
    public function validateCoupon(Request $request, string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Loja não encontrada', 404);
            }

            $code = strtoupper(trim((string) ($request->input('code') ?? $request->input('coupon_code'))));

            if (empty($code)) {
                return ApiResponseClass::sendResponse('', 'Informe o código do cupom.', 422);
            }

            $products = $request->input('products');
            $subtotal = $request->input('subtotal');

            if (is_array($products) && count($products) > 0) {
                $calculation = $this->publicOrderCalculationService->calculateOrder($products, $tenant);
                $subtotal = $calculation['total'];
            }

            if ($subtotal === null) {
                return ApiResponseClass::sendResponse('', 'Informe o subtotal ou os produtos para validar o cupom.', 422);
            }

            $clientIdentifier = $request->input('client_identifier');

            $result = $this->couponService->validateForPreview(
                $tenant,
                $code,
                (float) $subtotal,
                null,
                $clientIdentifier
            );

            return ApiResponseClass::sendResponse([
                'coupon' => new CouponResource($result['coupon']),
                'discount_amount' => number_format($result['discount_amount'], 2, '.', ''),
                'final_total' => number_format($result['final_total'], 2, '.', ''),
            ], 'Cupom aplicado com sucesso', 200);
        } catch (DomainException $e) {
            return ApiResponseClass::sendResponse('', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Error validating coupon', [
                'error' => $e->getMessage(),
                'slug' => $slug,
            ]);

            return ApiResponseClass::rollback($e, 'Erro ao validar cupom');
        }
    }

    /**
     * Get featured promotions and loyalty highlights for the store front.
     */
    public function getPromotions(string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Loja não encontrada', 404);
            }

            $featuredCoupons = $this->couponService->featuredCoupons($tenant->id, 6);

            $slides = $featuredCoupons->map(function (CouponResource|\App\Models\Coupon $coupon) {
                $model = $coupon instanceof \App\Models\Coupon ? $coupon : $coupon->resource;

                $highlight = $model->discount_type === 'percentage'
                    ? number_format((float) $model->discount_value, 0) . '% OFF'
                    : 'R$ ' . number_format((float) $model->discount_value, 2, ',', '.');

                return [
                    'type' => 'coupon',
                    'title' => $model->name,
                    'description' => $model->description,
                    'badge' => 'Cupom: ' . $model->code,
                    'highlight' => $highlight,
                    'expires_at' => optional($model->end_at)->toIso8601String(),
                    'is_featured' => (bool) $model->is_featured,
                    'code' => $model->code,
                ];
            })->values();

            $program = $this->loyaltyProgramService->getActiveProgramByTenant($tenant->id);

            if ($program) {
                $slides->push([
                    'type' => 'loyalty',
                    'title' => $program->name ?? 'Programa de Fidelidade',
                    'description' => $program->description ?? 'Acumule pontos e troque por recompensas exclusivas.',
                    'badge' => 'Fidelidade',
                    'highlight' => sprintf('%.0f pts a cada R$ 1', $program->points_per_currency ?? 1),
                    'cta' => 'Participe do programa',
                ]);
            }

            return ApiResponseClass::sendResponse([
                'slides' => $slides->toArray(),
            ], 'Promoções carregadas com sucesso', 200);
        } catch (\Exception $e) {
            Log::error('Error loading promotions', [
                'error' => $e->getMessage(),
                'slug' => $slug,
            ]);

            return ApiResponseClass::rollback($e, 'Erro ao carregar promoções');
        }
    }

    /**
     * Busca cliente existente por CPF ou telefone (autofill no checkout público).
     */
    public function lookupClient(Request $request, string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse('', 'Loja não encontrada', 404);
            }

            $cpf = $request->query('cpf');
            $phone = $request->query('phone');

            if (!$cpf && !$phone) {
                return ApiResponseClass::sendResponse('', 'Informe CPF ou telefone', 422);
            }

            $result = $this->publicClientService->lookupClient($tenant, $cpf, $phone);

            if (!$result) {
                return ApiResponseClass::sendResponse([
                    'exists' => false,
                    'client' => null,
                    'address' => null,
                ], 'Cliente não encontrado', 200);
            }

            return ApiResponseClass::sendResponse([
                'exists' => true,
                'client' => $result['client'],
                'address' => $result['address'],
            ], 'Cliente encontrado', 200);
        } catch (\Exception $e) {
            Log::error('Error looking up client', [
                'error' => $e->getMessage(),
                'slug' => $slug,
            ]);

            return ApiResponseClass::rollback($e, 'Erro ao buscar cliente');
        }
    }

    /**
     * Verifica se a loja está aberta
     */
    public function checkStoreHours(string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada',
                    404
                );
            }

            $isOpen = $this->storeHourService->isStoreOpen($tenant->id);
            $storeHours = $this->storeHourService->getAllStoreHours($tenant->id, ['is_active' => true]);
            
            // Formatar horários para exibição
            $formattedHours = [];
            $isAlwaysOpen = false;

            foreach ($storeHours as $hour) {
                if ($hour->is_always_open) {
                    $isAlwaysOpen = true;
                    break;
                }

                $dayName = $hour->day_name;
                if (!isset($formattedHours[$dayName])) {
                    $formattedHours[$dayName] = [];
                }

                // Adicionar primeiro período
                $formattedHours[$dayName][] = [
                    'start' => $hour->start_time?->format('H:i'),
                    'end' => $hour->end_time?->format('H:i'),
                    'delivery_type' => $hour->delivery_type,
                ];

                // Adicionar segundo período se existir (intervalo)
                if ($hour->start_time_2 && $hour->end_time_2) {
                    $formattedHours[$dayName][] = [
                        'start' => $hour->start_time_2->format('H:i'),
                        'end' => $hour->end_time_2->format('H:i'),
                        'delivery_type' => $hour->delivery_type,
                    ];
                }
            }

            return ApiResponseClass::sendResponse([
                'is_open' => $isOpen,
                'is_always_open' => $isAlwaysOpen,
                'current_time' => now()->format('H:i'),
                'current_day' => now()->locale('pt_BR')->dayName,
                'store_hours' => $formattedHours,
            ], 'Status da loja verificado', 200);

        } catch (\Exception $e) {
            Log::error('Error checking store hours', [
                'error' => $e->getMessage(),
                'slug' => $slug
            ]);
            
            return ApiResponseClass::rollback($e, 'Erro ao verificar horário da loja');
        }
    }

    /**
     * Consulta pública de pedido por telefone
     */
    public function trackOrder(string $slug): JsonResponse
    {
        try {
            $tenant = $this->publicStoreService->getTenantEntityBySlug($slug);

            if (!$tenant) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Loja não encontrada',
                    404
                );
            }

            $phone = request('phone');

            // Validar que o telefone foi fornecido
            if (!$phone) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Informe o telefone para consultar o pedido',
                    422
                );
            }

            // Validar formato de telefone
            $phoneClean = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phoneClean) < 10 || strlen($phoneClean) > 11) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Telefone inválido. Informe DDD + número.',
                    422
                );
            }

            $orderData = $this->orderService->findOrderByClientInfo($tenant->id, null, $phone);

            if (!$orderData) {
                return ApiResponseClass::sendResponse(
                    '',
                    'Nenhum pedido em andamento foi encontrado para este telefone.',
                    404
                );
            }

            return ApiResponseClass::sendResponse(
                $orderData,
                'Pedido encontrado',
                200
            );

        } catch (\Exception $e) {
            Log::error('Error tracking order', [
                'error' => $e->getMessage(),
                'slug' => $slug
            ]);
            
            return ApiResponseClass::rollback($e, 'Erro ao consultar pedido');
        }
    }

}
