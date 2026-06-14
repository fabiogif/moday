<div>

    <form action="" class="p-8 bg-gray-200 flex-col w-1/2 mx-auto gap-4">
        <h1>Buscador de CEP</h1>
        <div>
            <label for="zipcode">CEP</label>
            <input  class="border" id="zipcode" type="text" wire:model.lazy="zipcode"/>
        </div>

        <div>
            <label for="city">Rua</label>
            <input  class="border" id="city" type="text" wire:model="logradouro"/>
        </div>

        <div>
            <label for="state">Bairro</label>
            <input  class="border" id="state" type="text" wire:model="bairro"/>
        </div>
        <div>
            <label for="state">Cidade</label>
            <input  class="border" id="state" type="text" wire:model="cidade"/>
        </div>
        <div>
            <label for="state">Estado</label>
            <input  class="border" id="state" type="text" wire:model="uf"/>
        </div>
        <div>
            <button class="px-4 py-2 bg-green-400 hover:bg-green-300 text-white rounded-md" type="button" wire:click="search">Buscar</button>
        </div>
    </form>
</div>
