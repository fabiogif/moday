"use client"

import { ArrowRight, CheckCircle, Gift, BarChart3, Package, Truck } from 'lucide-react'
import { Button } from '@/components/ui/button'
import Image from 'next/image'
import Link from 'next/link'
import { useRegisterModal } from '@/contexts/register-modal-context'

const modules = [
  {
    title: 'Marketing & Fidelização',
    description: 'Nossos clientes voltam 45% mais vezes após implementar o programa de fidelidade',
    features: [
      'Cupons de desconto',
      'Programa de pontos',
      'Cashback automático',
      'Campanhas por email/SMS',
      'Aniversariante do mês'
    ],
    image: '/dashboard-light.png',
    badge: '↑ 45% retenção',
    layout: 'left' // imagem à esquerda
  },
  {
    title: 'Relatórios Inteligentes',
    description: 'Tome decisões baseadas em dados reais, não em achismos',
    features: [
      'Painel de Controle em tempo real',
      'Análise de vendas por produto/categoria',
      'Ranking de garçons',
      'Previsão de demanda (IA)',
      'Exportação para Excel'
    ],
    image: '/dashboard-light.png',
    badge: 'Decisões 3x mais rápidas',
    layout: 'right' // imagem à direita
  },
  {
    title: 'Gestão de Estoque',
    description: 'Mantenha o controle total do seu estoque com alertas inteligentes',
    features: [
      'Controle de movimentação',
      'Alertas de baixo estoque',
      'Gestão de fornecedores',
      'Cálculo de custos',
      'Relatórios de desperdício'
    ],
    image: '/dashboard-light.png',
    badge: '-30% desperdício',
    layout: 'left'
  },
  {
    title: 'Integração com Delivery',
    description: 'Centralize todos os pedidos em um só lugar',
    features: [
      'Integração com iFood',
      'Integração com outros apps',
      'Sincronização automática',
      'Gestão unificada',
      'Relatórios consolidados'
    ],
    image: '/dashboard-light.png',
    badge: '100% integrado',
    layout: 'right'
  }
]

export function ModulesSection() {
  const { openModal } = useRegisterModal()

  return (
    <section id="modules" className="py-24 bg-white">
      <div className="container mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="mx-auto max-w-3xl text-center mb-20">
          <h2 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight mb-6 text-gray-900">
            Módulos Principais
          </h2>
          <p className="text-xl text-gray-600 leading-relaxed">
            Funcionalidades completas que transformam a gestão do seu restaurante
          </p>
        </div>

        {/* Modules */}
        {modules.map((module, index) => (
          <div
            key={module.title}
            className={`grid lg:grid-cols-2 gap-8 lg:gap-12 items-center mb-16 sm:mb-24 lg:mb-32 ${
              index % 2 === 0 ? '' : 'lg:grid-flow-dense'
            }`}
          >
            {/* Imagem */}
            <div className={`relative ${module.layout === 'right' ? 'lg:col-start-2' : ''}`}>
              <div className="absolute inset-0 bg-gradient-to-r from-apricot-300/40 to-apricot-500/30 rounded-3xl blur-3xl -z-10" />
              <div className="relative rounded-2xl shadow-2xl overflow-hidden">
                <Image
                  src={module.image}
                  alt={module.title}
                  width={600}
                  height={400}
                  className="w-full h-auto object-cover"
                />
                {/* Floating Badge */}
                <div className="absolute top-4 right-4 bg-apricot-400 text-gray-900 px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                  {module.badge}
                </div>
              </div>
            </div>

            {/* Conteúdo */}
            <div className={`${module.layout === 'right' ? 'lg:col-start-1 lg:row-start-1' : ''}`}>
              <h3 className="text-3xl sm:text-4xl font-bold mb-4 text-gray-900">
                {module.title}
              </h3>
              <p className="text-lg text-gray-600 mb-8 leading-relaxed">
                {module.description}
              </p>

              {/* Features List */}
              <ul className="space-y-4 mb-8">
                {module.features.map((feature) => (
                  <li key={feature} className="flex items-start gap-3">
                    <CheckCircle className="w-6 h-6 text-green-500 shrink-0 mt-0.5" />
                    <span className="text-gray-700">{feature}</span>
                  </li>
                ))}
              </ul>

              {/* CTA */}
              <Button
                className="bg-primary hover:bg-primary-600 text-white group"
                onClick={() => openModal()}
              >
                <span className="inline-flex items-center gap-2">
                  Ver demonstração
                  <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                </span>
              </Button>
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}

