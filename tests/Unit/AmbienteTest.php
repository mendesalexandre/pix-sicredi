<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Enums\Ambiente;

final class AmbienteTest extends TestCase
{
    public function test_base_url_de_producao(): void
    {
        self::assertSame('https://api-pix.sicredi.com.br', Ambiente::Producao->baseUrl());
    }

    public function test_base_url_de_homologacao(): void
    {
        self::assertSame('https://api-pix-h.sicredi.com.br', Ambiente::Homologacao->baseUrl());
    }
}
