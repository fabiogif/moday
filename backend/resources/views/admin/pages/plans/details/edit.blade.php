@extends('adminlte::page')

@section('title', 'Alterar detalhe do plano')

@section('content_header')
    <h1>Alterar Detalhe</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('details.plans.update', [$plan->id, $detail->id]) }}" class="form" method="POST">
                @csrf
                @method('PUT')
                @include('admin.pages.plans.details._partials.form')
            </form>

        </div>
    </div>
@endsection
