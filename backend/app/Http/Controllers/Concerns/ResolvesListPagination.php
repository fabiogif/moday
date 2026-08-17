<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Parâmetros de paginação das listagens de tenant.
 *
 * Sem page/per_page/search as listagens devolviam a coleção inteira, o que
 * derruba o php-fpm por memory_limit em tenants com milhares de registros.
 */
trait ResolvesListPagination
{
    protected function listPage(Request $request): int
    {
        return max((int) $request->get('page', 1), 1);
    }

    protected function listPerPage(Request $request): int
    {
        if (! $this->hasListPaginationParams($request)) {
            return (int) config('api.listing.unpaginated_cap', 500);
        }

        return min(
            max((int) $request->get('per_page', 50), 1),
            (int) config('api.listing.max_per_page', 100)
        );
    }

    protected function listSearch(Request $request): ?string
    {
        $search = $request->get('search');
        $search = is_string($search) ? trim($search) : null;

        return $search !== '' ? $search : null;
    }

    protected function hasListPaginationParams(Request $request): bool
    {
        return $request->has('page') || $request->has('per_page') || $request->has('search');
    }

    /**
     * @return array<string, int>
     */
    protected function listMeta(LengthAwarePaginator $paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }
}
