<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Exceptions\AuthenticationException;
use PixSicredi\Tests\Unit\Support\MocksHttp;

final class AuthTest extends TestCase
{
    use MocksHttp;

    public function test_returns_token_on_success(): void
    {
        $http = $this->httpClient([$this->tokenResponse('tok_xyz')]);

        self::assertSame('tok_xyz', $this->auth($http)->getToken());
    }

    public function test_token_request_sends_grant_type_in_body_not_query(): void
    {
        $http = $this->httpClient([$this->tokenResponse()]);
        $this->auth($http)->getToken();

        $request = $this->history[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/oauth/token', $request->getUri()->getPath());
        // grant_type vai no corpo, NUNCA na query (gotcha do 400 do Sicredi)
        self::assertStringNotContainsString('grant_type', $request->getUri()->getQuery());
        self::assertStringContainsString('grant_type=client_credentials', (string) $request->getBody());
    }

    public function test_throws_with_detail_on_error(): void
    {
        $http = $this->httpClient([
            $this->jsonResponse(400, [
                'detail' => "gerarToken.grantType: O 'grant_type' informado é inválido.",
                'violacoes' => [['razao' => 'gerarToken.grantType']],
            ]),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("grant_type' informado é inválido");
        $this->auth($http)->getToken();
    }
}
