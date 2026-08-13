import { ForgotPasswordForm } from "./components/forgot-password-form"

export default function ForgotPasswordPage() {
  return (
    <div className="bg-gradient-to-b from-muted/80 via-background to-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
      <div className="w-full max-w-md lg:max-w-5xl">
        <ForgotPasswordForm />
      </div>
    </div>
  )
}
