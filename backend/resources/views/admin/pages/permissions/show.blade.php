@extends('adminlte::page')

@section('title', "Permissão Detalhes:  {$permission->name }")

@section('content_header')
    <h1>Detalhes: <b>{{ $permission->name }}</b></h1>
@stop


@section('content')
    <div class="card">
        <div class="card-body">
            <ul>
                <li><b>Nome:</b> {{ $permission->name }}</li>
                <li><b>Descrição:</b>{{ $permission->description }}</li>
            </ul>
            <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST" class="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
                <a href="{{ route('permissions.index') }}" class="btn btn-default">Voltar</a>
            </form>
        </div>
    </div>
@endsection

