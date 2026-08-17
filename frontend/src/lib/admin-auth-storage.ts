import { createDualStorageSession } from "@/lib/dual-storage-session"

const session = createDualStorageSession({
  tokenKey: "admin-token",
  userKey: "admin-user",
  rememberKey: "admin-auth-remember",
})

export const isAdminRememberSession = session.isRememberSession
export const getAdminToken = session.getToken
export const getAdminUserJson = session.getUserRaw
export const updateAdminToken = session.persistToken
export const updateAdminUser = session.persistUser
export const clearAdminSession = session.clear

export function persistAdminSession({
  token,
  userJson,
  remember,
}: {
  token: string
  userJson: string
  remember: boolean
}) {
  session.persist({ token, userJson, remember })
}
