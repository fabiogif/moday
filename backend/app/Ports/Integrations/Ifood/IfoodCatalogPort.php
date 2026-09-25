<?php

namespace App\Ports\Integrations\Ifood;

interface IfoodCatalogPort
{
    public function listCatalogs(string $accessToken, string $merchantId): array;

    public function listCategories(string $accessToken, string $merchantId, string $catalogId, bool $includeItems = false): array;

    public function listUnsellableItems(string $accessToken, string $merchantId, string $catalogId): array;

    public function listSellableItemsByGroup(string $accessToken, string $merchantId, string $groupId): array;

    public function getCatalogVersion(string $accessToken, string $merchantId): array;
}
