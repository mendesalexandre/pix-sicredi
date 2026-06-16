<?php

declare(strict_types=1);

namespace PixSicredi\Http;

/** Resposta HTTP normalizada. */
final class Response
{
    /** @var array<string,mixed>|null */
    private ?array $decoded;

    public function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {
        $decoded = json_decode($body, true);
        $this->decoded = is_array($decoded) ? $decoded : null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        return $this->decoded ?? [];
    }

    public function hasJson(): bool
    {
        return $this->decoded !== null;
    }
}
