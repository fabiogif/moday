@extends('adminlte::page')

@section('title', 'Permissão')

@section('content_header')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Painel de Controle</a></li>
        <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}" class="active">Permissão</a></li>
    </ol>

    <h1>Permissão <a href="{{ route('permissions.create') }}" class="btn btn-dark">Adicionar</a></h1>

@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form  action="{{ route('permissions.search') }}" method="POST" class="form form-inline">
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
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                @foreach($permissions as $permission)
                    <tr>
                        <td>{{$permission->name}}</td>
                        <td>{{$permission->price}}</td>
                      <td>
                          <a href="{{ route('permissions.show', $permission->id) }}" class="btn btn-warning">Detalhes</a>
                          <a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-dark">Alterar</a>
                          <a href="{{ route('permissions.profiles', $permission->id) }}" class="btn btn-dark"><i class="fas fa-address-book"></i></a>
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

