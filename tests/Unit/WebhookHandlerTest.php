<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Exceptions\ValidationException;
use PixSicredi\Webhook\WebhookHandler;

final class WebhookHandlerTest extends TestCase
{
    private function payload(): string
    {
        return json_encode([
            'pix' => [
                [
                    'endToEndId' => 'E0000000020260616105534abc',
                    'txid' => 'OS00537253C0060568916062026105534',
                    'valor' => '3046.18',
                    'horario' => '2026-06-16T10:55:34Z',
                    'chave' => 'financeiro.sinopfavo@gmail.com',
                    'infoPagador' => 'OS 537253',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function test_parses_received_pix(): void
    {
        $received = (new WebhookHandler())->parse($this->payload());

        self::assertCount(1, $received);
        self::assertSame('E0000000020260616105534abc', $received[0]->endToEndId);
        self::assertSame('OS00537253C0060568916062026105534', $received[0]->txid);
        self::assertSame('3046.18', $received[0]->amount);
        self::assertSame('financeiro.sinopfavo@gmail.com', $received[0]->pixKey);
        self::assertNotNull($received[0]->dateTime);
    }

    public function test_filters_by_expected_keys(): void
    {
        $received = (new WebhookHandler())
            ->withExpectedKeys(['outra@chave.com'])
            ->parse($this->payload());

        self::assertSame([], $received);
    }

    public function test_validation_call_without_pix(): void
    {
        $handler = new WebhookHandler();
        $ping = json_encode(['evento' => 'teste'], JSON_THROW_ON_ERROR);

        self::assertTrue($handler->isValidationCall($ping));
        self::assertSame([], $handler->parse($ping));
        self::assertFalse($handler->isValidationCall($this->payload()));
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(ValidationException::class);
        (new WebhookHandler())->parse('isso nao e json');
    }
}
