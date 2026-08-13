@php
    $brand = config('mail.brand');
    $brandName = $brand['name'] ?? 'DistribTec';
    $tagline = $brand['tagline'] ?? 'Sistema de Gestão para Distribuidoras';
    $primary = $brand['primary'] ?? '#006A91';
    $primaryDark = $brand['primary_dark'] ?? '#005577';
    $secondary = $brand['secondary'] ?? '#EDF2F4';
    $muted = $brand['muted'] ?? '#F4F7FA';
    $mutedFg = $brand['muted_foreground'] ?? '#424F56';
    $fg = $brand['foreground'] ?? '#0B0B0B';
    $border = $brand['border'] ?? '#D6DDE4';
    $gold = $brand['gold'] ?? '#D9B674';
    $surface = $brand['surface'] ?? '#FFFFFF';
    $radius = $brand['radius'] ?? '8px';
    $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
    $logoUrl = $frontend . ($brand['logo_path'] ?? '/brand/iconfundotranparente.png');
    $preheader = $preheader ?? null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', $brandName)</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            background-color: {{ $muted }};
            color: {{ $fg }};
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.55;
        }
        img { border: 0; outline: none; text-decoration: none; max-width: 100%; }
        a { color: {{ $primary }}; }
        .email-wrapper { width: 100%; background-color: {{ $muted }}; padding: 24px 12px; }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: {{ $surface }};
            border: 1px solid {{ $border }};
            border-radius: {{ $radius }};
            overflow: hidden;
        }
        .email-header {
            background-color: {{ $primary }};
            padding: 28px 32px;
            text-align: center;
        }
        .email-header-title {
            margin: 12px 0 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .email-header-subtitle {
            margin: 6px 0 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            font-weight: 400;
        }
        .email-body { padding: 32px; color: {{ $fg }}; }
        .email-body p { margin: 0 0 16px; font-size: 15px; color: {{ $mutedFg }}; }
        .email-body h2 {
            margin: 0 0 12px;
            font-size: 18px;
            font-weight: 700;
            color: {{ $fg }};
        }
        .greeting { font-size: 16px; color: {{ $primary }}; margin-bottom: 16px; }
        .info-box {
            background-color: {{ $secondary }};
            border-left: 4px solid {{ $primary }};
            border-radius: {{ $radius }};
            padding: 16px 18px;
            margin: 20px 0;
        }
        .info-row {
            font-size: 14px;
            color: {{ $mutedFg }};
            margin: 0 0 8px;
        }
        .info-row:last-child { margin-bottom: 0; }
        .info-row strong { color: {{ $primary }}; min-width: 110px; display: inline-block; }
        .badge {
            display: inline-block;
            background-color: {{ $primary }};
            color: #ffffff;
            padding: 8px 16px;
            border-radius: {{ $radius }};
            font-size: 13px;
            font-weight: 700;
            margin: 8px 0;
        }
        .features {
            background-color: {{ $muted }};
            border: 1px solid {{ $border }};
            border-radius: {{ $radius }};
            padding: 16px 18px;
            margin: 20px 0;
        }
        .features h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: {{ $primary }};
        }
        .features ul { margin: 0; padding: 0; list-style: none; }
        .features li {
            position: relative;
            padding: 6px 0 6px 22px;
            font-size: 14px;
            color: {{ $mutedFg }};
        }
        .features li:before {
            content: "";
            position: absolute;
            left: 0;
            top: 12px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: {{ $gold }};
        }
        .cta-wrap { text-align: center; margin: 28px 0 8px; }
        .cta-button {
            display: inline-block;
            background-color: {{ $primary }};
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: {{ $radius }};
            font-size: 15px;
            font-weight: 700;
            border: 1px solid {{ $primaryDark }};
        }
        .note-box {
            margin-top: 24px;
            padding: 14px 16px;
            background-color: #FBF6EC;
            border-left: 4px solid {{ $gold }};
            border-radius: {{ $radius }};
            font-size: 13px;
            color: #6B5428;
        }
        .email-footer {
            background-color: {{ $muted }};
            border-top: 1px solid {{ $border }};
            padding: 24px 32px;
            text-align: center;
        }
        .email-footer p {
            margin: 4px 0;
            font-size: 13px;
            color: {{ $mutedFg }};
        }
        .email-footer .brand {
            font-weight: 700;
            color: {{ $primary }};
            font-size: 14px;
        }
        .email-footer .fineprint {
            font-size: 12px;
            color: #7A8790;
            margin-top: 12px;
        }
        @media only screen and (max-width: 600px) {
            .email-body, .email-header, .email-footer { padding-left: 20px !important; padding-right: 20px !important; }
            .email-header-title { font-size: 20px !important; }
        }
    </style>
</head>
<body>
@if($preheader)
    <div style="display:none;font-size:1px;color:{{ $muted }};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ $preheader }}
    </div>
@endif
<table role="presentation" class="email-wrapper" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center">
            <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td class="email-header">
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" width="48" height="48" style="display:block;margin:0 auto;border-radius:6px;background:#ffffff;padding:4px;">
                        <h1 class="email-header-title">@yield('heading', $brandName)</h1>
                        @hasSection('subheading')
                            <p class="email-header-subtitle">@yield('subheading')</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="email-body">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td class="email-footer">
                        <p class="brand">{{ $brandName }}</p>
                        <p>{{ $tagline }}</p>
                        <p class="fineprint">
                            @yield('footer_note', 'Este é um e-mail automático. Em caso de dúvidas, responda esta mensagem ou fale com o suporte.')
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
