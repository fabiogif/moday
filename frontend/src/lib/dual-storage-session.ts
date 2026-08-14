function canUseDom() {
  return typeof window !== "undefined"
}

function storeFor(remember: boolean): Storage {
  return remember ? localStorage : sessionStorage
}

interface DualStorageSessionKeys {
  tokenKey: string
  userKey: string
  rememberKey: string
}

/**
 * Fábrica do padrão de sessão "lembrar-me": token/usuário em localStorage
 * quando remember=true, em sessionStorage (limpo ao fechar o navegador)
 * quando remember=false. Usado por auth-storage.ts (tenant) e
 * admin-auth-storage.ts (admin) — não duplicar esta lógica em outro lugar.
 */
export function createDualStorageSession({ tokenKey, userKey, rememberKey }: DualStorageSessionKeys) {
  function isRememberSession(): boolean {
    if (!canUseDom()) return true
    return localStorage.getItem(rememberKey) !== "0"
  }

  function getToken(): string | null {
    if (!canUseDom()) return null
    return localStorage.getItem(tokenKey) || sessionStorage.getItem(tokenKey)
  }

  function getUserRaw(): string | null {
    if (!canUseDom()) return null
    return localStorage.getItem(userKey) || sessionStorage.getItem(userKey)
  }

  function persist({
    token,
    userJson,
    remember,
  }: {
    token: string
    userJson?: string
    remember: boolean
  }) {
    if (!canUseDom()) return

    const keep = storeFor(remember)
    const drop = remember ? sessionStorage : localStorage

    drop.removeItem(tokenKey)
    drop.removeItem(userKey)

    keep.setItem(tokenKey, token)
    if (userJson) keep.setItem(userKey, userJson)

    localStorage.setItem(rememberKey, remember ? "1" : "0")
  }

  function persistToken(token: string) {
    if (!canUseDom()) return
    storeFor(isRememberSession()).setItem(tokenKey, token)
  }

  function persistUser(userJson: string) {
    if (!canUseDom()) return
    storeFor(isRememberSession()).setItem(userKey, userJson)
  }

  function clear() {
    if (!canUseDom()) return
    localStorage.removeItem(tokenKey)
    localStorage.removeItem(userKey)
    sessionStorage.removeItem(tokenKey)
    sessionStorage.removeItem(userKey)
  }

  return { isRememberSession, getToken, getUserRaw, persist, persistToken, persistUser, clear }
}
