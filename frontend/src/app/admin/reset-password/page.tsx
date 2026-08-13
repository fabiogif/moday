import { AdminResetPasswordForm } from './components/admin-reset-password-form'

export default async function AdminResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>
}) {
  const params = await searchParams

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 p-4">
      <AdminResetPasswordForm
        token={params.token ?? ''}
        email={params.email ? decodeURIComponent(params.email) : ''}
      />
    </div>
  )
}
