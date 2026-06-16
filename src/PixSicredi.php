<?php

declare(strict_types=1);

namespace PixSicredi;

use PixSicredi\Http\HttpClient;
use PixSicredi\Resources\Cob;
use PixSicredi\Resources\Cobv;
use PixSicredi\Resources\Pix;
use PixSicredi\Resources\Webhook;
use PixSicredi\Webhook\WebhookHandler;

/**
 * Fachada principal do pacote.
 *
 * ```php
 * $pix = new PixSicredi(new Config(
 *     clientId: '...', clientSecret: '...',
 *     certificatePath: '/path/cert.pem', privateKeyPath: '/path/app.key',
 *     environment: Environment::Production,
 *     cache: $psr16, // opcional (cacheia o token)
 * ));
 *
 * $cobranca = $pix->cob()->create($txid, [...]);
 * $pix->webhook()->configure('chave@pix.com', 'https://meusite/webhook/pix');
 * $recebidos = $pix->webhookHandler()->parse($request->getBody());
 * ```
 */
final class PixSicredi
{
    private readonly HttpClient $http;
    private readonly Auth $auth;

    public function __construct(private readonly Config $config)
    {
        $this->http = new HttpClient($config);
        $this->auth = new Auth($config, $this->http);
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function cob(): Cob
    {
        return new Cob($this->config, $this->http, $this->auth);
    }

    public function cobv(): Cobv
    {
        return new Cobv($this->config, $this->http, $this->auth);
    }

    public function webhook(): Webhook
    {
        return new Webhook($this->config, $this->http, $this->auth);
    }

    public function pix(): Pix
    {
        return new Pix($this->config, $this->http, $this->auth);
    }

    public function webhookHandler(): WebhookHandler
    {
        return new WebhookHandler();
    }
}
