@extends('emails.layouts.brand', [
    'preheader' => $isMigration
        ? 'Seu plano foi atualizado no DistribTec.'
        : 'Seu plano foi confirmado no DistribTec.',
])

@section('title', $isMigration ? 'Plano atualizado — DistribTec' : 'Confirmação de plano — DistribTec')
@section('heading', $isMigration ? 'Plano atualizado' : 'Plano confirmado')
@section('subheading', $plan->name)

@section('content')
    <p class="greeting">Olá, <strong>{{ $tenant->name }}</strong>!</p>

    <h2>
        {{ $isMigration
            ? 'Sua migração de plano foi concluída'
            : 'Seu plano foi confirmado com sucesso' }}
    </h2>

    <p>
        @if($isMigration)
            Sua empresa migrou do plano <strong>{{ $oldPlan->name }}</strong>
            para o plano <strong>{{ $plan->name }}</strong>.
            As funcionalidades do novo plano já estão disponíveis.
        @else
            Sua empresa está no plano <strong>{{ $plan->name }}</strong>.
            As funcionalidades do plano já estão disponíveis.
        @endif
    </p>

    @if($isMigration && $oldPlan)
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
            <tr>
                <td width="46%" style="background:#FBF1F1;border:1px solid #F0C9C9;border-radius:8px;padding:14px;text-align:center;">
                    <div style="font-size:12px;color:#8A3A3A;font-weight:700;margin-bottom:6px;">Plano anterior</div>
                    <div style="font-size:16px;font-weight:700;color:#0B0B0B;">{{ $oldPlan->name }}</div>
                    @if($oldPlan->price > 0)
                        <div style="font-size:13px;color:#424F56;margin-top:4px;">
                            R$ {{ number_format($oldPlan->price, 2, ',', '.') }}/mês
                        </div>
                    @endif
                </td>
                <td width="8%" style="text-align:center;color:#006A91;font-size:18px;font-weight:700;">→</td>
                <td width="46%" style="background:#EDF2F4;border:1px solid #D6DDE4;border-radius:8px;padding:14px;text-align:center;">
                    <div style="font-size:12px;color:#006A91;font-weight:700;margin-bottom:6px;">Novo plano</div>
                    <div style="font-size:16px;font-weight:700;color:#0B0B0B;">{{ $plan->name }}</div>
                    @if($plan->price > 0)
                        <div style="font-size:13px;color:#424F56;margin-top:4px;">
                            R$ {{ number_format($plan->price, 2, ',', '.') }}/mês
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <div class="info-box">
        <p class="info-row"><strong>Empresa:</strong> {{ $tenant->name }}</p>
        <p class="info-row"><strong>Plano atual:</strong> {{ $plan->name }}</p>
        @if($plan->price > 0)
            <p class="info-row">
                <strong>Valor:</strong>
                R$ {{ number_format($plan->price, 2, ',', '.') }}/mês
            </p>
        @endif
        @if($migration && $migration->migrated_at)
            <p class="info-row">
                <strong>Data:</strong>
                {{ $migration->migrated_at->format('d/m/Y H:i') }}
            </p>
        @endif
    </div>

    <div style="text-align:center;">
        <span class="badge">{{ $plan->name }}</span>
    </div>

    @if($plan->details && $plan->details->count() > 0)
        <div class="features">
            <h3>Benefícios do plano</h3>
            <ul>
                @foreach($plan->details as $detail)
                    <li>{{ $detail->description ?? $detail->name }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="cta-wrap">
        <a href="{{ $dashboardUrl }}" class="cta-button">Acessar o painel</a>
    </div>

    @if($migration && $migration->notes)
        <div class="note-box">
            <strong>Observação:</strong> {{ $migration->notes }}
        </div>
    @endif

    <p style="margin-top:28px;font-size:13px;">
        Dúvidas sobre o plano? Entre em contato pelo painel ou responda este e-mail.
    </p>
@endsection

@section('footer_note', 'Este é um e-mail automático de confirmação. Se você não reconhece esta ação, contate o suporte.')
