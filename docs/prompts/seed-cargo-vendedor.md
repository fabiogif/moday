> **Nota (adaptação para o repo moday):** este prompt foi escrito para o DistribTec (`backend_distribtec/`), que tem o conceito de cargo (`JobPosition`/`job_positions`) para motoristas, vendedores etc. O backend deste repo (`backend_moday/`, Alba Tec — restaurante) **não tem** esse model/service — não existe `JobPosition`, `JobPositionService` nem `TenantAclProvisioner` em `backend_moday/app`. Não aplicar este prompt aqui sem antes confirmar se um conceito equivalente (ex.: cargos de funcionário do restaurante) existe ou é desejado; não inventar o model/service só para seguir este prompt.

# Prompt — Seed do cargo "Vendedor" (DistribTec)

Use este prompt no Claude Code / Cursor para implementar ou auditar o seed do cargo **Vendedor**.

---

## Objetivo

Garantir que o cargo **Vendedor** exista para:

1. **Novas empresas** — criado automaticamente no provisionamento da conta.
2. **Empresas já cadastradas** — backfill idempotente em local e produção.

## Contexto do projeto (não inventar arquitetura)

- Backend Laravel em `backend_distribtec/`.
- Cargos = model `App\Models\JobPosition` (tabela `job_positions`, multi-tenant via `tenant_id`).
- Defaults já centralizados em `App\Services\JobPositionService::DEFAULT_POSITIONS`.
- Novas contas: `App\Services\TenantAclProvisioner::provisionAndAssignOwner()` já chama `JobPositionService::seedDefaultsForTenant($tenantId)`.
- Backfill: seeder `Database\Seeders\JobPositionSeeder` itera todos os tenants e chama `seedDefaultsForTenant`.
- O seed deve ser **idempotente** (`checkDuplicateName` / `firstOrCreate` por `tenant_id` + `name`). Não duplicar se o cargo já existir.

## Requisitos

1. Incluir em `JobPositionService::DEFAULT_POSITIONS`:

   ```php
   ['name' => 'Vendedor', 'description' => 'Responsável por vendas, atendimento e agenda de visitas a clientes']
   ```

   Preferir colocar **Vendedor** no início da lista (cargo comercial mais comum).

2. Manter os demais defaults existentes (Motorista, Entregador, Ajudante, Administrativo).

3. Não criar migration de dados se o seeder/serviço já cobrir o caso — preferir reutilizar `JobPositionSeeder`.

4. Cobrir com teste (se ainda não existir) que `seedDefaultsForTenant` cria **Vendedor** e que rodar duas vezes não duplica.

5. Rodar o backfill:

   ```bash
   # Local
   cd backend_distribtec
   php artisan db:seed --class=JobPositionSeeder --force

   # Produção (container distribtec-backend)
   # 1) Atualizar JobPositionService.php no container
   # 2) php artisan db:seed --class=JobPositionSeeder --force
   ```

6. Validar por tenant: todo `Tenant` ativo deve ter ao menos um `JobPosition` com `name = 'Vendedor'` e `is_active = true`.

## Critérios de aceite

- [ ] Nova empresa (registro/trial) nasce com cargo Vendedor.
- [ ] Todas as empresas atuais (local + produção) têm cargo Vendedor.
- [ ] Rodar o seeder de novo não cria duplicatas.
- [ ] Sem alterar contratos da API de cargos além do dado seedado.

## Restrições

- Seguir `docs/specs/*` e padrões existentes.
- Não inventar novo padrão de seed se `JobPositionService` + `JobPositionSeeder` já resolvem.
- Não commitar `.env` nem credenciais.
- Responder em português ao concluir, com contagem de tenants atualizados.
