<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Builders\CobBuilder;
use PixSicredi\Exceptions\ValidationException;

final class CobBuilderTest extends TestCase
{
    public function test_builds_minimal_payload_with_default_expiration(): void
    {
        $payload = CobBuilder::make()
            ->debtor('05314742160', 'Fulano')
            ->amount('3046.18')
            ->pixKey('financeiro@cartorio.com.br')
            ->toArray();

        self::assertSame(['expiracao' => 3600], $payload['calendario']);
        self::assertSame(['cpf' => '05314742160', 'nome' => 'Fulano'], $payload['devedor']);
        self::assertSame(['original' => '3046.18'], $payload['valor']);
        self::assertSame('financeiro@cartorio.com.br', $payload['chave']);
        self::assertArrayNotHasKey('solicitacaoPagador', $payload);
    }

    public function test_detects_cnpj_and_includes_extras(): void
    {
        $payload = CobBuilder::make()
            ->expiration(1800)
            ->debtor('12.345.678/0001-95', 'Empresa LTDA')
            ->amount('10.00')
            ->pixKey('chave')
            ->payerRequest('OS 1')
            ->addInfo('OS', '1')
            ->toArray();

        self::assertSame(['cnpj' => '12345678000195', 'nome' => 'Empresa LTDA'], $payload['devedor']);
        self::assertSame(1800, $payload['calendario']['expiracao']);
        self::assertSame('OS 1', $payload['solicitacaoPagador']);
        self::assertSame([['nome' => 'OS', 'valor' => '1']], $payload['infoAdicionais']);
    }

    public function test_invalid_document_throws(): void
    {
        $this->expectException(ValidationException::class);
        CobBuilder::make()->debtor('123', 'X');
    }

    public function test_invalid_amount_format_throws(): void
    {
        $this->expectException(ValidationException::class);
        CobBuilder::make()->amount('10');
    }

    public function test_payer_request_too_long_throws(): void
    {
        $this->expectException(ValidationException::class);
        CobBuilder::make()->payerRequest(str_repeat('a', 141));
    }

    public function test_to_array_requires_debtor_amount_and_key(): void
    {
        $this->expectException(ValidationException::class);
        CobBuilder::make()->amount('10.00')->pixKey('x')->toArray(); // sem devedor
    }
}
