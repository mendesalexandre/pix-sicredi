<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Config;
use PixSicredi\Enums\Ambiente;
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
            caminhoCertificado: $this->cert,
            caminhoChave: $this->key,
            ambiente: Ambiente::Homologacao,
            scopes: $scopes,
        );
    }

    public function test_scopes_padrao_cobrem_cob_pix_webhook_sem_cobv(): void
    {
        $scopes = $this->config()->scopes;

        self::assertContains('cob.write', $scopes);
        self::assertContains('webhook.write', $scopes);
        self::assertContains('pix.read', $scopes);
        self::assertNotContains('cobv.write', $scopes);
        self::assertNotContains('lotecobv.write', $scopes);
    }

    public function test_aceita_scopes_customizados(): void
    {
        self::assertSame(['cob.read'], $this->config(['cob.read'])->scopes);
    }

    public function test_base_url_vem_do_ambiente(): void
    {
        self::assertSame('https://api-pix-h.sicredi.com.br', $this->config()->baseUrl());
    }

    public function test_lanca_quando_credenciais_vazias(): void
    {
        $this->expectException(ValidationException::class);
        new Config('', '', $this->cert, $this->key);
    }

    public function test_lanca_quando_certificado_nao_existe(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Certificado não encontrado');
        new Config('id', 'secret', '/nao/existe/cert.pem', $this->key);
    }
}
