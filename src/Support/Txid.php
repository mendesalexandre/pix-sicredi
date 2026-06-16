<?php

declare(strict_types=1);

namespace PixSicredi\Support;

/**
 * Geração de `txid` válido pra cobrança (BACEN: `[a-zA-Z0-9]{26,35}`).
 */
final class Txid
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const MIN = 26;
    private const MAX = 35;

    /** Gera um txid aleatório (32 chars por padrão). */
    public static function random(int $length = 32): string
    {
        $length = max(self::MIN, min(self::MAX, $length));
        $max = strlen(self::ALPHABET) - 1;

        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    /**
     * Gera um txid determinístico a partir de um identificador (ex: nº da O.S.).
     * Útil pra idempotência: o mesmo seed sempre produz o mesmo txid, então
     * reenviar a cobrança não duplica.
     */
    public static function fromSeed(string $seed): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9]/', '', $seed) ?? '';

        // Completa com um hash (alfanumérico) pra garantir o mínimo e unicidade.
        if (strlen($clean) < self::MIN) {
            $clean .= substr(hash('sha256', $seed), 0, self::MAX);
        }

        $clean = substr($clean, 0, self::MAX);

        // Caso patológico (seed vazio e hash curto não deveria ocorrer): pad.
        if (strlen($clean) < self::MIN) {
            $clean = str_pad($clean, self::MIN, '0');
        }

        return $clean;
    }
}
