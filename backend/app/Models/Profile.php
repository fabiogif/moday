<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tenant_id',
        'is_active',
        'max_discount_percent',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $profile) {
            if ($profile->users()->exists() || User::where('profile_id', $profile->id)->exists()) {
                return false;
            }

            $profile->permissions()->detach();
            $profile->plans()->detach();

            return true;
        });
    }

    /**
     * Relacionamento com tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com usuários
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_profiles');
    }

    /**
     * Relacionamento com permissões
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_profiles');
    }

    /**
     * Relacionamento com planos
     */
    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }

    /**
     * Scope para perfis ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para perfis de um tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function search($filter)
    {
        return $this->where('name', 'LIKE', "%{$filter}%")->orWhere('description', 'LIKE', "%{$filter}%")->paginate(10);
    }

    public function permissionAvailable($search = null)
    {
        return Permission::whereNotIn('permissions.id', function($query) {
            $query->select('permission_profiles.permission_id')
                ->from('permission_profiles')->whereRaw("permission_profiles.profile_id = {$this->id}");
        })->where(function ($query) use ($search){
            if($search != null){
                $query->where('name', 'LIKE', "%{$search}%")->orWhere('description', 'LIKE', "%{$search}%");
            }
        }) ->paginate();
    }
}
