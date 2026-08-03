@extends('emails.layouts.brand', [
    'preheader' => 'Novidades e atualizações do DistribTec.',
])

@section('title', 'DistribTec')
@section('heading', 'DistribTec')
@section('subheading', 'Informativo')

@section('content')
    <div style="font-size:15px;color:#424F56;line-height:1.7;white-space:pre-wrap;">{!! nl2br(e($message)) !!}</div>
@endsection

@section('footer_note', 'Você recebeu este e-mail porque se inscreveu no informativo DistribTec.')
