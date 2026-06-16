<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

use PixSicredi\Builders\CobvBuilder;
use PixSicredi\Exceptions\ValidationException;

/**
 * Cobrança com vencimento (COBV) — /api/v2/cobv.
 *
 * Como o COB, mas com data de vencimento, endereço do devedor e encargos
 * (multa/juros/desconto/abatimento). Veja {@see CobvBuilder}.
 */
final class Cobv extends Resource
{
    /**
     * Cria/atualiza a cobrança com vencimento (PUT idempotente, txid 26–35 alfanum).
     *
     * @param array<string,mixed>|CobvBuilder $data
     * @return array<string,mixed>
     */
    public function create(string $txid, array|CobvBuilder $data): array
    {
        $this->assertTxid($txid);

        return $this->call('PUT', "/cobv/{$txid}", $this->payload($data))->json();
    }

    /** @return array<string,mixed> */
    public function get(string $txid): array
    {
        return $this->call('GET', "/cobv/{$txid}")->json();
    }

    /**
     * Revisa uma cobrança com vencimento (PATCH).
     *
     * @param array<string,mixed>|CobvBuilder $data
     * @return array<string,mixed>
     */
    public function update(string $txid, array|CobvBuilder $data): array
    {
        $this->assertTxid($txid);

        return $this->call('PATCH', "/cobv/{$txid}", $this->payload($data))->json();
    }

    /**
     * Lista cobranças com vencimento por período (ISO 8601).
     *
     * @param array<string,scalar> $extraFilters
     * @return array<string,mixed>
     */
    public function list(string $start, string $end, array $extraFilters = []): array
    {
        return $this->call('GET', '/cobv', null, ['inicio' => $start, 'fim' => $end] + $extraFilters)->json();
    }

    /**
     * @param array<string,mixed>|CobvBuilder $data
     * @return array<string,mixed>
     */
    private function payload(array|CobvBuilder $data): array
    {
        return $data instanceof CobvBuilder ? $data->toArray() : $data;
    }

    private function assertTxid(string $txid): void
    {
        if (! preg_match('/^[a-zA-Z0-9]{26,35}$/', $txid)) {
            throw new ValidationException(
                'txid inválido: deve ter 26–35 caracteres alfanuméricos (recebido: ' . strlen($txid) . ' chars).'
            );
        }
    }
}
