<?php

declare(strict_types=1);

namespace PixSicredi\Support;

use PixSicredi\Enums\PixKeyType;
use PixSicredi\Exceptions\ValidationException;

/**
 * Detecção e validação do tipo de chave PIX (DICT).
 *
 * Telefone deve estar em E.164 (+55DDNNNNNNNNN); chave aleatória é um UUID.
 */
final class PixKey
{
    /** Detecta o tipo da chave, ou null se não reconhecer. */
    public static function type(string $key): ?PixKeyType
    {
        $key = trim($key);

        if (str_contains($key, '@')) {
            return filter_var($key, FILTER_VALIDATE_EMAIL) !== false ? PixKeyType::Email : null;
        }

        if (preg_match('/^\+55\d{10,11}$/', $key)) {
            return PixKeyType::Phone;
        }

        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $key)) {
            return PixKeyType::Evp;
        }

        $digits = preg_replace('/\D/', '', $key) ?? '';
        if ($key === $digits) {
            return match (strlen($digits)) {
                11 => PixKeyType::Cpf,
                14 => PixKeyType::Cnpj,
                default => null,
            };
        }

        return null;
    }

    public static function isValid(string $key): bool
    {
        return self::type($key) !== null;
    }

    public static function assertValid(string $key): void
    {
        if (! self::isValid($key)) {
            throw new ValidationException("Chave PIX inválida ou de tipo não reconhecido: {$key}");
        }
    }
}
