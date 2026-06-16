<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

/**
 * Configuração do webhook NO Sicredi (lado cliente) — /api/v2/webhook/{chave}.
 *
 * Isto registra a URL que o Sicredi vai CHAMAR quando um pix for recebido. O
 * recebimento/validação da chamada de volta é tratado por {@see \PixSicredi\Webhook\WebhookHandler}.
 */
final class Webhook extends Resource
{
    /**
     * Registra/atualiza a URL de callback para uma chave PIX.
     *
     * @return array<string,mixed>
     */
    public function configurar(string $chavePix, string $webhookUrl): array
    {
        return $this->chamar('PUT', "/webhook/{$chavePix}", ['webhookUrl' => $webhookUrl])->json();
    }

    /** @return array<string,mixed> */
    public function consultar(string $chavePix): array
    {
        return $this->chamar('GET', "/webhook/{$chavePix}")->json();
    }

    public function excluir(string $chavePix): void
    {
        $this->chamar('DELETE', "/webhook/{$chavePix}");
    }

    /**
     * Lista os webhooks cadastrados.
     *
     * @param array<string,scalar> $filtros
     * @return array<string,mixed>
     */
    public function listar(array $filtros = []): array
    {
        return $this->chamar('GET', '/webhook', null, $filtros)->json();
    }
}
