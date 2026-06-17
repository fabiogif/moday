<?php

namespace App\Support;

class BrazilianDocuments
{
    public static function onlyDigits(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }

    public static function onlyAlphanumeric(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $value) ?? '');
    }

    private static function cnpjCharValue(string $char): int
    {
        return ord($char) - 48;
    }

    public static function isValidCpf(?string $value): bool
    {
        $cpf = self::onlyDigits($value);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(?string $value): bool
    {
        $cnpj = self::onlyAlphanumeric($value);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (!preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $cnpj)) {
            return false;
        }

        if (preg_match('/^(.)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += self::cnpjCharValue($cnpj[$i]) * $weights1[$i];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        if ((int) $cnpj[12] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += self::cnpjCharValue($cnpj[$i]) * $weights2[$i];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

        return (int) $cnpj[13] === $digit2;
    }
}
