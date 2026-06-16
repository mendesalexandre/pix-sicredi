<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Builders\CobBuilder;
use PixSicredi\Exceptions\ValidationException;
use PixSicredi\Http\HttpClient;
use PixSicredi\Resources\Cob;
use PixSicredi\Tests\Unit\Support\MocksHttp;

final class CobTest extends TestCase
{
    use MocksHttp;

    private const TXID = 'OS00537253C0060568916062026105';

    private function cob(HttpClient $http): Cob
    {
        return new Cob($this->config(), $http, $this->auth($http));
    }

    public function test_create_sends_put_with_bearer_and_payload(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(201, ['txid' => self::TXID, 'status' => 'ATIVA']),
        ]);

        $result = $this->cob($http)->create(self::TXID, ['valor' => ['original' => '10.00']]);

        self::assertSame('ATIVA', $result['status']);

        $request = $this->history[1]['request'];
        self::assertSame('PUT', $request->getMethod());
        self::assertSame('/api/v2/cob/' . self::TXID, $request->getUri()->getPath());
        self::assertSame('Bearer tok_abc', $request->getHeaderLine('Authorization'));
        self::assertStringContainsString('"original":"10.00"', (string) $request->getBody());
    }

    public function test_create_accepts_cob_builder(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(201, ['txid' => self::TXID]),
        ]);

        $this->cob($http)->create(self::TXID, CobBuilder::make()
            ->debtor('05314742160', 'Fulano')
            ->amount('10.00')
            ->pixKey('chave'));

        $body = (string) $this->history[1]['request']->getBody();
        self::assertStringContainsString('"cpf":"05314742160"', $body);
        self::assertStringContainsString('"chave":"chave"', $body);
    }

    public function test_invalid_txid_throws_without_calling_api(): void
    {
        $http = $this->httpClient([]); // nenhuma resposta: não pode chamar a API

        $this->expectException(ValidationException::class);
        $this->cob($http)->create('curto', ['valor' => ['original' => '10.00']]);
    }

    public function test_retries_once_on_401(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),                 // token inicial
            $this->jsonResponse(401, ['detail' => 'token expirado']),
            $this->tokenResponse('tok_new'),        // re-autentica
            $this->jsonResponse(200, ['status' => 'ATIVA']),
        ]);

        $result = $this->cob($http)->get(self::TXID);

        self::assertSame('ATIVA', $result['status']);
        self::assertCount(4, $this->history); // token, 401, token, 200
    }
}
