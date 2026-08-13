<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tymon\JWTAuth\Facades\JWTAuth;

class FinancialPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Profile $profile;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Models\Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->profile = Profile::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Administrador Financeiro',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'profile_id' => $this->profile->id,
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    private function getAuthHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function it_can_create_financial_permissions_for_suppliers()
    {
        $permissions = [
            ['name' => 'Visualizar Fornecedores', 'slug' => 'suppliers.index', 'module' => 'suppliers', 'action' => 'index', 'resource' => 'supplier'],
            ['name' => 'Criar Fornecedores', 'slug' => 'suppliers.store', 'module' => 'suppliers', 'action' => 'store', 'resource' => 'supplier'],
            ['name' => 'Editar Fornecedores', 'slug' => 'suppliers.update', 'module' => 'suppliers', 'action' => 'update', 'resource' => 'supplier'],
            ['name' => 'Excluir Fornecedores', 'slug' => 'suppliers.destroy', 'module' => 'suppliers', 'action' => 'destroy', 'resource' => 'supplier'],
            ['name' => 'Ver Detalhes do Fornecedor', 'slug' => 'suppliers.show', 'module' => 'suppliers', 'action' => 'show', 'resource' => 'supplier'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::create(array_merge($permissionData, [
                'tenant_id' => $this->tenant->id,
                'description' => $permissionData['name'],
                'is_active' => true,
            ]));

            $this->assertDatabaseHas('permissions', [
                'slug' => $permissionData['slug'],
                'tenant_id' => $this->tenant->id,
            ]);
        }

        $this->assertEquals(5, Permission::where('module', 'suppliers')->count());
    }

    #[Test]
    public function it_can_create_financial_permissions_for_categories()
    {
        $permissions = [
            ['name' => 'Visualizar Categorias Financeiras', 'slug' => 'financial-categories.index', 'module' => 'financial-categories', 'action' => 'index', 'resource' => 'financial-category'],
            ['name' => 'Criar Categorias Financeiras', 'slug' => 'financial-categories.store', 'module' => 'financial-categories', 'action' => 'store', 'resource' => 'financial-category'],
            ['name' => 'Editar Categorias Financeiras', 'slug' => 'financial-categories.update', 'module' => 'financial-categories', 'action' => 'update', 'resource' => 'financial-category'],
            ['name' => 'Excluir Categorias Financeiras', 'slug' => 'financial-categories.destroy', 'module' => 'financial-categories', 'action' => 'destroy', 'resource' => 'financial-category'],
            ['name' => 'Ver Detalhes da Categoria Financeira', 'slug' => 'financial-categories.show', 'module' => 'financial-categories', 'action' => 'show', 'resource' => 'financial-category'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create(array_merge($permissionData, [
                'tenant_id' => $this->tenant->id,
                'description' => $permissionData['name'],
                'is_active' => true,
            ]));
        }

        $this->assertEquals(5, Permission::where('module', 'financial-categories')->count());
    }

    #[Test]
    public function it_can_create_financial_permissions_for_expenses()
    {
        $permissions = [
            ['name' => 'Visualizar Despesas', 'slug' => 'expenses.index', 'module' => 'expenses', 'action' => 'index', 'resource' => 'expense'],
            ['name' => 'Criar Despesas', 'slug' => 'expenses.store', 'module' => 'expenses', 'action' => 'store', 'resource' => 'expense'],
            ['name' => 'Editar Despesas', 'slug' => 'expenses.update', 'module' => 'expenses', 'action' => 'update', 'resource' => 'expense'],
            ['name' => 'Excluir Despesas', 'slug' => 'expenses.destroy', 'module' => 'expenses', 'action' => 'destroy', 'resource' => 'expense'],
            ['name' => 'Ver Detalhes da Despesa', 'slug' => 'expenses.show', 'module' => 'expenses', 'action' => 'show', 'resource' => 'expense'],
            ['name' => 'Visualizar Estatísticas de Despesas', 'slug' => 'expenses.stats', 'module' => 'expenses', 'action' => 'stats', 'resource' => 'expense'],
            ['name' => 'Anexar Arquivo à Despesa', 'slug' => 'expenses.attachment', 'module' => 'expenses', 'action' => 'attachment', 'resource' => 'expense'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create(array_merge($permissionData, [
                'tenant_id' => $this->tenant->id,
                'description' => $permissionData['name'],
                'is_active' => true,
            ]));
        }

        $this->assertEquals(7, Permission::where('module', 'expenses')->count());
    }

    #[Test]
    public function it_can_create_financial_permissions_for_accounts_payable()
    {
        $permissions = [
            ['name' => 'Visualizar Contas a Pagar', 'slug' => 'accounts-payable.index', 'module' => 'accounts-payable', 'action' => 'index', 'resource' => 'account-payable'],
            ['name' => 'Criar Contas a Pagar', 'slug' => 'accounts-payable.store', 'module' => 'accounts-payable', 'action' => 'store', 'resource' => 'account-payable'],
            ['name' => 'Editar Contas a Pagar', 'slug' => 'accounts-payable.update', 'module' => 'accounts-payable', 'action' => 'update', 'resource' => 'account-payable'],
            ['name' => 'Excluir Contas a Pagar', 'slug' => 'accounts-payable.destroy', 'module' => 'accounts-payable', 'action' => 'destroy', 'resource' => 'account-payable'],
            ['name' => 'Ver Detalhes da Conta a Pagar', 'slug' => 'accounts-payable.show', 'module' => 'accounts-payable', 'action' => 'show', 'resource' => 'account-payable'],
            ['name' => 'Pagar Conta', 'slug' => 'accounts-payable.pay', 'module' => 'accounts-payable', 'action' => 'pay', 'resource' => 'account-payable'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create(array_merge($permissionData, [
                'tenant_id' => $this->tenant->id,
                'description' => $permissionData['name'],
                'is_active' => true,
            ]));
        }

        $this->assertEquals(6, Permission::where('module', 'accounts-payable')->count());
    }

    #[Test]
    public function it_can_create_financial_permissions_for_accounts_receivable()
    {
        $permissions = [
            ['name' => 'Visualizar Contas a Receber', 'slug' => 'accounts-receivable.index', 'module' => 'accounts-receivable', 'action' => 'index', 'resource' => 'account-receivable'],
            ['name' => 'Criar Contas a Receber', 'slug' => 'accounts-receivable.store', 'module' => 'accounts-receivable', 'action' => 'store', 'resource' => 'account-receivable'],
            ['name' => 'Editar Contas a Receber', 'slug' => 'accounts-receivable.update', 'module' => 'accounts-receivable', 'action' => 'update', 'resource' => 'account-receivable'],
            ['name' => 'Excluir Contas a Receber', 'slug' => 'accounts-receivable.destroy', 'module' => 'accounts-receivable', 'action' => 'destroy', 'resource' => 'account-receivable'],
            ['name' => 'Ver Detalhes da Conta a Receber', 'slug' => 'accounts-receivable.show', 'module' => 'accounts-receivable', 'action' => 'show', 'resource' => 'account-receivable'],
            ['name' => 'Receber Conta', 'slug' => 'accounts-receivable.receive', 'module' => 'accounts-receivable', 'action' => 'receive', 'resource' => 'account-receivable'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create(array_merge($permissionData, [
                'tenant_id' => $this->tenant->id,
                'description' => $permissionData['name'],
                'is_active' => true,
            ]));
        }

        $this->assertEquals(6, Permission::where('module', 'accounts-receivable')->count());
    }

    #[Test]
    public function it_can_assign_financial_permissions_to_profile()
    {
        // Criar permissões de fornecedores
        $permission1 = Permission::create([
            'name' => 'Visualizar Fornecedores',
            'slug' => 'suppliers.index',
            'module' => 'suppliers',
            'action' => 'index',
            'resource' => 'supplier',
            'tenant_id' => $this->tenant->id,
            'description' => 'Visualizar lista de fornecedores',
            'is_active' => true,
        ]);

        $permission2 = Permission::create([
            'name' => 'Criar Fornecedores',
            'slug' => 'suppliers.store',
            'module' => 'suppliers',
            'action' => 'store',
            'resource' => 'supplier',
            'tenant_id' => $this->tenant->id,
            'description' => 'Criar novos fornecedores',
            'is_active' => true,
        ]);

        // Vincular permissões ao perfil
        $this->profile->permissions()->attach([$permission1->id, $permission2->id]);

        // Verificar se as permissões foram vinculadas
        $this->assertEquals(2, $this->profile->permissions()->count());
        $this->assertTrue($this->profile->permissions->contains($permission1));
        $this->assertTrue($this->profile->permissions->contains($permission2));
    }

    #[Test]
    public function it_verifies_all_financial_modules_have_basic_crud_permissions()
    {
        $modules = ['suppliers', 'financial-categories', 'expenses', 'accounts-payable', 'accounts-receivable'];
        $basicActions = ['index', 'show', 'store', 'update', 'destroy'];

        foreach ($modules as $module) {
            foreach ($basicActions as $action) {
                $slug = "{$module}.{$action}";
                $permission = Permission::create([
                    'name' => ucfirst($action) . ' ' . ucfirst($module),
                    'slug' => $slug,
                    'module' => $module,
                    'action' => $action,
                    'resource' => str_replace('-', '_', $module),
                    'tenant_id' => $this->tenant->id,
                    'description' => ucfirst($action) . ' permission for ' . $module,
                    'is_active' => true,
                ]);

                $this->assertDatabaseHas('permissions', [
                    'slug' => $slug,
                    'module' => $module,
                    'action' => $action,
                ]);
            }
        }

        // Verificar totais
        $this->assertEquals(25, Permission::whereIn('module', $modules)->count());
    }

    #[Test]
    public function it_prevents_duplicate_permission_slugs()
    {
        Permission::create([
            'name' => 'Visualizar Fornecedores',
            'slug' => 'suppliers.index',
            'module' => 'suppliers',
            'action' => 'index',
            'resource' => 'supplier',
            'tenant_id' => $this->tenant->id,
            'description' => 'Visualizar lista de fornecedores',
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Tentar criar outra permissão com o mesmo slug (vai falhar)
        Permission::create([
            'name' => 'Visualizar Fornecedores (Duplicado)',
            'slug' => 'suppliers.index', // Mesmo slug - constraint unique no banco
            'module' => 'suppliers',
            'action' => 'index',
            'resource' => 'supplier',
            'tenant_id' => $this->tenant->id,
            'description' => 'Duplicado',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_check_if_permission_exists_before_creating()
    {
        // Criar permissão inicial
        Permission::create([
            'name' => 'Visualizar Fornecedores',
            'slug' => 'suppliers.index',
            'module' => 'suppliers',
            'action' => 'index',
            'resource' => 'supplier',
            'tenant_id' => $this->tenant->id,
            'description' => 'Visualizar lista de fornecedores',
            'is_active' => true,
        ]);

        // Verificar se já existe antes de tentar criar
        $exists = Permission::where('slug', 'suppliers.index')->exists();
        $this->assertTrue($exists);

        // Não tentar criar novamente se já existir
        if (!$exists) {
            Permission::create([
                'name' => 'Visualizar Fornecedores',
                'slug' => 'suppliers.index',
                'module' => 'suppliers',
                'action' => 'index',
                'resource' => 'supplier',
                'tenant_id' => $this->tenant->id,
                'description' => 'Visualizar lista de fornecedores',
                'is_active' => true,
            ]);
        }

        // Deve haver apenas 1 permissão com esse slug
        $this->assertEquals(1, Permission::where('slug', 'suppliers.index')->count());
    }
}

