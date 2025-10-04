@extends('adminlte::page')

@section('title', 'Detalhes do plano')

@section('content_header')
    <h1>Adicionar ao Plano | {{ $plan->name }}</h1>
@stop


@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('details.plans.store', $plan->id) }}" class="form" method="POST">
                @csrf
                @include('admin.pages.plans.details._partials.form')
            </form>
        </div>
    </div>
@endsection
