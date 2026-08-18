import { RegisterForm } from "./components/register-form"

export default async function RegisterPage({
  searchParams,
}: {
  searchParams: Promise<{ plan?: string }>
}) {
  const params = await searchParams

  return (
    <div className="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
      <div className="flex w-full max-w-xl flex-col gap-6">
        <RegisterForm preSelectedPlanId={params.plan} />
      </div>
    </div>
  )
}
