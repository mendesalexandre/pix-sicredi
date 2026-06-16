<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Enums\Environment;

final class EnvironmentTest extends TestCase
{
    public function test_production_base_url(): void
    {
        self::assertSame('https://api-pix.sicredi.com.br', Environment::Production->baseUrl());
    }

    public function test_homologation_base_url(): void
    {
        self::assertSame('https://api-pix-h.sicredi.com.br', Environment::Homologation->baseUrl());
    }
}
