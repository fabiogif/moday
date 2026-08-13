/**
 * Serviço de integração com API ReceitaWS
 * Documentação: https://receitaws.com.br/api
 *
 * A consulta é feita via proxy no backend (endpoints.cnpj.lookup), não
 * diretamente do navegador — a ReceitaWS não envia cabeçalhos CORS para
 * requisições de browser, então uma chamada direta é bloqueada em produção.
 */
import { apiClient, endpoints } from '@/lib/api-client';

export interface ReceitaWSResponse {
  // Dados básicos
  cnpj: string;
  tipo: string;
  abertura: string;
  nome: string;
  fantasia: string;
  porte: string;
  
  // Atividade
  natureza_juridica: string;
  atividade_principal: Array<{
    code: string;
    text: string;
  }>;
  atividades_secundarias: Array<{
    code: string;
    text: string;
  }>;
  
  // Localização
  logradouro: string;
  numero: string;
  complemento: string;
  bairro: string;
  municipio: string;
  uf: string;
  cep: string;
  
  // Contato
  email: string;
  telefone: string;
  
  // Situação
  situacao: string;
  data_situacao: string;
  motivo_situacao: string;
  
  // Outros
  capital_social: string;
  qsa: Array<{
    nome: string;
    qual: string;
  }>;
  
  // Billing
  billing: {
    free: boolean;
    database: boolean;
  };
  
  // Erro
  status?: string;
  message?: string;
}

export interface CompanyData {
  // Dados básicos
  cnpj: string;
  nome: string;
  nomeFantasia: string;
  
  // Endereço
  address: string;
  number: string;
  complement: string;
  neighborhood: string;
  city: string;
  state: string;
  zipCode: string;
  
  // Contato
  email: string;
  phone: string;
  
  // Status
  situacao: string;
  abertura: string;
  porte: string;
  naturezaJuridica: string;
}

/**
 * Consulta CNPJ na ReceitaWS
 * @param cnpj - CNPJ com ou sem máscara
 * @returns Dados da empresa ou null se não encontrado
 */
export async function searchCompanyByCNPJ(cnpj: string): Promise<CompanyData | null> {
  // Remove máscara do CNPJ
  const cleanCNPJ = cnpj.replace(/\D/g, '');

  // Valida se tem 14 dígitos
  if (cleanCNPJ.length !== 14) {
    throw new Error('CNPJ deve ter 14 dígitos');
  }

  try {
    // Consulta via proxy no backend (evita bloqueio de CORS do navegador)
    const response = await apiClient.get<ReceitaWSResponse>(endpoints.cnpj.lookup(cleanCNPJ));
    const data = response.data;

    // Verifica se retornou erro ou não encontrou
    if (!data || data.status === 'ERROR' || data.message) {
      return null;
    }

    // Mapeia para o formato da aplicação
    return {
      // Dados básicos
      cnpj: data.cnpj || '',
      nome: data.nome || '',
      nomeFantasia: data.fantasia || '',
      
      // Endereço
      address: data.logradouro || '',
      number: data.numero || '',
      complement: data.complemento || '',
      neighborhood: data.bairro || '',
      city: data.municipio || '',
      state: data.uf || '',
      zipCode: data.cep || '',
      
      // Contato
      email: data.email || '',
      phone: data.telefone || '',
      
      // Status
      situacao: data.situacao || '',
      abertura: data.abertura || '',
      porte: data.porte || '',
      naturezaJuridica: data.natureza_juridica || '',
    };
  } catch (error: any) {
    // Backend retorna 404 quando o CNPJ não é encontrado — não é um erro real
    if (error?.status === 404) {
      return null;
    }
    throw error;
  }
}

/**
 * Verifica se o CNPJ é válido (apenas formato)
 */
export function isValidCNPJFormat(cnpj: string): boolean {
  const cleanCNPJ = cnpj.replace(/\D/g, '');
  return cleanCNPJ.length === 14;
}

/**
 * Formata telefone da ReceitaWS (vem como "1234-5678" ou "(11) 1234-5678")
 */
export function formatReceitaWSPhone(phone: string): string {
  if (!phone) return '';
  
  // Remove tudo que não é número
  const numbers = phone.replace(/\D/g, '');
  
  // Se já tem DDD (10 ou 11 dígitos), retorna como está
  if (numbers.length >= 10) {
    return phone;
  }
  
  // Se não tem DDD, retorna apenas o número
  return phone;
}

/**
 * Formata CEP da ReceitaWS (vem como "12345-678" ou "12.345-678")
 */
export function formatReceitaWSCEP(cep: string): string {
  if (!cep) return '';
  
  // Remove tudo que não é número
  const numbers = cep.replace(/\D/g, '');
  
  // Aplica máscara padrão 00000-000
  return numbers.replace(/(\d{5})(\d)/, '$1-$2');
}

