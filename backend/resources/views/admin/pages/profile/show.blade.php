
@extends('adminlte::page')

@section('title', "Perfil Detalhes:  {$profile->name }")

@section('content_header')
    <h1>Detalhes: <b>{{ $profile->name }}</b></h1>
@stop


@section('content')
    <div class="card">
        <div class="card-body">
            <ul>
                <li><b>Nome:</b> {{ $profile->name }}</li>
                <li><b>Descrição:</b>{{ $profile->description }}</li>
            </ul>
            <form action="{{ route('profiles.destroy', $profile->id) }}" method="POST" class="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
                <a href="{{ route('profiles.index') }}" class="btn btn-default">Voltar</a>
            </form>
        </div>
    </div>
@endsection
