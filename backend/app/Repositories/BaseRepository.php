<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\{Model};
use App\Repositories\contracts\{BaseRepositoryInterface,
    PaginateRepositoryInterface,
    Presenter\PaginatePresenter};

use stdClass;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $entity;

    public function index(string $filter = null): array
    {
        return $this->entity->where(function($query) use($filter) {
            if($filter) {
                $query->where('name' ,'like', "%{$filter}%");
            }
        })->get()->toArray();
    }

    public function getById(int $id)
    {
        return $this->entity->where('id', $id)->first();
    }

    public function store(array $data)
    {
        return $this->entity->create($data);
    }

    public function update(array $data, $id):mixed
    {
        return $this->entity->whereId($id)->update($data);
    }

    public function delete(string $identify)
    {
        return $this->entity->where('uuid',  $identify)->update(['status'=> 'I']);
    }

    public function paginate(int $page = 1, int $totalPrePage = 15, string $filter = null): PaginateRepositoryInterface
    {
        $result = $this->entity->where(function($query) use($filter) {
            if($filter) {
               $query->where('name' ,'like', "%{$filter}%");
            }
            $query->where('is_active', '!=', '0');
        })->paginate(perPage: $totalPrePage, columns: ['*'], pageName:'page', page: $page, total: null);
        return new PaginatePresenter($result);
    }
}
