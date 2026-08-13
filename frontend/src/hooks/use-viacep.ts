import { useState, useCallback } from 'react';
import { searchAddressByCEP, AddressData } from '@/services/viacep';
import { toast } from 'sonner';

interface UseViaCEPReturn {
  loading: boolean;
  error: string | null;
  searchCEP: (cep: string) => Promise<AddressData | null>;
}

/**
 * Hook para buscar endereço por CEP usando ViaCEP
 * 
 * @example
 * ```tsx
 * const { loading, searchCEP } = useViaCEP();
 * 
 * const handleCEPBlur = async (cep: string) => {
 *   const address = await searchCEP(cep);
 *   if (address) {
 *     form.setValue('address', address.address);
 *     form.setValue('city', address.city);
 *     // ...
 *   }
 * };
 * ```
 */
export function useViaCEP(): UseViaCEPReturn {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const searchCEP = useCallback(async (cep: string): Promise<AddressData | null> => {
    try {
      setLoading(true);
      setError(null);
      
      const address = await searchAddressByCEP(cep);
      
      if (!address) {
        setError('CEP não encontrado');
        toast.error('CEP não encontrado. Verifique e tente novamente.');
        return null;
      }
      
      toast.success('Endereço encontrado!');
      return address;
    } catch (err: any) {
      const errorMessage = err.message || 'Erro ao buscar CEP';
      setError(errorMessage);
      toast.error(errorMessage);
      return null;
    } finally {
      setLoading(false);
    }
  }, []);
  
  return {
    loading,
    error,
    searchCEP,
  };
}

/**
 * Hook simplificado que apenas busca o CEP sem feedback visual
 */
export function useViaCEPQuiet(): {
  searchCEP: (cep: string) => Promise<AddressData | null>;
} {
  const searchCEP = useCallback(async (cep: string): Promise<AddressData | null> => {
    try {
      return await searchAddressByCEP(cep);
    } catch (err) {

      return null;
    }
  }, []);
  
  return { searchCEP };
}

