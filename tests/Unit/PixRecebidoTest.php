<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\DTO\PixRecebido;

final class PixRecebidoTest extends TestCase
{
    public function test_de_array_mapeia_campos_e_preserva_raw(): void
    {
        $item = [
            'endToEndId' => 'E123',
            'txid' => 'TX1',
            'valor' => '10.00',
            'horario' => '2026-06-16T10:55:34Z',
            'chave' => 'a@b.com',
            'infoPagador' => 'obs',
            'componentesValor' => ['original' => ['valor' => '10.00']],
        ];

        $pix = PixRecebido::deArray($item);

        self::assertSame('E123', $pix->endToEndId);
        self::assertSame('TX1', $pix->txid);
        self::assertSame('10.00', $pix->valor);
        self::assertSame('2026-06-16', $pix->horario?->format('Y-m-d'));
        self::assertSame($item['componentesValor'], $pix->raw['componentesValor']);
    }

    public function test_de_array_tolera_campos_ausentes(): void
    {
        $pix = PixRecebido::deArray(['endToEndId' => 'E1', 'valor' => '1.00']);

        self::assertNull($pix->txid);
        self::assertNull($pix->chave);
        self::assertNull($pix->horario);
    }
}
