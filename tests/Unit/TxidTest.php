<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Support\Txid;

final class TxidTest extends TestCase
{
    public function test_random_is_valid_and_default_32(): void
    {
        $txid = Txid::random();

        self::assertSame(32, strlen($txid));
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{26,35}$/', $txid);
    }

    public function test_random_clamps_length_to_range(): void
    {
        self::assertSame(26, strlen(Txid::random(10)));
        self::assertSame(35, strlen(Txid::random(999)));
    }

    public function test_from_seed_is_deterministic_and_valid(): void
    {
        $a = Txid::fromSeed('OS-537253');
        $b = Txid::fromSeed('OS-537253');

        self::assertSame($a, $b);
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{26,35}$/', $a);
        self::assertStringStartsWith('OS537253', $a); // mantém o prefixo legível
    }

    public function test_from_seed_pads_short_input(): void
    {
        $txid = Txid::fromSeed('1');

        self::assertGreaterThanOrEqual(26, strlen($txid));
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{26,35}$/', $txid);
    }
}
