<?php

declare(strict_types=1);

namespace PixSicredi\DTO;

use DateTimeImmutable;
use PixSicredi\Enums\RefundStatus;

/** Visão tipada da resposta de uma devolução (devolucao). */
final class Refund
{
    /**
     * @param array<string,mixed> $raw payload completo da devolução
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $rtrId,
        public readonly ?string $amount,
        public readonly ?string $status,
        public readonly ?DateTimeImmutable $requestedAt,
        public readonly ?DateTimeImmutable $settledAt,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string,mixed> $horario */
        $horario = is_array($data['horario'] ?? null) ? $data['horario'] : [];

        return new self(
            id: (string) ($data['id'] ?? ''),
            rtrId: isset($data['rtrId']) ? (string) $data['rtrId'] : null,
            amount: isset($data['valor']) ? (string) $data['valor'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            requestedAt: self::parseDate($horario['solicitacao'] ?? null),
            settledAt: self::parseDate($horario['liquidacao'] ?? null),
            raw: $data,
        );
    }

    public function statusEnum(): ?RefundStatus
    {
        return $this->status !== null ? RefundStatus::tryFrom($this->status) : null;
    }

    /** Devolução concluída (status DEVOLVIDO). */
    public function isCompleted(): bool
    {
        return $this->statusEnum()?->isCompleted() ?? false;
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }
        $ts = strtotime($value);

        return $ts !== false ? (new DateTimeImmutable())->setTimestamp($ts) : null;
    }
}
