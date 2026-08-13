# Relatório de Testes — Backend e Frontend

Data da execução: 2026-08-13
Executado em ambiente Windows/WSL (a aplicação roda no WSL).

---

## 1. Backend (`backend_moday` — Laravel 11 / PHPUnit 11)

### Comando
Rodado dentro de container `laravelsail/php83-composer` com suporte a SQLite + GD (o SQLite não está instalado no PHP do WSL, e o container padrão `sail-8.3/app` não tem `pdo_sqlite`). O `phpunit.xml` já forçava `DB_CONNECTION=sqlite :memory:`.

```
php vendor/bin/phpunit --log-junit backend-junit.xml
```

### Resultado
**1009 testes, 3718 asserções, 0 erros, 0 falhas, 12 pulados. OK** (com GD habilitado).

| Item | Valor |
|---|---|
| Suítes | Unit + Feature |
| Testes | 1009 |
| Asserções | 3718 |
| Erros | 0 |
| Falhas | 0 |
| Pulados | 12 |
| Tempo | ~6 min |

### Problemas/encontros

1. **Ambiente – falta de SQLite no PHP do WSL**
   - O PHP 8.2 do WSL não tem `pdo_sqlite` nem `sqlite3` (só `pdo_mysql`, `pdo_pgsql`).
   - O `phpunit.xml` força SQLite `:memory:` (correto), mas localmente sem a extensão não roda.
   - O container `sail-8.3/app` (imagem da aplicação) também **não** instala `php8.2-sqlite3` no Dockerfile — é um gap de ambiente para rodar a suíte de teste localmente.

2. **Ambiente – falta de GD**
   - Na primeira execução (sem GD) foram 26 erros: `LogicException: GD extension is not installed` em testes que geram imagens (`CouponApiTest`, `ProductApiTest`, `ProductImageUploadTest`, `FileUploadServiceTest`) via `Illuminate\Http\Testing\FileFactory`.
   - O Dockerfile da aplicação instala `php8.2-gd`, então em produção/CI o GD existe. A falha era só do container de teste usado nesta execução.

3. **Falha esporádica (flaky)** — `FinancialCategoryApiTest::it_can_filter_categories_by_type` (`tests/Feature/FinancialCategoryApiTest.php:100`)
   - Falhou 1x no run 1 e 1x em 5 execuções isoladas: `Failed asserting that 2 is equal to 3 or is greater than 3.`
   - Causa raiz: o teste cria 3 categorias `receita` **sem definir `is_active`**, e o `FinancialCategoryFactory` usa `$this->faker->boolean(95)` (5% de chance de vir inativa). A query `getByType` aplica o scope `active()` (`FinancialCategoryRepository.php:31`), então sempre que uma das 3 cai inativa a contagem vira 2.
   - Correção sugerida: fixar `is_active => true` nas criações do teste, ou usar o estado `receita()` da factory.

---

## 2. Frontend (`moday_frontend` — Next.js 15 / Jest 30 / Testing Library)

### Comando
```
npx jest --ci --watchAll=false
```

### Resultado
**711 testes — 706 passaram, 1 pulado, 4 falhas em 4 suítes** (de 100 suítes).

| Item | Valor |
|---|---|
| Suítes | 100 (96 passaram) |
| Testes | 711 |
| Passou | 706 |
| Pulados | 1 |
| Falhou | 4 |

### Falhas detalhadas

#### F1. `src/app/landing/__tests__/cta-section.test.tsx` — "deve renderizar trust indicators"
- **Erro:** `Found multiple elements with the text: /Sem cartão de crédito/i` em `getByText` (linha 30).
- **Causa:** `CTASection` renderiza a frase em dois lugares (`src/app/landing/components/cta-section.tsx` linhas 36 e 88).
- **Ação:** usar `getAllByText(...).length` ≥ 1 (ou `querySelector` mais específico).

#### F2. `src/app/landing/__tests__/faq-section.test.tsx` — "deve ter link para contato"
- **Erro:** `Found multiple elements with the text: /Fale Conosco/i` em `getByText` (linha 46).
- **Causa:** `faq-section.tsx` tem o link "Fale conosco" (linha 23) e o CTA "Fale Conosco" (linha 60).
- **Ação:** usar `getAllByText(...)` ou tornar o matcher mais específico.

#### F3. `src/app/landing/__tests__/footer.test.tsx` — "deve renderizar links de navegação"
- **Erro:** `Unable to find an element with the text: /FAQ/i` (linha 21).
- **Causa:** o rodapé passou a usar o texto "Perguntas frequentes" (`footer.tsx:41`), não "FAQ" como o teste espera. O teste ficou defasado após mudança de copy.
- **Ação:** atualizar o teste para `/Perguntas frequentes/i` (ou o texto atual).
- **Observação extra (aviso, não falha):** `console.error` — `Received false for a non-boolean attribute priority` vindo de `next/image` em `albatec-logo.tsx:74`/`:102` (prop `priority={false}`). Pode ser silenciado fazendo `priority={priority || undefined}`.

#### F4. `src/__tests__/cruds/categories.test.tsx` — "should show authentication error when not authenticated"
- **Erro:** `Unable to find an element with the text: 'Usuário não autenticado...'` (linha 121). O DOM renderiza `Usu�rio n�o autenticado. Fa�a login para continuar.` (mojibake).
- **Causa:** **Buga real de encoding** — `src/app/(dashboard)/categories/page.tsx` está salvo em **ISO-8859-1 / Latin-1** (confirmado via `file`), enquanto o restante do projeto é UTF-8. A linha 105 mostra `Usu�rio n�o autenticado. Fa�a login para continuar.` O acento é literal corrompido no arquivo e aparece errado para o usuário no navegador.
- **Ação:** re-salvar `categories/page.tsx` em UTF-8 (e revisar arquivos daquela pasta; os demais `page.tsx` — `clients`, `products`, `reviews`, `orders`, `tables` — estão em UTF-8 e corretos).

---

## 3. Resumo executivo

| Área | Testes | Falhas | Erros | Status |
|---|---|---|---|---|
| Backend (Laravel) | 1009 | 0 (1 flaky) | 0 | Aprovado; 1 teste flaky a corrigir |
| Frontend (Next.js) | 711 | 4 | 0 | 4 falhas — 3 de teste desatualizado + 1 bug real de encoding |

**Prioridade:**
1. Corrigir encoding de `moday_frontend/src/app/(dashboard)/categories/page.tsx` (bug visível ao usuário).
2. Corrigir o teste flaky `FinancialCategoryApiTest::it_can_filter_categories_by_type` (fixar `is_active` na factory/estado).
3. Atualizar os 3 testes de landing (CTA, FAQ, Footer) para o texto/estrutura atual.
4. Ambiente local: instalar `php8.2-sqlite3` no WSL ou adicionar `php8.2-sqlite3` ao Dockerfile para rodar a suíte fora de container custom.