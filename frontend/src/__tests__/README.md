# Frontend CRUD Tests

Este diretório contém testes abrangentes para todas as operações CRUD (Create, Read, Update, Delete) implementadas no frontend da aplicação.

## 📁 Estrutura de Testes

```
src/__tests__/
├── cruds/                  # Testes específicos de CRUD
│   ├── users.test.tsx     # Testes do CRUD de usuários
│   ├── products.test.tsx  # Testes do CRUD de produtos
│   ├── categories.test.tsx# Testes do CRUD de categorias
│   ├── orders.test.tsx    # Testes do CRUD de pedidos
│   ├── roles.test.tsx     # Testes do CRUD de roles
│   ├── permissions.test.tsx# Testes do CRUD de permissões
│   ├── clients.test.tsx   # Testes do CRUD de clientes
│   ├── tasks.test.tsx     # Testes do CRUD de tarefas
│   └── index.test.tsx     # Suite completa de testes
├── components/            # Testes de componentes
├── utils/                 # Utilitários de teste
│   └── test-utils.tsx     # Funções auxiliares e mocks
└── README.md             # Este arquivo
```

## 🧪 Tipos de Testes Implementados

### 1. Testes de Operações CRUD

Para cada entidade, os testes cobrem:

#### **Create (Criar)**
- ✅ Criação com dados válidos
- ✅ Validação de campos obrigatórios
- ✅ Validação de formato de dados
- ✅ Tratamento de erros de criação
- ✅ Geração automática de IDs únicos
- ✅ Upload de arquivos (quando aplicável)

#### **Read (Ler)**
- ✅ Listagem de registros
- ✅ Estados de carregamento
- ✅ Estados de erro
- ✅ Exibição de detalhes
- ✅ Paginação
- ✅ Busca e filtros

#### **Update (Atualizar)**
- ✅ Edição com dados válidos
- ✅ Pré-preenchimento de formulários
- ✅ Validação de alterações
- ✅ Tratamento de erros de atualização
- ✅ Atualizações em tempo real

#### **Delete (Excluir)**
- ✅ Exclusão de registros
- ✅ Confirmação antes da exclusão
- ✅ Tratamento de erros de exclusão
- ✅ Exclusão em lote (bulk delete)
- ✅ Proteção de registros críticos

### 2. Testes de Interface

- ✅ Renderização de componentes
- ✅ Interações do usuário
- ✅ Estados visuais (loading, error, success)
- ✅ Responsividade
- ✅ Acessibilidade

### 3. Testes de Integração

- ✅ Comunicação com APIs
- ✅ Estados de autenticação
- ✅ Fluxos completos de usuário
- ✅ Consistência de dados

## 🛠️ Ferramentas Utilizadas

- **Jest**: Framework de testes principal
- **React Testing Library**: Testes de componentes React
- **@testing-library/user-event**: Simulação de eventos do usuário
- **@testing-library/jest-dom**: Matchers adicionais para DOM

## 🚀 Como Executar os Testes

### Executar todos os testes
```bash
npm test
# ou
pnpm test
```

### Executar testes em modo watch
```bash
npm run test:watch
# ou
pnpm test:watch
```

### Executar testes com coverage
```bash
npm run test:coverage
# ou
pnpm test:coverage
```

### Executar testes específicos

#### Todos os testes CRUD
```bash
npm test -- src/__tests__/cruds
```

#### Teste específico de uma entidade
```bash
npm test -- src/__tests__/cruds/users.test.tsx
npm test -- src/__tests__/cruds/products.test.tsx
npm test -- src/__tests__/cruds/categories.test.tsx
# etc...
```

#### Testes por padrão
```bash
# Todos os testes de criação
npm test -- --testNamePattern="Create Operations"

# Todos os testes de busca/filtro
npm test -- --testNamePattern="Search and Filter"

# Todos os testes de validação
npm test -- --testNamePattern="validation"
```

## 📊 Cobertura de Testes

Os testes cobrem:

### **Entidades Testadas**
- 👥 **Users** - Gestão de usuários
- 📦 **Products** - Gestão de produtos
- 🏷️ **Categories** - Gestão de categorias
- 📋 **Orders** - Gestão de pedidos
- 👤 **Clients** - Gestão de clientes
- ✅ **Tasks** - Gestão de tarefas
- 🔐 **Roles** - Gestão de roles/funções
- 🛡️ **Permissions** - Gestão de permissões

### **Cenários de Teste**
- ✅ Fluxos positivos (happy path)
- ❌ Cenários de erro
- 🔒 Validações de segurança
- 📱 Responsividade
- ♿ Acessibilidade
- 🌐 Internacionalização

## 🔧 Configuração

### Arquivo de configuração principal
- `jest.config.js` - Configuração do Jest
- `jest.setup.js` - Setup global dos testes

### Mocks e Utilitários
- `src/__tests__/utils/test-utils.tsx` - Funções auxiliares
- Mocks de APIs e hooks
- Geradores de dados de teste
- Providers customizados para testes

## 📝 Exemplos de Uso

### Teste básico de CRUD
```tsx
import { render, screen, waitFor } from '../utils/test-utils'
import userEvent from '@testing-library/user-event'
import UsersPage from '@/app/(dashboard)/users/page'

describe('Users CRUD', () => {
  it('should create new user with valid data', async () => {
    const user = userEvent.setup()
    render(<UsersPage />)
    
    // Abrir dialog de criação
    const addButton = screen.getByRole('button', { name: /add.*user/i })
    await user.click(addButton)
    
    // Preencher formulário
    await user.type(screen.getByLabelText(/name/i), 'New User')
    await user.type(screen.getByLabelText(/email/i), 'new@example.com')
    
    // Submeter
    const submitButton = screen.getByRole('button', { name: /create/i })
    await user.click(submitButton)
    
    // Verificar resultado
    await waitFor(() => {
      expect(mockMutate).toHaveBeenCalledWith(
        '/api/users',
        'POST',
        expect.objectContaining({
          name: 'New User',
          email: 'new@example.com'
        })
      )
    })
  })
})
```

## 🐛 Debug e Troubleshooting

### Visualizar renderização dos componentes
```tsx
import { render, screen } from '../utils/test-utils'
import { debug } from '@testing-library/react'

// Em qualquer teste
render(<MyComponent />)
screen.debug() // Mostra a árvore DOM atual
```

### Verificar queries disponíveis
```tsx
// Se um elemento não é encontrado, use:
screen.getByRole('') // Vazio para ver todos os roles disponíveis
screen.getByLabelText('') // Vazio para ver todos os labels
```

### Aguardar elementos assíncronos
```tsx
// Para elementos que aparecem após operações assíncronas
await waitFor(() => {
  expect(screen.getByText('Success message')).toBeInTheDocument()
})

// Ou use queries assíncronas
const element = await screen.findByText('Async content')
```

## 📈 Métricas e Relatórios

### Coverage Report
Após executar `npm run test:coverage`, verifique:
- `coverage/lcov-report/index.html` - Relatório visual
- `coverage/coverage-summary.json` - Resumo em JSON

### Métricas esperadas
- **Statements**: > 80%
- **Branches**: > 75%  
- **Functions**: > 80%
- **Lines**: > 80%

## 🔄 CI/CD Integration

Os testes são executados automaticamente em:
- ✅ Pull Requests
- ✅ Push para branch main
- ✅ Releases

### Comandos CI
```bash
# Validação completa
npm run test:ci
npm run test:coverage
npm run lint
```

## 📚 Recursos Adicionais

### Documentação das ferramentas
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/)
- [Testing Library Queries](https://testing-library.com/docs/queries/about)
- [Jest DOM Matchers](https://github.com/testing-library/jest-dom)

### Boas práticas
- Prefira queries por role e accessible name
- Use `screen.debug()` para debug visual
- Teste comportamentos, não implementação
- Mantenha testes independentes e determinísticos
- Use mocks apenas quando necessário

## 🤝 Contribuindo

Para adicionar novos testes:

1. Crie arquivo `.test.tsx` no diretório apropriado
2. Use a estrutura padrão de describe/it
3. Importe utilitários de `test-utils.tsx`
4. Siga os padrões de nomenclatura existentes
5. Documente cenários complexos
6. Execute testes localmente antes do commit

## 📞 Suporte

Para dúvidas sobre os testes:
- Consulte a documentação das ferramentas
- Veja exemplos nos arquivos existentes
- Verifique os mocks em `test-utils.tsx`