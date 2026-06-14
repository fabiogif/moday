<?php

namespace App\Services\Integrations\Ifood;

use App\Repositories\Contracts\IfoodApiLogRepositoryInterface;
use App\Repositories\Contracts\IfoodCatalogSnapshotRepositoryInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class IfoodCatalogService
{
    public function __construct(
        private readonly IfoodTokenService $tokenService,
        private readonly IfoodApiLogRepositoryInterface $logRepository,
        private readonly IfoodCatalogSnapshotRepositoryInterface $snapshotRepository,
    ) {
    }

    public function listCatalogs(int $tenantId, string $merchantId): array
    {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);
        $url = $this->buildUrl(sprintf('/merchants/%s/catalogs', $merchantId));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url);

            $response->throw();

            $payload = $response->json() ?? [];

            $this->log($tenantId, 'catalog.list', 'success', [
                'merchant_id' => $merchantId,
            ]);

            $this->storeSnapshot($tenantId, $merchantId, 'catalogs', $payload);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, 'catalog.list', 'error', [
                'merchant_id' => $merchantId,
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Erro ao listar catálogos iFood', [
                'merchant_id' => $merchantId,
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, 'catalog.list', 'error', [
                'merchant_id' => $merchantId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function listCategories(int $tenantId, string $merchantId, string $catalogId, bool $includeItems = false): array
    {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);
        $queryString = $includeItems ? '?include_items=true' : '';
        $url = $this->buildUrl(sprintf('/merchants/%s/catalogs/%s/categories%s', $merchantId, $catalogId, $queryString));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url);

            $response->throw();

            $payload = $response->json() ?? [];

            $this->log($tenantId, 'catalog.categories', 'success', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'include_items' => $includeItems,
            ]);

            $this->storeSnapshot($tenantId, $merchantId, 'categories', $payload, $catalogId);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, 'catalog.categories', 'error', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'include_items' => $includeItems,
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Erro ao listar categorias iFood', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, 'catalog.categories', 'error', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'include_items' => $includeItems,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function listGroups(int $tenantId, string $merchantId, string $catalogId): array
    {
        $categories = $this->listCategories($tenantId, $merchantId, $catalogId, false);

        $groups = array_map(function (array $category) {
            return [
                'id' => $category['id'] ?? null,
                'name' => $category['name'] ?? null,
                'status' => $category['status'] ?? null,
                'sequence' => $category['sequence'] ?? null,
                'index' => $category['index'] ?? null,
                'items_count' => isset($category['items']) && is_array($category['items'])
                    ? count($category['items'])
                    : null,
            ];
        }, $categories);

        $this->storeSnapshot(
            $tenantId,
            $merchantId,
            'catalog_groups',
            $groups,
            $catalogId
        );

        return $groups;
    }

    public function paginateSnapshots(int $tenantId, array $filters = [], int $perPage = 20)
    {
        return $this->snapshotRepository->paginate($tenantId, $filters, $perPage);
    }

    public function listUnsellableItems(int $tenantId, string $merchantId, string $catalogId): array
    {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);
        $url = $this->buildUrl(sprintf('/merchants/%s/catalogs/%s/unsellableItems', $merchantId, $catalogId));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url);

            $response->throw();

            $payload = $response->json() ?? [];

            $this->log($tenantId, 'catalog.unsellable_items', 'success', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
            ]);

            $this->storeSnapshot($tenantId, $merchantId, 'unsellable_items', $payload, $catalogId);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, 'catalog.unsellable_items', 'error', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Erro ao listar itens indisponíveis do catálogo iFood', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, 'catalog.unsellable_items', 'error', [
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function listSellableItemsByGroup(int $tenantId, string $merchantId, string $groupId): array
    {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);
        $url = $this->buildUrl(sprintf('/merchants/%s/catalogs/%s/sellableItems', $merchantId, $groupId));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url);

            $response->throw();

            $payload = $response->json() ?? [];

            $this->log($tenantId, 'catalog.sellable_items', 'success', [
                'merchant_id' => $merchantId,
                'group_id' => $groupId,
            ]);

            $this->storeSnapshot($tenantId, $merchantId, 'sellable_items', $payload, null, $groupId);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, 'catalog.sellable_items', 'error', [
                'merchant_id' => $merchantId,
                'group_id' => $groupId,
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Erro ao listar itens vendáveis do grupo iFood', [
                'merchant_id' => $merchantId,
                'group_id' => $groupId,
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, 'catalog.sellable_items', 'error', [
                'merchant_id' => $merchantId,
                'group_id' => $groupId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getCatalogVersion(int $tenantId, string $merchantId): array
    {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);
        $url = $this->buildUrl(sprintf('/merchants/%s/catalog/version', $merchantId));

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($url);

            $response->throw();

            $payload = $response->json() ?? [];

            $this->log($tenantId, 'catalog.version', 'success', [
                'merchant_id' => $merchantId,
            ]);

            $this->storeSnapshot($tenantId, $merchantId, 'catalog_version', $payload);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, 'catalog.version', 'error', [
                'merchant_id' => $merchantId,
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error('Erro ao consultar versão do catálogo iFood', [
                'merchant_id' => $merchantId,
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, 'catalog.version', 'error', [
                'merchant_id' => $merchantId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function buildUrl(string $path): string
    {
        $base = config('services.ifood.catalog_base_url');

        if (!$base) {
            throw new RuntimeException('URL base do catálogo iFood não configurada.');
        }

        return rtrim($base, '/') . $path;
    }

    private function log(int $tenantId, string $context, string $status, array $payload = []): void
    {
        $this->logRepository->create([
            'tenant_id' => $tenantId,
            'level' => $status === 'success' ? 'info' : 'error',
            'context' => 'catalog',
            'action' => $context,
            'payload' => $payload,
            'message' => sprintf('iFood catalog %s (%s)', $context, $status),
        ]);
    }

    private function storeSnapshot(
        int $tenantId,
        string $merchantId,
        string $type,
        array $payload,
        ?string $catalogId = null,
        ?string $groupId = null
    ): void {
        try {
            $this->snapshotRepository->store([
                'tenant_id' => $tenantId,
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'group_id' => $groupId,
                'snapshot_type' => $type,
                'payload' => $payload,
                'captured_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::channel('ifood')->warning('Falha ao armazenar snapshot do catálogo iFood', [
                'tenant_id' => $tenantId,
                'merchant_id' => $merchantId,
                'catalog_id' => $catalogId,
                'group_id' => $groupId,
                'type' => $type,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

