const TOKEN_KEY = "auth-token"
const USER_KEY = "auth-user"
const TRIAL_KEY = "trial-status"
const REMEMBER_KEY = "auth-remember"
const EMAIL_KEY = "auth-email"

const COOKIE_MAX_AGE = 2 * 60 * 60

function canUseDom() {
  return typeof window !== "undefined"
}

function storeFor(remember: boolean): Storage {
  return remember ? localStorage : sessionStorage
}

export function isRememberSession(): boolean {
  if (!canUseDom()) return true
  return localStorage.getItem(REMEMBER_KEY) !== "0"
}

export function getAuthToken(): string | null {
  if (!canUseDom()) return null
  const stored = localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY)
  if (stored) return stored

  const authCookie = document.cookie
    .split(";")
    .find((cookie) => cookie.trim().startsWith(`${TOKEN_KEY}=`))
  return authCookie?.split("=")[1]?.trim() || null
}

export function getAuthUserRaw(): string | null {
  if (!canUseDom()) return null
  return localStorage.getItem(USER_KEY) || sessionStorage.getItem(USER_KEY)
}

export function getTrialStatusRaw(): string | null {
  if (!canUseDom()) return null
  return localStorage.getItem(TRIAL_KEY) || sessionStorage.getItem(TRIAL_KEY)
}

function setAuthCookie(token: string, remember: boolean) {
  const maxAge = remember ? `; max-age=${COOKIE_MAX_AGE}` : ""
  document.cookie = `auth-token=${token}; path=/${maxAge}; SameSite=Lax`
}

function clearAuthCookie() {
  document.cookie = "auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT"
}

export function persistAuthSession({
  token,
  userJson,
  trialJson,
  remember,
}: {
  token: string
  userJson?: string
  trialJson?: string | null
  remember: boolean
}) {
  if (!canUseDom()) return

  const keep = storeFor(remember)
  const drop = remember ? sessionStorage : localStorage

  drop.removeItem(TOKEN_KEY)
  drop.removeItem(USER_KEY)
  drop.removeItem(TRIAL_KEY)

  keep.setItem(TOKEN_KEY, token)
  if (userJson) keep.setItem(USER_KEY, userJson)
  if (trialJson) keep.setItem(TRIAL_KEY, trialJson)

  localStorage.setItem(REMEMBER_KEY, remember ? "1" : "0")
  setAuthCookie(token, remember)
}

export function persistAuthToken(token: string) {
  persistAuthSession({ token, remember: isRememberSession() })
}

export function persistAuthUser(userJson: string) {
  if (!canUseDom()) return
  storeFor(isRememberSession()).setItem(USER_KEY, userJson)
}

export function persistTrialStatusRaw(trialJson: string) {
  if (!canUseDom()) return
  if (!trialJson) {
    localStorage.removeItem(TRIAL_KEY)
    sessionStorage.removeItem(TRIAL_KEY)
    return
  }
  storeFor(isRememberSession()).setItem(TRIAL_KEY, trialJson)
}

export function clearAuthSession() {
  if (!canUseDom()) return

  for (const store of [localStorage, sessionStorage]) {
    store.removeItem(TOKEN_KEY)
    store.removeItem(USER_KEY)
    store.removeItem(TRIAL_KEY)
  }

  clearAuthCookie()
}

export function getRememberedEmail(): string {
  if (!canUseDom()) return ""
  return localStorage.getItem(EMAIL_KEY) || ""
}

export function setRememberedEmail(email: string | null) {
  if (!canUseDom()) return
  if (!email) {
    localStorage.removeItem(EMAIL_KEY)
    return
  }
  localStorage.setItem(EMAIL_KEY, email)
}
