<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\DTO\Charge;
use PixSicredi\Enums\ChargeStatus;

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
        self::assertNull($charge->statusEnum());
        self::assertFalse($charge->isPaid());
        self::assertFalse($charge->isActive());
    }

    public function test_typed_status_helpers(): void
    {
        $paga = Charge::fromArray(['txid' => 'T', 'status' => 'CONCLUIDA']);
        self::assertSame(ChargeStatus::Concluida, $paga->statusEnum());
        self::assertTrue($paga->isPaid());
        self::assertFalse($paga->isActive());

        $ativa = Charge::fromArray(['txid' => 'T', 'status' => 'ATIVA']);
        self::assertTrue($ativa->isActive());
        self::assertFalse($ativa->isPaid());

        // status desconhecido não quebra
        $estranho = Charge::fromArray(['txid' => 'T', 'status' => 'FOO']);
        self::assertNull($estranho->statusEnum());
    }
}
