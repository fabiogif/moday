import { ForbiddenError } from "./components/forbidden-error"

export const dynamic = 'force-dynamic'

export default function ForbiddenPage() {
  return <ForbiddenError />
}
