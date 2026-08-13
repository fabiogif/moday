"use client"

import { LandingFlowSection } from './landing-flow-section'
import { OperationFlowAnimation } from './operation-flow-animation'
import { FinanceFlowAnimation } from './finance-flow-animation'
import { AppMobileFlowAnimation } from './app-mobile-flow-animation'

export function OperationFlowSection() {
  return (
    <LandingFlowSection
      id="operacao-flow"
      eyebrow="Operação Flow"
      title="Venda em todos os canais sem perder o controle"
      description="Automatize pedidos do salão, delivery e cardápio digital. Quando um pedido chega, o Alba Tec organiza a cozinha, atualiza o estoque e mantém sua equipe sincronizada."
      ctaLabel="Explorar Operação"
      ctaHref="/auth/register"
      ctaEvent="cta_operacao_flow_click"
      animation={<OperationFlowAnimation />}
      features={[
        {
          icon: 'user-plus',
          title: 'Captura instantânea de clientes',
          description:
            'Registre clientes no PDV ou pelo cardápio digital. Histórico de pedidos e preferências sempre à mão.',
        },
        {
          icon: 'shopping-cart',
          title: 'Pedidos unificados',
          description:
            'Balcão, mesas, delivery e iFood em um único painel. Menos erros, mais velocidade no pico de movimento.',
        },
        {
          icon: 'mail',
          title: 'Comunicação automática',
          description:
            'Status do pedido atualizado em tempo real para equipe e cliente. Cozinha e salão sempre alinhados.',
        },
      ]}
    />
  )
}

export function FinanceFlowSection() {
  return (
    <LandingFlowSection
      id="financeiro-flow"
      eyebrow="Financeiro Flow"
      title="Seja proativo com os números do restaurante"
      description="Configure automações que convertem dados em decisão. Contas a pagar, receber, despesas e fluxo de caixa projetado — tudo visível antes que vire problema."
      ctaLabel="Explorar Financeiro"
      ctaHref="/auth/register"
      ctaEvent="cta_financeiro_flow_click"
      reverse
      animation={<FinanceFlowAnimation />}
      features={[
        {
          icon: 'wallet',
          title: 'Fluxo de caixa em tempo real',
          description:
            'Acompanhe saldo projetado, contas vencidas e receitas do mês em um painel financeiro integrado ao PDV.',
        },
        {
          icon: 'file-text',
          title: 'Contas a pagar e receber',
          description:
            'Organize fornecedores, categorias e vencimentos. Saiba exatamente quanto entra e quanto sai.',
        },
        {
          icon: 'bar-chart-3',
          title: 'Relatórios que orientam',
          description:
            'Gráficos de receita, despesas e margem para tomar decisões com dados — não com achismo.',
        },
      ]}
    />
  )
}

export function AppMobileFlowSection() {
  return (
    <LandingFlowSection
      id="app-mobile"
      eyebrow="App Mobile"
      badge="NOVO"
      title="Aceite pedidos pelo celular, com o mesmo fluxo do painel"
      description="O Alba Tec Restaurante leva a operação para o bolso: push com som quando chega pedido, aceite e avance de status como no PDV, histórico completo e horários do salão editáveis no app."
      ctaLabel="Começar grátis"
      ctaHref="/auth/register"
      ctaEvent="cta_app_mobile_flow_click"
      animation={<AppMobileFlowAnimation />}
      features={[
        {
          icon: 'bell',
          title: 'Push com som, mesmo com app fechado',
          description:
            'Novo pedido gera alerta no celular via Expo Push / FCM. Toque na notificação e abra o detalhe na hora.',
        },
        {
          icon: 'check-circle',
          title: 'Aceitar e avançar status',
          description:
            'Do Pedido Recebido ao Entregue — o mesmo fluxo do painel: aceitar, preparar, expedir, entregar ou cancelar.',
        },
        {
          icon: 'smartphone',
          title: 'Histórico e horários no bolso',
          description:
            'Consulte pedidos, detalhes do cliente e itens, e ajuste o horário de funcionamento sem abrir o computador.',
        },
      ]}
    />
  )
}
