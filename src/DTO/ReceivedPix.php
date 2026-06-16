<?php

declare(strict_types=1);

namespace PixSicredi\DTO;

use DateTimeImmutable;

/** Um pix recebido, conforme entregue na notificação de webhook do Sicredi. */
final class ReceivedPix
{
    /**
     * @param array<string,mixed> $raw payload cru do item (pra campos não mapeados)
     */
    public function __construct(
        public readonly string $endToEndId,
        public readonly ?string $txid,
        public readonly string $amount,
        public readonly ?DateTimeImmutable $dateTime,
        public readonly ?string $pixKey,
        public readonly ?string $payerInfo,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string,mixed> $item */
    public static function fromArray(array $item): self
    {
        $dateTime = null;
        if (isset($item['horario']) && is_string($item['horario'])) {
            $ts = strtotime($item['horario']);
            $dateTime = $ts !== false ? (new DateTimeImmutable())->setTimestamp($ts) : null;
        }

        return new self(
            endToEndId: (string) ($item['endToEndId'] ?? $item['endToEndID'] ?? ''),
            txid: isset($item['txid']) ? (string) $item['txid'] : null,
            amount: (string) ($item['valor'] ?? ''),
            dateTime: $dateTime,
            pixKey: isset($item['chave']) ? (string) $item['chave'] : null,
            payerInfo: isset($item['infoPagador']) ? (string) $item['infoPagador'] : null,
            raw: $item,
        );
    }
}
