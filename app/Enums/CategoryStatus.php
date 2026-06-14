<?php

namespace App\Enums;

enum CategoryStatus: string
{
    case A = 'Ativo';
    case I = 'Inativo';

    public static function fromValue(string $value): string
    {
       foreach (self::cases() as $status)
       {
           if($status->name === $value)
           {
               return $status->value;
           }
       }
       throw  new \ValueError("$value não é válido");
    }
}
