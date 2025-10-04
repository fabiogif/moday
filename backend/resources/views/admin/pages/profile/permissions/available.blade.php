@extends('adminlte::page')

@section('title', 'Permissões do perfil - { $profile->name}')

@section('content_header')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Painel de Controle</a></li>
        <li class="breadcrumb-item"><a href="{{ route('profiles.index') }}" class="active">Permissões do perfil  </a></li>
    </ol>

    <h1>Permissões do perfil | <b>{{  $profile->name }} </b></h1>

@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form  action="{{ route('profiles.permissions.available', $profile->id) }}" method="POST" class="form form-inline">
                @csrf
                <div class="form-group">
                    <input type="text"  name="filter" placeholder="Nome" class="form-control" value="{{ $filters['filter'] ?? '' }}">
                    <button type="submit" class="btn btn-dark m-lg-2">Buscar</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            @include('admin.includes.alert')

            <table class="table table-condensed">
                <thead>
                <tr>
                    <th width="50px">#</th>
                    <th>Nome</th>
                </tr>
                </thead>
                <tbody>
                @foreach($permissions as $permission)
                    <form method="POST" action="{{ route('profiles.permissions.attach', $profile->id) }}" class="form-inline">
                        @csrf
                        <tr>
                            <td>
                                <input type="checkbox" name="permission[]" value="{{ $permission->id }}"/>
                            </td>
                            <td>{{$permission->name}}</td>
                        </tr>

                @endforeach
                <tr>
                    <td colspan="500">
                        <button type="submit" class="btn btn-success">Vincular</button>
                    </td>
                </tr>
                    </form>
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

