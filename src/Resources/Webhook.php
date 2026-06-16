<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

/**
 * Configuração do webhook NO Sicredi (lado cliente) — /api/v2/webhook/{chave}.
 *
 * Isto registra a URL que o Sicredi vai CHAMAR quando um pix for recebido. O
 * recebimento/parsing da chamada de volta é tratado por {@see \PixSicredi\Webhook\WebhookHandler}.
 */
final class Webhook extends Resource
{
    /**
     * Registra/atualiza a URL de callback para uma chave PIX.
     *
     * @return array<string,mixed>
     */
    public function configure(string $pixKey, string $webhookUrl): array
    {
        return $this->call('PUT', "/webhook/{$pixKey}", ['webhookUrl' => $webhookUrl])->json();
    }

    /** @return array<string,mixed> */
    public function get(string $pixKey): array
    {
        return $this->call('GET', "/webhook/{$pixKey}")->json();
    }

    public function delete(string $pixKey): void
    {
        $this->call('DELETE', "/webhook/{$pixKey}");
    }

    /**
     * Lista os webhooks cadastrados.
     *
     * @param array<string,scalar> $filters
     * @return array<string,mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->call('GET', '/webhook', null, $filters)->json();
    }
}
