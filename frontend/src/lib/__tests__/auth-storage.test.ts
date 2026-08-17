import {
  clearAuthSession,
  getAuthToken,
  getRememberedEmail,
  persistAuthSession,
  setRememberedEmail,
} from "../auth-storage"

describe("auth-storage", () => {
  beforeEach(() => {
    localStorage.clear()
    sessionStorage.clear()
    document.cookie = "auth-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT"
  })

  it("persiste a sessão no localStorage quando remember é true", () => {
    persistAuthSession({
      token: "token-local",
      userJson: JSON.stringify({ id: "1" }),
      remember: true,
    })

    expect(localStorage.getItem("auth-token")).toBe("token-local")
    expect(sessionStorage.getItem("auth-token")).toBeNull()
    expect(getAuthToken()).toBe("token-local")
  })

  it("persiste a sessão no sessionStorage quando remember é false", () => {
    persistAuthSession({
      token: "token-session",
      userJson: JSON.stringify({ id: "1" }),
      remember: false,
    })

    expect(sessionStorage.getItem("auth-token")).toBe("token-session")
    expect(localStorage.getItem("auth-token")).toBeNull()
    expect(getAuthToken()).toBe("token-session")
  })

  it("remove token e usuário de ambos os storages ao limpar a sessão", () => {
    persistAuthSession({
      token: "token-local",
      userJson: JSON.stringify({ id: "1" }),
      remember: true,
    })

    clearAuthSession()

    expect(getAuthToken()).toBeNull()
    expect(localStorage.getItem("auth-user")).toBeNull()
    expect(sessionStorage.getItem("auth-user")).toBeNull()
  })

  it("guarda e recupera o e-mail lembrado", () => {
    setRememberedEmail("user@example.com")
    expect(getRememberedEmail()).toBe("user@example.com")

    setRememberedEmail(null)
    expect(getRememberedEmail()).toBe("")
  })
})
