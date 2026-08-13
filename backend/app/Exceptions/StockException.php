<?php

namespace App\Exceptions;

use Exception;

class StockException extends Exception
{
    public static function insufficientStock(string $productName, float $requested, float $available): self
    {
        return new self(
            "Estoque insuficiente para '{$productName}': solicitado {$requested}, disponível {$available}."
        );
    }

    public static function batchBlocked(string $batchNumber, string $reason): self
    {
        return new self("Lote '{$batchNumber}' bloqueado: {$reason}.");
    }

    public static function invalidMovement(string $message): self
    {
        return new self($message);
    }
}
