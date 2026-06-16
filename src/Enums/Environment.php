<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/**
 * Ambientes da API PIX do Sicredi.
 *
 * O path da API (/api/v2) e o do OAuth (/oauth/token) são iguais nos dois
 * ambientes; só muda o host.
 */
enum Environment: string
{
    case Production = 'production';
    case Homologation = 'homologation';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Production => 'https://api-pix.sicredi.com.br',
            self::Homologation => 'https://api-pix-h.sicredi.com.br',
        };
    }
}
