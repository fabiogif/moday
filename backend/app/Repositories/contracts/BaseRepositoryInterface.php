<?php

namespace App\Repositories\contracts;

interface BaseRepositoryInterface
{
    public function index(?string $filter = null);
    public function getById(int $id);
    public function store(array $data);
    public function update(array $data, $id);
    public function delete(string $identify);
    public function paginate(int $page = 1, int $totalPrePage = 10, ?string $filter = null): PaginateRepositoryInterface;
}
