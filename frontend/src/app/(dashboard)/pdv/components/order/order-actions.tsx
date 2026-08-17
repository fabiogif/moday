"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import {
  MessageSquare,
  FileText,
  Split,
  ArrowRightLeft,
  Users,
  Gift,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { CustomerNoteDialog } from "../dialogs/customer-note-dialog"
import { InternalNoteDialog } from "../dialogs/internal-note-dialog"
import { RefundDialog } from "../dialogs/refund-dialog"
import { SplitBillDialog } from "../dialogs/split-bill-dialog"
import { TransferOrderDialog } from "../dialogs/transfer-order-dialog"
import { GuestsDialog } from "../dialogs/guests-dialog"

interface Table {
  uuid?: string
  identify?: string
  name: string
  isOccupied?: boolean
}

interface OrderActionsProps {
  orderId?: string | number
  orderStatus?: string | null
  isFinalStatus: boolean
  onCustomerNote?: (note: string) => void
  onInternalNote?: (note: string) => void
  onRefund?: (amount: number, reason: string) => void
  onSplit?: (items: any[], amounts: number[]) => void
  onTransfer?: (targetTable: string) => void
  onGuests?: (count: number) => void
  onQuotation?: () => void
  onReward?: (rewardId: string) => void
  tables?: Table[]
  currentTable?: string | null
  className?: string
}

export function OrderActions({
  orderId,
  orderStatus,
  isFinalStatus,
  onCustomerNote,
  onInternalNote,
  onRefund,
  onSplit,
  onTransfer,
  onGuests,
  onReward,
  tables = [],
  currentTable = null,
  className,
}: OrderActionsProps) {
  const [showCustomerNote, setShowCustomerNote] = useState(false)
  const [showInternalNote, setShowInternalNote] = useState(false)
  const [showRefund, setShowRefund] = useState(false)
  const [showSplit, setShowSplit] = useState(false)
  const [showTransfer, setShowTransfer] = useState(false)
  const [showGuests, setShowGuests] = useState(false)

  const disabled = isFinalStatus && orderStatus !== "Cancelado"

  const actions = [
    {
      key: "customer",
      icon: MessageSquare,
      label: "Obs. cliente",
      onClick: () => setShowCustomerNote(true),
      show: true,
    },
    {
      key: "internal",
      icon: FileText,
      label: "Obs. interna",
      onClick: () => setShowInternalNote(true),
      show: true,
    },
    {
      key: "split",
      icon: Split,
      label: "Dividir",
      onClick: () => setShowSplit(true),
      show: true,
    },
    {
      key: "transfer",
      icon: ArrowRightLeft,
      label: "Transferir",
      onClick: () => setShowTransfer(true),
      show: true,
    },
    {
      key: "guests",
      icon: Users,
      label: "Clientes",
      onClick: () => setShowGuests(true),
      show: true,
    },
    {
      key: "reward",
      icon: Gift,
      label: "Recompensa",
      onClick: () => {},
      show: !!onReward,
    },
  ].filter((a) => a.show)

  return (
    <div className={cn("space-y-2", className)}>
      <div className="grid grid-cols-3 gap-1.5">
        {actions.map(({ key, icon: Icon, label, onClick }) => (
          <Button
            key={key}
            variant="outline"
            size="sm"
            onClick={onClick}
            disabled={disabled}
            className="h-10 flex-col gap-0.5 px-1 text-[10px] font-medium"
            title={label}
          >
            <Icon className="h-3.5 w-3.5 shrink-0" />
            <span className="leading-none">{label}</span>
          </Button>
        ))}
      </div>

      <CustomerNoteDialog
        open={showCustomerNote}
        onOpenChange={setShowCustomerNote}
        onSave={(note) => {
          onCustomerNote?.(note)
          setShowCustomerNote(false)
        }}
      />

      <InternalNoteDialog
        open={showInternalNote}
        onOpenChange={setShowInternalNote}
        onSave={(note) => {
          onInternalNote?.(note)
          setShowInternalNote(false)
        }}
      />

      <RefundDialog
        open={showRefund}
        onOpenChange={setShowRefund}
        orderTotal={0}
        onConfirm={(amount, reason) => {
          onRefund?.(amount, reason)
          setShowRefund(false)
        }}
      />

      <SplitBillDialog
        open={showSplit}
        onOpenChange={setShowSplit}
        items={[]}
        onConfirm={(items, amounts) => {
          onSplit?.(items, amounts)
          setShowSplit(false)
        }}
      />

      <TransferOrderDialog
        open={showTransfer}
        onOpenChange={setShowTransfer}
        currentTable={currentTable}
        tables={tables}
        onConfirm={(targetTable) => {
          onTransfer?.(targetTable)
          setShowTransfer(false)
        }}
      />

      <GuestsDialog
        open={showGuests}
        onOpenChange={setShowGuests}
        currentGuests={1}
        onConfirm={(count) => {
          onGuests?.(count)
          setShowGuests(false)
        }}
      />
    </div>
  )
}
