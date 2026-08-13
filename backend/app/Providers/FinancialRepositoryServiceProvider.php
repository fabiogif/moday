<?php

namespace App\Providers;

use App\Repositories\Contracts\{
    FinancialCategoryRepositoryInterface,
    SupplierRepositoryInterface,
    ExpenseRepositoryInterface,
    AccountPayableRepositoryInterface,
    AccountReceivableRepositoryInterface,
    BankAccountRepositoryInterface
};
use App\Repositories\{
    FinancialCategoryRepository,
    SupplierRepository,
    ExpenseRepository,
    AccountPayableRepository,
    AccountReceivableRepository,
    BankAccountRepository
};
use Illuminate\Support\ServiceProvider;

class FinancialRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Domínio financeiro:
     * - Categorias financeiras
     * - Fornecedores
     * - Despesas / contas a pagar / receber
     * - Contas bancárias
     */
    public function register(): void
    {
        $this->app->bind(FinancialCategoryRepositoryInterface::class, FinancialCategoryRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
        $this->app->bind(AccountPayableRepositoryInterface::class, AccountPayableRepository::class);
        $this->app->bind(AccountReceivableRepositoryInterface::class, AccountReceivableRepository::class);
        $this->app->bind(BankAccountRepositoryInterface::class, BankAccountRepository::class);
    }

    public function boot(): void
    {
        //
    }
}


