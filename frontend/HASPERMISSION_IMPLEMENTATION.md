# Sistema de Permissões Frontend - React/TypeScript

## Visão Geral

O sistema de permissões no frontend React é responsável por controlar a exibição de elementos da interface, navegação e funcionalidades baseadas nas permissões do usuário autenticado. Ele funciona em conjunto com o sistema backend Laravel para garantir uma experiência de usuário segura e personalizada.

## Arquitetura do Sistema Frontend

### 1. Estrutura de Componentes

```
src/
├── contexts/
│   └── AuthContext.tsx (gerenciamento de autenticação)
├── hooks/
│   └── usePermissions.ts (hook principal de permissões)
├── services/
│   ├── api.ts (comunicação com backend)
│   └── secureStorage.ts (armazenamento seguro)
├── components/
│   ├── layout/
│   │   ├── AppHeader.tsx (menu com permissões)
│   │   └── HeaderActions.tsx (ações do header)
│   └── AvatarDropdown.tsx (dropdown do usuário)
└── pages/
    ├── users/ (páginas de usuários)
    ├── profiles/ (páginas de perfis)
    └── permissions/ (páginas de permissões)
```

### 2. Fluxo de Dados

```
Backend API → AuthContext → usePermissions → Componentes
     ↓              ↓            ↓            ↓
  Permissões    Estado Auth   Hook Cache   Renderização
```

## Implementação Técnica

### 1. Hook usePermissions

**Localização**: `src/hooks/usePermissions.ts`

```typescript
export const usePermissions = () => {
  const [permissions, setPermissions] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const { user, isAuthenticated } = useAuth();

  useEffect(() => {
    const fetchPermissions = async () => {
      if (!isAuthenticated || !user) {
        setPermissions([]);
        setLoading(false);
        return;
      }

      try {
        setLoading(true);
        const response = await apiService.getUserPermissions();
        setPermissions(response.data || []);
      } catch (error) {
        setPermissions([]);
      } finally {
        setLoading(false);
      }
    };

    fetchPermissions();
  }, [user, isAuthenticated]);

  const hasPermission = (permission: string): boolean => {
    if (!isAuthenticated || !user) return false;
    return permissions.includes(permission);
  };

  const hasAnyPermission = (permissionList: string[]): boolean => {
    if (!isAuthenticated || !user) return false;
    return permissionList.some((permission) =>
      permissions.includes(permission)
    );
  };

  const hasAllPermissions = (permissionList: string[]): boolean => {
    if (!isAuthenticated || !user) return false;
    return permissionList.every((permission) =>
      permissions.includes(permission)
    );
  };

  return {
    permissions,
    loading,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  };
};
```

### 2. Contexto de Autenticação

**Localização**: `src/contexts/AuthContext.tsx`

```typescript
interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  loginWithGovBr: (code: string, state: string) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  clearExpiredSession: () => void;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Inicialização da autenticação
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        const storedToken =
          localStorage.getItem("auth_token") || secureStorage.getToken();

        if (storedToken) {
          apiService.setToken(storedToken);
          const isValid = await apiService.checkAuth();

          if (isValid) {
            const currentUser = await apiService.getCurrentUser();
            setUser(currentUser.user);
          } else {
            setUser(null);
            localStorage.removeItem("auth_token");
            secureStorage.removeToken();
            apiService.setToken(null);
          }
        }
      } catch (error) {
        console.error("❌ Erro ao verificar autenticação:", error);
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    initializeAuth();
  }, []);

  // ... outros métodos
};
```

### 3. Serviço de API

**Localização**: `src/services/api.ts`

```typescript
class ApiService {
  async getUserPermissions(): Promise<{ data: string[]; message: string }> {
    return this.request<{ data: string[]; message: string }>(
      "/users/me/permissions"
    );
  }

  async getCurrentUser(): Promise<{ user: User }> {
    const response = await this.request<{ success: boolean; data: User }>(
      "/users/me"
    );
    return { user: response.data };
  }

  async checkAuth(): Promise<boolean> {
    try {
      const response = await this.request<{ authenticated: boolean }>(
        "/check-auth"
      );
      return response.authenticated;
    } catch (error) {
      return false;
    }
  }
}
```

## Funcionalidades do Sistema

### 1. Verificação de Permissões

#### A. Verificação Simples

```typescript
const UserList = () => {
  const { hasPermission } = usePermissions();

  return (
    <div>{hasPermission("users.create") && <button>Criar Usuário</button>}</div>
  );
};
```

#### B. Verificação Múltipla (OR)

```typescript
const UserActions = () => {
  const { hasAnyPermission } = usePermissions();

  const canEdit = hasAnyPermission(["users.edit", "users.admin"]);

  return <div>{canEdit && <button>Editar</button>}</div>;
};
```

#### C. Verificação Múltipla (AND)

```typescript
const AdminPanel = () => {
  const { hasAllPermissions } = usePermissions();

  const isFullAdmin = hasAllPermissions(["users.admin", "system.admin"]);

  return <div>{isFullAdmin && <AdminControls />}</div>;
};
```

### 2. Menu com Permissões

**AppHeader.tsx**:

```typescript
const menuItems: MenuItemProps[] = [
  {
    id: "usuarios",
    label: "Usuários",
    icon: "fas fa-users",
    permission: "users.view",
    children: [
      {
        id: "usuarios-list",
        label: "Listar Usuários",
        href: "/usuarios",
        icon: "fas fa-list",
        permission: "users.view",
      },
      {
        id: "usuarios-create",
        label: "Novo Usuário",
        href: "/usuarios/novo",
        icon: "fas fa-plus",
        permission: "users.create",
      },
    ],
  },
];
```

### 3. Proteção de Rotas

```typescript
const ProtectedRoute: React.FC<{ permission: string; children: ReactNode }> = ({
  permission,
  children,
}) => {
  const { hasPermission, loading } = usePermissions();

  if (loading) {
    return <LoadingSpinner />;
  }

  if (!hasPermission(permission)) {
    return <AccessDenied />;
  }

  return <>{children}</>;
};

// Uso
<ProtectedRoute permission="users.view">
  <UserList />
</ProtectedRoute>;
```

### 4. Componentes Condicionais

```typescript
const UserCard = ({ user }: { user: User }) => {
  const { hasPermission } = usePermissions();

  return (
    <div className="user-card">
      <h3>{user.name}</h3>
      <p>{user.email}</p>

      {hasPermission("users.edit") && (
        <button onClick={() => editUser(user.id)}>Editar</button>
      )}

      {hasPermission("users.delete") && (
        <button onClick={() => deleteUser(user.id)}>Excluir</button>
      )}
    </div>
  );
};
```

## Cache e Performance

### 1. Cache de Permissões

```typescript
// O hook usePermissions já implementa cache automático
const { permissions, loading } = usePermissions();

// Permissões são carregadas apenas uma vez por sessão
// Atualização automática quando usuário muda
```

### 2. Otimização de Renderização

```typescript
// Usar React.memo para evitar re-renders desnecessários
const UserActions = React.memo(({ user }: { user: User }) => {
  const { hasPermission } = usePermissions();

  return (
    <div>
      {hasPermission("users.edit") && <EditButton />}
      {hasPermission("users.delete") && <DeleteButton />}
    </div>
  );
});
```

### 3. Lazy Loading de Componentes

```typescript
const LazyUserList = React.lazy(() => import("./UserList"));

const UsersPage = () => {
  const { hasPermission, loading } = usePermissions();

  if (loading) return <LoadingSpinner />;

  if (!hasPermission("users.view")) {
    return <AccessDenied />;
  }

  return (
    <Suspense fallback={<LoadingSpinner />}>
      <LazyUserList />
    </Suspense>
  );
};
```

## Estados e Loading

### 1. Estados de Carregamento

```typescript
const UserPage = () => {
  const { hasPermission, loading } = usePermissions();

  if (loading) {
    return (
      <div className="loading-container">
        <LoadingSpinner />
        <p>Carregando permissões...</p>
      </div>
    );
  }

  if (!hasPermission("users.view")) {
    return <AccessDenied />;
  }

  return <UserList />;
};
```

### 2. Tratamento de Erros

```typescript
const usePermissions = () => {
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchPermissions = async () => {
      try {
        setError(null);
        const response = await apiService.getUserPermissions();
        setPermissions(response.data || []);
      } catch (error) {
        setError("Erro ao carregar permissões");
        setPermissions([]);
      }
    };

    fetchPermissions();
  }, [user, isAuthenticated]);

  return { permissions, loading, error, hasPermission };
};
```

## Integração com Backend

### 1. Endpoints Utilizados

```typescript
// GET /api/users/me/permissions
// Retorna: { data: string[], message: string }

// GET /api/users/me
// Retorna: { user: User }

// GET /api/check-auth
// Retorna: { authenticated: boolean }
```

### 2. Sincronização de Estado

```typescript
// AuthContext sincroniza com backend
useEffect(() => {
  const handleStorageChange = async (e: StorageEvent) => {
    if (e.key === "auth_user") {
      if (e.newValue === null && user) {
        setUser(null);
      } else if (e.newValue && !user) {
        const cachedUser = JSON.parse(e.newValue);
        const isValid = await apiService.checkAuth();
        if (isValid) {
          setUser(cachedUser);
        }
      }
    }
  };

  window.addEventListener("storage", handleStorageChange);
  return () => window.removeEventListener("storage", handleStorageChange);
}, [user]);
```

## Exemplos Práticos

### 1. Lista de Usuários com Permissões

```typescript
const UsersList = () => {
  const { hasPermission, loading } = usePermissions();
  const [users, setUsers] = useState<User[]>([]);

  if (loading) return <LoadingSpinner />;

  if (!hasPermission("users.view")) {
    return <AccessDenied />;
  }

  return (
    <div>
      <div className="actions">
        {hasPermission("users.create") && (
          <button onClick={createUser}>Novo Usuário</button>
        )}
        {hasPermission("users.export") && (
          <button onClick={exportUsers}>Exportar</button>
        )}
      </div>

      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          {users.map((user) => (
            <tr key={user.id}>
              <td>{user.name}</td>
              <td>{user.email}</td>
              <td>
                {hasPermission("users.edit") && (
                  <button onClick={() => editUser(user.id)}>Editar</button>
                )}
                {hasPermission("users.delete") && (
                  <button onClick={() => deleteUser(user.id)}>Excluir</button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
```

### 2. Menu Dinâmico

```typescript
const DynamicMenu = () => {
  const { hasPermission } = usePermissions();

  const menuItems = [
    {
      label: "Usuários",
      permission: "users.view",
      children: [
        { label: "Listar", permission: "users.view" },
        { label: "Criar", permission: "users.create" },
      ],
    },
    {
      label: "Perfis",
      permission: "profiles.view",
      children: [
        { label: "Listar", permission: "profiles.view" },
        { label: "Criar", permission: "profiles.create" },
      ],
    },
  ];

  const filteredItems = menuItems.filter((item) =>
    hasPermission(item.permission)
  );

  return (
    <nav>
      {filteredItems.map((item) => (
        <MenuItem key={item.label} item={item} />
      ))}
    </nav>
  );
};
```

### 3. Formulário Condicional

```typescript
const UserForm = ({ user }: { user?: User }) => {
  const { hasPermission } = usePermissions();
  const isEdit = !!user;

  return (
    <form>
      <input name="name" defaultValue={user?.name} />
      <input name="email" defaultValue={user?.email} />

      {hasPermission("users.admin") && (
        <div className="admin-fields">
          <select name="role">
            <option value="user">Usuário</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      )}

      <div className="actions">
        <button type="submit">{isEdit ? "Atualizar" : "Criar"}</button>

        {isEdit && hasPermission("users.delete") && (
          <button type="button" onClick={deleteUser}>
            Excluir
          </button>
        )}
      </div>
    </form>
  );
};
```

## Debugging e Troubleshooting

### 1. Logs de Debug

```typescript
const usePermissions = () => {
  const [permissions, setPermissions] = useState<string[]>([]);

  useEffect(() => {
    const fetchPermissions = async () => {
      try {
        const response = await apiService.getUserPermissions();
        // console.log("🔐 Permissões carregadas:", response.data);
        setPermissions(response.data || []);
      } catch (error) {
        console.error("❌ Erro ao carregar permissões:", error);
        setPermissions([]);
      }
    };

    fetchPermissions();
  }, [user, isAuthenticated]);

  const hasPermission = (permission: string): boolean => {
    const result = permissions.includes(permission);
    // console.log(`🔍 Verificando permissão "${permission}":`, result);
    return result;
  };

  return { permissions, hasPermission };
};
```

### 2. Componente de Debug

```typescript
const PermissionsDebug = () => {
  const { permissions, hasPermission } = usePermissions();

  return (
    <div className="debug-panel">
      <h3>Debug de Permissões</h3>
      <p>Total de permissões: {permissions.length}</p>
      <ul>
        {permissions.map((permission) => (
          <li key={permission}>{permission}</li>
        ))}
      </ul>

      <div>
        <h4>Teste de Permissões:</h4>
        <p>users.view: {hasPermission("users.view") ? "✅" : "❌"}</p>
        <p>users.create: {hasPermission("users.create") ? "✅" : "❌"}</p>
        <p>users.delete: {hasPermission("users.delete") ? "✅" : "❌"}</p>
      </div>
    </div>
  );
};
```

## Conclusão

O sistema de permissões no frontend oferece:

- ✅ **Controle granular** de exibição de elementos
- ✅ **Performance otimizada** com cache automático
- ✅ **Experiência de usuário** fluida e responsiva
- ✅ **Segurança** com verificação em tempo real
- ✅ **Flexibilidade** para diferentes tipos de verificação
- ✅ **Debugging** facilitado com logs e componentes de debug
- ✅ **Integração** perfeita com o sistema backend

O sistema está preparado para escalar com a aplicação, mantendo performance e segurança em todos os níveis da interface do usuário.
