# 🧪 Guia Rápido - Testes de Frontend

## ⚡ Execução Rápida

### Executar testes de upload de imagem
```bash
cd frontend
npm test product-image-upload
```

### Executar todos os testes de produtos
```bash
npm test products
```

### Executar com watch mode (desenvolvimento)
```bash
npm test -- --watch
```

### Executar com cobertura
```bash
npm test -- --coverage
```

---

## 📋 Resultado Esperado

```
PASS  src/__tests__/cruds/product-image-upload.test.tsx
  Product Image Upload - Frontend Tests
    Seleção de Arquivo
      ✓ deve permitir selecionar uma imagem
      ✓ deve aceitar múltiplos formatos de imagem
      ✓ deve mostrar preview da imagem selecionada
      ✓ deve limpar arquivo selecionado quando cancelar
    Validação de Arquivo
      ✓ deve rejeitar arquivos que não são imagens
      ✓ deve validar tamanho máximo do arquivo
    Criação de Produto com Imagem
      ✓ deve criar produto com imagem usando FormData
      ✓ deve criar produto sem imagem (campo opcional)
    Atualização de Produto com Imagem
      ✓ deve atualizar produto adicionando nova imagem
      ✓ deve atualizar produto mantendo imagem existente
    Mensagens de Erro e Sucesso
      ✓ deve mostrar mensagem de sucesso ao criar produto
      ✓ deve mostrar mensagem de erro quando upload falhar
    Loading States
      ✓ deve mostrar loading durante upload
      ✓ deve desabilitar botão de submit durante upload
    Integração com API
      ✓ deve enviar FormData corretamente para a API
      ✓ deve chamar refetch após criação bem-sucedida
    Acessibilidade
      ✓ deve ter labels apropriados para inputs
      ✓ deve permitir navegação por teclado

Test Suites: 1 passed, 1 total
Tests:       18 passed, 18 total
Time:        3.2s
```

---

## 🎯 Testes Específicos

### Testar apenas seleção de arquivo
```bash
npm test -- -t "Seleção de Arquivo"
```

### Testar apenas validação
```bash
npm test -- -t "Validação de Arquivo"
```

### Testar criação
```bash
npm test -- -t "Criação de Produto"
```

### Testar atualização
```bash
npm test -- -t "Atualização de Produto"
```

---

## 🔧 Comandos Úteis

### Executar e ver detalhes
```bash
npm test -- --verbose
```

### Executar apenas arquivos modificados
```bash
npm test -- --onlyChanged
```

### Executar com relatório de cobertura detalhado
```bash
npm test -- --coverage --coverageReporters=html
# Depois abra: coverage/index.html
```

### Limpar cache do Jest
```bash
npm test -- --clearCache
```

### Debug de testes
```bash
node --inspect-brk node_modules/.bin/jest --runInBand
```

---

## 📊 Verificar Cobertura

### Ver cobertura no terminal
```bash
npm test -- --coverage
```

### Gerar relatório HTML
```bash
npm test -- --coverage --coverageReporters=html
open coverage/index.html
```

### Ver apenas arquivos não cobertos
```bash
npm test -- --coverage --coverageReporters=text-summary
```

---

## 🐛 Solução de Problemas

### Erro: Cannot find module
**Solução:**
```bash
rm -rf node_modules package-lock.json
npm install
```

### Erro: Jest encountered an unexpected token
**Solução:**
```bash
# Limpar cache
npm test -- --clearCache

# Verificar jest.config.js
cat jest.config.js
```

### Testes muito lentos
**Solução:**
```bash
# Executar em paralelo
npm test -- --maxWorkers=4

# Ou desabilitar coverage
npm test -- --no-coverage
```

### Erro: Test timeout
**Solução:**
```typescript
// Adicionar timeout maior no teste
it('teste demorado', async () => {
  // ...
}, 10000) // 10 segundos
```

---

## 💡 Dicas de Desenvolvimento

### Watch mode inteligente
```bash
# Só executa testes relacionados aos arquivos modificados
npm test -- --watch --onlyChanged
```

### Filtrar por nome
```bash
# Executar apenas testes com "imagem" no nome
npm test -- -t imagem
```

### Ver apenas falhas
```bash
npm test -- --onlyFailures
```

### Modo silencioso
```bash
npm test -- --silent
```

---

## 📝 Criar Novos Testes

### Template básico
```typescript
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render } from '../utils/test-utils'

describe('Meu Componente', () => {
  it('deve fazer algo', async () => {
    // Arrange
    render(<MeuComponente />)
    
    // Act
    const button = screen.getByRole('button', { name: /texto/i })
    await userEvent.click(button)
    
    // Assert
    await waitFor(() => {
      expect(screen.getByText(/resultado/i)).toBeInTheDocument()
    })
  })
})
```

### Testar upload de arquivo
```typescript
const file = new File(['conteudo'], 'teste.jpg', { type: 'image/jpeg' })
const input = screen.getByLabelText(/imagem/i) as HTMLInputElement
await userEvent.upload(input, file)
expect(input.files?.[0]).toBe(file)
```

### Testar chamada de API
```typescript
const mockMutate = jest.fn()
// ... setup
await userEvent.click(submitButton)
await waitFor(() => {
  expect(mockMutate).toHaveBeenCalledWith(expect.objectContaining({
    name: 'Produto'
  }))
})
```

---

## 🎨 Formatação e Lint

### Executar testes + lint
```bash
npm run lint && npm test
```

### Formatar código antes de testar
```bash
npm run format && npm test
```

---

## 🚀 CI/CD

### GitHub Actions
```yaml
name: Frontend Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
        with:
          node-version: '18'
      - run: cd frontend && npm ci
      - run: cd frontend && npm test -- --coverage
```

### Pre-commit hook
```bash
# .husky/pre-commit
#!/bin/sh
cd frontend
npm test -- --bail --findRelatedTests $(git diff --cached --name-only --diff-filter=ACMR | grep -E '\.(tsx?|jsx?)$')
```

---

## 📚 Recursos

### Documentação
- Testing Library: https://testing-library.com/
- Jest: https://jestjs.io/
- User Event: https://testing-library.com/docs/user-event/intro

### Queries
```typescript
// Preferência (mais acessível primeiro)
screen.getByRole()
screen.getByLabelText()
screen.getByPlaceholderText()
screen.getByText()
screen.getByDisplayValue()
screen.getByAltText()
screen.getByTitle()
screen.getByTestId() // último recurso
```

---

## ✅ Checklist

Antes de fazer commit:

- [ ] `npm test` passa sem erros
- [ ] Cobertura mantida ou aumentada
- [ ] Não há `.only` ou `.skip` nos testes
- [ ] Mocks limpos no `beforeEach`
- [ ] Testes são independentes
- [ ] Nomes descritivos
- [ ] Usa queries semânticas

---

**Dica:** Execute `npm test -- --watch` durante o desenvolvimento para feedback instantâneo!
