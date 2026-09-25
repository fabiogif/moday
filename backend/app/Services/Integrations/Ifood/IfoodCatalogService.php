<?php

namespace App\Services\Integrations\Ifood;

use App\Ports\Integrations\Ifood\IfoodCatalogPort;
use App\Repositories\Contracts\IfoodApiLogRepositoryInterface;
use App\Repositories\Contracts\IfoodCatalogSnapshotRepositoryInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;

class IfoodCatalogService
{
    public function __construct(
        private readonly IfoodTokenService $tokenService,
        private readonly IfoodCatalogPort $catalogPort,
        private readonly IfoodApiLogRepositoryInterface $logRepository,
        private readonly IfoodCatalogSnapshotRepositoryInterface $snapshotRepository,
    ) {
    }

    public function listCatalogs(int $tenantId, string $merchantId): array
    {
        return $this->executeCatalogCall(
            tenantId: $tenantId,
            context: 'catalog.list',
            snapshotType: 'catalogs',
            merchantId: $merchantId,
            fetch: fn (string $accessToken) => $this->catalogPort->listCatalogs($accessToken, $merchantId),
            logContext: ['merchant_id' => $merchantId],
        );
    }

    public function listCategories(int $tenantId, string $merchantId, string $catalogId, bool $includeItems = false): array
    {
        return $this->executeCatalogCall(
            tenantId: $tenantId,
            context: 'catalog.categories',
            snapshotType: 'categories',
            merchantId: $merchantId,
            fetch: fn (string $accessToken) => $this->catalogPort->listCategories($accessToken, $merchantId, $catalogId, $includeItems),
            logContext: ['merchant_id' => $merchantId, 'catalog_id' => $catalogId, 'include_items' => $includeItems],
            catalogId: $catalogId,
        );
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

        $this->storeSnapshot($tenantId, $merchantId, 'catalog_groups', $groups, $catalogId);

        return $groups;
    }

    public function paginateSnapshots(int $tenantId, array $filters = [], int $perPage = 20)
    {
        return $this->snapshotRepository->paginate($tenantId, $filters, $perPage);
    }

    public function listUnsellableItems(int $tenantId, string $merchantId, string $catalogId): array
    {
        return $this->executeCatalogCall(
            tenantId: $tenantId,
            context: 'catalog.unsellable_items',
            snapshotType: 'unsellable_items',
            merchantId: $merchantId,
            fetch: fn (string $accessToken) => $this->catalogPort->listUnsellableItems($accessToken, $merchantId, $catalogId),
            logContext: ['merchant_id' => $merchantId, 'catalog_id' => $catalogId],
            catalogId: $catalogId,
        );
    }

    public function listSellableItemsByGroup(int $tenantId, string $merchantId, string $groupId): array
    {
        return $this->executeCatalogCall(
            tenantId: $tenantId,
            context: 'catalog.sellable_items',
            snapshotType: 'sellable_items',
            merchantId: $merchantId,
            fetch: fn (string $accessToken) => $this->catalogPort->listSellableItemsByGroup($accessToken, $merchantId, $groupId),
            logContext: ['merchant_id' => $merchantId, 'group_id' => $groupId],
            groupId: $groupId,
        );
    }

    public function getCatalogVersion(int $tenantId, string $merchantId): array
    {
        return $this->executeCatalogCall(
            tenantId: $tenantId,
            context: 'catalog.version',
            snapshotType: 'catalog_version',
            merchantId: $merchantId,
            fetch: fn (string $accessToken) => $this->catalogPort->getCatalogVersion($accessToken, $merchantId),
            logContext: ['merchant_id' => $merchantId],
        );
    }

    /**
     * Skeleton único para: obter token, chamar a Port, logar sucesso/erro e
     * armazenar snapshot — antes duplicado em cada um dos 5 métodos acima.
     */
    private function executeCatalogCall(
        int $tenantId,
        string $context,
        string $snapshotType,
        string $merchantId,
        \Closure $fetch,
        array $logContext = [],
        ?string $catalogId = null,
        ?string $groupId = null,
    ): array {
        $accessToken = $this->tokenService->getValidAccessToken($tenantId);

        try {
            $payload = $fetch($accessToken);

            $this->log($tenantId, $context, 'success', $logContext);
            $this->storeSnapshot($tenantId, $merchantId, $snapshotType, $payload, $catalogId, $groupId);

            return $payload;
        } catch (RequestException $exception) {
            $this->log($tenantId, $context, 'error', $logContext + [
                'response' => $exception->response?->json(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('ifood')->error("Erro ao executar {$context} do catálogo iFood", $logContext + [
                'exception' => $exception->getMessage(),
            ]);

            $this->log($tenantId, $context, 'error', $logContext + [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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
