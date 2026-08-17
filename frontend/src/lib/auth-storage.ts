import { createDualStorageSession } from "@/lib/dual-storage-session"

const TOKEN_KEY = "auth-token"
const USER_KEY = "auth-user"
const TRIAL_KEY = "trial-status"
const REMEMBER_KEY = "auth-remember"
const EMAIL_KEY = "auth-email"

const COOKIE_MAX_AGE = 2 * 60 * 60

function canUseDom() {
  return typeof window !== "undefined"
}

const session = createDualStorageSession({
  tokenKey: TOKEN_KEY,
  userKey: USER_KEY,
  rememberKey: REMEMBER_KEY,
})

export const isRememberSession = session.isRememberSession

export function getAuthToken(): string | null {
  const stored = session.getToken()
  if (stored) return stored
  if (!canUseDom()) return null

  const authCookie = document.cookie
    .split(";")
    .find((cookie) => cookie.trim().startsWith(`${TOKEN_KEY}=`))
  return authCookie?.split("=")[1]?.trim() || null
}

export const getAuthUserRaw = session.getUserRaw

export function getTrialStatusRaw(): string | null {
  if (!canUseDom()) return null
  return localStorage.getItem(TRIAL_KEY) || sessionStorage.getItem(TRIAL_KEY)
}

function setAuthCookie(token: string, remember: boolean) {
  const maxAge = remember ? `; max-age=${COOKIE_MAX_AGE}` : ""
  document.cookie = `${TOKEN_KEY}=${token}; path=/${maxAge}; SameSite=Lax`
}

function clearAuthCookie() {
  document.cookie = `${TOKEN_KEY}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`
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

  session.persist({ token, userJson, remember })

  const keep = remember ? localStorage : sessionStorage
  const drop = remember ? sessionStorage : localStorage
  drop.removeItem(TRIAL_KEY)
  if (trialJson) keep.setItem(TRIAL_KEY, trialJson)

  setAuthCookie(token, remember)
}

export function persistAuthToken(token: string) {
  if (!canUseDom()) return
  session.persistToken(token)
  setAuthCookie(token, isRememberSession())
}

export const persistAuthUser = session.persistUser

export function persistTrialStatusRaw(trialJson: string) {
  if (!canUseDom()) return
  if (!trialJson) {
    localStorage.removeItem(TRIAL_KEY)
    sessionStorage.removeItem(TRIAL_KEY)
    return
  }
  const keep = isRememberSession() ? localStorage : sessionStorage
  keep.setItem(TRIAL_KEY, trialJson)
}

export function clearAuthSession() {
  if (!canUseDom()) return

  session.clear()
  localStorage.removeItem(TRIAL_KEY)
  sessionStorage.removeItem(TRIAL_KEY)
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
