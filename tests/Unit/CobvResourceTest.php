<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Builders\CobvBuilder;
use PixSicredi\Exceptions\ValidationException;
use PixSicredi\Http\HttpClient;
use PixSicredi\Resources\Cobv;
use PixSicredi\Tests\Unit\Support\MocksHttp;

final class CobvResourceTest extends TestCase
{
    use MocksHttp;

    private const TXID = 'COBV0053725300605689160620261';

    private function cobv(HttpClient $http): Cobv
    {
        return new Cobv($this->config(), $http, $this->auth($http));
    }

    public function test_create_sends_put_to_cobv_with_builder(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(201, ['txid' => self::TXID, 'status' => 'ATIVA']),
        ]);

        $result = $this->cobv($http)->create(self::TXID, CobvBuilder::make()
            ->dueDate('2026-12-31')
            ->debtor('05314742160', 'Fulano')
            ->debtorAddress('Rua X', 'Sinop', 'MT', '78550000')
            ->amount('100.00')
            ->pixKey('chave'));

        self::assertSame('ATIVA', $result['status']);

        $request = $this->history[1]['request'];
        self::assertSame('PUT', $request->getMethod());
        self::assertSame('/api/v2/cobv/' . self::TXID, $request->getUri()->getPath());
        self::assertStringContainsString('"dataDeVencimento":"2026-12-31"', (string) $request->getBody());
    }

    public function test_invalid_txid_throws_without_calling_api(): void
    {
        $http = $this->httpClient([]); // nenhuma resposta: não pode chamar a API

        $this->expectException(ValidationException::class);
        $this->cobv($http)->create('curto', ['valor' => ['original' => '1.00']]);
    }
}
