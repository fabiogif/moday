<?php

namespace App\Repositories\contracts\Presenter;

use App\Repositories\contracts\PaginateRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginatePresenter implements PaginateRepositoryInterface
{
    /**
     * @var \Illuminate\Database\Eloquent\Model[]
     */
    private array $items;
    public function __construct(protected LengthAwarePaginator $paginator, protected  $relationships = array())
    {
        $this->items  = $this->resultItems($this->paginator->items(), $relationships);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->paginator->total() ?? 0;
    }

    public function isFirstPage(): bool
    {
        return $this->paginator->onFirstPage();
    }

    public function isLastPage(): bool
    {
        return $this->paginator->currentPage() === $this->paginator->lastPage();
    }

    public function currentPage(): int
    {
        return $this->paginator->currentPage() ?? 1;
    }

    public function getNumberNextPage(): int
    {
        return $this->paginator->currentPage() + 1;
    }

    public function getNumberPreviousPage(): int
    {
        return $this->paginator->currentPage() - 1;
    }

    private function resultItems(array $items, $relationships): array
    {
        $response = [];

        foreach ($items as $item) {
            // Load relationships if they haven't been loaded yet
            if (!empty($relationships)) {
                $item->load($relationships);
            }
            
            // Return the Eloquent model directly instead of converting to stdClass
            // This preserves accessors and methods
            $response[] = $item;
        }
        
        return $response;
    }
}
