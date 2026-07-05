<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Logistics\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PodController extends Controller
{
    private const PHOTO_MAX_KB = 10240;

    private const ALLOWED_PHOTO_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/webp',
        'image/heic',
        'image/heif',
        'image/heic-sequence',
        'image/heif-sequence',
    ];

    private const ALLOWED_PHOTO_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif',
    ];

    public function __construct(private readonly ShipmentService $shipmentService) {}

    public function show(string $token): JsonResponse
    {
        $shipment = Shipment::where('delivery_token', $token)
            ->where('status', 'dispatched')
            ->with(['saleOrders.client'])
            ->first();

        if (!$shipment) {
            return response()->json(['success' => false, 'message' => 'Romaneio não encontrado ou já encerrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatDelivery($shipment),
        ]);
    }

    public function submitPod(Request $request, string $token, int $orderId): JsonResponse
    {
        $shipment = Shipment::where('delivery_token', $token)
            ->where('status', 'dispatched')
            ->first();

        if (!$shipment) {
            return response()->json(['success' => false, 'message' => 'Romaneio não encontrado ou já encerrado'], 404);
        }

        $exists = DB::table('shipment_sale_order')
            ->where('shipment_id', $shipment->id)
            ->where('sale_order_id', $orderId)
            ->exists();

        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Pedido não encontrado neste romaneio'], 404);
        }

        $photoFile = $request->file('photo');
        if ($photoFile && !$photoFile->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Foto invalida ou corrompida. Tire a foto novamente.',
            ], 422);
        }

        if ($photoFile && $photoFile->getSize() > self::PHOTO_MAX_KB * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'A foto deve ter no maximo 10 MB.',
            ], 422);
        }

        if ($photoFile) {
            $photoError = null;
            $this->validatePhoto($photoFile, function (string $message) use (&$photoError) {
                $photoError = $message;
            });
            if ($photoError) {
                return response()->json(['success' => false, 'message' => $photoError], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'status'         => 'required|in:delivered,refused,partial',
            'recipient_name' => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'notes'          => 'nullable|string|max:1000',
            'signature'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Dados inválidos.',
            ], 422);
        }

        $validated = $validator->validated();

        $photoPath = null;
        $sigPath   = null;
        $basePath  = "pod/{$shipment->tenant_id}/{$shipment->id}/{$orderId}";

        if ($request->hasFile('photo')) {
            try {
                $photo     = $request->file('photo');
                $extension = $this->resolvePhotoExtension($photo);
                $filename  = now()->format('Ymd_His') . '_photo_' . Str::random(8) . '.' . $extension;
                $photoPath = Storage::disk('public')->putFileAs($basePath, $photo, $filename);
            } catch (\Throwable $e) {
                Log::error('PodController: falha ao salvar foto', [
                    'shipment_id' => $shipment->id,
                    'order_id'    => $orderId,
                    'error'       => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao salvar a foto da entrega. Tente novamente.',
                ], 500);
            }
        }

        if (!empty($validated['signature'])) {
            $sigData = $validated['signature'];
            if (str_contains($sigData, ',')) {
                $sigData = substr($sigData, strpos($sigData, ',') + 1);
            }
            $sigBinary = base64_decode($sigData);
            if ($sigBinary !== false && strlen($sigBinary) > 100) {
                $filename = now()->format('Ymd_His') . '_sig_' . Str::random(8) . '.png';
                Storage::disk('public')->put("{$basePath}/{$filename}", $sigBinary);
                $sigPath = "{$basePath}/{$filename}";
            }
        }

        DB::table('shipment_sale_order')
            ->where('shipment_id', $shipment->id)
            ->where('sale_order_id', $orderId)
            ->update([
                'pod_status'         => $validated['status'],
                'pod_recipient_name' => $validated['recipient_name'] ?? null,
                'pod_latitude'       => $validated['latitude'] ?? null,
                'pod_longitude'      => $validated['longitude'] ?? null,
                'pod_notes'          => $validated['notes'] ?? null,
                'pod_photo_path'     => $photoPath,
                'pod_signature_path' => $sigPath,
                'pod_delivered_at'   => now(),
            ]);

        $pendingCount = DB::table('shipment_sale_order')
            ->where('shipment_id', $shipment->id)
            ->whereNull('pod_status')
            ->count();

        if ($pendingCount === 0) {
            try {
                $this->shipmentService->deliver(
                    $shipment->fresh(['saleOrders']),
                    $shipment->created_by
                );
            } catch (StockException) {
                // Already delivered by concurrent submission ? safe to ignore
            }
        }

        return response()->json(['success' => true, 'message' => 'Comprovante registrado com sucesso']);
    }

    private function validatePhoto(UploadedFile $file, callable $fail): void
    {
        $mime = strtolower((string) $file->getMimeType());
        $ext  = strtolower((string) $file->getClientOriginalExtension());

        $mimeOk = in_array($mime, self::ALLOWED_PHOTO_MIMES, true);
        $extOk  = in_array($ext, self::ALLOWED_PHOTO_EXTENSIONS, true);

        if (!$mimeOk && !$extOk) {
            $fail('Formato de foto nao suportado. Use JPG, PNG ou WEBP.');
        }
    }

    private function resolvePhotoExtension(UploadedFile $photo): string
    {
        $ext = strtolower((string) $photo->getClientOriginalExtension());
        if (in_array($ext, self::ALLOWED_PHOTO_EXTENSIONS, true)) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        $mime = strtolower((string) $photo->getMimeType());

        return match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence' => 'heic',
            default      => 'jpg',
        };
    }

    private function formatDelivery(Shipment $shipment): array
    {
        return [
            'id'            => $shipment->id,
            'identify'      => $shipment->identify,
            'driver_name'   => $shipment->driver_name,
            'vehicle_plate' => $shipment->vehicle_plate,
            'orders'        => $shipment->saleOrders
                ->map(fn ($order) => [
                    'id'                => $order->id,
                    'identify'          => $order->identify,
                    'client_name'       => $order->client
                        ? ($order->client->trade_name ?: $order->client->company_name ?: $order->client->name)
                        : null,
                    'address'           => implode(', ', array_filter([
                        $order->shipping_city  ?? null,
                        $order->shipping_state ?? null,
                    ])),
                    'shipping_zipcode'  => $order->shipping_zipcode ?? null,
                    'delivery_sequence' => $order->pivot->delivery_sequence,
                    'pod_status'        => $order->pivot->pod_status,
                    'pod_delivered_at'  => $order->pivot->pod_delivered_at,
                ])
                ->sortBy('delivery_sequence')
                ->values(),
        ];
    }
}
