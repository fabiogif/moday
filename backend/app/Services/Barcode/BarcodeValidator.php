<?php

namespace App\Services\Barcode;

use InvalidArgumentException;

class BarcodeValidator
{
    /** EAN-8, UPC-A (12), EAN-13 */
    private const ALLOWED_LENGTHS = [8, 12, 13];

    /**
     * @throws InvalidArgumentException
     */
    public function assertValid(?string $code): string
    {
        if ($code === null || trim($code) === '') {
            throw new InvalidArgumentException('Informe o código de barras.');
        }

        $normalized = $this->normalize($code);

        if ($normalized === '') {
            throw new InvalidArgumentException('Informe o código de barras.');
        }

        if (!ctype_digit($normalized)) {
            throw new InvalidArgumentException('O código de barras deve conter apenas números.');
        }

        if (!in_array(strlen($normalized), self::ALLOWED_LENGTHS, true)) {
            throw new InvalidArgumentException(
                'Código de barras inválido. Use EAN-8 (8 dígitos), UPC-A (12) ou EAN-13 (13).'
            );
        }

        return $normalized;
    }

    public function isValid(?string $code): bool
    {
        try {
            $this->assertValid($code);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function normalize(string $code): string
    {
        $normalized = trim($code);
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? '';

        return $normalized;
    }
}
