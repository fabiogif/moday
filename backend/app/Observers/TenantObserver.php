<?php

namespace App\Observers;
use Illuminate\Support\Str;
use App\Models\Tenant;

class TenantObserver
{

    public function creating(Tenant $tenant): void
    {
        $tenant->uuid = Str::uuid();
        $tenant->url = Str::kebab($tenant->name);
    }


    public function updating(Tenant $tenant): void
    {
        $tenant->url = Str::kebab($tenant->name);

    }


}
