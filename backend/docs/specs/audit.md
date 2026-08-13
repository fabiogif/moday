# Auditoria pré-conclusão

Antes de considerar uma tarefa concluída, percorra esta checklist mentalmente (e corrija o que falhar).  
Baseada na arquitetura **real** do DistribTec (`docs/specs/*`), não em Clean Architecture teórica.

## 1. Specs consultadas

- [ ] Li `architecture.md`, `backend.md` ou `frontend.md` conforme a área
- [ ] Li `coding-standards.md`, `design-patterns.md`
- [ ] Li `security.md`, `performance.md`, `testing.md` se aplicável

## 2. Arquitetura

- [ ] Segui o fluxo do módulo vizinho (Controller→Service→Repository quando esse for o padrão)
- [ ] Não inventei Interface/DTO/Provider/lib sem precedente
- [ ] Reutilizei service/hook/componente/endpoint existente quando havia equivalente
- [ ] Controller sem regra de negócio e sem HTML
- [ ] Service sem Request/Response HTTP
- [ ] Repository só com persistência/queries

## 3. SOLID / DRY / KISS / YAGNI

- [ ] Uma responsabilidade clara por classe/componente novo
- [ ] Sem duplicar lógica que já existe em outro service/helper
- [ ] Solução no menor escopo que resolve o pedido
- [ ] Sem abstração “para o futuro”

## 4. Segurança

- [ ] Auth + tenant scope
- [ ] Permission / plan.feature se o módulo exige
- [ ] Validação de input
- [ ] Sem secrets no código

## 5. Performance

- [ ] Sem N+1 óbvio
- [ ] Paginação em listagens
- [ ] Cache invalidado se o domínio usa cache

## 6. Frontend (se aplicável)

- [ ] Paths via `endpoints`
- [ ] Hooks `useAuthenticatedApi` / `useMutation` (ou padrão do módulo)
- [ ] Form RHF+Zod se for formulário
- [ ] Componentes em `ui/` reutilizados

## 7. Testes

- [ ] Teste alinhado ao domínio ou justificativa explícita se não houver
- [ ] Suite relacionada passa

## 8. Saída da auditoria

Se encontrar problemas:

1. Liste-os priorizados (bloqueante → menor)
2. Explique o desvio vs specs
3. Corrija antes de encerrar a tarefa

Não declare a tarefa concluída com desvios bloqueantes abertos.
