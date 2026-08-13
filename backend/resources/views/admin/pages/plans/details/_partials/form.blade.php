@include('admin.includes.alert')
<div class="form-group">
        <label>Nome:</label>
        <input type="text" class="form-control" name="name" placeholder="Nome:" value="{{ $detail->name ?? old('name') }}">
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="{{ url()->previous() }}" class="btn btn-default">Voltar</a>
    </div>
