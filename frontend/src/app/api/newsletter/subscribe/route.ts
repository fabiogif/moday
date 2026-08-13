import { NextRequest, NextResponse } from 'next/server'
import { buildApiUrl } from '@/lib/api-config'

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { email } = body

    if (!email) {
      return NextResponse.json(
        { message: 'Informe um e-mail válido.' },
        { status: 400 }
      )
    }

    const response = await fetch(buildApiUrl('/api/newsletter/subscribe', { server: true }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ email }),
    })

    const data = await response.json()

    if (!response.ok) {
      return NextResponse.json(
        { message: data.message || 'Erro ao realizar inscrição' },
        { status: response.status }
      )
    }

    return NextResponse.json(
      { message: data.message || 'Inscrição realizada com sucesso!' },
      { status: 200 }
    )
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : 'Erro ao realizar inscrição'
    return NextResponse.json({ message }, { status: 500 })
  }
}
