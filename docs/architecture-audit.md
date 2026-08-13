# Architecture Audit Specification

## Objetivo

Esta especificação define as regras obrigatórias para revisão arquitetural e análise de qualidade do código.

Toda implementação nova, alteração ou refatoração deve ser validada contra esta especificação antes de ser considerada concluída.

---

# Objetivos da Auditoria

Garantir que o projeto mantenha:

- Baixo acoplamento
- Alta coesão
- Código reutilizável
- Fácil manutenção
- Fácil evolução
- Alta testabilidade
- Arquitetura consistente
- Performance
- Segurança

---

# Regras Arquiteturais

## Camadas

Cada responsabilidade deve permanecer em sua camada.

### Controller

Responsável apenas por:

- Receber requisições
- Validar entrada
- Invocar casos de uso/Services
- Retornar resposta

Não deve:

- Acessar banco
- Executar regras de negócio
- Executar consultas SQL
- Manipular infraestrutura

---

### Service

Responsável por:

- Casos de uso
- Orquestração
- Regras de negócio

Não deve:

- Conhecer HTTP
- Manipular Request
- Manipular Response

---

### Repository

Responsável apenas por persistência.

Não deve:

- Conter regras de negócio
- Validar regras do domínio

---

### Domain

Responsável por:

- Entidades
- Value Objects
- Regras do domínio

Não deve conhecer:

- Banco
- Framework
- HTTP
- Infraestrutura

---

# SOLID

Toda implementação deve respeitar:

- Single Responsibility Principle
- Open Closed Principle
- Liskov Substitution Principle
- Interface Segregation Principle
- Dependency Inversion Principle

---

# DRY

É proibido duplicar:

- regras de negócio
- validações
- consultas
- algoritmos
- conversões

Sempre reutilizar implementações existentes.

---

# KISS

Priorizar sempre a solução mais simples que resolva corretamente o problema.

Evitar abstrações prematuras.

---

# YAGNI

Não implementar funcionalidades para uso futuro.

Implementar apenas o necessário para o requisito atual.

---

# Clean Code

Evitar:

- métodos longos
- classes grandes
- parâmetros excessivos
- comentários desnecessários
- números mágicos
- strings mágicas
- switch gigantes
- if aninhados
- código morto
- código comentado

---

# Code Smells

Detectar:

- God Object
- Long Method
- Long Parameter List
- Primitive Obsession
- Feature Envy
- Data Clumps
- Temporary Fields
- Shotgun Surgery
- Divergent Change

---

# Performance

Detectar:

- N+1 Queries
- consultas repetidas
- processamento duplicado
- loops desnecessários
- carregamentos redundantes

---

# Segurança

Detectar:

- SQL Injection
- XSS
- CSRF
- Hardcoded Secrets
- Exposição de dados
- Falta de autorização
- Falta de autenticação
- Mass Assignment

---

# Testabilidade

O código deve:

- possuir baixo acoplamento
- permitir mocks
- utilizar interfaces
- evitar dependências estáticas
- permitir testes unitários

---

# Reutilização

Antes de criar qualquer:

- Service
- Repository
- Component
- Hook
- Helper
- Utilitário

deve ser verificado se já existe implementação equivalente.

Duplicação é proibida.

---

# Design Patterns

Aplicar somente quando agregarem valor.

Preferencialmente:

- Strategy
- Factory
- Builder
- Adapter
- Specification
- Repository

Evitar overengineering.

---

# Resultado Esperado

Ao finalizar uma auditoria, apresentar:

## Resumo Executivo

- Nota da arquitetura
- Nota de SOLID
- Nota de DRY
- Nota de Clean Code
- Nota de Segurança
- Nota de Performance

---

## Problemas encontrados

Para cada problema:

- Arquivo
- Classe
- Método
- Severidade
- Explicação
- Impacto
- Sugestão de correção

---

## Roadmap

Organizar as correções em:

- Críticas
- Alta prioridade
- Média prioridade
- Baixa prioridade

---

# Critérios de Aprovação

Uma implementação somente pode ser considerada aprovada quando:

- Não viola SOLID
- Não viola DRY
- Respeita a arquitetura
- Não duplica código
- Não adiciona dependências desnecessárias
- Mantém consistência com o restante do projeto
- Possui boa testabilidade
- Não reduz a legibilidade do código