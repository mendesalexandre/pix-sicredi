<?php

declare(strict_types=1);

namespace PixSicredi;

use PixSicredi\Enums\Environment;
use PixSicredi\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Configuração da integração PIX Sicredi.
 *
 * A autenticação é mTLS: o Sicredi exige o certificado da APLICAÇÃO (cliente) +
 * a chave privada. A senha da chave (`keyPassword`) é opcional (depende de como
 * o .key foi gerado). `caBundlePath` (opcional) aponta pra cadeia da AC do
 * Sicredi pra validar o servidor; se null, usa o bundle de CAs do sistema.
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
        public readonly string $certificatePath,
        public readonly string $privateKeyPath,
        public readonly Environment $environment = Environment::Production,
        public readonly ?string $keyPassword = null,
        public readonly ?string $caBundlePath = null,
        public readonly int $timeout = 30,
        ?array $scopes = null,
        public readonly ?CacheInterface $cache = null,
        public readonly ?LoggerInterface $logger = null,
    ) {
        if ($clientId === '' || $clientSecret === '') {
            throw new ValidationException('client_id e client_secret são obrigatórios.');
        }
        if (! is_readable($certificatePath)) {
            throw new ValidationException("Certificado não encontrado/ilegível: {$certificatePath}");
        }
        if (! is_readable($privateKeyPath)) {
            throw new ValidationException("Chave privada não encontrada/ilegível: {$privateKeyPath}");
        }

        $this->scopes = $scopes ?? self::defaultScopes();
    }

    /**
     * Scopes para COB + COBV + Webhook + Devolução.
     * LoteCobv fica de fora de propósito.
     *
     * @return list<string>
     */
    public static function defaultScopes(): array
    {
        return [
            'cob.read',
            'cob.write',
            'cobv.read',
            'cobv.write',
            'pix.read',
            'pix.write',
            'webhook.read',
            'webhook.write',
        ];
    }

    public function baseUrl(): string
    {
        return $this->environment->baseUrl();
    }
}
