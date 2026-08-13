@if($errors->any())
    @foreach($errors->all() as $erros)
        <div class="alert alert-danger">
            <p>{{ $erros }}</p>
        </div>
    @endforeach
@endif
