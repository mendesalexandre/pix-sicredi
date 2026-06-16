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
     * @param array<string,mixed> $dados payload BACEN (calendario, devedor, valor, chave, solicitacaoPagador, infoAdicionais)
     * @return array<string,mixed>
     */
    public function criar(string $txid, array $dados): array
    {
        return $this->chamar('PUT', "/cob/{$txid}", $dados)->json();
    }

    /**
     * Cria a cobrança deixando o Sicredi gerar o txid (POST).
     *
     * @param array<string,mixed> $dados
     * @return array<string,mixed>
     */
    public function criarSemTxid(array $dados): array
    {
        return $this->chamar('POST', '/cob', $dados)->json();
    }

    /** @return array<string,mixed> */
    public function consultar(string $txid): array
    {
        return $this->chamar('GET', "/cob/{$txid}")->json();
    }

    /**
     * Revisa uma cobrança existente (PATCH) — ex: alterar status pra REMOVIDA_PELO_USUARIO_RECEBEDOR.
     *
     * @param array<string,mixed> $dados
     * @return array<string,mixed>
     */
    public function revisar(string $txid, array $dados): array
    {
        return $this->chamar('PATCH', "/cob/{$txid}", $dados)->json();
    }

    /**
     * Lista cobranças por período (datas em ISO 8601). Filtros extras opcionais:
     * cpf, cnpj, status, paginacao.paginaAtual, paginacao.itensPorPagina.
     *
     * @param array<string,scalar> $filtrosExtras
     * @return array<string,mixed>
     */
    public function listar(string $inicio, string $fim, array $filtrosExtras = []): array
    {
        return $this->chamar('GET', '/cob', null, ['inicio' => $inicio, 'fim' => $fim] + $filtrosExtras)->json();
    }
}
