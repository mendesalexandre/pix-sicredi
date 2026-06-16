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
    protected function chamar(string $method, string $path, ?array $json = null, array $query = []): Response
    {
        $res = $this->http->send(
            method: $method,
            url: self::API_PATH . $path,
            headers: ['Authorization' => 'Bearer ' . $this->auth->getToken()],
            json: $json,
            query: $query,
        );

        if (! $res->sucesso()) {
            $corpo = $res->temJson() ? $res->json() : null;
            $detalhe = $corpo['detail'] ?? $corpo['title'] ?? ($res->body !== '' ? $res->body : 'sem corpo');

            throw new RequestException(
                message: "Erro na API PIX Sicredi (HTTP {$res->status}): {$detalhe}",
                statusCode: $res->status,
                corpoBruto: $res->body,
                corpo: $corpo,
            );
        }

        return $res;
    }
}
