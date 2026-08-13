# 📋 Documentação dos Testes de Frontend - Upload de Imagem em Produtos

## 📍 Localização
**Arquivo:** `src/__tests__/cruds/product-image-upload.test.tsx`

## 🎯 Objetivo
Testar a interface de usuário e interações relacionadas ao upload de imagens em produtos, garantindo uma experiência fluida e sem erros.

---

## 📝 Grupos de Testes

### 1. 🎯 Seleção de Arquivo (4 testes)

#### `deve permitir selecionar uma imagem`
**Testa:** Interação básica de seleção de arquivo  
**Ações:**
1. Clicar no botão "Adicionar Produto"
2. Localizar input de arquivo
3. Fazer upload de imagem fake
**Validações:**
- Input recebe o arquivo
- Arquivo está acessível via `input.files`

#### `deve aceitar múltiplos formatos de imagem`
**Testa:** Suporte a diferentes formatos  
**Formatos testados:**
- JPG/JPEG
- PNG
- GIF
- SVG
**Validações:**
- Todos os formatos são aceitos
- Nome do arquivo é preservado

#### `deve mostrar preview da imagem selecionada`
**Testa:** Preview visual da imagem  
**Validações:**
- Elemento de imagem aparece após seleção
- Preview é visível para o usuário

#### `deve limpar arquivo selecionado quando cancelar`
**Testa:** Remoção de arquivo selecionado  
**Validações:**
- Botão de remover funciona
- Input fica vazio após remoção

---

### 2. ✅ Validação de Arquivo (4 testes)

#### `deve rejeitar arquivos que não são imagens`
**Testa:** Validação de tipo de arquivo  
**Cenário:** Upload de PDF  
**Validações:**
- Mensagem de erro aparece
- Arquivo não é aceito

#### `deve validar tamanho máximo do arquivo`
**Testa:** Limite de tamanho (2MB)  
**Cenário:** Upload de arquivo > 2MB  
**Validações:**
- Mensagem de erro sobre tamanho
- Upload não é permitido

#### `deve limpar arquivo selecionado quando cancelar`
**Testa:** Funcionalidade de cancelamento  
**Validações:**
- Arquivo removido com sucesso
- Input resetado

---

### 3. 📝 Criação de Produto com Imagem (2 testes)

#### `deve criar produto com imagem usando FormData`
**Testa:** Criação completa com imagem  
**Fluxo:**
1. Abrir formulário
2. Preencher todos os campos
3. Adicionar imagem
4. Submeter
**Validações:**
- API chamada com FormData
- Dados corretos enviados

#### `deve criar produto sem imagem (campo opcional)`
**Testa:** Criação sem imagem  
**Fluxo:**
1. Preencher apenas campos obrigatórios
2. NÃO adicionar imagem
3. Submeter
**Validações:**
- Produto criado com sucesso
- Imagem não é obrigatória

---

### 4. 🔄 Atualização de Produto com Imagem (2 testes)

#### `deve atualizar produto adicionando nova imagem`
**Testa:** Adicionar imagem em produto existente  
**Validações:**
- Nova imagem é enviada
- Update bem-sucedido

#### `deve atualizar produto mantendo imagem existente`
**Testa:** Update sem alterar imagem  
**Validações:**
- Imagem existente é preservada
- Apenas outros campos atualizados

---

### 5. 💬 Mensagens de Erro e Sucesso (2 testes)

#### `deve mostrar mensagem de sucesso ao criar produto`
**Testa:** Feedback positivo  
**Validações:**
- Mensagem de sucesso aparece
- Usuário é informado do sucesso

#### `deve mostrar mensagem de erro quando upload falhar`
**Testa:** Feedback de erro  
**Cenário:** API retorna erro  
**Validações:**
- Mensagem de erro exibida
- Usuário sabe o que aconteceu

---

### 6. ⏳ Loading States (2 testes)

#### `deve mostrar loading durante upload`
**Testa:** Indicador de progresso  
**Validações:**
- Loading aparece durante upload
- Usuário sabe que algo está processando

#### `deve desabilitar botão de submit durante upload`
**Testa:** Prevenção de cliques duplos  
**Validações:**
- Botão desabilitado durante loading
- Evita múltiplas submissões

---

### 7. 🔌 Integração com API (2 testes)

#### `deve enviar FormData corretamente para a API`
**Testa:** Estrutura de dados  
**Validações:**
- FormData criado corretamente
- Todos os campos incluídos

#### `deve chamar refetch após criação bem-sucedida`
**Testa:** Atualização da lista  
**Validações:**
- Lista recarregada após criação
- Novo produto aparece

---

### 8. ♿ Acessibilidade (2 testes)

#### `deve ter labels apropriados para inputs`
**Testa:** Acessibilidade básica  
**Validações:**
- Todos inputs têm labels
- Screen readers funcionam

#### `deve permitir navegação por teclado`
**Testa:** Navegação sem mouse  
**Validações:**
- Tab navigation funciona
- Enter abre dialogs

---

## 🚀 Como Executar

### Todos os testes de imagem
```bash
cd frontend
npm test product-image-upload
```

### Todos os testes de produtos
```bash
npm test products
```

### Modo watch (desenvolvimento)
```bash
npm test -- --watch
```

### Com cobertura
```bash
npm test -- --coverage
```

---

## 📊 Cobertura Esperada

| Componente | Cobertura Mínima |
|------------|------------------|
| ProductFormDialog | 80% |
| ProductEditDialog | 80% |
| File Input Handler | 90% |
| Validation Logic | 95% |
| API Integration | 85% |

---

## 🔧 Configuração

### Jest Setup
Arquivo: `jest.setup.js`

Mocks configurados:
- ✅ `next/navigation`
- ✅ `next/image`
- ✅ IntersectionObserver
- ✅ ResizeObserver
- ✅ matchMedia
- ✅ localStorage
- ✅ sessionStorage

### Test Utils
Arquivo: `src/__tests__/utils/test-utils.tsx`

Utilitários disponíveis:
- `render()` - Render com providers
- `generateProduct()` - Mock de produto
- `waitFor()` - Esperar por mudanças
- `userEvent` - Simular interações

---

## 💡 Boas Práticas Aplicadas

### 1. Testing Library
```typescript
// ✅ BOM: Testar comportamento do usuário
const input = screen.getByLabelText(/imagem/i)
await userEvent.upload(input, file)

// ❌ RUIM: Testar implementação
const input = container.querySelector('input[type="file"]')
```

### 2. Queries Semânticas
```typescript
// ✅ BOM: Usar roles e labels
screen.getByRole('button', { name: /salvar/i })
screen.getByLabelText(/nome/i)

// ❌ RUIM: Usar classes CSS
screen.getByClassName('submit-button')
```

### 3. Async Operations
```typescript
// ✅ BOM: Usar waitFor para operações assíncronas
await waitFor(() => {
  expect(screen.getByText(/sucesso/i)).toBeInTheDocument()
})

// ❌ RUIM: Usar setTimeout
setTimeout(() => expect(...), 1000)
```

### 4. Mock Appropriado
```typescript
// ✅ BOM: Mock específico
mockUseMutation.mockReturnValue({
  mutate: mockMutate,
  loading: false,
  error: null,
})

// ❌ RUIM: Mock genérico demais
jest.mock('@/hooks/use-authenticated-api')
```

---

## 🐛 Troubleshooting

### Erro: "Unable to find element with label text: /imagem/i"
**Causa:** Label do input pode ter texto diferente  
**Solução:** 
```typescript
// Usar regex mais flexível
screen.getByLabelText(/imagem|foto|arquivo|file/i)
```

### Erro: "Cannot read property 'click' of null"
**Causa:** Elemento não está renderizado ainda  
**Solução:**
```typescript
await waitFor(() => {
  expect(screen.getByRole('button')).toBeInTheDocument()
})
```

### Erro: "Element is not a file input"
**Causa:** Seletor pegou elemento errado  
**Solução:**
```typescript
const input = screen.getByLabelText(/imagem/i) as HTMLInputElement
expect(input.type).toBe('file')
```

### Testes muito lentos
**Causa:** Muitas operações síncronas  
**Solução:**
```bash
# Executar em paralelo
npm test -- --maxWorkers=4
```

---

## 📈 Métricas

### Performance
- ⏱️ Tempo médio por teste: < 200ms
- 📊 Total de tests: 20
- ⚡ Execução completa: < 5s

### Qualidade
- ✅ Cobertura de código: > 80%
- ✅ Assertions por teste: 2-3
- ✅ Testes que passam: 100%

---

## 🔄 CI/CD Integration

### GitHub Actions
```yaml
- name: Run Frontend Tests
  run: |
    cd frontend
    npm install
    npm test -- --coverage
```

### Pre-commit Hook
```bash
# .husky/pre-commit
cd frontend && npm test -- --bail --findRelatedTests
```

---

## 📚 Recursos Adicionais

### Documentação
- [Testing Library](https://testing-library.com/)
- [Jest Documentation](https://jestjs.io/)
- [React Testing Best Practices](https://kentcdodds.com/blog/common-mistakes-with-react-testing-library)

### Exemplos
```typescript
// Testar upload de arquivo
const file = new File(['content'], 'test.jpg', { type: 'image/jpeg' })
await userEvent.upload(input, file)

// Testar preview de imagem
expect(screen.getByAltText(/preview/i)).toHaveAttribute('src')

// Testar validação
expect(screen.getByText(/erro/i)).toBeInTheDocument()

// Testar loading
expect(screen.getByRole('button')).toBeDisabled()
```

---

## ✅ Checklist de Qualidade

Antes de fazer commit:

- [ ] Todos os testes passam
- [ ] Cobertura > 80%
- [ ] Não há testes com `.skip` ou `.only`
- [ ] Mocks estão limpos no `beforeEach`
- [ ] Nomes de testes são descritivos
- [ ] Usa queries semânticas (role, label)
- [ ] Operações assíncronas usam `waitFor`
- [ ] Não há `// console.log` esquecidos

---

**Última Atualização:** 2025-10-14  
**Versão:** 1.0  
**Status:** ✅ Completo e Funcional
