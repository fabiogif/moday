<?php

namespace App\Services;

use App\Helpers\ImageHelper;
use App\Models\Plan;
use App\Models\Tenant;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TenantService
{
    /** Imagens da loja: campo => tipo de upload do FileUploadService (ambas no disco "logos") */
    private const IMAGE_FIELDS = ['logo' => 'logo', 'cover' => 'cover'];

    public function __construct(private readonly TenantRepositoryInterface $tenantRepositoryInterface,
                                private readonly PlanRepositoryInterface   $planRepositoryInterface,
                                private readonly FileUploadService         $fileUploadService,
                                private Plan                               $plan,
                                private array                              $data = []){}

    public function index(string $filter):PaginateRepositoryInterface
    {
        return $this->tenantRepositoryInterface->index(filter: $filter);
    }

    public function store(array $data)
    {
        $this->plan = $this->planRepositoryInterface->getById($data['plan_id']);
        $this->data = $data;
        $tenant = $this->storeTenant();
        $this->storeUser($tenant);
        app(TenantOrderStatusProvisioner::class)->provision($tenant);
        return  $tenant;
    }

    public function storeTenant()
    {
        $data = [
            'cnpj' => $this->data['cnpj'],
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'subscription' => now(),
            'expires_at' => now()->addDays(7)];

        $tenant = $this->plan->tenants()->create($data);
        
        // Garantir que o slug seja único se não foi fornecido
        if (empty($tenant->slug)) {
            $baseSlug = \Illuminate\Support\Str::slug($tenant->name);
            $slug = $baseSlug;
            $counter = 1;
            
            while (\App\Models\Tenant::where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $tenant->update(['slug' => $slug]);
        }
        
        return $tenant;
    }

    public function storeUser($tenant)
    {
        return  $tenant->users()->create([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'password' => bcrypt($this->data['password']),
        ]);
    }

    public function getTenantByUuid(string $uuid)
    {
        return $this->tenantRepositoryInterface->getTenantByUuid($uuid);
    }

    public function paginate(int $page, int $totalPerPage, string $filter):PaginateRepositoryInterface
    {
        return $this->tenantRepositoryInterface->paginate(page: $page, totalPrePage: $totalPerPage, filter:  $filter);
    }

    public function update(string $uuid, array $data)
    {
        $tenant = $this->getTenantByUuid($uuid);

        if (!$tenant) {
            return null;
        }

        // settings e um blob JSON compartilhado por varias telas (delivery, fiscal,
        // seguranca...). Mesclar em vez de substituir evita que salvar uma tela apague
        // chaves gravadas por outra.
        if (array_key_exists('settings', $data) && is_array($data['settings'])) {
            $data['settings'] = array_merge($tenant->settings ?? [], $data['settings']);
        }

        // Se o nome foi alterado, atualizar o slug
        if (isset($data['name']) && $data['name'] !== $tenant->name) {
            $baseSlug = \Illuminate\Support\Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            
            while (\App\Models\Tenant::where('slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $data['slug'] = $slug;
        }

        $replacedImages = [];
        foreach (self::IMAGE_FIELDS as $field => $uploadType) {
            $old = $this->applyImageChange($tenant, $data, $field, $uploadType);
            if ($old !== null) {
                $replacedImages[] = $old;
            }
        }

        $tenant->update($data);

        // Só apaga as imagens antigas depois que as novas foram salvas no tenant
        foreach ($replacedImages as $oldImage) {
            $this->deleteStoredImage($oldImage);
        }

        return $tenant->fresh();
    }

    /**
     * Novo arquivo em $data[$field] é enviado ao storage; "remove_{$field}" limpa o campo.
     * Qualquer outro valor do campo é ignorado (o caminho só muda por upload ou remoção).
     *
     * @return string|null imagem anterior a apagar depois de salvar, se houve troca/remoção
     */
    private function applyImageChange(Tenant $tenant, array &$data, string $field, string $uploadType): ?string
    {
        $file = $data[$field] ?? null;
        $remove = !empty($data["remove_{$field}"]);
        unset($data[$field], $data["remove_{$field}"]);

        if ($file instanceof UploadedFile) {
            $data[$field] = $this->fileUploadService->uploadFile($file, $uploadType, $tenant->uuid)['path'];
        } elseif ($remove) {
            $data[$field] = null;
        } else {
            return null;
        }

        return $tenant->{$field} ?: null;
    }

    private function deleteStoredImage(string $image): void
    {
        $path = ImageHelper::normalizeToStoragePath($image);

        if ($path && Storage::disk('logos')->exists($path)) {
            Storage::disk('logos')->delete($path);
        }
    }
}
