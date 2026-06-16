<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit\Support;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PixSicredi\Auth;
use PixSicredi\Config;
use PixSicredi\Enums\Environment;
use PixSicredi\Http\HttpClient;

/**
 * Helper de teste: monta Config + HttpClient com Guzzle mockado e guarda o
 * histórico de requisições pra assertions.
 */
trait MocksHttp
{
    /** @var list<array<string,mixed>> */
    protected array $history = [];

    protected function config(): Config
    {
        return new Config(
            clientId: 'id',
            clientSecret: 'secret',
            certificatePath: __DIR__ . '/../../fixtures/cert.pem',
            privateKeyPath: __DIR__ . '/../../fixtures/app.key',
            environment: Environment::Homologation,
        );
    }

    /**
     * @param list<GuzzleResponse> $responses respostas enfileiradas (em ordem)
     */
    protected function httpClient(array $responses): HttpClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new HttpClient($this->config(), $stack);
    }

    /** Resposta de token OAuth válida. */
    protected function tokenResponse(string $token = 'tok_abc'): GuzzleResponse
    {
        return new GuzzleResponse(200, [], (string) json_encode([
            'access_token' => $token,
            'expires_in' => 300,
        ]));
    }

    /** @param array<string,mixed> $body */
    protected function jsonResponse(int $status, array $body): GuzzleResponse
    {
        return new GuzzleResponse($status, [], (string) json_encode($body));
    }

    protected function auth(HttpClient $http): Auth
    {
        return new Auth($this->config(), $http);
    }
}
