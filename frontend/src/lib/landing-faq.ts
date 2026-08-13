export type LandingFaqItem = {
  id: string
  question: string
  answer: string
}

export const LANDING_FAQ_ITEMS: LandingFaqItem[] = [
  {
    id: 'o-que-e',
    question: 'O que é o Alba Tec?',
    answer:
      'O Alba Tec é um sistema de gestão para restaurantes com PDV, cardápio digital, controle de mesas, financeiro e relatórios em tempo real. Tudo em nuvem, para operar salão, balcão e delivery em um só lugar.',
  },
  {
    id: 'preco',
    question: 'Quanto custa o sistema de gestão para restaurantes?',
    answer:
      'Há plano gratuito para começar e planos pagos conforme o tamanho do negócio. Nos planos pagos você testa por 7 dias sem cartão. Os valores atualizados estão na seção de planos desta página.',
  },
  {
    id: 'teste-gratis',
    question: 'Existe teste gratuito?',
    answer:
      'Sim. Você pode usar o plano gratuito sem prazo de expiração ou testar os planos pagos por 7 dias, sem cartão de crédito. Cadastre-se e comece em minutos.',
  },
  {
    id: 'mobile',
    question: 'Funciona em celular e tablet?',
    answer:
      'Sim. O sistema é em nuvem e funciona no navegador de computador, tablet e smartphone. Há também o app Alba Tec Restaurante para receber pedidos com push e gerenciar o status pelo celular.',
  },
  {
    id: 'nfe',
    question: 'O Alba Tec emite NF-e / NFC-e?',
    answer:
      'Hoje o PDV e o cardápio digital já permitem coletar CPF/CNPJ do cliente para nota. A emissão automática de NFC-e/NF-e junto à SEFAZ ainda está em evolução — quando liberada no seu plano, o fluxo usa o documento já capturado no pedido. Enquanto isso, você organiza vendas e documentos no sistema e pode emitir a nota no seu emissor atual.',
  },
  {
    id: 'whatsapp',
    question: 'Tem integração com WhatsApp?',
    answer:
      'Sim. Você compartilha o cardápio digital por WhatsApp e redes sociais, e o fluxo de pedidos pode notificar o cliente. No dia a dia, o suporte prioritário via WhatsApp está disponível nos planos avançados.',
  },
  {
    id: 'implantacao',
    question: 'Quanto tempo leva a implantação?',
    answer:
      'A maioria dos restaurantes começa no mesmo dia: cadastro, produtos e cardápio digital. Treinamento e suporte ajudam a equipe a operar o PDV e os pedidos com segurança.',
  },
  {
    id: 'pedidos',
    question: 'Como funciona o sistema de gestão de pedidos?',
    answer:
      'O Alba Tec permite gerenciar pedidos em tempo real, desde o recebimento até a entrega. Você acompanha o status de cada pedido, organiza a produção, notifica clientes e gera relatórios de vendas em um só lugar.',
  },
  {
    id: 'variacoes',
    question: 'Como funcionam as variações e opcionais dos produtos?',
    answer:
      'Configure variações de escolha única, como tamanhos e sabores, e opcionais de múltipla escolha, como adicionais e bordas. O preço final é calculado automaticamente no PDV e no cardápio digital.',
  },
  {
    id: 'cardapio',
    question: 'Posso personalizar o cardápio digital?',
    answer:
      'Sim. Você controla produtos, categorias, preços, imagens e descrições. O cardápio fica online com link exclusivo para compartilhar por QR Code, WhatsApp e redes sociais.',
  },
  {
    id: 'estoque',
    question: 'Como funciona o controle de estoque?',
    answer:
      'O sistema registra entradas e saídas, alerta quando itens estão acabando e ajuda a acompanhar a movimentação de produtos com relatórios por período.',
  },
  {
    id: 'suporte',
    question: 'O sistema oferece suporte e treinamento?',
    answer:
      'Sim. Há suporte por chat e e-mail (atendimento@albatec.com.br), documentação e materiais de treinamento para sua equipe começar a usar o sistema com segurança.',
  },
  {
    id: 'multiplas-unidades',
    question: 'Posso gerenciar múltiplos restaurantes?',
    answer:
      'Planos avançados permitem administrar mais de um estabelecimento na mesma conta, com cardápios, equipes e relatórios separados por unidade.',
  },
  {
    id: 'pagamentos',
    question: 'Como funciona a integração com pagamentos?',
    answer:
      'O Alba Tec integra formas de pagamento usadas no dia a dia do restaurante e registra as transações nos relatórios financeiros para facilitar o fechamento.',
  },
  {
    id: 'acesso',
    question: 'Posso acessar o sistema de qualquer lugar?',
    answer:
      'Sim. O sistema é em nuvem e funciona em computador, tablet e smartphone, com dados sincronizados em tempo real.',
  },
  {
    id: 'app-mobile',
    question: 'Existe app mobile para gerenciar pedidos?',
    answer:
      'Sim. O Alba Tec Restaurante permite receber push com som quando chega um pedido, aceitar e avançar o status no mesmo fluxo do painel, consultar histórico e ajustar os horários de funcionamento pelo celular.',
  },
  {
    id: 'relatorios',
    question: 'Quais relatórios o sistema oferece?',
    answer:
      'Há relatórios de vendas, produtos mais vendidos, desempenho por período, clientes frequentes, estoque e indicadores financeiros, com exportação quando necessário.',
  },
]
