import { ResetPasswordForm } from "./components/reset-password-form"

export default async function ResetPasswordPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; email?: string }>
}) {
  const params = await searchParams

  return (
    <div className="bg-gradient-to-b from-muted/80 via-background to-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
      <div className="w-full max-w-md lg:max-w-5xl">
        <ResetPasswordForm
          token={params.token ?? ""}
          email={params.email ? decodeURIComponent(params.email) : ""}
        />
      </div>
    </div>
  )
}
