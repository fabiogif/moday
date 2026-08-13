export type AccountType = 'checking' | 'savings' | 'payment'
export type AccountHolderType = 'individual' | 'company'
export type PixKeyType = 'cpf' | 'cnpj' | 'email' | 'phone' | 'random'
export type VerificationMethod = 'manual' | 'api' | 'document'

export interface Bank {
  code: string
  name: string
  full_name: string | null
  supports_pix: boolean
}

export interface BankAccount {
  uuid: string
  account_type: AccountType
  bank_code: string
  bank_name: string
  agency: string
  agency_digit: string | null
  account_number_masked: string
  account_holder_name: string
  account_holder_document_masked: string
  account_holder_type: AccountHolderType
  pix_key_type: PixKeyType | null
  pix_key: string | null
  is_primary: boolean
  is_active: boolean
  is_verified: boolean
  verified_at: string | null
  created_at: string
}

export interface BankAccountDetail extends Omit<BankAccount, 'account_number_masked' | 'account_holder_document_masked'> {
  account_number: string
  account_digit: string
  account_holder_document: string
  notes: string | null
  updated_at: string
}

export interface BankAccountFormData {
  account_type: AccountType
  bank_code: string
  agency: string
  agency_digit?: string
  account_number: string
  account_digit: string
  account_holder_name: string
  account_holder_document: string
  account_holder_type: AccountHolderType
  pix_key_type?: PixKeyType
  pix_key?: string
  is_primary?: boolean
  notes?: string
}

export interface BankAccountLog {
  action: string
  user: string
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address: string | null
  created_at: string
}

