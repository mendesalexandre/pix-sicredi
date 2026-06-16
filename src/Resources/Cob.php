<?php

declare(strict_types=1);

namespace PixSicredi\Resources;

/**
 * Cobrança imediata (COB) — /api/v2/cob.
 *
 * É o único tipo usado pelo cartório (COBV/LoteCobv ficam de fora do pacote).
 */
final class Cob extends Resource
{
    /**
     * Cria/atualiza a cobrança com um txid escolhido pelo cliente (26–35 chars).
     * PUT é idempotente: repetir o mesmo txid não duplica.
     *
     * @param array<string,mixed> $data payload BACEN (calendario, devedor, valor, chave, solicitacaoPagador, infoAdicionais)
     * @return array<string,mixed>
     */
    public function create(string $txid, array $data): array
    {
        return $this->call('PUT', "/cob/{$txid}", $data)->json();
    }

    /**
     * Cria a cobrança deixando o Sicredi gerar o txid (POST).
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function createWithGeneratedTxid(array $data): array
    {
        return $this->call('POST', '/cob', $data)->json();
    }

    /** @return array<string,mixed> */
    public function get(string $txid): array
    {
        return $this->call('GET', "/cob/{$txid}")->json();
    }

    /**
     * Revisa uma cobrança existente (PATCH) — ex: status REMOVIDA_PELO_USUARIO_RECEBEDOR.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function update(string $txid, array $data): array
    {
        return $this->call('PATCH', "/cob/{$txid}", $data)->json();
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
}
