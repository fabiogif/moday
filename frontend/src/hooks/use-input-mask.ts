import { ChangeEvent, useCallback } from 'react';
import { 
  maskCPF, 
  maskCNPJ, 
  maskPhone, 
  maskZipCode 
} from '@/lib/masks';

export type MaskType = 'cpf' | 'cnpj' | 'phone' | 'zipCode';

interface UseInputMaskReturn {
  handleChange: (e: ChangeEvent<HTMLInputElement>) => void;
  value: string;
}

/**
 * Hook para aplicar máscaras em inputs
 * 
 * @example
 * ```tsx
 * const { handleChange, value } = useInputMask('cpf', field.onChange);
 * 
 * <Input
 *   value={value}
 *   onChange={handleChange}
 * />
 * ```
 */
export function useInputMask(
  maskType: MaskType,
  onChange: (value: string) => void
): (e: ChangeEvent<HTMLInputElement>) => void {
  
  const getMaskFunction = useCallback((type: MaskType) => {
    switch (type) {
      case 'cpf':
        return maskCPF;
      case 'cnpj':
        return maskCNPJ;
      case 'phone':
        return maskPhone;
      case 'zipCode':
        return maskZipCode;
      default:
        return (value: string) => value;
    }
  }, []);
  
  const handleChange = useCallback((e: ChangeEvent<HTMLInputElement>) => {
    const maskFn = getMaskFunction(maskType);
    const maskedValue = maskFn(e.target.value);
    onChange(maskedValue);
  }, [maskType, onChange, getMaskFunction]);
  
  return handleChange;
}

/**
 * Hook simplificado que retorna apenas a função de máscara
 */
export function useMask(maskType: MaskType) {
  const getMaskFunction = useCallback((type: MaskType) => {
    switch (type) {
      case 'cpf':
        return maskCPF;
      case 'cnpj':
        return maskCNPJ;
      case 'phone':
        return maskPhone;
      case 'zipCode':
        return maskZipCode;
      default:
        return (value: string) => value;
    }
  }, []);
  
  return getMaskFunction(maskType);
}

