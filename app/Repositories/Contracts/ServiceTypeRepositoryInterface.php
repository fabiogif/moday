<?php

namespace App\Repositories\Contracts;

interface ServiceTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function getServiceTypeByUuid(string $uuid);
    public function getServiceTypeByIdentify(string $identify);
    public function getServiceTypeBySlug(string $slug);
    public function getActiveServiceTypes();
    public function getMenuServiceTypes();
}


