<?php

declare(strict_types=1);

namespace PixSicredi;

use PixSicredi\Enums\Ambiente;
use PixSicredi\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Configuração da integração PIX Sicredi.
 *
 * Autenticação é mTLS: o Sicredi exige o certificado da APLICAÇÃO (cliente) +
 * chave privada. A senha da chave é opcional (depende de como o .key foi gerado).
 * `caminhoCadeiaCa` (opcional) aponta pra cadeia completa da AC do Sicredi pra
 * validar o servidor (CURLOPT_CAINFO); se null, usa o bundle de CAs do sistema.
 *
 * `cache` (PSR-16) e `logger` (PSR-3) são opcionais — o consumidor pluga os dele
 * (ex: o cache do Laravel) sem o pacote depender de nenhum framework.
 */
final class Config
{
    /** @var list<string> */
    public readonly array $scopes;

    /**
     * @param list<string>|null $scopes scopes OAuth; null usa o conjunto padrão
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $caminhoCertificado,
        public readonly string $caminhoChave,
        public readonly Ambiente $ambiente = Ambiente::Producao,
        public readonly ?string $senhaChave = null,
        public readonly ?string $caminhoCadeiaCa = null,
        public readonly int $timeout = 30,
        ?array $scopes = null,
        public readonly ?CacheInterface $cache = null,
        public readonly ?LoggerInterface $logger = null,
    ) {
        if ($clientId === '' || $clientSecret === '') {
            throw new ValidationException('client_id e client_secret são obrigatórios.');
        }
        if (! is_readable($caminhoCertificado)) {
            throw new ValidationException("Certificado não encontrado/ilegível: {$caminhoCertificado}");
        }
        if (! is_readable($caminhoChave)) {
            throw new ValidationException("Chave privada não encontrada/ilegível: {$caminhoChave}");
        }

        $this->scopes = $scopes ?? self::scopesPadrao();
    }

    /**
     * Conjunto de scopes para COB + Webhook + Devolução (escopo usado pelo SINOP).
     * COBV/LoteCobv ficam de fora de propósito — não são usados.
     *
     * @return list<string>
     */
    public static function scopesPadrao(): array
    {
        return [
            'cob.read',
            'cob.write',
            'pix.read',
            'pix.write',
            'webhook.read',
            'webhook.write',
        ];
    }

    public function baseUrl(): string
    {
        return $this->ambiente->baseUrl();
    }
}
