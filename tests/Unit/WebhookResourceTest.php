<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Http\HttpClient;
use PixSicredi\Resources\Webhook;
use PixSicredi\Tests\Unit\Support\MocksHttp;

final class WebhookResourceTest extends TestCase
{
    use MocksHttp;

    private function webhook(HttpClient $http): Webhook
    {
        return new Webhook($this->config(), $http, $this->auth($http));
    }

    public function test_configure_sends_put_with_webhook_url(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(200, ['webhookUrl' => 'https://site/webhook']),
        ]);

        $this->webhook($http)->configure('a@b.com', 'https://site/webhook');

        $request = $this->history[1]['request'];
        self::assertSame('PUT', $request->getMethod());
        self::assertSame('/api/v2/webhook/a@b.com', $request->getUri()->getPath());
        self::assertStringContainsString('"webhookUrl":"https:\/\/site\/webhook"', (string) $request->getBody());
    }

    public function test_delete_sends_delete(): void
    {
        $http = $this->httpClient([
            $this->tokenResponse(),
            $this->jsonResponse(204, []),
        ]);

        $this->webhook($http)->delete('a@b.com');

        self::assertSame('DELETE', $this->history[1]['request']->getMethod());
        self::assertSame('/api/v2/webhook/a@b.com', $this->history[1]['request']->getUri()->getPath());
    }
}
