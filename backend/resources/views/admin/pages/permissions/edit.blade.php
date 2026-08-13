@extends('adminlte::page')

@section('title', 'Planos Alterar')

@section('content_header')
    <h1>Alterar</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('permissions.update', $permission->id) }}" class="form" method="POST">
                @csrf
                @method('PUT')
                @include('admin.pages.permissions._partials.form')
            </form>

        </div>
    </div>
@endsection
