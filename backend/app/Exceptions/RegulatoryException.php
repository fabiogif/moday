<?php

namespace App\Exceptions;

use Exception;

class RegulatoryException extends Exception
{
    public static function controlledRequiresPrescription(string $productName): self
    {
        return new self("Produto controlado '{$productName}' exige verificação de receita.");
    }

    public static function coldChainViolation(string $productName, string $required, string $warehouse): self
    {
        return new self(
            "Produto '{$productName}' exige armazenagem {$required}, mas o lote está em armazém {$warehouse}."
        );
    }
}
