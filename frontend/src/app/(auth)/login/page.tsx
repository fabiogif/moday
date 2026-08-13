import { LoginForm3 } from "./components/login-form-3"

export default function LoginPage() {
  return (
    <div className="bg-gradient-to-b from-muted/80 via-background to-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
      <div className="w-full max-w-md lg:max-w-5xl">
        <LoginForm3 />
      </div>
    </div>
  )
}
