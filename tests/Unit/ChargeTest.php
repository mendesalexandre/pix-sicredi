<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\DTO\Charge;

final class ChargeTest extends TestCase
{
    public function test_from_array_maps_fields(): void
    {
        $charge = Charge::fromArray([
            'txid' => 'TX1',
            'status' => 'ATIVA',
            'calendario' => ['criacao' => '2026-06-16T10:00:00Z', 'expiracao' => 3600],
            'valor' => ['original' => '10.00'],
            'chave' => 'a@b.com',
            'loc' => ['location' => 'pix.sicredi.com.br/qr/v2/abc'],
            'pixCopiaECola' => '00020126...',
        ]);

        self::assertSame('TX1', $charge->txid);
        self::assertSame('ATIVA', $charge->status);
        self::assertSame('10.00', $charge->amount);
        self::assertSame('a@b.com', $charge->pixKey);
        self::assertSame('pix.sicredi.com.br/qr/v2/abc', $charge->location);
        self::assertSame('00020126...', $charge->copyPaste);
        self::assertSame(3600, $charge->expiration);
        self::assertSame('2026-06-16', $charge->createdAt?->format('Y-m-d'));
    }

    public function test_from_array_tolerates_missing(): void
    {
        $charge = Charge::fromArray(['txid' => 'T']);

        self::assertSame('T', $charge->txid);
        self::assertNull($charge->status);
        self::assertNull($charge->location);
        self::assertNull($charge->copyPaste);
        self::assertNull($charge->createdAt);
    }
}
