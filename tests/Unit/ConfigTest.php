<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Config;
use PixSicredi\Enums\Environment;
use PixSicredi\Exceptions\ValidationException;

final class ConfigTest extends TestCase
{
    private string $cert;
    private string $key;

    protected function setUp(): void
    {
        $this->cert = __DIR__ . '/../fixtures/cert.pem';
        $this->key = __DIR__ . '/../fixtures/app.key';
    }

    private function config(?array $scopes = null): Config
    {
        return new Config(
            clientId: 'id',
            clientSecret: 'secret',
            certificatePath: $this->cert,
            privateKeyPath: $this->key,
            environment: Environment::Homologation,
            scopes: $scopes,
        );
    }

    public function test_default_scopes_cover_cob_pix_webhook_without_cobv(): void
    {
        $scopes = $this->config()->scopes;

        self::assertContains('cob.write', $scopes);
        self::assertContains('webhook.write', $scopes);
        self::assertContains('pix.read', $scopes);
        self::assertNotContains('cobv.write', $scopes);
        self::assertNotContains('lotecobv.write', $scopes);
    }

    public function test_accepts_custom_scopes(): void
    {
        self::assertSame(['cob.read'], $this->config(['cob.read'])->scopes);
    }

    public function test_base_url_comes_from_environment(): void
    {
        self::assertSame('https://api-pix-h.sicredi.com.br', $this->config()->baseUrl());
    }

    public function test_throws_when_credentials_empty(): void
    {
        $this->expectException(ValidationException::class);
        new Config('', '', $this->cert, $this->key);
    }

    public function test_throws_when_certificate_missing(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificado não encontrado');
        new Config('id', 'secret', '/nao/existe/cert.pem', $this->key);
    }
}
