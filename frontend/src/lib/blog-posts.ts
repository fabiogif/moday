export type BlogPost = {
  slug: string
  title: string
  description: string
  date: string
  readingMinutes: number
  keywords: string[]
  content: string[]
}

export const BLOG_POSTS: BlogPost[] = [
  {
    slug: 'como-escolher-sistema-gestao-restaurantes',
    title: 'Como escolher um sistema de gestão para restaurantes',
    description:
      'Critérios práticos para escolher PDV, cardápio digital e relatórios sem cair em software genérico.',
    date: '2026-07-20',
    readingMinutes: 7,
    keywords: ['sistema de gestão para restaurantes', 'PDV', 'cardápio digital'],
    content: [
      'Escolher um sistema de gestão para restaurantes não é só comparar preço. O software precisa acompanhar o ritmo do salão, do balcão e do delivery — com pedidos claros, estoque sob controle e números que o dono entende no fim do dia.',
      'Comece pelo fluxo de pedido: o PDV precisa registrar variações e opcionais sem erro, e a cozinha precisa ver o status em tempo real. Se a equipe ainda anota em papel ou WhatsApp solto, o sistema deve substituir esse caos — não somar outra tela.',
      'O cardápio digital é o segundo critério. Link e QR Code reduzem atrito no delivery e no salão. Avalie se preços, imagens e categorias são fáceis de atualizar sem depender de desenvolvedor.',
      'Relatórios e financeiro fecham a decisão. Vendas por período, produtos mais vendidos e visão de caixa evitam decisões no “achismo”. Prefira plataformas em nuvem, com acesso no celular e suporte em português.',
      'Antes de assinar, teste o fluxo completo: cadastro de produtos, pedido no PDV, cardápio digital e um relatório simples. No Alba Tec você pode começar com teste grátis e validar se o sistema de gestão para restaurantes encaixa na sua operação.',
    ],
  },
  {
    slug: 'pdv-para-restaurante',
    title: 'PDV para restaurante: o que não pode faltar',
    description:
      'Funções essenciais de um PDV para restaurante — do ticket rápido às personalizações de pizza e lanches.',
    date: '2026-07-18',
    readingMinutes: 6,
    keywords: ['PDV para restaurante', 'sistema para restaurante'],
    content: [
      'Um PDV para restaurante bom é rápido no horário de pico. A interface touch-first reduz cliques e evita erros de digitação quando a fila está cheia.',
      'Variações (tamanho, sabor) e opcionais (adicionais, remoções) precisam calcular o preço automaticamente. Isso evita briga no caixa e no delivery.',
      'Integração com cardápio digital e app mobile completa o ciclo: o pedido nasce no canal certo e segue o mesmo fluxo até a produção.',
      'Também importa o histórico: repetir pedidos frequentes, consultar status e fechar o caixa com clareza. Um sistema de gestão para restaurantes une PDV, estoque e relatórios — em vez de três ferramentas desconectadas.',
      'Se você está avaliando PDV agora, experimente o fluxo do Alba Tec no cadastro gratuito e veja o cardápio de demonstração em ação.',
    ],
  },
  {
    slug: 'cardapio-digital-para-restaurantes',
    title: 'Cardápio digital para restaurantes: guia prático',
    description:
      'Como montar um cardápio digital que vende mais: fotos, categorias, QR Code e compartilhamento no WhatsApp.',
    date: '2026-07-15',
    readingMinutes: 6,
    keywords: ['cardápio digital', 'QR Code', 'WhatsApp'],
    content: [
      'O cardápio digital deixou de ser “extra” e virou canal de venda. Cliente abre o link, escolhe, personaliza e reduz ida e volta no WhatsApp.',
      'Organize por categorias claras, use fotos reais e descreva o que importa (porção, ingredientes, restrições). Preços atualizados evitam cobrança errada no caixa.',
      'QR Code na mesa e no balcão acelera o atendimento. No delivery, o mesmo link no status do WhatsApp e nas redes sociais mantém a marca consistente.',
      'Integre o cardápio ao sistema de gestão para restaurantes: o pedido que nasce no digital precisa aparecer no PDV e na cozinha sem retrabalho.',
      'No Alba Tec o cardápio digital faz parte do mesmo sistema — você testa o fluxo completo na demonstração e no plano gratuito.',
    ],
  },
  {
    slug: 'controle-estoque-restaurante',
    title: 'Controle de estoque para restaurantes: como reduzir perdas',
    description:
      'Práticas simples de estoque para food service: entradas, saídas, alertas e ligação com as vendas do PDV.',
    date: '2026-07-12',
    readingMinutes: 5,
    keywords: ['controle de estoque', 'perdas', 'food service'],
    content: [
      'Perda de estoque come margem. O controle começa registrando entradas e saídas com disciplina — e alertando quando o item está acabando.',
      'Ligue o estoque às vendas: quando o PDV vende, a movimentação precisa refletir a realidade. Sem isso, o inventário vira planilha morta.',
      'Foque nos itens de alta rotação e alto custo. Relatórios por período mostram o que sai mais e onde há desperdício.',
      'Um sistema de gestão para restaurantes ajuda a manter o ciclo fechado: compra, venda, alerta e decisão. Comece simples e evolua a rigor conforme a equipe se adapta.',
      'Se ainda controla tudo em planilha, vale testar o Alba Tec e centralizar estoque com pedidos e financeiro.',
    ],
  },
  {
    slug: 'como-reduzir-erros-pedidos-cozinha',
    title: 'Como reduzir erros de pedido entre salão e cozinha',
    description:
      'Processos e ferramentas para diminuir pedido errado, retrabalho e atrito entre salão, balcão e produção.',
    date: '2026-07-08',
    readingMinutes: 5,
    keywords: ['gestão de pedidos', 'cozinha', 'PDV'],
    content: [
      'Pedido errado custa dinheiro e reputação. A causa mais comum é informação incompleta entre quem anota e quem produz.',
      'Padronize o fluxo: o pedido entra uma vez no PDV ou no cardápio digital, com observações claras, e a cozinha acompanha o status em tempo real.',
      'Evite canais paralelos (papel + WhatsApp + grito). Quanto mais canais, mais chance de esquecer adicional ou ponto da carne.',
      'Treine a equipe no mesmo sistema e use app mobile para quem não fica preso ao caixa. Relatórios mostram gargalos de horário e itens com mais cancelamento.',
      'O Alba Tec foi pensado para esse fluxo único — do pedido à produção — no sistema de gestão para restaurantes.',
    ],
  },
  {
    slug: 'gestao-financeira-restaurante',
    title: 'Gestão financeira para restaurantes: do caixa aos indicadores',
    description:
      'Como acompanhar vendas, despesas e indicadores sem virar escravo de planilha no fim do mês.',
    date: '2026-07-05',
    readingMinutes: 6,
    keywords: ['gestão financeira', 'relatórios', 'restaurante'],
    content: [
      'A gestão financeira do restaurante começa no dia a dia: cada venda registrada, cada despesa classificada, cada fechamento de caixa conferido.',
      'Indicadores úteis: faturamento por período, ticket médio, produtos mais vendidos e comparação entre unidades (se houver rede).',
      'Separe o operacional do estratégico. O gerente precisa do pulso do dia; o dono precisa da tendência da semana e do mês.',
      'Um sistema de gestão para restaurantes com financeiro e relatórios reduz a dependência de planilhas e acelera a decisão — especialmente quando o PDV e o cardápio já alimentam os dados.',
      'Experimente o Alba Tec com teste grátis e veja vendas e indicadores no mesmo painel.',
    ],
  },
]

export function getBlogPost(slug: string): BlogPost | undefined {
  return BLOG_POSTS.find((post) => post.slug === slug)
}

export function getAllBlogSlugs(): string[] {
  return BLOG_POSTS.map((post) => post.slug)
}
