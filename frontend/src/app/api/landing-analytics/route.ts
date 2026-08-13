import { NextResponse } from 'next/server'

export async function POST(request: Request) {
  try {
    await request.json()
    // Endpoint reservado para integração futura (GA4, PostHog, etc.)
    return NextResponse.json({ ok: true })
  } catch {
    return NextResponse.json({ ok: false }, { status: 400 })
  }
}
