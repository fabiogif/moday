<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_active', 'tenant_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function permissionsAvailable($filter = null)
    {
        $permissions = Permission::whereNotIn('permissions.id', function ($query) {
            $query->select('role_permissions.permission_id');
            $query->from('role_permissions');
            $query->whereRaw("role_permissions.role_id={$this->id}");
        })->where(function ($queryFilter) use ($filter) {
            if ($filter) {
                $queryFilter->where('permissions.name', 'LIKE', "%{$filter}%");
            }
        })->paginate();

        return $permissions;
    }
}