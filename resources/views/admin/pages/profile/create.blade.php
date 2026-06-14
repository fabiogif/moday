@extends('adminlte::page')

@section('title', 'Planos Adicionar')

@section('content_header')
    <h1>Adicionar</h1>
@stop


@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('profiles.store') }}" class="form" method="POST">
                @csrf
                @include('admin.pages.profile._partials.form')
            </form>
        </div>
    </div>
@endsection
