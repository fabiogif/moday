<?php

namespace App\Services;

use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialCategoryService
{
    public function __construct(
        private readonly FinancialCategoryRepositoryInterface $categoryRepository
    ) {}

    public function getAllCategories(int $tenantId)
    {
        return $this->categoryRepository->getAllByTenant($tenantId);
    }

    public function getCategoriesByType(int $tenantId, string $type)
    {
        return $this->categoryRepository->getByType($tenantId, $type);
    }

    public function getCategoryByUuid(string $uuid)
    {
        return $this->categoryRepository->getByUuid($uuid);
    }

    public function createCategory(array $data, int $tenantId)
    {
        DB::beginTransaction();
        try {
            $data = $this->normalizeCategoryData($data);

            if ($this->categoryRepository->checkDuplicateName($tenantId, $data['name'], $data['type'])) {
                throw new \Exception('Já existe uma categoria financeira com este nome.');
            }

            $data['tenant_id'] = $tenantId;
            $category = $this->categoryRepository->create($data);

            DB::commit();
            Log::info('Categoria financeira criada', ['category_id' => $category->id, 'tenant_id' => $tenantId]);

            return $category;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar categoria: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateCategory(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $category = $this->categoryRepository->getById($id);
            if (!$category) {
                throw new \Exception('Categoria não encontrada.');
            }

            $data = $this->normalizeCategoryData($data, $category);

            if (isset($data['name']) && $data['name'] !== $category->name) {
                if ($this->categoryRepository->checkDuplicateName($category->tenant_id, $data['name'], $data['type'] ?? $category->type, $id)) {
                    throw new \Exception('Já existe uma categoria financeira com este nome.');
                }
            }

            $updated = $this->categoryRepository->update($id, $data);

            DB::commit();
            Log::info('Categoria atualizada', ['category_id' => $id]);

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar categoria: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteCategory(int $id)
    {
        DB::beginTransaction();
        try {
            $category = $this->categoryRepository->getById($id);
            if (!$category) {
                throw new \Exception('Categoria não encontrada.');
            }

            $hasExpenses = $category->expenses()->exists();

            try {
                $hasPayables = $category->accountsPayable()->exists();
            } catch (\Exception $e) {
                $hasPayables = false;
            }

            try {
                $hasReceivables = $category->accountsReceivable()->exists();
            } catch (\Exception $e) {
                $hasReceivables = false;
            }

            if ($hasExpenses || $hasPayables || $hasReceivables) {
                throw new \Exception('Não é possível excluir esta categoria pois existem lançamentos vinculados.');
            }

            $result = $this->categoryRepository->delete($id);

            DB::commit();
            Log::info('Categoria excluída', ['category_id' => $id]);

            return $result;
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Erro ao excluir categoria: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCategoryData(array $data, ?object $existing = null): array
    {
        $isUpdate = $existing !== null;

        // Começa com os valores existentes se for um update, ou nulo se for criação.
        $payable = $isUpdate ? (bool) $existing->applies_to_payable : null;
        $receivable = $isUpdate ? (bool) $existing->applies_to_receivable : null;

        // Sobrescreve com dados da requisição, se existirem.
        if (array_key_exists('applies_to_payable', $data)) {
            $payable = (bool) $data['applies_to_payable'];
        }
        if (array_key_exists('applies_to_receivable', $data)) {
            $receivable = (bool) $data['applies_to_receivable'];
        }
        
        // Se 'type' foi passado, ele tem a maior precedência.
        if (isset($data['type'])) {
            $payable = in_array($data['type'], ['despesa', 'ambos']);
            $receivable = in_array($data['type'], ['receita', 'ambos']);
        }

        // A verificação final só deve acontecer se, após toda a lógica, ainda for inválido.
        if ($payable === null || $receivable === null || (!$payable && !$receivable)) {
            throw new \Exception('Selecione ao menos Contas a Pagar ou Contas a Receber.');
        }

        $data['applies_to_payable'] = $payable;
        $data['applies_to_receivable'] = $receivable;
        $data['type'] = $payable && $receivable
            ? 'ambos'
            : ($receivable ? 'receita' : 'despesa');

        return $data;
    }
}
