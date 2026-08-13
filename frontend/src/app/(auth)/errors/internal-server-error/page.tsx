import { InternalServerError } from "./components/internal-server-error"

export const dynamic = 'force-dynamic'

export default function InternalServerErrorPage() {
  return <InternalServerError />
}
