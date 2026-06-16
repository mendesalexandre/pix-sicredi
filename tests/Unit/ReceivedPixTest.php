<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\DTO\ReceivedPix;

final class ReceivedPixTest extends TestCase
{
    public function test_from_array_maps_fields_and_keeps_raw(): void
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

        $pix = ReceivedPix::fromArray($item);

        self::assertSame('E123', $pix->endToEndId);
        self::assertSame('TX1', $pix->txid);
        self::assertSame('10.00', $pix->amount);
        self::assertSame('2026-06-16', $pix->dateTime?->format('Y-m-d'));
        self::assertSame($item['componentesValor'], $pix->raw['componentesValor']);
    }

    public function test_from_array_tolerates_missing_fields(): void
    {
        $pix = ReceivedPix::fromArray(['endToEndId' => 'E1', 'valor' => '1.00']);

        self::assertNull($pix->txid);
        self::assertNull($pix->pixKey);
        self::assertNull($pix->dateTime);
    }
}
