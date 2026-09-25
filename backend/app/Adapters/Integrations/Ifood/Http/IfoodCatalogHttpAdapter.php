<?php

namespace App\Adapters\Integrations\Ifood\Http;

use App\Ports\Integrations\Ifood\IfoodCatalogPort;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IfoodCatalogHttpAdapter implements IfoodCatalogPort
{
    public function __construct(
        private readonly string $catalogBaseUrl,
    ) {
    }

    public static function makeFromConfig(): self
    {
        $config = config('services.ifood');

        if (!$config || empty($config['catalog_base_url'])) {
            throw new RuntimeException('Configuração de catalog_base_url do iFood ausente.');
        }

        return new self(
            catalogBaseUrl: rtrim($config['catalog_base_url'], '/'),
        );
    }

    public function listCatalogs(string $accessToken, string $merchantId): array
    {
        return $this->get($accessToken, sprintf('/merchants/%s/catalogs', $merchantId));
    }

    public function listCategories(string $accessToken, string $merchantId, string $catalogId, bool $includeItems = false): array
    {
        $queryString = $includeItems ? '?include_items=true' : '';

        return $this->get($accessToken, sprintf('/merchants/%s/catalogs/%s/categories%s', $merchantId, $catalogId, $queryString));
    }

    public function listUnsellableItems(string $accessToken, string $merchantId, string $catalogId): array
    {
        return $this->get($accessToken, sprintf('/merchants/%s/catalogs/%s/unsellableItems', $merchantId, $catalogId));
    }

    public function listSellableItemsByGroup(string $accessToken, string $merchantId, string $groupId): array
    {
        return $this->get($accessToken, sprintf('/merchants/%s/catalogs/%s/sellableItems', $merchantId, $groupId));
    }

    public function getCatalogVersion(string $accessToken, string $merchantId): array
    {
        return $this->get($accessToken, sprintf('/merchants/%s/catalog/version', $merchantId));
    }

    /**
     * @throws RequestException
     */
    private function get(string $accessToken, string $path): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get($this->catalogBaseUrl . $path);

        $response->throw();

        return $response->json() ?? [];
    }
}
