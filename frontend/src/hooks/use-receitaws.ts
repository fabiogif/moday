import { useState, useCallback } from 'react';
import { searchCompanyByCNPJ, CompanyData } from '@/services/receitaws';
import { toast } from 'sonner';

interface UseReceitaWSReturn {
  loading: boolean;
  error: string | null;
  companyData: CompanyData | null;
  searchCNPJ: (cnpj: string) => Promise<CompanyData | null>;
  clearData: () => void;
}

/**
 * Hook para consultar CNPJ usando ReceitaWS
 * 
 * @example
 * ```tsx
 * const { loading, companyData, searchCNPJ } = useReceitaWS();
 * 
 * const handleCNPJBlur = async (cnpj: string) => {
 *   const company = await searchCNPJ(cnpj);
 *   if (company) {
 *     form.setValue('name', company.nome);
 *     form.setValue('address', company.address);
 *     // ...
 *   }
 * };
 * ```
 */
export function useReceitaWS(): UseReceitaWSReturn {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [companyData, setCompanyData] = useState<CompanyData | null>(null);
  
  const searchCNPJ = useCallback(async (cnpj: string): Promise<CompanyData | null> => {
    // Limpa dados anteriores
    setCompanyData(null);
    
    try {
      setLoading(true);
      setError(null);
      
      const company = await searchCompanyByCNPJ(cnpj);
      
      if (!company) {
        setError('CNPJ não encontrado');
        toast.error('CNPJ não encontrado. Verifique e tente novamente.');
        return null;
      }
      
      // Verificar se a empresa está ativa
      if (company.situacao && company.situacao.toLowerCase() !== 'ativa') {
        toast.warning(`Empresa ${company.situacao}. Verifique a situação cadastral.`);
      } else {
        toast.success('Dados da empresa encontrados!');
      }
      
      setCompanyData(company);
      return company;
    } catch (err: any) {
      const errorMessage = err.message || 'Erro ao buscar CNPJ';
      setError(errorMessage);
      
      // Mensagens de erro mais amigáveis
      if (errorMessage.includes('14 dígitos')) {
        toast.error('CNPJ incompleto. Digite os 14 dígitos.');
      } else if (errorMessage.includes('Failed to fetch')) {
        toast.error('Erro de conexão. Verifique sua internet.');
      } else {
        toast.error('Erro ao consultar CNPJ. Tente novamente.');
      }
      
      return null;
    } finally {
      setLoading(false);
    }
  }, []);
  
  const clearData = useCallback(() => {
    setCompanyData(null);
    setError(null);
  }, []);
  
  return {
    loading,
    error,
    companyData,
    searchCNPJ,
    clearData,
  };
}

/**
 * Hook simplificado que apenas consulta o CNPJ sem feedback visual
 */
export function useReceitaWSQuiet(): {
  searchCNPJ: (cnpj: string) => Promise<CompanyData | null>;
} {
  const searchCNPJ = useCallback(async (cnpj: string): Promise<CompanyData | null> => {
    try {
      return await searchCompanyByCNPJ(cnpj);
    } catch (err) {

      return null;
    }
  }, []);
  
  return { searchCNPJ };
}

