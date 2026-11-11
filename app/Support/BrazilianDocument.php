<?php

namespace App\Support;

class BrazilianDocument
{
    /**
     * Return only the digits of a document.
     */
    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function formatCpf(string $digits): string
    {
        $digits = str_pad(self::onlyDigits($digits), 11, '0', STR_PAD_LEFT);

        return preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits) ?? $digits;
    }

    public static function formatCnpj(string $digits): string
    {
        $digits = str_pad(self::onlyDigits($digits), 14, '0', STR_PAD_LEFT);

        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $digits) ?? $digits;
    }

    /**
     * Format the document according to the person type.
     */
    public static function format(?string $value, string $personType): ?string
    {
        $digits = self::onlyDigits($value);

        if ($digits === '') {
            return null;
        }

        return $personType === 'company'
            ? self::formatCnpj($digits)
            : self::formatCpf($digits);
    }

    public static function isValidCpf(?string $value): bool
    {
        $cpf = self::onlyDigits($value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;

            for ($index = 0; $index < $digit; $index++) {
                $sum += (int) $cpf[$index] * (($digit + 1) - $index);
            }

            $remainder = ($sum * 10) % 11;
            $verifier = $remainder === 10 ? 0 : $remainder;

            if ((int) $cpf[$digit] !== $verifier) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(?string $value): bool
    {
        $cnpj = self::onlyDigits($value);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $position => $sequence) {
            $sum = 0;

            foreach ($sequence as $index => $weight) {
                $sum += (int) $cnpj[$index] * $weight;
            }

            $remainder = $sum % 11;
            $verifier = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[12 + $position] !== $verifier) {
                return false;
            }
        }

        return true;
    }

    public static function isValid(?string $value, string $personType): bool
    {
        return $personType === 'company'
            ? self::isValidCnpj($value)
            : self::isValidCpf($value);
    }
}
