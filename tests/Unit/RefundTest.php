<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\DTO\Refund;
use PixSicredi\Enums\RefundStatus;

final class RefundTest extends TestCase
{
    public function test_from_array_maps_fields_and_status(): void
    {
        $refund = Refund::fromArray([
            'id' => 'dev1',
            'rtrId' => 'RTR123',
            'valor' => '10.00',
            'status' => 'DEVOLVIDO',
            'horario' => [
                'solicitacao' => '2026-06-16T10:00:00Z',
                'liquidacao' => '2026-06-16T10:01:00Z',
            ],
        ]);

        self::assertSame('dev1', $refund->id);
        self::assertSame('RTR123', $refund->rtrId);
        self::assertSame('10.00', $refund->amount);
        self::assertSame(RefundStatus::Devolvido, $refund->statusEnum());
        self::assertTrue($refund->isCompleted());
        self::assertSame('2026-06-16', $refund->requestedAt?->format('Y-m-d'));
        self::assertNotNull($refund->settledAt);
    }

    public function test_in_processing_is_not_completed(): void
    {
        $refund = Refund::fromArray(['id' => 'd', 'status' => 'EM_PROCESSAMENTO']);

        self::assertFalse($refund->isCompleted());
        self::assertNull($refund->settledAt);
    }
}
