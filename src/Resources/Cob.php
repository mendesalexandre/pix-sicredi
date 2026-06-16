<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

use PixSicredi\Builders\CobBuilder;
use PixSicredi\Exceptions\ValidationException;

/**
 * Cobrança imediata (COB) — /api/v2/cob.
 *
 * É o único tipo usado pelo cartório (COBV/LoteCobv ficam de fora do pacote).
 */
final class Cob extends Resource
{
    /**
     * Cria/atualiza a cobrança com um txid escolhido pelo cliente (26–35 chars
     * alfanuméricos). PUT é idempotente: repetir o mesmo txid não duplica.
     *
     * @param array<string,mixed>|CobBuilder $data payload BACEN ou CobBuilder
     * @return array<string,mixed>
     */
    public function create(string $txid, array|CobBuilder $data): array
    {
        $this->assertTxid($txid);

        return $this->call('PUT', "/cob/{$txid}", $this->payload($data))->json();
    }

    /**
     * Cria a cobrança deixando o Sicredi gerar o txid (POST).
     *
     * @param array<string,mixed>|CobBuilder $data
     * @return array<string,mixed>
     */
    public function createWithGeneratedTxid(array|CobBuilder $data): array
    {
        return $this->call('POST', '/cob', $this->payload($data))->json();
    }

    /** @return array<string,mixed> */
    public function get(string $txid): array
    {
        return $this->call('GET', "/cob/{$txid}")->json();
    }

    /**
     * Revisa uma cobrança existente (PATCH) — ex: status REMOVIDA_PELO_USUARIO_RECEBEDOR.
     *
     * @param array<string,mixed>|CobBuilder $data
     * @return array<string,mixed>
     */
    public function update(string $txid, array|CobBuilder $data): array
    {
        $this->assertTxid($txid);

        return $this->call('PATCH', "/cob/{$txid}", $this->payload($data))->json();
    }

    /**
     * Lista cobranças por período (datas em ISO 8601). Filtros extras opcionais:
     * cpf, cnpj, status, paginacao.paginaAtual, paginacao.itensPorPagina.
     *
     * @param array<string,scalar> $extraFilters
     * @return array<string,mixed>
     */
    public function list(string $start, string $end, array $extraFilters = []): array
    {
        return $this->call('GET', '/cob', null, ['inicio' => $start, 'fim' => $end] + $extraFilters)->json();
    }

    /**
     * @param array<string,mixed>|CobBuilder $data
     * @return array<string,mixed>
     */
    private function payload(array|CobBuilder $data): array
    {
        return $data instanceof CobBuilder ? $data->toArray() : $data;
    }

    private function assertTxid(string $txid): void
    {
        // Sicredi/BACEN: txid da cob é [a-zA-Z0-9]{26,35}.
        if (! preg_match('/^[a-zA-Z0-9]{26,35}$/', $txid)) {
            throw new ValidationException(
                "txid inválido: deve ter 26–35 caracteres alfanuméricos (recebido: " . strlen($txid) . ' chars).'
            );
        }
    }
}
