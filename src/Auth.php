<?php

declare(strict_types=1);

namespace PixSicredi;

use PixSicredi\Exceptions\AuthenticationException;
use PixSicredi\Http\HttpClient;

/**
 * Autenticação OAuth2 client_credentials + mTLS do Sicredi.
 *
 * Gotcha importante (confirmado em produção 16/06/2026): o `grant_type` vai
 * SÓ no corpo (form-urlencoded). Se também for repetido na query da URL do
 * token, o Sicredi rejeita com 400 "gerarToken.grantType: O 'grant_type'
 * informado é inválido". Por isso a URL do token aqui é sempre limpa.
 */
final class Auth
{
    private const CACHE_KEY = 'pix_sicredi_token';
    private const CACHE_TTL_FALLBACK = 270; // 4,5 min (token Sicredi dura ~300s)

    public function __construct(
        private readonly Config $config,
        private readonly HttpClient $http,
    ) {
    }

    public function getToken(): string
    {
        $cache = $this->config->cache;

        if ($cache !== null) {
            $cached = $cache->get(self::CACHE_KEY);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        [$token, $expiresIn] = $this->requestToken();

        if ($cache !== null) {
            $ttl = $expiresIn > 60 ? $expiresIn - 60 : self::CACHE_TTL_FALLBACK;
            $cache->set(self::CACHE_KEY, $token, $ttl);
        }

        return $token;
    }

    public function forgetToken(): void
    {
        $this->config->cache?->delete(self::CACHE_KEY);
    }

    /** @return array{0:string,1:int} [token, expires_in] */
    private function requestToken(): array
    {
        $basic = base64_encode("{$this->config->clientId}:{$this->config->clientSecret}");

        // URL do token SEM query (grant_type vai no corpo — ver gotcha acima).
        $res = $this->http->send(
            method: 'POST',
            url: '/oauth/token',
            headers: ['Authorization' => "Basic {$basic}"],
            form: [
                'grant_type' => 'client_credentials',
                'scope' => implode(' ', $this->config->scopes),
            ],
        );

        if (! $res->successful()) {
            $body = $res->json();
            $detail = $body['error_description']
                ?? $body['detail']
                ?? $body['error']
                ?? ($res->body !== '' ? $res->body : 'sem corpo na resposta');

            $this->config->logger?->error('[PIX Sicredi] Falha ao obter token', [
                'status' => $res->status,
                'body' => $res->body,
            ]);

            throw new AuthenticationException(
                "Falha na autenticação PIX Sicredi (HTTP {$res->status}): {$detail}"
            );
        }

        $token = $res->json()['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new AuthenticationException('Token de acesso não retornado pelo Sicredi.');
        }

        $expiresIn = (int) ($res->json()['expires_in'] ?? 0);

        return [$token, $expiresIn];
    }
}
