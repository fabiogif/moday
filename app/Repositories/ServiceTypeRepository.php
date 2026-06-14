<?php

namespace App\Repositories;

use App\Models\ServiceType;
use App\Repositories\Contracts\ServiceTypeRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Support\Facades\Log;

class ServiceTypeRepository extends BaseRepository implements ServiceTypeRepositoryInterface
{
    public function __construct()
    {
        $this->entity = new ServiceType();
    }

    public function getServiceTypeByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function getServiceTypeByIdentify(string $identify)
    {
        return $this->entity->where('identify', $identify)->first();
    }

    public function getServiceTypeBySlug(string $slug)
    {
        return $this->entity->where('slug', $slug)->first();
    }

    public function getActiveServiceTypes()
    {
        return $this->entity->active()->ordered()->get();
    }

    public function getMenuServiceTypes()
    {
        return $this->entity->availableInMenu()->ordered()->get();
    }

    /**
     * Sobrescreve o método paginate para garantir exclusão de soft deletes
     */
    public function paginate(int $page = 1, int $totalPrePage = 15, ?string $filter = null): PaginateRepositoryInterface
    {
        $result = $this->entity->where(function($query) use($filter) {
            if($filter) {
               $query->where('name' ,'like', "%{$filter}%");
            }
            $query->where('is_active', '!=', '0');
        })
        // Garantir que soft deletes sejam excluídos (whereNull('deleted_at'))
        ->whereNull('deleted_at')
        ->paginate(perPage: $totalPrePage, columns: ['*'], pageName:'page', page: $page, total: null);
        
        return new PaginatePresenter($result);
    }

    public function delete(string $identify)
    {
        Log::info('ServiceTypeRepository::delete - Buscando tipo de atendimento', ['identify' => $identify]);
        
        // Buscar por uuid ou identify
        // Primeiro tenta por uuid, depois por identify
        $serviceType = $this->entity->where('uuid', $identify)->first();
        
        if (!$serviceType) {
            Log::info('ServiceTypeRepository::delete - Não encontrado por UUID, tentando por identify', ['identify' => $identify]);
            $serviceType = $this->entity->where('identify', $identify)->first();
        }
        
        if ($serviceType) {
            Log::info('ServiceTypeRepository::delete - Tipo encontrado, realizando soft delete', [
                'id' => $serviceType->id,
                'uuid' => $serviceType->uuid,
                'identify' => $serviceType->identify,
                'name' => $serviceType->name
            ]);
            
            // Soft delete (marca deleted_at)
            $deleted = $serviceType->delete();
            
            Log::info('ServiceTypeRepository::delete - Resultado do soft delete', [
                'deleted' => $deleted,
                'deleted_at' => $serviceType->fresh()->deleted_at
            ]);
            
            return $deleted;
        }
        
        Log::warning('ServiceTypeRepository::delete - Tipo de atendimento não encontrado', ['identify' => $identify]);
        return false;
    }
}

