import type { BoardColumnDef } from "./types"

export const BOARD_COLUMNS: BoardColumnDef[] = [
  { id: "Em Preparo", title: "Em Preparo", color: "bg-yellow-100 text-yellow-800" },
  { id: "Pronto", title: "Pronto", color: "bg-blue-100 text-blue-800" },
  { id: "Entregue", title: "Entregue", color: "bg-green-100 text-green-800" },
  { id: "Cancelado", title: "Cancelado", color: "bg-red-100 text-red-800" },
]
