"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ArrowLeft } from "lucide-react"
import { toast } from "sonner"
import { useMutation } from "@/hooks/use-authenticated-api"
import { endpoints } from "@/lib/api-client"
import { CouponForm, CouponFormValues } from "../components/coupon-form"

export default function NewCouponPage() {
  const router = useRouter()
  const { mutate: mutateCoupon, loading: saving } = useMutation<any, FormData>()
  const [redirecting, setRedirecting] = useState(false)

  const handleSubmit = async ({
    values,
    imageFile,
    removeImage: _removeImage,
  }: {
    values: CouponFormValues
    imageFile: File | null
    removeImage: boolean
  }) => {
    const formData = new FormData()

    Object.entries(values).forEach(([key, value]) => {
      if (value === null || value === undefined) return
      if (typeof value === "string" && value.trim() === "") return

      if (typeof value === "boolean") {
        formData.append(key, value ? "1" : "0")
        return
      }

      formData.append(key, String(value))
    })

    if (imageFile) {
      formData.append("image", imageFile)
    }

    try {
      await mutateCoupon(endpoints.marketing.coupons.create, "POST", formData)

      toast.success("Cupom criado com sucesso!")
      setRedirecting(true)
      router.push("/marketing/coupons")
    } catch (error: any) {
      toast.error(error?.message || "Não foi possível criar o cupom")
    }
  }

  return (
    <div className="flex flex-col gap-6 py-2 px-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.back()} disabled={saving || redirecting}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-3xl font-bold">Novo cupom</h1>
          <p className="text-muted-foreground">Cadastre uma nova campanha promocional</p>
        </div>
      </div>

      <Card className="border border-border/60 shadow-sm">
        <CardHeader>
          <CardTitle>Detalhes do cupom</CardTitle>
          <CardDescription>Defina valores, limites e período de disponibilidade.</CardDescription>
        </CardHeader>
        <CardContent>
          <CouponForm
            mode="create"
            busy={saving || redirecting}
            onCancel={() => router.push("/marketing/coupons")}
            onSubmit={async ({ values, imageFile, removeImage }) => handleSubmit({ values, imageFile, removeImage })}
          />
        </CardContent>
      </Card>
    </div>
  )
}
