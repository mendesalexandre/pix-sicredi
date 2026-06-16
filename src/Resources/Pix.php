<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

/**
 * Pix recebidos e devolução — /api/v2/pix.
 *
 * A devolução no padrão BACEN é sobre o pix RECEBIDO (identificado pelo endToEndId),
 * não sobre a cobrança.
 */
final class Pix extends Resource
{
    /** @return array<string,mixed> */
    public function get(string $endToEndId): array
    {
        return $this->call('GET', "/pix/{$endToEndId}")->json();
    }

    /**
     * Lista pix recebidos por período (ISO 8601).
     *
     * @param array<string,scalar> $extraFilters
     * @return array<string,mixed>
     */
    public function list(string $start, string $end, array $extraFilters = []): array
    {
        return $this->call('GET', '/pix', null, ['inicio' => $start, 'fim' => $end] + $extraFilters)->json();
    }

    /**
     * Solicita devolução (total ou parcial) de um pix recebido.
     * O `refundId` é gerado pelo cliente (idempotente).
     *
     * @return array<string,mixed>
     */
    public function refund(string $endToEndId, string $refundId, string $amount, ?string $description = null): array
    {
        $data = ['valor' => $amount];
        if ($description !== null) {
            $data['descricao'] = $description;
        }

        return $this->call('PUT', "/pix/{$endToEndId}/devolucao/{$refundId}", $data)->json();
    }

    /** @return array<string,mixed> */
    public function getRefund(string $endToEndId, string $refundId): array
    {
        return $this->call('GET', "/pix/{$endToEndId}/devolucao/{$refundId}")->json();
    }
}
