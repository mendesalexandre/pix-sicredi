<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Http\HttpClient;
use PixSicredi\Resources\Pix;
use PixSicredi\Tests\Unit\Support\MocksHttp;

final class PixResourceTest extends TestCase
{
    use MocksHttp;

    private function pix(HttpClient $http): Pix
    {
        return new Pix($this->config(), $http, $this->auth($http));
    }

    public function test_refund_sends_put_with_amount(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(201, ['id' => 'dev1', 'status' => 'EM_PROCESSAMENTO']),
        ]);

        $result = $this->pix($http)->refund('E123', 'dev1', '10.00', 'estorno');

        self::assertSame('EM_PROCESSAMENTO', $result['status']);

        $request = $this->history[1]['request'];
        self::assertSame('PUT', $request->getMethod());
        self::assertSame('/api/v2/pix/E123/devolucao/dev1', $request->getUri()->getPath());
        self::assertStringContainsString('"valor":"10.00"', (string) $request->getBody());
        self::assertStringContainsString('"descricao":"estorno"', (string) $request->getBody());
    }

    public function test_list_sends_period_query(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(200, ['pix' => []]),
        ]);

        $this->pix($http)->list('2026-06-01T00:00:00Z', '2026-06-30T23:59:59Z');

        $query = $this->history[1]['request']->getUri()->getQuery();
        self::assertStringContainsString('inicio=', $query);
        self::assertStringContainsString('fim=', $query);
    }
}
