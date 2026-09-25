<?php

namespace App\Http\Controllers\Api\Integrations\Ifood;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Resources\Integrations\Ifood\IfoodCatalogSnapshotResource;
use App\Services\Integrations\Ifood\IfoodCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class IfoodCatalogController extends Controller
{
    public function __construct(
        private readonly IfoodCatalogService $catalogService,
    ) {
    }

    public function catalogs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $catalogs = $this->catalogService->listCatalogs($tenantId, $data['merchant_id']);

        return ApiResponseClass::sendResponse(
            $catalogs,
            'Catálogos iFood recuperados com sucesso.',
            Response::HTTP_OK
        );
    }

    public function categories(Request $request, string $catalogId): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
            'include_items' => ['nullable', 'boolean'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $categories = $this->catalogService->listCategories(
            $tenantId,
            $data['merchant_id'],
            $catalogId,
            (bool) ($data['include_items'] ?? false)
        );

        return ApiResponseClass::sendResponse(
            $categories,
            'Categorias do catálogo iFood recuperadas com sucesso.',
            Response::HTTP_OK
        );
    }

    public function groups(Request $request, string $catalogId): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $groups = $this->catalogService->listGroups(
            $tenantId,
            $data['merchant_id'],
            $catalogId,
        );

        return ApiResponseClass::sendResponse(
            $groups,
            'Grupos do catálogo iFood recuperados com sucesso.',
            Response::HTTP_OK
        );
    }

    public function unsellableItems(Request $request, string $catalogId): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $items = $this->catalogService->listUnsellableItems(
            $tenantId,
            $data['merchant_id'],
            $catalogId
        );

        return ApiResponseClass::sendResponse(
            $items,
            'Itens indisponíveis do catálogo iFood recuperados com sucesso.',
            Response::HTTP_OK
        );
    }

    public function sellableItems(Request $request, string $groupId): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $items = $this->catalogService->listSellableItemsByGroup(
            $tenantId,
            $data['merchant_id'],
            $groupId
        );

        return ApiResponseClass::sendResponse(
            $items,
            'Itens vendáveis do catálogo iFood recuperados com sucesso.',
            Response::HTTP_OK
        );
    }

    public function version(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $version = $this->catalogService->getCatalogVersion(
            $tenantId,
            $data['merchant_id']
        );

        return ApiResponseClass::sendResponse(
            $version,
            'Versão do catálogo iFood recuperada com sucesso.',
            Response::HTTP_OK
        );
    }

    public function snapshots(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'merchant_id' => ['required', 'string'],
            'catalog_id' => ['nullable', 'string'],
            'group_id' => ['nullable', 'string'],
            'snapshot_type' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenantId = $this->resolveTenantId($request);

        $snapshots = $this->catalogService->paginateSnapshots(
            $tenantId,
            $data,
            $data['per_page'] ?? 20,
        );

        return ApiResponseClass::sendResponsePaginate(
            IfoodCatalogSnapshotResource::class,
            $snapshots,
            Response::HTTP_OK
        );
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->integer('tenant_id')
            ?? $request->header('X-Tenant-Id')
            ?? Auth::user()?->tenant_id;

        if (!$tenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => 'tenant_id não informado.',
            ]);
        }

        return (int) $tenantId;
    }
}

