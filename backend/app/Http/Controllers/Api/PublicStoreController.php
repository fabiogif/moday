<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PublicStoreOrderRequest;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PublicStoreController extends Controller
{
    /**
     * Get store info by tenant slug
     */
    public function getStoreInfo(string $slug): JsonResponse
    {
        try {
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada ou inativa'
                ], 404);
            }

            $storeInfo = [
                'id' => $tenant->id,
                'uuid' => $tenant->uuid,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'state' => $tenant->state,
                'zipcode' => $tenant->zipcode,
                'logo' => $tenant->logo,
                'whatsapp' => $this->formatWhatsApp($tenant->phone),
                'settings' => $tenant->settings ?? [],
            ];

            return response()->json([
                'success' => true,
                'data' => $storeInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar informações da loja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by tenant slug
     */
    public function getProducts(string $slug): JsonResponse
    {
        try {
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ], 404);
            }

            $products = Product::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('qtd_stock', '>', 0)
                ->with('categories')
                ->get()
                ->map(function ($product) {
                    return [
                        'uuid' => $product->uuid,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'promotional_price' => $product->promotional_price,
                        'image' => $product->image,
                        'qtd_stock' => $product->qtd_stock,
                        'brand' => $product->brand,
                        'sku' => $product->sku,
                        'variations' => $product->variations ?? [],
                        'categories' => $product->categories->map(fn($cat) => [
                            'uuid' => $cat->uuid ?? $cat->identify,
                            'name' => $cat->name
                        ]),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produtos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create public order (without authentication)
     */
    public function createOrder(PublicStoreOrderRequest $request, string $slug): JsonResponse
    {
        try {
            $tenant = Tenant::where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada'
                ], 404);
            }

            // Get validated data from Form Request
            $validated = $request->validated();

            // Prepare client data for public store (no password required)
            $clientData = [
                'uuid' => Str::uuid(),
                'name' => $validated['client']['name'],
                'email' => $validated['client']['email'],
                'phone' => $validated['client']['phone'],
                'cpf' => $validated['client']['cpf'] ?? null,
                'is_active' => true,
            ];

            // Create or update client
            $client = Client::updateOrCreate(
                [
                    'email' => $validated['client']['email'],
                    'tenant_id' => $tenant->id,
                ],
                $clientData
            );

            // Calculate total
            $total = 0;
            $orderProducts = [];

            foreach ($validated['products'] as $item) {
                $product = Product::where('uuid', $item['uuid'])
                    ->where('tenant_id', $tenant->id)
                    ->first();

                // Product validation is now handled in Form Request
                $price = $product->promotional_price ?? $product->price;
                $total += $price * $item['quantity'];

                $orderProducts[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
            }

            // Prepare order data
            $orderData = [
                'identify' => $this->generateOrderIdentify(),
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'total' => $total,
                'status' => 'Em Preparo',
                'origin' => 'public_store',
                'is_delivery' => $validated['delivery']['is_delivery'],
                'payment_method' => $validated['payment_method'],
                'shipping_method' => $validated['shipping_method'],
            ];

            // Only add delivery fields if delivery is selected
            if ($validated['delivery']['is_delivery']) {
                $orderData = array_merge($orderData, [
                    'delivery_address' => $validated['delivery']['address'],
                    'delivery_number' => $validated['delivery']['number'],
                    'delivery_neighborhood' => $validated['delivery']['neighborhood'],
                    'delivery_city' => $validated['delivery']['city'],
                    'delivery_state' => $validated['delivery']['state'],
                    'delivery_zip_code' => $validated['delivery']['zip_code'],
                    'delivery_complement' => $validated['delivery']['complement'] ?? null,
                    'delivery_notes' => $validated['delivery']['notes'] ?? null,
                ]);
            }

            // Create order
            $order = Order::create($orderData);

            // Attach products to order
            foreach ($orderProducts as $product) {
                $order->products()->attach($product['product_id'], [
                    'qty' => $product['quantity'],
                    'price' => $product['price'],
                ]);

                // Update stock
                Product::where('id', $product['product_id'])
                    ->decrement('qtd_stock', $product['quantity']);
            }

            // Generate WhatsApp message
            $whatsappMessage = $this->generateWhatsAppMessage($order, $client, $tenant);
            $whatsappLink = $this->generateWhatsAppLink($tenant->phone, $whatsappMessage);

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso',
                'data' => [
                    'order_id' => $order->identify,
                    'total' => number_format((float)$total, 2, ',', '.'),
                    'whatsapp_link' => $whatsappLink,
                    'whatsapp_message' => $whatsappMessage,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique order identify
     */
    private function generateOrderIdentify(): string
    {
        do {
            $identify = strtoupper(Str::random(8));
        } while (Order::where('identify', $identify)->exists());

        return $identify;
    }

    /**
     * Format phone to WhatsApp format
     */
    private function formatWhatsApp(?string $phone): ?string
    {
        if (!$phone) return null;

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present
        if (strlen($phone) === 11) {
            $phone = '55' . $phone;
        } elseif (strlen($phone) === 10) {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * Generate WhatsApp message
     */
    private function generateWhatsAppMessage(Order $order, Client $client, Tenant $tenant): string
    {
        $products = $order->products->map(function ($product) {
            $qty = $product->pivot->quantity;
            $price = number_format($product->pivot->price, 2, ',', '.');
            return "• {$qty}x {$product->name} - R$ {$price}";
        })->implode("\n");

        $total = number_format((float)$order->total, 2, ',', '.');

        $message = "*Novo Pedido #{$order->identify}*\n\n";
        $message .= "*Cliente:* {$client->name}\n";
        $message .= "*Telefone:* {$client->phone}\n";
        $message .= "*Email:* {$client->email}\n\n";
        $message .= "*Produtos:*\n{$products}\n\n";
        $message .= "*Total:* R$ {$total}\n\n";

        if ($order->is_delivery) {
            $address = "{$order->delivery_address}, {$order->delivery_number}";
            if ($order->delivery_complement) {
                $address .= " - {$order->delivery_complement}";
            }
            $address .= "\n{$order->delivery_neighborhood}, {$order->delivery_city}/{$order->delivery_state}";
            $address .= "\nCEP: {$order->delivery_zip_code}";

            $message .= "*Endereço de Entrega:*\n{$address}\n\n";

            if ($order->delivery_notes) {
                $message .= "*Observações:* {$order->delivery_notes}\n\n";
            }
        } else {
            $message .= "*Retirada no Local*\n\n";
        }

        $message .= "*Forma de Pagamento:* " . $this->translatePaymentMethod($order->payment_method);

        return $message;
    }

    /**
     * Generate WhatsApp link
     */
    private function generateWhatsAppLink(string $phone, string $message): string
    {
        $whatsapp = $this->formatWhatsApp($phone);
        $encodedMessage = urlencode($message);
        return "https://wa.me/{$whatsapp}?text={$encodedMessage}";
    }

    /**
     * Translate payment method
     */
    private function translatePaymentMethod(string $method): string
    {
        $methods = [
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'money' => 'Dinheiro',
            'bank_transfer' => 'Transferência Bancária',
        ];

        return $methods[$method] ?? $method;
    }
}
