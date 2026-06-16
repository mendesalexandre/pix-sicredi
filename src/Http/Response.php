<?php

declare(strict_types=1);

namespace PixSicredi\Http;

/** Resposta HTTP normalizada. */
final class Response
{
    /** @var array<string,mixed>|null */
    private ?array $json;

    public function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {
        $decoded = json_decode($body, true);
        $this->json = is_array($decoded) ? $decoded : null;
    }

    public function sucesso(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /** @return array<string,mixed> */
    public function json(): array
    {
        return $this->json ?? [];
    }

    public function temJson(): bool
    {
        return $this->json !== null;
    }
}
