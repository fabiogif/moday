@extends('adminlte::page')

@section('title', 'Planos Alterar')

@section('content_header')
    <h1>Alterar</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('plans.update', $plan->url) }}" class="form" method="POST">
                @csrf
                @method('PUT')
                @include('admin.pages.plans._partials.form')
            </form>

        </div>
    </div>
@endsection
