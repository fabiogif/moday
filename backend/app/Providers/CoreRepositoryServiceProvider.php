<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    CategoryRepositoryInterface,
    ClientRepositoryInterface,
    DeletionGuardRepositoryInterface,
    EvaluationRepositoryInterface,
    EventRepositoryInterface,
    LocationRepositoryInterface,
    OrderRepositoryInterface,
    OrderStatusRepositoryInterface,
    PaymentMethodRepositoryInterface,
    PermissionRepositoryInterface,
    PlanRepositoryInterface,
    ProductRepositoryInterface,
    PublicStoreRepositoryInterface,
    UserRepositoryInterface,
    TableRepositoryInterface,
    TenantRepositoryInterface,
    StoreHourRepositoryInterface,
    RoleRepositoryInterface,
    ServiceTypeRepositoryInterface,
    ReviewRepositoryInterface,
    ProfileRepositoryInterface,
    JobPositionRepositoryInterface
};
use App\Repositories\{
    CategoryRepository,
    ClientRepository,
    DeletionGuardRepository,
    EvaluationRepository,
    EventRepository,
    LocationRepository,
    OrderRepository,
    OrderStatusRepository,
    PaymentMethodRepository,
    PermissionRepository,
    PlanRepository,
    ProductRepository,
    PublicStoreRepository,
    UserRepository,
    TableRepository,
    TenantRepository,
    StoreHourRepository,
    RoleRepository,
    ServiceTypeRepository,
    ReviewRepository,
    ProfileRepository,
    JobPositionRepository
};
use Illuminate\Support\ServiceProvider;

class CoreRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repositórios centrais da aplicação:
     * - Catálogo / produtos
     * - Tenant / planos / usuários / clientes
     * - Pedidos / mesas
     * - Avaliações / localização / eventos
     * - Pagamentos / permissões / roles
     * - Loja pública / horários
     */
    public function register(): void
    {
        // Catálogo / produtos
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);

        // Tenant / planos / usuários / clientes
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(DeletionGuardRepositoryInterface::class, DeletionGuardRepository::class);

        // Pedidos / mesas
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderStatusRepositoryInterface::class, OrderStatusRepository::class);
        $this->app->bind(TableRepositoryInterface::class, TableRepository::class);
        $this->app->bind(ServiceTypeRepositoryInterface::class, ServiceTypeRepository::class);

        // Avaliações / localização / eventos
        $this->app->bind(EvaluationRepositoryInterface::class, EvaluationRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);

        // Pagamento / permissões / roles
        $this->app->bind(PaymentMethodRepositoryInterface::class, PaymentMethodRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);

        // Loja pública / funcionamento
        $this->app->bind(PublicStoreRepositoryInterface::class, PublicStoreRepository::class);
        $this->app->bind(StoreHourRepositoryInterface::class, StoreHourRepository::class);

        // Reviews / perfis
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(JobPositionRepositoryInterface::class, JobPositionRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


