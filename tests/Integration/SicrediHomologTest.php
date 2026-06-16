<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PixSicredi\Builders\CobBuilder;
use PixSicredi\Config;
use PixSicredi\DTO\Charge;
use PixSicredi\Enums\Environment;
use PixSicredi\PixSicredi;
use PixSicredi\Support\Txid;

/**
 * Testa contra a API REAL do Sicredi (homologação por padrão). NÃO roda no CI:
 * é pulado automaticamente quando as variáveis de ambiente não estão definidas.
 *
 * Pra rodar localmente, exporte:
 *   PIXSICREDI_CLIENT_ID, PIXSICREDI_CLIENT_SECRET,
 *   PIXSICREDI_CERT (caminho .pem), PIXSICREDI_KEY (caminho .key),
 *   PIXSICREDI_PIX_KEY (chave recebedora)
 * Opcionais: PIXSICREDI_KEY_PASS, PIXSICREDI_AMOUNT (default 0.01),
 *            PIXSICREDI_ENV (homologation|production, default homologation),
 *            PIXSICREDI_CA (cadeia de CAs).
 *
 * Depois: `vendor/bin/phpunit --testsuite Integration`
 */
final class SicrediHomologTest extends TestCase
{
    private PixSicredi $pix;
    private string $pixKey;

    protected function setUp(): void
    {
        foreach (['PIXSICREDI_CLIENT_ID', 'PIXSICREDI_CLIENT_SECRET', 'PIXSICREDI_CERT', 'PIXSICREDI_KEY', 'PIXSICREDI_PIX_KEY'] as $var) {
            if (getenv($var) === false || getenv($var) === '') {
                $this->markTestSkipped("Defina {$var} pra rodar os testes de integração.");
            }
        }

        $env = getenv('PIXSICREDI_ENV') === 'production' ? Environment::Production : Environment::Homologation;

        $this->pixKey = (string) getenv('PIXSICREDI_PIX_KEY');
        $this->pix = new PixSicredi(new Config(
            clientId: (string) getenv('PIXSICREDI_CLIENT_ID'),
            clientSecret: (string) getenv('PIXSICREDI_CLIENT_SECRET'),
            certificatePath: (string) getenv('PIXSICREDI_CERT'),
            privateKeyPath: (string) getenv('PIXSICREDI_KEY'),
            environment: $env,
            keyPassword: getenv('PIXSICREDI_KEY_PASS') ?: null,
            caBundlePath: getenv('PIXSICREDI_CA') ?: null,
        ));
    }

    public function test_authenticates(): void
    {
        $token = $this->pix->auth()->getToken();

        self::assertNotEmpty($token);
    }

    public function test_creates_and_reads_immediate_charge(): void
    {
        $txid = Txid::random();
        $amount = (string) (getenv('PIXSICREDI_AMOUNT') ?: '0.01');

        $created = $this->pix->cob()->create($txid, CobBuilder::make()
            ->expiration(3600)
            ->debtor('12345678909', 'Cliente Teste')
            ->amount($amount)
            ->pixKey($this->pixKey)
            ->payerRequest('Teste de integracao'));

        self::assertSame($txid, $created['txid'] ?? null);

        $charge = Charge::fromArray($this->pix->cob()->get($txid));

        self::assertSame($txid, $charge->txid);
        self::assertTrue($charge->isActive(), 'Cobrança deveria estar ATIVA');
        self::assertNotNull($charge->copyPaste, 'pixCopiaECola deveria vir preenchido');
    }
}
