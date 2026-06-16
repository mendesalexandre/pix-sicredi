<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/**
 * Ambientes da API PIX do Sicredi.
 *
 * O path da API (/api/v2) e o do OAuth (/oauth/token) são os mesmos nos dois
 * ambientes; só muda o host.
 */
enum Ambiente: string
{
    case Producao = 'producao';
    case Homologacao = 'homologacao';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Producao => 'https://api-pix.sicredi.com.br',
            self::Homologacao => 'https://api-pix-h.sicredi.com.br',
        };
    }
}
