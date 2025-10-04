<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class SearchZipcode extends Component
{


    public string $zipcode = '';

    public string $cidade = '';


    public function updatedZipcode(string $value)
    {
        $response = Http::get("https://viacep.com.br/ws/{$value}/json/")->json();

    }


    public function mount():void
    {

    }
    public function render()
    {
        return view('livewire.search-zipcode');
    }
}
