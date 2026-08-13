# Auditoria pré-conclusão

Antes de considerar uma tarefa concluída, percorra esta checklist mentalmente (e corrija o que falhar).
Baseada na arquitetura **real** do Moday/Alba Tec (`docs/specs/*`), não em Clean Architecture teórica.

Este arquivo é a checklist de **fechamento**. O planejamento pré-implementação (Medium/High) está em `docs/specs/engineering-protocol.md` — os dois se somam; o protocolo **não** substitui o audit.

## 1. Specs consultadas

- [ ] Li `architecture.md`, `backend.md` ou `frontend.md` conforme a área
- [ ] Li `coding-standards.md`, `design-patterns.md`
- [ ] Li `security.md`, `performance.md`, `testing.md` se aplicável

## 2. Arquitetura

- [ ] Segui o fluxo do módulo vizinho (Controller→Service→Repository; facade+sub-services só se o domínio já tiver essa complexidade, como Orders)
- [ ] Não inventei Interface/DTO/Provider/lib/exception de domínio sem precedente
- [ ] Reutilizei service/hook/componente/endpoint existente quando havia equivalente
- [ ] Controller sem regra de negócio e sem HTML
- [ ] Service sem Request/Response HTTP
- [ ] Repository só com persistência/queries

## 3. SOLID / DRY / KISS / YAGNI

- [ ] Uma responsabilidade clara por classe/componente novo
- [ ] Sem duplicar lógica que já existe em outro service/helper
- [ ] Solução no menor escopo que resolve o pedido
- [ ] Sem abstração "para o futuro"

## 4. Segurança

- [ ] Auth + tenant scope (guard correto: `api`/`client`/`admin`)
- [ ] Permission (`acl.permission`) ou plan feature/limit (`plan.feature`, `plan.order_limit`, `plan.user_limit`) se o módulo exige
- [ ] Validação de input (Form Request no padrão dominante)
- [ ] Sem secrets no código

## 5. Performance

- [ ] Sem N+1 óbvio
- [ ] Paginação em listagens (via `PaginateRepositoryInterface` quando o domínio já usa)
- [ ] Cache invalidado se o domínio usa `CacheService`/`ListingCacheService`

## 6. Frontend (se aplicável)

- [ ] Paths via `endpoints` (`lib/api-client.ts`)
- [ ] Hooks `useAuthenticatedApi` / `useMutation` / `useMutationWithValidation` (nunca o `use-api.ts` legado)
- [ ] Form RHF+Zod se for formulário
- [ ] Componentes em `{area}/components/` reutilizados/colocados corretamente

## 7. Testes

- [ ] Teste alinhado ao domínio ou justificativa explícita se não houver
- [ ] Suite relacionada passa
- [ ] `composer run ci:architecture` sem violação nova, se a mudança for no backend

## 8. Saída da auditoria

Se encontrar problemas:

1. Liste-os priorizados (bloqueante → menor)
2. Explique o desvio vs specs
3. Corrija antes de encerrar a tarefa

Não declare a tarefa concluída com desvios bloqueantes abertos.
