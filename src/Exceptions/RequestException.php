<?php

declare(strict_types=1);

namespace PixSicredi\Exceptions;

/**
 * Erro retornado pela API do Sicredi (HTTP >= 400 fora do fluxo de auth).
 *
 * Preserva o status e o corpo cru da resposta — a API do Sicredi devolve um
 * objeto BACEN `{type,title,status,detail,violacoes[]}` que é essencial pra
 * diagnóstico (ex: 400 "gerarToken.grantType inválido").
 */
class RequestException extends PixSicrediException
{
    /**
     * @param array<string,mixed>|null $body corpo já decodificado (se JSON)
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $rawBody = null,
        public readonly ?array $body = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    /** @return list<array{razao?:string,propriedade?:string}> */
    public function violations(): array
    {
        return $this->body['violacoes'] ?? [];
    }
}
