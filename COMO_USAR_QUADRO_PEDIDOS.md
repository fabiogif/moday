# Como Usar o Quadro de Pedidos - Guia Rápido

## 🎯 Acesso Rápido

1. Abra o navegador
2. Acesse: `http://localhost:3000`
3. Faça login
4. Clique em "Pedidos" no menu lateral
5. Clique em "Quadro de Pedidos"

## 🖱️ Como Arrastar um Pedido

### Passo a Passo Visual:

```
┌─────────────────────────────────────────────────────────────────┐
│ Quadro de Pedidos                    [🟢 Tempo real ativo]      │
│ Arraste os pedidos entre os status                             │
└─────────────────────────────────────────────────────────────────┘

┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│Em Preparo│  │  Pronto  │  │ Entregue │  │Cancelado │
│    3     │  │    2     │  │    5     │  │    0     │
├──────────┤  ├──────────┤  ├──────────┤  ├──────────┤
│          │  │          │  │          │  │          │
│ #PED-001 │  │ #PED-004 │  │ #PED-002 │  │          │
│ João     │  │ Maria    │  │ Carlos   │  │          │
│          │  │          │  │          │  │          │
│ #PED-005 │  │ #PED-007 │  │ #PED-003 │  │          │
│ Ana      │  │ Pedro    │  │ Lucas    │  │          │
│          │  │          │  │          │  │          │
│ #PED-008 │  │          │  │ #PED-006 │  │          │
│ Paula    │  │          │  │ Marcos   │  │          │
│          │  │          │  │          │  │          │
│          │  │          │  │ #PED-009 │  │          │
│          │  │          │  │ Rita     │  │          │
│          │  │          │  │          │  │          │
│          │  │          │  │ #PED-010 │  │          │
│          │  │          │  │ José     │  │          │
└──────────┘  └──────────┘  └──────────┘  └──────────┘
```

### 1️⃣ Passo 1: Posicione o Mouse
```
┌──────────┐
│Em Preparo│
├──────────┤
│  ┌─────┐ │  ← Posicione o mouse sobre o pedido
│  │►────│ │
│  │#001 │ │
│  │João │ │
│  └─────┘ │
└──────────┘
```

### 2️⃣ Passo 2: Clique e Segure
```
┌──────────┐
│Em Preparo│
├──────────┤
│  ┌═════┐ │  ← Card fica semi-transparente
│  ║ 🖱️ ║ │  ← Cursor vira "mãozinha fechada"
│  ║#001 ║ │
│  ║João ║ │
│  └═════┘ │
└──────────┘
```

### 3️⃣ Passo 3: Arraste para Outra Coluna
```
┌──────────┐  ┌══════════┐  ← Coluna fica destacada
│Em Preparo│  ║  Pronto  ║
├──────────┤  ║    2     ║
│          │  ╠══════════╣
│          │  ║  ┌─────┐ ║
│          │  ║  │#001 │◄╣── Arraste aqui
│          │  ║  │João │ ║
│          │  ║  └─────┘ ║
└──────────┘  ║          ║
              ║ #PED-004 ║
              ║ Maria    ║
              ╚══════════╝
```

### 4️⃣ Passo 4: Solte o Mouse
```
┌──────────┐  ┌──────────┐
│Em Preparo│  │  Pronto  │
│    2     │  │    3     │  ← Contador atualizado
├──────────┤  ├──────────┤
│          │  │ #PED-001 │  ← Pedido movido!
│ #PED-005 │  │ João     │
│ Ana      │  │          │
│          │  │ #PED-004 │
│ #PED-008 │  │ Maria    │
│ Paula    │  │          │
└──────────┘  └──────────┘

┌─────────────────────────────────────┐
│ ✅ Pedido #PED-001 movido para Pronto│
└─────────────────────────────────────┘
```

## 🎨 Feedback Visual

### Durante o Arraste:
- 🎯 **Card arrastado**: Fica com 50% de opacidade
- 🖱️ **Cursor**: Muda para "grabbing" (mão fechada)
- 🔵 **Borda**: Fica azul destacada
- ⬆️ **Sombra**: Aumenta para dar sensação de elevação
- 🎨 **Coluna destino**: Background colorido ao passar sobre ela

### Após Soltar:
- 📢 **Toast**: Notificação de sucesso ou erro
- 🔄 **Atualização**: Pedido aparece na nova coluna
- 📊 **Contador**: Número de pedidos atualizado

## 🔔 Notificações

### Sucesso:
```
┌─────────────────────────────────────────┐
│ ✅ Pedido #PED-001 movido para Pronto   │
└─────────────────────────────────────────┘
```

### Erro:
```
┌─────────────────────────────────────────┐
│ ❌ Não foi possível atualizar o status  │
└─────────────────────────────────────────┘
```

### Carregando:
```
┌─────────────────────────────────────────┐
│ ⏳ Movendo pedido #PED-001 para Pronto...│
└─────────────────────────────────────────┘
```

## 🌐 Tempo Real

### Conexão Ativa:
```
┌────────────────────────────────────────────────┐
│ Quadro de Pedidos   [🟢 Wifi] Tempo real ativo│
└────────────────────────────────────────────────┘
```
✅ Outros usuários veem mudanças instantaneamente

### Conexão Inativa:
```
┌────────────────────────────────────────────────┐
│ Quadro de Pedidos   [⚪ WifiOff] Offline      │
└────────────────────────────────────────────────┘
```
✅ Drag-and-drop ainda funciona normalmente
❌ Sem atualização automática para outros usuários

## 🎓 Dicas de Uso

### ✅ Boas Práticas:
1. **Arraste suavemente**: O sistema detecta apenas após 8px de movimento
2. **Verifique o toast**: Confirme que a operação foi bem-sucedida
3. **Atualize se necessário**: Use o botão "Recarregar" se algo parecer errado

### ⚠️ Atenções:
1. **Não clique rapidamente**: Espere o toast de confirmação
2. **Verifique a conexão**: Badge mostra se tempo real está ativo
3. **Em caso de erro**: A tela recarrega automaticamente

## 🚀 Atalhos e Truques

### Recarregar Pedidos:
```
┌────────────────────────────────────────────────┐
│ Quadro de Pedidos              [🔄 Recarregar] │ ← Clique aqui
└────────────────────────────────────────────────┘
```

### Ver Detalhes do Pedido:
- Atualmente: Clique no número do pedido (#PED-001)
- Futuro: Modal com detalhes completos (em desenvolvimento)

## 📱 Fluxo Típico de Uso

### Restaurante/Lanchonete:
```
1. Pedido Criado → [Em Preparo]
2. Cozinha Termina → Arraste para [Pronto]
3. Entregador Pega → Arraste para [Entregue]
4. Se Cancelar → Arraste para [Cancelado]
```

### Exemplo Real:
```
09:00 - Cliente faz pedido
        → #PED-015 criado em [Em Preparo]

09:15 - Pedido pronto
        → Arraste #PED-015 para [Pronto]
        → Cliente recebe notificação (se configurado)

09:20 - Cliente retira
        → Arraste #PED-015 para [Entregue]
        → Faturamento registrado
```

## 🎯 Resumo Rápido

| Ação | Como Fazer |
|------|------------|
| **Arrastar Pedido** | Clique e segure → Arraste → Solte |
| **Ver Status** | Pedidos organizados por coluna |
| **Recarregar** | Botão "Recarregar" no topo |
| **Ver Conexão** | Badge no canto superior direito |
| **Ver Quantidade** | Número em cada coluna |

## ❓ Problemas Comuns

### Pedido não se move:
1. ✅ Verifique se está logado
2. ✅ Confirme que tem permissão
3. ✅ Tente recarregar a página

### Erro ao atualizar:
1. ✅ Verifique conexão com internet
2. ✅ Confirme que backend está rodando
3. ✅ Veja o console do navegador (F12)

### Tempo real não funciona:
1. ✅ Verifique badge de conexão
2. ✅ Confirme que Reverb está rodando
3. ✅ Não afeta funcionalidade básica

---

**Pronto para usar! Comece a arrastar seus pedidos agora! 🎉**
