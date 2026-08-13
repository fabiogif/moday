# Prompt — App Mobile Alba Tec

Documento de briefing para design e desenvolvimento de aplicativo mobile nativo ou híbrido, **fiel ao layout e identidade visual do frontend web** (Next.js + shadcn/ui).

**Produto:** Alba Tec — sistema de gestão para restaurantes  
**Referência web:** `moday_frontend` (dashboard, PDV, landing, loja pública)  
**Site:** https://albatec.com.br  
**Última atualização:** junho/2026

---

## 1. Objetivo do app

Criar um **app mobile para gestores e operadores de restaurante** que replique a experiência do painel web Alba Tec, priorizando:

- **PDV touch-first** (uso em salão e balcão)
- **Painel de controle** com KPIs e gráficos
- **Gestão de pedidos** (lista + Kanban)
- **Módulos financeiros** e operacionais essenciais

O app deve parecer uma **extensão natural do frontend web**, não um produto visualmente diferente.

---

## 2. Prompt principal (copiar e colar)

```
Crie o design de um aplicativo mobile (iOS e Android) para o sistema Alba Tec — gestão de restaurantes.

IDENTIDADE VISUAL (obrigatório — espelhar o frontend web):
- Marca: Alba Tec
- Símbolo: letra "A" estilizada com traços de circuito, gradiente roxo (#9D36FF → #5A18C9)
- Wordmark: "Alba" em navy (#1A1A2E) + "Tec" em roxo (#7C3AED)
- App icon: apenas o símbolo "A" em tile arredondado (branco ou navy)
- Referência: docs/identidade-visual-albatec.md e public/brand/
- Tipografia: Inter (Regular 400, Medium 500, Semibold 600, Bold 700)
- Estilo: clean, moderno, profissional; inspirado em shadcn/ui + Radix
- Cantos arredondados: 10px base (botões/inputs), 14–16px (cards), 20px (modais/sheets)
- Suporte a tema claro e escuro (preferência do sistema)

PALETA DE CORES:
- Primary (roxo/violeta): #6D28D9 (light) / #8B5CF6 (dark) — botões principais, links, item ativo
- Background: #FFFFFF (light) / #1A1A1F (dark)
- Card: #FFFFFF (light) / #242429 (dark)
- Foreground (texto): #1A1A1F (light) / #FAFAFA (dark)
- Muted (texto secundário): #71717A
- Border: #E4E4E7 (light) / rgba(255,255,255,0.1) (dark)
- Success: #10B981 (emerald) — badges positivos, finanças saudáveis
- Warning: #F59E0B (amber) — alertas, mesa ocupada
- Destructive: #EF4444 — cancelar pedido, contas vencidas
- Accent charts: roxo (primary), verde (#22C55E), âmbar (#F59E0B), rosa (#EC4899)

GRADIENTES (CTAs e destaques):
- Botão principal: linear-gradient(135deg, #6D28D9 → #7C3AED)
- Hero/destaque: fundo com blur roxo/violeta suave (primary/5 a primary/25)
- Badge trial: borda primary/30, fundo primary/10

COMPONENTES BASE:
- Cards: fundo card, borda 1px border/60, sombra leve (shadow-md), padding 16–24px
- Botões: altura mínima 44px (touch), primary sólido ou outline com borda
- Inputs: altura 44px, borda input, focus ring roxo 3px
- Badges: pill (rounded-full), variantes outline/secondary/default/destructive
- Toasts: canto inferior, estilo Sonner — sucesso verde, erro vermelho
- Sheets/Modais: slide de baixo no mobile (bottom sheet), backdrop escurecido 50%

NAVEGAÇÃO MOBILE:
- Bottom tab bar fixa (5 itens): Início | Pedidos | PDV | Cardápio | Mais
- Tab ativa: ícone + label em primary; inativa: muted-foreground
- Header fixo por tela: breadcrumb/título à esquerda, sino de notificações + toggle tema à direita
- Menu lateral (drawer) para módulos secundários: Financeiro, Marketing, Relatórios, Configurações
- PDV usa bottom nav própria com 2 abas: "Produtos" | "Pedido" (com badge de quantidade)

TELAS OBRIGATÓRIAS:

1) LOGIN
- Logo Alba Tec centralizado
- Campos e-mail e senha
- Botão "Entrar" gradiente roxo
- Link "Esqueci minha senha"

2) PAINEL DE CONTROLE (Dashboard)
- Saudação: "Boa tarde, {Nome}!" + data por extenso (pt-BR)
- Botões rápidos no topo: + Novo Pedido (primary), PDV (outline), Cardápio (outline)
- 4 cards KPI em grid 2×2:
  · Receita Total (ícone $, badge verde +%)
  · Clientes Ativos
  · Total de Pedidos
  · Taxa de Conversão
- Cada card: valor grande, subtítulo muted, mini sparkline opcional
- Gráfico "Volume de Pedidos" (barras azul + verde, últimos 7 dias)
- Gráfico "Número de Clientes" (barras empilhadas roxo + laranja, 6 meses)

3) PDV (tela principal do app)
- Header: logo do restaurante, busca "Produto ou mesa", toggles Delivery ON / Garçom ON, badge Online verde
- Alerta amarelo quando mesa tem pedidos abertos
- Categorias: scroll horizontal com chips circulares/retangulares; ativo = primary
- Grid de produtos 2 colunas: foto, nome, preço, badge PROMO se houver preço riscado
- Aba Pedido (carrinho):
  · Tabs: Cliente | Atend. | Pag. | Carr.
  · Seletor de cliente com botão + Adicionar
  · Lista de itens com quantidade
  · Botões de ação: Atualizar (laranja), Avançar status (azul), Cancelar (vermelho)
  · Botão verde grande "+ Iniciar Pedido" ou "Avançar para Em Preparo"
- Bottom nav PDV: Produtos | Pedido (com contador e total R$)

4) PEDIDOS
- Lista com cards: número, cliente, status badge colorido, total, horário
- Status colors: Pendente=amarelo, Preparando=azul, Pronto=verde, Em Entrega=roxo, Entregue=verde escuro, Cancelado=vermelho
- Alternativa: Quadro Kanban com colunas arrastáveis

5) FINANCEIRO
- Título "Painel de Controle Financeiro" + badge "Finanças saudáveis" (verde)
- 4 cards resumo: Total a Receber (verde), Total a Pagar (vermelho), Saldo Projetado (roxo), Despesas do Mês (âmbar)
- Grid de acesso rápido 2×3: Contas a Receber, Contas a Pagar, Despesas, Fornecedores, Categorias, Dados Bancários

6) CARDÁPIO (produtos)
- Lista/grid de produtos com busca e filtro por categoria
- FAB ou botão + para novo produto

7) LOJA PÚBLICA (opcional — app do cliente)
- Header com logo e nome do restaurante
- Categorias em tabs horizontais
- Carrinho flutuante com total
- Sheet de checkout: retirada/delivery, pagamento, observações

INTERAÇÕES TOUCH:
- Área mínima de toque: 44×44px
- Feedback visual: scale 0.98 no press (active), scale 1.02 no hover (tablet)
- Haptic leve em adicionar produto e confirmar pagamento
- Pull-to-refresh nas listas
- Swipe para ações rápidas em pedidos (opcional)

IDIOMA: Português brasileiro (pt-BR)
MOEDA: Real (R$), formato 1.234,56
DENSIDADE: confortável para uso em pé, operação rápida em restaurante

ENTREGÁVEIS:
- Design system (cores, tipografia, espaçamento, componentes)
- Fluxo de navegação
- Telas em 390×844 (iPhone) e 360×800 (Android)
- Variantes light e dark
- Estados: loading (skeleton), vazio, erro, offline
```

---

## 3. Design system — extraído do frontend

### 3.1 Tipografia

| Token | Uso | Tamanho / Peso |
|-------|-----|----------------|
| `font-sans` | Global | Inter |
| `text-2xl font-bold` | Título de tela (saudação dashboard) | 24px / 700 |
| `text-3xl–4xl font-bold` | Valores KPI | 30–36px / 700 |
| `text-lg font-semibold` | Subtítulos de seção | 18px / 600 |
| `text-sm` | Corpo, labels | 14px / 400 |
| `text-xs` | Badges, hints | 12px / 500 |
| `text-muted-foreground` | Texto secundário | cor muted |

### 3.2 Cores (CSS variables do `globals.css`)

| Token | Light (oklch) | Uso no app |
|-------|---------------|------------|
| `--primary` | oklch(0.511 0.262 276.97) | Botões, tab ativa, links |
| `--primary` (dark) | oklch(0.623 0.214 276.53) | Primary no tema escuro |
| `--background` | oklch(1 0 0) | Fundo da tela |
| `--foreground` | oklch(0.141 0.005 285.823) | Texto principal |
| `--muted-foreground` | oklch(0.556 0.013 285.823) | Subtítulos, placeholders |
| `--card` | oklch(1 0 0) | Fundo de cards |
| `--border` | oklch(0.914 0.013 285.823) | Bordas e divisores |
| `--destructive` | oklch(0.577 0.245 27.325) | Ações destrutivas |
| `--chart-1` | primary | Gráficos principais |
| `--chart-2` | verde | Pedidos concluídos, tendência positiva |
| `--chart-3` | âmbar | Clientes recorrentes |
| `--chart-4` | roxo/rosa | Séries secundárias |

### 3.3 Espaçamento e raios

| Token | Valor | Equivalente mobile |
|-------|-------|-------------------|
| `--radius` | 0.625rem (10px) | Botões, inputs |
| `rounded-xl` | 12px | Chips de categoria PDV |
| `rounded-2xl` | 16px | Cards, screenshots, modais |
| `rounded-[14px]` | 14px | Cards de produto PDV |
| Padding tela | `px-4` (16px) mobile / `px-6` (24px) tablet | Margem horizontal padrão |
| Gap entre cards | `gap-4` (16px) ou `gap-6` (24px) | Grid de KPIs e listas |

### 3.4 Sombras

- Cards padrão: `shadow-md` (suave, contida na borda do card)
- Cards em hover: `shadow-lg`
- Evitar `shadow-2xl` em imagens — sombra deve seguir o contorno do elemento
- Header sticky: `backdrop-blur-xl` + `bg-background/80`

---

## 4. Arquitetura de navegação (web → mobile)

### 4.1 Sidebar web (referência)

O frontend organiza o menu em 4 grupos:

| Grupo | Itens |
|-------|-------|
| **Principal** | Painel de Controle, Pedidos, PDV, Clientes, Avaliações |
| **Operações** | Cardápio (Produtos, Categorias, Mesas, Tipos de Atendimento), Financeiro, Marketing |
| **Análise** | Relatórios, Desempenho de Vendas, Formas de Pagamento, iFood |
| **Sistema** | Usuários, Perfis e Permissões, Configurações |

### 4.2 Mapeamento para mobile

```
Bottom Tab Bar (sempre visível, exceto no PDV fullscreen):
┌─────────┬─────────┬─────────┬─────────┬─────────┐
│  Início │ Pedidos │   PDV   │ Cardápio│  Mais   │
│  🏠     │  🛒     │  📱     │  🍽️     │  ☰      │
└─────────┴─────────┴─────────┴─────────┴─────────┘

Drawer "Mais" (Sheet lateral direita):
- Clientes
- Avaliações
- Financeiro →
- Marketing →
- Relatórios →
- iFood
- Usuários
- Configurações
- Sair
```

### 4.3 Header padrão (todas as telas exceto PDV)

```
┌──────────────────────────────────────────────┐
│ ☰  Painel de Controle          🔔  🌙       │
│     Financeiro > Resumo                      │
└──────────────────────────────────────────────┘
```

- `☰` abre drawer quando não houver tab equivalente
- Breadcrumb em 1–2 níveis (`text-sm text-muted-foreground`)
- Sino com badge vermelho para notificações de pedido
- Toggle tema claro/escuro

### 4.4 PDV — layout mobile (já implementado no web)

O PDV web usa **duas colunas no desktop** e **alternância por aba no mobile**:

| Aba | Conteúdo |
|-----|----------|
| **Produtos** | Busca, categorias, grid de produtos |
| **Pedido** | Cliente, itens, pagamento, ações |

Bottom nav fixa (`fixed bottom-0`, `backdrop-blur`, `border-t`):
- Ícone + label
- Tab ativa: `text-primary`
- Carrinho: badge circular com quantidade + total em R$ abaixo do label

---

## 5. Especificação por módulo

### 5.1 Dashboard (Painel de Controle)

**Layout web de referência:** `dashboard/page.tsx`, `metrics-overview.tsx`

```
┌─────────────────────────────────────┐
│ Boa tarde, Fabio!                   │
│ Segunda-feira, 8 de junho           │
│           [+ Novo Pedido] [PDV]     │
├─────────────────────────────────────┤
│ ┌──────────┐  ┌──────────┐         │
│ │ Receita  │  │ Clientes │         │
│ │ R$ 530   │  │    6     │         │
│ │ +100% ▲  │  │ +100% ▲  │         │
│ └──────────┘  └──────────┘         │
│ ┌──────────┐  ┌──────────┐         │
│ │ Pedidos  │  │ Conversão│         │
│ │   12     │  │  133,3%  │         │
│ └──────────┘  └──────────┘         │
├─────────────────────────────────────┤
│ Volume de Pedidos (7 dias)          │
│ [════════════ gráfico barras ═══]   │
├─────────────────────────────────────┤
│ Número de Clientes (6 meses)        │
│ [════════════ barras empilhadas ══] │
└─────────────────────────────────────┘
```

**Ícones KPI (Lucide):** DollarSign, Users, ShoppingCart, BarChart3  
**Cores dos ícones:** primary/10, emerald/10, violet/10, amber/10

### 5.2 PDV

**Layout web de referência:** `pdv/page.tsx`, `pdv-main-layout.tsx`, `product-card.tsx`

**Card de produto:**
- Foto `h-32` (128px), `object-cover`, fundo `bg-muted` se sem imagem
- Nome `font-medium text-sm`
- Preço: `text-primary font-bold`; promo com preço original riscado + badge "PROMO"
- Botão "Observação" discreto
- Touch: `active:scale-[0.98]`, `rounded-[14px]`, `shadow-md`

**Carrinho / pedido:**
- Tabs horizontais: Cliente | Atend. | Pag. | Carr.
- Status badge: "Pedido Recebido", "Em Preparo", etc.
- Botões de ação em grid 2×2: Observação cliente, Nota interna, Dividir, Transferir
- CTA principal verde: "+ Iniciar Pedido"
- CTAs de fluxo: laranja (Atualizar), azul (Avançar), vermelho (Cancelar)

### 5.3 Financeiro

**Layout web de referência:** `financial/dashboard/page.tsx`

**Cards de resumo (4):**

| Card | Cor de destaque | Conteúdo |
|------|-----------------|----------|
| Total a Receber | Verde (emerald) | Valor + barra de progresso + % recebido |
| Total a Pagar | Vermelho (rose) | Valor + indicador de vencidos |
| Saldo Projetado | Roxo (primary) | Valor + "Fluxo de caixa positivo" |
| Despesas do Mês | Âmbar (amber) | Valor + status de atraso |

**Acesso rápido:** botões grandes 2 colunas com ícone, título e valor resumido.

### 5.4 Pedidos

- Lista: card por pedido com `OrderStatusBadge`
- Kanban: colunas com cores de status
- Swipe ou long-press para mudar status

### 5.5 Loja pública (cliente final)

**Layout web de referência:** `store/[slug]/page.tsx`

- Header sticky com logo do tenant
- Banner de horário de funcionamento
- Busca de produtos
- Categorias em scroll horizontal
- Carrinho via Sheet lateral
- Dialog de variações/opcionais com cálculo de preço em tempo real

---

## 6. Componentes — checklist para o app

| Componente web (shadcn) | Equivalente mobile |
|-------------------------|-------------------|
| `Button` | Botão nativo, min 44px altura |
| `Card` | Surface elevada com borda |
| `Badge` | Chip / Tag |
| `Input` / `Textarea` | TextField nativo |
| `Select` / `Combobox` | Picker / Bottom sheet selector |
| `Dialog` | Modal centralizado |
| `Sheet` | Bottom sheet (iOS) / Side sheet (Android tablet) |
| `Tabs` | Tab bar segmentada ou scroll horizontal |
| `Toast` (Sonner) | Snackbar / Toast nativo |
| `Skeleton` | Shimmer loading |
| `ScrollArea` | FlatList / ScrollView |
| `Progress` | Barra de progresso linear |
| `Sidebar` | Drawer navigation |
| `Breadcrumb` | Texto hierárquico no header |

---

## 7. Estados e feedback

| Estado | Tratamento visual |
|--------|-------------------|
| **Loading** | Skeleton nos cards e listas; spinner central em telas inteiras |
| **Vazio** | Ilustração leve + texto muted + CTA primary |
| **Erro** | Toast vermelho + botão "Tentar novamente" |
| **Offline** | Banner âmbar no topo ("Sem conexão — modo offline") |
| **Sucesso** | Toast verde: "Produto adicionado ao pedido" |
| **Plano limitado** | Banner informativo (como `PlanLimitNotification`) |

---

## 8. Animações e motion

| Interação | Comportamento (igual ao web) |
|-----------|---------------------------|
| Entrada de tela | Fade + slide from bottom (200–300ms) |
| Bottom sheet | Slide up com spring |
| Adicionar produto | Toast + leve bounce no contador do carrinho |
| Tab switch PDV | Crossfade entre Produtos e Pedido |
| Pull to refresh | Spinner primary no topo |
| Botão CTA | Gradiente roxo, sem escala excessiva |

Evitar animações 3D pesadas (como o antigo `Image3D` da landing) — preferir transições flat e rápidas.

---

## 9. Prompts auxiliares por tela

### 9.1 Apenas PDV

```
Design mobile da tela PDV do Alba Tec. Fundo branco/cinza claro. Header com busca e toggles Delivery/Garçom. Categorias em scroll horizontal. Grid 2 colunas de produtos com foto, nome, preço e badge PROMO. Bottom bar com abas Produtos e Pedido (badge com quantidade). Aba Pedido: tabs Cliente/Atend/Pag/Carr, botão verde Iniciar Pedido. Roxo #6D28D9 como cor primária. Touch targets 44px. pt-BR.
```

### 9.2 Apenas Dashboard

```
Design mobile do Painel de Controle Alba Tec. Saudação personalizada com data. 4 KPI cards em grid 2x2 com ícones coloridos e badges de crescimento verde. Gráficos de barras para volume de pedidos e clientes. Botões + Novo Pedido (roxo), PDV e Cardápio (outline). Estilo shadcn/ui, Inter, tema claro e escuro.
```

### 9.3 Design system apenas

```
Crie um design system mobile para Alba Tec (gestão de restaurantes). Baseado em shadcn/ui: roxo primary #6D28D9, Inter, radius 10–16px, cards com borda sutil, bottom tab navigation, sheets modais, badges de status coloridos para pedidos, botões com gradiente roxo→violeta. Incluir tokens light/dark, espaçamento 4px grid, e componentes: Button, Card, Badge, Input, TabBar, BottomSheet, Toast.
```

---

## 10. Stack técnica sugerida

Para máxima fidelidade ao frontend:

| Camada | Sugestão | Motivo |
|--------|----------|--------|
| **UI** | React Native + NativeWind **ou** Flutter com tokens exportados | Reutilizar paleta Tailwind |
| **Ícones** | Lucide (mesmo do web) | Consistência visual |
| **Navegação** | React Navigation (tabs + stack + drawer) | Espelha sidebar + PDV tabs |
| **API** | Mesmos endpoints `/api/*` do `backend_moday` | Paridade funcional |
| **Estado** | TanStack Query | Igual aos hooks `use-authenticated-api` |
| **Forms** | React Hook Form + Zod | Padrão do frontend |

---

## 11. Referências de arquivos no repositório

| Área | Arquivo |
|------|---------|
| Cores e tema | `moday_frontend/src/app/globals.css` |
| Menu / módulos | `moday_frontend/src/components/app-sidebar.tsx` |
| Layout dashboard | `moday_frontend/src/app/(dashboard)/layout.tsx` |
| Dashboard KPIs | `moday_frontend/src/app/(dashboard)/dashboard/` |
| PDV layout mobile | `moday_frontend/src/app/(dashboard)/pdv/components/layout/pdv-main-layout.tsx` |
| Card de produto PDV | `moday_frontend/src/app/(dashboard)/pdv/components/catalog/product-card.tsx` |
| Status de pedido | `moday_frontend/src/app/(dashboard)/pdv/components/order/order-status-badge.tsx` |
| Financeiro | `moday_frontend/src/app/(dashboard)/financial/dashboard/page.tsx` |
| Landing (marketing) | `moday_frontend/src/app/landing/components/` |
| Loja pública | `moday_frontend/src/app/store/[slug]/page.tsx` |
| Screenshots reais | `moday_frontend/public/landing/*.png` |

---

## 12. Critérios de aceite visual

- [ ] Paleta roxo/violeta primary idêntica ao web (não laranja `#FF6528` do `modules-section` legado)
- [ ] Logo: símbolo "A" Alba Tec (gradiente roxo), wordmark Alba navy + Tec roxo
- [ ] Marca exibida como **Alba Tec** (não "Moday" na interface pública)
- [ ] PDV com bottom nav de 2 abas no mobile
- [ ] KPI cards com mesmo conteúdo e hierarquia do dashboard web
- [ ] Status de pedidos com mesmas cores semânticas
- [ ] Tema claro e escuro funcional
- [ ] Textos em português brasileiro, moeda R$
- [ ] Touch targets ≥ 44px em todos os botões do PDV
