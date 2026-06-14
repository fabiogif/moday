@extends('adminlte::page')

@section('title', 'Permissões do perfil - { $profile->name}')

@section('content_header')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Painel de Controle</a></li>
        <li class="breadcrumb-item"><a href="{{ route('profiles.index') }}">Perfis</a></li>
        <li class="breadcrumb-item active"><a href="{{ route('plans.profiles', $plan->id) }}"class="active">Planos</a></li>
    </ol>

    <h1>Perfis disponiveis para o plano | <b>{{  $plan->name }} </b></h1>

@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form  action="{{ route('plans.profiles.available', $plan->id) }}" method="POST" class="form form-inline">
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
                @foreach($profiles as $profile)
                    <form method="POST" action="{{ route('plans.profiles.attach', $profile->id) }}" class="form-inline">
                        @csrf
                        <tr>
                            <td>
                                <input type="checkbox" name="profiles[]" value="{{ $profile->id }}"/>
                            </td>
                            <td>{{$profile->name}}</td>
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
                {!! $profiles->appends($filters)->links() !!}
            @else
                {!! $profiles->links() !!}
            @endif
        </div>
    </div>
@stop

