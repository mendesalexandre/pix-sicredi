<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

use PixSicredi\Auth;
use PixSicredi\Config;
use PixSicredi\Exceptions\RequestException;
use PixSicredi\Http\HttpClient;
use PixSicredi\Http\Response;

/** Base dos resources autenticados (Bearer + JSON sobre o /api/v2). */
abstract class Resource
{
    protected const API_PATH = '/api/v2';

    public function __construct(
        protected readonly Config $config,
        protected readonly HttpClient $http,
        protected readonly Auth $auth,
    ) {
    }

    /**
     * Chamada autenticada à API. Lança RequestException em HTTP >= 400,
     * preservando status + corpo cru + violacoes[] do Sicredi.
     *
     * @param array<string,mixed>|null $json
     * @param array<string,scalar>     $query
     */
    protected function call(string $method, string $path, ?array $json = null, array $query = []): Response
    {
        $res = $this->send($method, $path, $json, $query);

        // Token cacheado pode ter expirado/sido revogado: invalida e tenta 1x.
        if ($res->status === 401) {
            $this->auth->forgetToken();
            $res = $this->send($method, $path, $json, $query);
        }

        if (! $res->successful()) {
            $body = $res->hasJson() ? $res->json() : null;
            $detail = $body['detail'] ?? $body['title'] ?? ($res->body !== '' ? $res->body : 'sem corpo');

            throw new RequestException(
                message: "Erro na API PIX Sicredi (HTTP {$res->status}): {$detail}",
                statusCode: $res->status,
                rawBody: $res->body,
                body: $body,
            );
        }

        return $res;
    }

    /**
     * @param array<string,mixed>|null $json
     * @param array<string,scalar>     $query
     */
    private function send(string $method, string $path, ?array $json, array $query): Response
    {
        return $this->http->send(
            method: $method,
            url: self::API_PATH . $path,
            headers: ['Authorization' => 'Bearer ' . $this->auth->getToken()],
            json: $json,
            query: $query,
        );
    }
}
