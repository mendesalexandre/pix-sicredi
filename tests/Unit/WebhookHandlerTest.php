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

    public function test_parseia_pix_recebido(): void
    {
        $recebidos = (new WebhookHandler())->processar($this->payload());

        self::assertCount(1, $recebidos);
        self::assertSame('E0000000020260616105534abc', $recebidos[0]->endToEndId);
        self::assertSame('OS00537253C0060568916062026105534', $recebidos[0]->txid);
        self::assertSame('3046.18', $recebidos[0]->valor);
        self::assertSame('financeiro.sinopfavo@gmail.com', $recebidos[0]->chave);
        self::assertNotNull($recebidos[0]->horario);
    }

    public function test_filtra_por_chaves_esperadas(): void
    {
        $recebidos = (new WebhookHandler())
            ->comChavesEsperadas(['outra@chave.com'])
            ->processar($this->payload());

        self::assertSame([], $recebidos);
    }

    public function test_ping_de_validacao_sem_pix(): void
    {
        $handler = new WebhookHandler();
        $ping = json_encode(['evento' => 'teste'], JSON_THROW_ON_ERROR);

        self::assertTrue($handler->ehPingDeValidacao($ping));
        self::assertSame([], $handler->processar($ping));
        self::assertFalse($handler->ehPingDeValidacao($this->payload()));
    }

    public function test_lanca_em_json_invalido(): void
    {
        $this->expectException(ValidationException::class);
        (new WebhookHandler())->processar('isso nao e json');
    }
}
