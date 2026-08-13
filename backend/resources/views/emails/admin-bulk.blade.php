@extends('emails.layouts.brand', [
    'preheader' => 'Mensagem da administração DistribTec.',
])

@section('title', $subject ?? 'Mensagem — DistribTec')
@section('heading', 'DistribTec')
@section('subheading', 'Mensagem da administração')

@section('content')
    <p class="greeting">Olá, <strong>{{ $tenant->name }}</strong>!</p>
    <div style="font-size:15px;color:#424F56;line-height:1.7;white-space:pre-wrap;">{!! nl2br(e($message)) !!}</div>
@endsection

@section('footer_note', 'Este e-mail foi enviado pela administração da plataforma. Para responder, utilize o suporte no painel.')
