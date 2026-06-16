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
    public function consultar(string $endToEndId): array
    {
        return $this->chamar('GET', "/pix/{$endToEndId}")->json();
    }

    /**
     * Lista pix recebidos por período (ISO 8601).
     *
     * @param array<string,scalar> $filtrosExtras
     * @return array<string,mixed>
     */
    public function listar(string $inicio, string $fim, array $filtrosExtras = []): array
    {
        return $this->chamar('GET', '/pix', null, ['inicio' => $inicio, 'fim' => $fim] + $filtrosExtras)->json();
    }

    /**
     * Solicita devolução (total ou parcial) de um pix recebido.
     * O `idDevolucao` é gerado pelo cliente (idempotente).
     *
     * @return array<string,mixed>
     */
    public function devolver(string $endToEndId, string $idDevolucao, string $valor, ?string $descricao = null): array
    {
        $dados = ['valor' => $valor];
        if ($descricao !== null) {
            $dados['descricao'] = $descricao;
        }

        return $this->chamar('PUT', "/pix/{$endToEndId}/devolucao/{$idDevolucao}", $dados)->json();
    }

    /** @return array<string,mixed> */
    public function consultarDevolucao(string $endToEndId, string $idDevolucao): array
    {
        return $this->chamar('GET', "/pix/{$endToEndId}/devolucao/{$idDevolucao}")->json();
    }
}
