@extends('adminlte::page')

@section('title', 'Perfil Alterar')

@section('content_header')
    <h1>Alterar</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('profiles.update', $profile->id) }}" class="form" method="POST">
                @csrf
                @method('PUT')
                @include('admin.pages.profile._partials.form')
            </form>

        </div>
    </div>
@endsection
