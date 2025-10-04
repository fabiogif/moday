<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Profile;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_pode_ter_tenant()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertEquals($tenant->id, $user->tenant->id);
    }

    /** @test */
    public function user_pode_ter_profile()
    {
        $profile = Profile::factory()->create();
        $user = User::factory()->create(['profile_id' => $profile->id]);

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }

    /** @test */
    public function scope_active_retorna_apenas_usuarios_ativos()
    {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);
        User::factory()->create(['is_active' => true]);

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
        $this->assertTrue($activeUsers->every(fn($user) => $user->is_active));
    }

    /** @test */
    public function scope_for_tenant_retorna_usuarios_do_tenant()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        User::factory()->create(['tenant_id' => $tenant1->id]);
        User::factory()->create(['tenant_id' => $tenant2->id]);
        User::factory()->create(['tenant_id' => $tenant1->id]);

        $tenant1Users = User::forTenant($tenant1->id)->get();

        $this->assertCount(2, $tenant1Users);
        $this->assertTrue($tenant1Users->every(fn($user) => $user->tenant_id === $tenant1->id));
    }

    /** @test */
    public function has_permission_verifica_permissoes_do_usuario()
    {
        $permission = Permission::factory()->create(['name' => 'create_posts']);
        $profile = Profile::factory()->create();
        $profile->permissions()->attach($permission);
        
        $user = User::factory()->create(['profile_id' => $profile->id]);
        $user->load('profile.permissions');

        $this->assertTrue($user->hasPermission('create_posts'));
        $this->assertFalse($user->hasPermission('delete_posts'));
    }

    /** @test */
    public function update_last_login_atualiza_campo_corretamente()
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $user->updateLastLogin();

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /** @test */
    public function jwt_identifier_retorna_id_do_usuario()
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    /** @test */
    public function jwt_custom_claims_retorna_dados_corretos()
    {
        $tenant = Tenant::factory()->create();
        $profile = Profile::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'profile_id' => $profile->id,
            'is_active' => true
        ]);

        $claims = $user->getJWTCustomClaims();

        $this->assertEquals($tenant->id, $claims['tenant_id']);
        $this->assertEquals($profile->id, $claims['profile_id']);
        $this->assertTrue($claims['is_active']);
    }

    /** @test */
    public function password_e_automaticamente_hasheada()
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertTrue(password_verify('plain-password', $user->getAuthPassword()));
    }

    /** @test */
    public function campos_hidden_nao_aparecem_na_serializacao()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function campos_fillable_podem_ser_mass_assigned()
    {
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'tenant_id' => 1,
            'profile_id' => 1,
            'is_active' => true,
            'phone' => '11999999999',
            'avatar' => 'avatar.jpg',
            'preferences' => ['theme' => 'dark']
        ];

        $user = new User($data);

        foreach (array_keys($data) as $field) {
            if ($field !== 'password') { // password é hasheada
                $this->assertEquals($data[$field], $user->{$field});
            }
        }
    }

    /** @test */
    public function soft_deletes_funciona_corretamente()
    {
        $user = User::factory()->create();
        
        $user->delete();
        
        $this->assertSoftDeleted($user);
        $this->assertNotNull($user->deleted_at);
    }
}
