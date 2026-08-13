@extends('emails.layouts.brand', [
    'preheader' => 'Sua empresa foi cadastrada no DistribTec. Acesse sua conta para começar.',
])

@section('title', 'Bem-vindo ao DistribTec')
@section('heading', 'Bem-vindo ao DistribTec')
@section('subheading', 'Cadastro concluído com sucesso')

@section('content')
    <p class="greeting">Olá, <strong>{{ $user->name }}</strong>!</p>

    <h2>Sua empresa foi cadastrada com sucesso</h2>

    <p>
        Estamos felizes em ter <strong>{{ $tenant->name }}</strong> na plataforma.
        Você já pode gerenciar estoque, pedidos e operação da sua distribuidora.
    </p>

    <div class="info-box">
        <p class="info-row"><strong>Empresa:</strong> {{ $tenant->name }}</p>
        <p class="info-row"><strong>E-mail:</strong> {{ $user->email }}</p>
        <p class="info-row"><strong>Usuário:</strong> {{ $user->name }}</p>
        @if($tenant->slug)
            <p class="info-row"><strong>URL:</strong> {{ $tenant->slug }}.distribtec.com.br</p>
        @endif
    </div>

    <div style="text-align:center;">
        <span class="badge">Plano: {{ $plan->name }}</span>
    </div>

    @if($plan->details && $plan->details->count() > 0)
        <div class="features">
            <h3>Benefícios do seu plano</h3>
            <ul>
                @foreach($plan->details as $detail)
                    <li>{{ $detail->description ?? $detail->name }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="cta-wrap">
        <a href="{{ $loginUrl }}" class="cta-button">Acessar minha conta</a>
    </div>

    <p style="margin-top:28px;font-size:13px;">
        Dúvidas? Fale conosco em
        <a href="mailto:{{ config('mail.support_to') }}">{{ config('mail.support_to') }}</a>
        ou responda este e-mail.
    </p>
@endsection

@section('footer_note', 'Este é um e-mail automático de boas-vindas. Se você não se cadastrou, ignore esta mensagem.')
