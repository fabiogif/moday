# Alba Tec — Identidade Visual

Documento de referência para implementação da marca no frontend, app mobile e materiais de marketing.

**Arquivo oficial:** [AlbaTec_Identidade_Visual.pdf](./AlbaTec_Identidade_Visual.pdf)  
**Guia visual:** `moday_frontend/public/brand/albatec-identidade-visual.png`  
**Última atualização:** junho/2026

---

## 1. Logomarca

### Símbolo
- Letra **"A"** estilizada com traços horizontais e nós circulares no lado esquerdo (circuito / conexão digital)
- Gradiente vertical: **roxo vibrante** (topo) → **índigo escuro** (base)

### Wordmark
| Parte | Cor (fundo claro) | Cor (fundo escuro) | Peso |
|-------|-------------------|--------------------|------|
| **Alba** | `#1A1A2E` (navy) | `#FFFFFF` | Bold |
| **Tec** | `#7C3AED` (roxo) | `#7C3AED` | Regular/Medium |

### Versões oficiais

| Versão | Uso |
|--------|-----|
| **Principal** | Fundos claros — símbolo + wordmark horizontal |
| **Fundo escuro** | Headers escuros, dark mode — "Alba" branco |
| **Ícone reduzido** | Favicon, app mobile, sidebar colapsada (< 32px sem texto) |
| **Monocromática** | Preto, roxo sólido ou branco sobre roxo |

---

## 2. Paleta de cores

| Nome | Hex | Uso |
|------|-----|-----|
| Roxo primário | `#7C3AED` | "Tec", CTAs, tab ativa |
| Roxo gradiente topo | `#9D36FF` | Topo do símbolo |
| Roxo gradiente base | `#5A18C9` | Base do símbolo |
| Navy / Alba | `#1A1A2E` | Texto "Alba", fundos escuros |
| Branco | `#FFFFFF` | Fundos claros, texto em dark |
| Cinza muted | `#71717A` | Texto secundário |

### Integração com o frontend (CSS)
O tema shadcn usa `oklch` em `globals.css`. O roxo primary atual (`--primary`) é compatível com a identidade. Opcionalmente alinhar para `#7C3AED` em futura atualização de tokens.

---

## 3. Diretrizes de uso

Conforme o PDF oficial:

1. Preferir a **versão principal** em fundos claros
2. Usar **versão branca/roxa** em fundos escuros
3. Em **aplicativos**, usar apenas o **ícone reduzido** na launcher icon
4. Manter **área de respiro** ao redor da marca
5. **Não** distorcer, aplicar sombras excessivas ou alterar cores

### Reduções (tamanhos mínimos)

| Tamanho | Conteúdo |
|---------|----------|
| ≥ 128px | Símbolo + wordmark completo |
| 64px | Símbolo + wordmark compacto |
| 32px | Apenas símbolo |
| 16px | Apenas símbolo (favicon) |

---

## 4. Assets no repositório

| Arquivo | Descrição |
|---------|-----------|
| `public/brand/logo-alba-tec-sem-fundo.png` | **Logo oficial** — símbolo + wordmark, fundo claro |
| `public/brand/logo-alba-escuro.png` | **Logo fundo escuro** — "Alba" branco + símbolo roxo |
| `public/brand/logo-icon.png` | Símbolo "A" isolado (512×512, derivado da logo oficial) |
| `public/brand/albatec-identidade-visual.png` | Guia completo de aplicações |
| `public/favicon.png` | Favicon 64px derivado do símbolo |
| `docs/AlbaTec_Identidade_Visual.pdf` | PDF oficial |

---

## 5. Componente React

```tsx
import { AlbaTecLogo } from '@/components/albatec-logo'

// Navbar / header (padrão)
<AlbaTecLogo href="/landing" height={36} />

// Sidebar — só ícone
<AlbaTecLogo variant="icon" height={32} />

// Login / hero — logo completa
<AlbaTecLogo variant="full" height={80} />

// Fundo escuro (fixo)
<AlbaTecLogo variant="full" height={80} onDark />

// Alterna conforme tema claro/escuro
<AlbaTecLogo href="/landing" height={36} adaptive />
```

**Variantes:** `horizontal` | `full` | `icon` | `wordmark`

---

## 6. App mobile

- **Launcher icon:** `logo-icon.png` em tile arredondado branco ou navy `#1A1A2E`
- **Splash screen:** símbolo centralizado + wordmark abaixo
- **Navbar:** variant `horizontal` com height 32–36
- **Não usar** o ícone antigo de garfo/faca (Utensils)

---

## 7. Pendências (quando disponível)

- [ ] Arquivos vetoriais (SVG) exportados do designer
- [ ] Versão horizontal oficial em PNG/SVG (símbolo à esquerda do texto)
- [ ] Ícone app iOS 1024×1024 e Android adaptive icon
- [ ] Atualizar `--primary` em `globals.css` para match exato com `#7C3AED`
