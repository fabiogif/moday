# Visualização da Miniatura de Produtos

## Como Ficará a Listagem

### Exemplo Visual da Tabela de Produtos:

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Produtos                                                                     │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  ☑  ┌────────┐  Pizza Margherita                  R$ 45,00  🏷️ Pizzas      │
│     │ 🍕     │  Pizza tradicional com tomate                                 │
│     └────────┘  e mussarela                                                  │
│                                                                               │
│  ☐  ┌────────┐  Coca-Cola 350ml                   R$ 4,50   🏷️ Bebidas     │
│     │  Sem   │  Refrigerante Coca-Cola lata                                 │
│     │ imagem │  350ml                                                        │
│     └────────┘                                                               │
│                                                                               │
│  ☐  ┌────────┐  Hambúrguer Artesanal              R$ 28,00  🏷️ Lanches     │
│     │ 🍔     │  Hambúrguer com carne                                        │
│     └────────┘  angus 200g                                                  │
│                                                                               │
│  ☐  ┌────────┐  Suco de Laranja 300ml             R$ 6,00   🏷️ Bebidas     │
│     │  Sem   │  Suco natural de laranja                                     │
│     │ imagem │                                                              │
│     └────────┘                                                               │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Estrutura do Layout

### Com Imagem:
```
Checkbox  [Miniatura 48x48px]  Nome do Produto        Preço    Categorias
          [    Imagem     ]    Descrição do produto
```

### Sem Imagem:
```
Checkbox  [Placeholder   ]  Nome do Produto        Preço    Categorias
          [ "Sem imagem" ]  Descrição do produto
```

## Características Visuais

### Miniatura (Thumbnail):
- **Tamanho:** 48x48 pixels (3rem)
- **Formato:** Quadrado com bordas arredondadas
- **Background:** Cinza claro (muted)
- **Comportamento:** 
  - Mostra imagem se disponível
  - Mostra "Sem imagem" se não houver foto
  - Fallback automático em caso de erro

### Layout do Nome:
- **Estrutura:** Flexbox horizontal
- **Gap:** 12px entre imagem e texto
- **Nome:** Fonte medium, truncado se muito longo
- **Descrição:** Texto menor, cinza, max 200px com truncate

## Benefícios da Implementação

1. **Identificação Visual Rápida**
   - Usuários reconhecem produtos pela imagem
   - Navegação mais intuitiva

2. **Profissionalismo**
   - Interface mais moderna
   - Melhor experiência do usuário

3. **Consistência**
   - Placeholder para produtos sem imagem
   - Layout uniforme

4. **Performance**
   - Imagens otimizadas com object-cover
   - Lazy loading nativo do navegador
   - Fallback para erros

## Como Adicionar Imagens aos Produtos

1. **Pelo Formulário de Criação/Edição:**
   - Campo de upload de imagem já disponível
   - Aceita imagens JPEG, PNG, etc.

2. **Pela API:**
   ```bash
   curl -X POST http://localhost:8000/api/product \
     -H "Authorization: Bearer $TOKEN" \
     -F "name=Pizza Margherita" \
     -F "description=Pizza tradicional" \
     -F "price=45.00" \
     -F "image=@/path/to/pizza.jpg"
   ```

3. **Resultado na Listagem:**
   - Imagem aparece automaticamente
   - Thumbnail renderizado com proporções corretas
   - Alt text com nome do produto

## Próximos Passos (Opcional)

- [ ] Adicionar zoom ao passar mouse sobre miniatura
- [ ] Implementar lightbox para ver imagem em tamanho maior
- [ ] Adicionar badge de "Sem imagem" mais estilizado
- [ ] Otimizar carregamento com lazy loading explícito
- [ ] Adicionar placeholder animado durante carregamento
