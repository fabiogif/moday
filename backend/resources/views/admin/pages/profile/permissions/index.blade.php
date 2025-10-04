@extends('adminlte::page')

@section('title', 'Permissões do perfil - { $profiles->name}')

@section('content_header')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Painel de Controle</a></li>
        <li class="breadcrumb-item"><a href="{{ route('profiles.index') }}" class="active">Permissões do perfil  </a></li>
    </ol>

    <h1>Permissões do perfil | {{  $profile->name }} <a href="{{ route('profiles.permissions.available',  $profile->id) }}" class="btn btn-dark">Adicionar</a></h1>

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
                @foreach($permissions as $permission)
                    <tr>
                        <td>{{$permission->name}}</td>
                        <td>
                            <a href="{{ route('profiles.permissions.detach', [$profile->id, $permission->id]) }}" class="btn btn-danger">Desvicular</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            @if(isset($filters))
                {!! $permissions->appends($filters)->links() !!}
            @else
                {!! $permissions->links() !!}
            @endif
        </div>
    </div>
@stop

