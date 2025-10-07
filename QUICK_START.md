# 🚀 Quick Start - Quadro de Pedidos com Drag-and-Drop

## ▶️ Início Rápido (3 Passos)

### 1️⃣ Inicie o Backend
```bash
cd backend
php artisan serve
```

### 2️⃣ Inicie o Frontend
```bash
cd frontend
npm run dev
```

### 3️⃣ Acesse o Quadro
```
http://localhost:3000/orders/board
```

**Pronto! Arraste os pedidos entre as colunas!** 🎉

---

## ⚡ Com WebSocket (Opcional - Tempo Real)

Se quiser atualização em tempo real entre usuários:

```bash
# Terminal adicional
cd backend
php artisan reverb:start
```

Depois recarregue a página do frontend.

---

## 🎯 Como Arrastar

1. **Clique e segure** em um pedido
2. **Arraste** para outra coluna (fundo fica colorido)
3. **Solte** o mouse
4. ✅ **Pronto!** Pedido movido

---

## 🔔 Sobre o Aviso no Console

Você verá:
```
⚠️ useRealtimeOrders: WebSocket not available (optional feature)
```

**Isso é normal!** Significa que o Reverb (WebSocket) não está rodando.

**Tudo funciona perfeitamente sem ele!**

---

## 📊 Status Disponíveis

| Status | Ação Típica |
|--------|------------|
| 🟡 **Em Preparo** | Pedido criado |
| 🔵 **Pronto** | Cozinha terminou |
| 🟢 **Entregue** | Cliente recebeu |
| 🔴 **Cancelado** | Pedido cancelado |

---

## ❓ Problemas?

### Pedido não arrasta
- Recarregue a página (F5)
- Verifique se está logado

### Badge sempre "Offline"
- Normal sem Reverb
- Para ativar: `php artisan reverb:start`

### Erro ao atualizar
- Verifique se backend está rodando
- Veja console (F12)

---

## 📚 Documentação Completa

- **RESUMO_FINAL_IMPLEMENTACAO.md** - Guia completo
- **COMO_USAR_QUADRO_PEDIDOS.md** - Guia visual do usuário
- **SOLUCAO_ERRO_ECHO.md** - Sobre o aviso do WebSocket

---

**Divirta-se arrastando pedidos!** 🎊
