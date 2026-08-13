# 🔧 Correção de Erro de Hidratação Next.js

## 🚨 Erro
```
A tree hydrated but some attributes of the server rendered HTML didn't match 
the client properties. This won't be patched up.
```

## 🔍 Causas Comuns

### 1. Data/Hora Dinâmica
❌ **Errado**: Renderizar data/hora diretamente
```tsx
function Component() {
  return <div>{new Date().toLocaleString()}</div>
}
```

✅ **Correto**: Usar useEffect ou suppressHydrationWarning
```tsx
'use client'
import { useState, useEffect } from 'react'

function Component() {
  const [date, setDate] = useState<string>('')
  
  useEffect(() => {
    setDate(new Date().toLocaleString())
  }, [])
  
  return <div>{date || 'Carregando...'}</div>
}
```

### 2. Dados de localStorage/sessionStorage
❌ **Errado**: Acessar localStorage no render
```tsx
function Component() {
  const user = localStorage.getItem('user') // Erro!
  return <div>{user}</div>
}
```

✅ **Correto**: Usar useEffect
```tsx
'use client'
import { useState, useEffect } from 'react'

function Component() {
  const [user, setUser] = useState<string | null>(null)
  
  useEffect(() => {
    setUser(localStorage.getItem('user'))
  }, [])
  
  return <div>{user || 'Carregando...'}</div>
}
```

### 3. Valores Aleatórios
❌ **Errado**: Math.random() no render
```tsx
function Component() {
  return <div id={Math.random()}></div> // Diferente no servidor e cliente!
}
```

✅ **Correto**: Gerar no cliente
```tsx
'use client'
import { useState, useEffect } from 'react'

function Component() {
  const [id, setId] = useState<string>('')
  
  useEffect(() => {
    setId(Math.random().toString())
  }, [])
  
  return <div id={id}></div>
}
```

### 4. Extensões do Browser
Extensões como React DevTools, Redux DevTools podem modificar o DOM.

✅ **Solução**: Desabilitar extensões durante desenvolvimento ou usar `suppressHydrationWarning`

### 5. HTML Inválido
❌ **Errado**: Aninhamento inválido
```tsx
<p>
  <div>Conteúdo</div> {/* div dentro de p é inválido! */}
</p>
```

✅ **Correto**: HTML semântico correto
```tsx
<div>
  <p>Conteúdo</p>
</div>
```

### 6. Espaços em Branco
❌ **Errado**: Espaços inconsistentes
```tsx
<div>
  Texto
  {condition && <span>Mais texto</span>}
</div>
```

✅ **Correto**: Remover espaços ou usar {' '}
```tsx
<div>
  Texto{' '}
  {condition && <span>Mais texto</span>}
</div>
```

---

## 🛠️ Soluções Práticas

### Solução 1: suppressHydrationWarning (Uso Específico)

Use apenas para elementos que você sabe que serão diferentes:

```tsx
function Clock() {
  return (
    <div suppressHydrationWarning>
      {new Date().toLocaleString()}
    </div>
  )
}
```

⚠️ **Atenção**: Use com moderação! Não esconde o problema, apenas silencia o aviso.

### Solução 2: Componente ClientOnly

Crie um componente wrapper para renderizar apenas no cliente:

```tsx
// components/ClientOnly.tsx
'use client'
import { useState, useEffect } from 'react'

export default function ClientOnly({ 
  children 
}: { 
  children: React.ReactNode 
}) {
  const [hasMounted, setHasMounted] = useState(false)
  
  useEffect(() => {
    setHasMounted(true)
  }, [])
  
  if (!hasMounted) {
    return null
  }
  
  return <>{children}</>
}
```

Uso:
```tsx
import ClientOnly from '@/components/ClientOnly'

function Page() {
  return (
    <div>
      <h1>Conteúdo SSR</h1>
      
      <ClientOnly>
        <ComponenteComDadosDinamicos />
      </ClientOnly>
    </div>
  )
}
```

### Solução 3: Hook useHydrated

```tsx
// hooks/useHydrated.ts
'use client'
import { useState, useEffect } from 'react'

export function useHydrated() {
  const [hydrated, setHydrated] = useState(false)
  
  useEffect(() => {
    setHydrated(true)
  }, [])
  
  return hydrated
}
```

Uso:
```tsx
'use client'
import { useHydrated } from '@/hooks/useHydrated'

function Component() {
  const hydrated = useHydrated()
  
  if (!hydrated) {
    return <div>Carregando...</div>
  }
  
  return (
    <div>
      {new Date().toLocaleString()}
      {localStorage.getItem('user')}
    </div>
  )
}
```

---

## 🔍 Debugging

### Passo 1: Identificar o Componente

O erro geralmente mostra no console qual componente causou o problema:

```
Warning: Prop `className` did not match. 
Server: "class1" Client: "class2"
```

### Passo 2: Verificar Causas Comuns

1. **Data/Hora**: Procure por `new Date()`, `Date.now()`
2. **Random**: Procure por `Math.random()`, `uuid()`
3. **Browser APIs**: `localStorage`, `sessionStorage`, `window`, `document`
4. **CSS-in-JS**: Alguns geram classes diferentes no servidor e cliente
5. **Condicionais**: Lógica que se baseia em dados do cliente

### Passo 3: Adicionar Logs

```tsx
'use client'
import { useEffect } from 'react'

function Component() {
  useEffect(() => {
    console.log('CLIENT:', document.getElementById('my-element'))
  }, [])
  
  console.log('SERVER/CLIENT:', 'renderizando')
  
  return <div id="my-element">Conteúdo</div>
}
```

---

## 📋 Checklist de Diagnóstico

- [ ] O componente usa `new Date()` ou `Date.now()`?
- [ ] O componente acessa `localStorage` ou `sessionStorage`?
- [ ] O componente usa `Math.random()` ou gera IDs únicos?
- [ ] O componente acessa `window` ou `document`?
- [ ] O HTML está semanticamente correto? (sem `<div>` dentro de `<p>`, etc)
- [ ] O componente tem lógica condicional baseada em dados do cliente?
- [ ] Alguma biblioteca CSS-in-JS pode estar gerando classes diferentes?
- [ ] Extensões do browser estão interferindo?

---

## 🎯 Exemplo Completo: Componente de Data

### ❌ Antes (Com Erro)
```tsx
function DateDisplay() {
  return (
    <div className="text-sm text-gray-500">
      Última atualização: {new Date().toLocaleString()}
    </div>
  )
}
```

### ✅ Depois (Corrigido)
```tsx
'use client'
import { useState, useEffect } from 'react'

function DateDisplay() {
  const [date, setDate] = useState<string>('')
  
  useEffect(() => {
    setDate(new Date().toLocaleString())
  }, [])
  
  if (!date) {
    return (
      <div className="text-sm text-gray-500">
        Última atualização: --
      </div>
    )
  }
  
  return (
    <div className="text-sm text-gray-500">
      Última atualização: {date}
    </div>
  )
}
```

---

## 🎯 Exemplo Completo: Dashboard com LocalStorage

### ❌ Antes (Com Erro)
```tsx
function Dashboard() {
  const theme = localStorage.getItem('theme') // Erro de hidratação!
  
  return (
    <div className={theme === 'dark' ? 'bg-black' : 'bg-white'}>
      Dashboard
    </div>
  )
}
```

### ✅ Depois (Corrigido)
```tsx
'use client'
import { useState, useEffect } from 'react'

function Dashboard() {
  const [theme, setTheme] = useState<string>('light')
  const [mounted, setMounted] = useState(false)
  
  useEffect(() => {
    setMounted(true)
    setTheme(localStorage.getItem('theme') || 'light')
  }, [])
  
  // Evitar flash de conteúdo não estilizado
  if (!mounted) {
    return (
      <div className="bg-white"> {/* Tema padrão durante SSR */}
        <div className="animate-pulse">Carregando...</div>
      </div>
    )
  }
  
  return (
    <div className={theme === 'dark' ? 'bg-black' : 'bg-white'}>
      Dashboard
    </div>
  )
}
```

---

## 🚀 Solução Rápida (Emergency Fix)

Se você precisa de uma solução imediata enquanto investiga:

```tsx
'use client'
import dynamic from 'next/dynamic'

// Desabilitar SSR completamente para este componente
const ProblematicComponent = dynamic(
  () => import('./ProblematicComponent'),
  { ssr: false }
)

export default function Page() {
  return (
    <div>
      <h1>Minha Página</h1>
      <ProblematicComponent />
    </div>
  )
}
```

⚠️ **Nota**: Isso remove o benefício do SSR. Use apenas temporariamente!

---

## 📚 Recursos Adicionais

- [Next.js - Hydration Errors](https://nextjs.org/docs/messages/react-hydration-error)
- [React - Hydration Mismatch](https://react.dev/reference/react-dom/client/hydrateRoot#hydrating-server-rendered-html)

---

## 💡 Dica Final

**Regra de Ouro**: Se algo depende do browser (localStorage, Date.now(), window), 
renderize-o apenas no cliente usando `useEffect`.

```tsx
// ✅ Padrão seguro
'use client'
function Component() {
  const [clientData, setClientData] = useState(null)
  
  useEffect(() => {
    // Qualquer código que depende do browser vai aqui
    setClientData(/* dados do browser */)
  }, [])
  
  return <div>{clientData}</div>
}
```
