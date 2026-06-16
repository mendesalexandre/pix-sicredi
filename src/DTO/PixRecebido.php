<?php

declare(strict_types=1);

namespace PixSicredi\DTO;

use DateTimeImmutable;

/** Um pix recebido, conforme entregue na notificação de webhook do Sicredi. */
final class PixRecebido
{
    /**
     * @param array<string,mixed> $raw payload cru do item (pra campos não mapeados)
     */
    public function __construct(
        public readonly string $endToEndId,
        public readonly ?string $txid,
        public readonly string $valor,
        public readonly ?DateTimeImmutable $horario,
        public readonly ?string $chave,
        public readonly ?string $infoPagador,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string,mixed> $item */
    public static function deArray(array $item): self
    {
        $horario = null;
        if (isset($item['horario']) && is_string($item['horario'])) {
            $ts = strtotime($item['horario']);
            $horario = $ts !== false ? (new DateTimeImmutable())->setTimestamp($ts) : null;
        }

        return new self(
            endToEndId: (string) ($item['endToEndId'] ?? $item['endToEndID'] ?? ''),
            txid: isset($item['txid']) ? (string) $item['txid'] : null,
            valor: (string) ($item['valor'] ?? ''),
            horario: $horario,
            chave: isset($item['chave']) ? (string) $item['chave'] : null,
            infoPagador: isset($item['infoPagador']) ? (string) $item['infoPagador'] : null,
            raw: $item,
        );
    }
}
