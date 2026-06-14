@extends('adminlte::page')

@section('title', "Planos Detalhes:  {$plan->name }")

@section('content_header')
    <h1>Detalhes: <b>{{ $plan->name }}</b></h1>
@stop


@section('content')
    <div class="card">
        <div class="card-body">
            <ul>
                <li><b>Nome:</b> {{ $plan->name }}</li>
                <li><b>Url:</b> {{ $plan->url }}</li>
                <li><b>Preço</b> R$ {{ number_format($plan->price, 2, ',', '.' ) }}</li>
                <li><b>Descrição:</b>{{ $plan->description }}</li>
            </ul>
            <form action="{{ route('plans.destroy', $plan->url) }}" method="POST" class="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
                <a href="{{ route('plans.index') }}" class="btn btn-default">Voltar</a>
            </form>
        </div>
    </div>
@endsection

