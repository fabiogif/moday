import { NextRequest, NextResponse } from 'next/server'

export async function GET(request: NextRequest) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '')
    
    if (!token) {
      return NextResponse.json({ message: 'Token não fornecido' }, { status: 401 })
    }

    const backendUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost'
    const response = await fetch(`${backendUrl}/api/table`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      return NextResponse.json(
        { message: 'Erro ao buscar mesas' },
        { status: response.status }
      )
    }

    const data = await response.json()
    return NextResponse.json(data)
  } catch (error) {
    return NextResponse.json(
      { message: 'Erro interno do servidor' },
      { status: 500 }
    )
  }
}
