@extends('adminlte::page')

@section('title', 'Perfis da permissão - { $profiles->name}')

@section('content_header')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Painel de Controle</a></li>
        <li class="breadcrumb-item"><a href="{{ route('profiles.index') }}" class="active">Perfis da permissão  </a></li>
    </ol>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form  action="{{ route('profiles.search') }}" method="POST" class="form form-inline">
                @csrf
                <div class="form-group">
                    <input type="text"  name="filter" placeholder="Nome" class="form-control" value="{{ $filters['filter'] ?? '' }}">
                    <button type="submit" class="btn btn-dark m-lg-2">Buscar</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <table class="table table-condensed">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach($profiles as $profile)
                    <tr>
                        <td>{{$profile->name}}</td>
                        <td>
                            <a href="{{ route('profiles.permissions.detach', [$profile->id, $permissions->id]) }}" class="btn btn-danger">Desvicular</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            @if(isset($filters))
                {!! $profiles->appends($filters)->links() !!}
            @else
                {!! $profiles->links() !!}
            @endif
        </div>
    </div>
@stop

