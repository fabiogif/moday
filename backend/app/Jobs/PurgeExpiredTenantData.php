<?php

namespace App\Jobs;

use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LGPD D120 — hard-delete tenant data for tenants whose data_deletion_scheduled_at has passed.
 *
 * Runs weekly. Deletes in a safe order to respect FKs:
 * subscription_events → invoices → users → tenant
 */
class PurgeExpiredTenantData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(): void
    {
        Log::info('PurgeExpiredTenantData: iniciando');

        $tenants = Tenant::pendingDeletion()->get();

        $purged = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->purgeTenant($tenant);
                $purged++;
            } catch (\Throwable $e) {
                Log::error('PurgeExpiredTenantData: falha ao purgar tenant', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info("PurgeExpiredTenantData: concluído. {$purged} tenants purgados.");
    }

    private function purgeTenant(Tenant $tenant): void
    {
        Log::warning('PurgeExpiredTenantData: deletando dados do tenant', ['tenant_id' => $tenant->id]);

        DB::transaction(function () use ($tenant) {
            // Log the deletion before wiping audit trail
            try {
                SubscriptionEvent::create([
                    'tenant_id'  => $tenant->id,
                    'event_type' => 'data_deleted',
                    'metadata'   => ['deletion_date' => now()->toIso8601String()],
                ]);
            } catch (\Throwable) {
                // Ignore if audit table is already cleared
            }

            // Delete in dependency order
            DB::table('subscription_dunning_events')->where('tenant_id', $tenant->id)->delete();
            DB::table('subscription_events')->where('tenant_id', $tenant->id)->delete();
            DB::table('subscription_invoices')->where('tenant_id', $tenant->id)->delete();
            DB::table('mp_webhook_events')->where('tenant_id', $tenant->id)->delete();
            DB::table('trial_fingerprints')->where('tenant_id', $tenant->id)->delete();

            // Delete users
            $tenant->users()->delete();

            // Hard-delete tenant
            $tenant->forceDelete();
        });
    }
}
