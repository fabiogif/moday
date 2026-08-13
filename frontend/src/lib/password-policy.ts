import { z } from 'zod'

export const PASSWORD_MIN_LENGTH = 8

export const passwordSchema = z
  .string()
  .min(PASSWORD_MIN_LENGTH, `Senha deve ter pelo menos ${PASSWORD_MIN_LENGTH} caracteres`)

export const passwordConfirmationSchema = z.object({
  password: passwordSchema,
  password_confirmation: z
    .string()
    .min(PASSWORD_MIN_LENGTH, 'Confirme sua senha'),
})
