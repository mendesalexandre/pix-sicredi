<?php

declare(strict_types=1);

namespace PixSicredi\DTO;

use DateTimeImmutable;
use PixSicredi\Enums\ChargeStatus;

/**
 * Visão tipada da resposta de uma cobrança (COB) — criação ou consulta.
 *
 * Acesso de conveniência aos campos mais usados; o payload completo continua
 * disponível em {@see self::$raw}.
 */
final class Charge
{
    /**
     * @param array<string,mixed> $raw payload completo da cobrança
     */
    public function __construct(
        public readonly string $txid,
        public readonly ?string $status,
        public readonly ?string $amount,
        public readonly ?string $pixKey,
        public readonly ?string $location,
        public readonly ?string $copyPaste,
        public readonly ?int $expiration,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly array $raw = [],
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string,mixed> $calendario */
        $calendario = is_array($data['calendario'] ?? null) ? $data['calendario'] : [];
        /** @var array<string,mixed> $valor */
        $valor = is_array($data['valor'] ?? null) ? $data['valor'] : [];
        /** @var array<string,mixed> $loc */
        $loc = is_array($data['loc'] ?? null) ? $data['loc'] : [];

        $createdAt = null;
        if (isset($calendario['criacao']) && is_string($calendario['criacao'])) {
            $ts = strtotime($calendario['criacao']);
            $createdAt = $ts !== false ? (new DateTimeImmutable())->setTimestamp($ts) : null;
        }

        return new self(
            txid: (string) ($data['txid'] ?? ''),
            status: isset($data['status']) ? (string) $data['status'] : null,
            amount: isset($valor['original']) ? (string) $valor['original'] : null,
            pixKey: isset($data['chave']) ? (string) $data['chave'] : null,
            location: self::stringOrNull($data['location'] ?? ($loc['location'] ?? null)),
            copyPaste: self::stringOrNull($data['pixCopiaECola'] ?? null),
            expiration: isset($calendario['expiracao']) ? (int) $calendario['expiracao'] : null,
            createdAt: $createdAt,
            raw: $data,
        );
    }

    /** Status como enum (null se desconhecido/ausente). */
    public function statusEnum(): ?ChargeStatus
    {
        return $this->status !== null ? ChargeStatus::tryFrom($this->status) : null;
    }

    /** Cobrança paga/liquidada (status CONCLUIDA). */
    public function isPaid(): bool
    {
        return $this->statusEnum()?->isPaid() ?? false;
    }

    /** Cobrança aberta, aguardando pagamento (status ATIVA). */
    public function isActive(): bool
    {
        return $this->statusEnum()?->isActive() ?? false;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
