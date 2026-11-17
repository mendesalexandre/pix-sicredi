# pix-sicredi

Framework-agnostic PHP SDK for the **Sicredi** Pix API: immediate charges (`cob`), charges with due date
(`cobv`), webhook and refunds, with **mTLS** authentication.

- No framework, **no database, no migrations** — you keep persistence in your application.
- Typed builders, DTOs and enums, and plain arrays whenever you prefer them.
- Documents what the Sicredi API actually does, with evidence: [docs/sicredi-api-notes.md](docs/sicredi-api-notes.md).

> Status: **experimental (`0.x`)**. Unit-tested with mocked HTTP; real-API behavior verified manually and dated in
> the notes. Out of scope on purpose: batch of due charges (`lotecobv`) and payload locations (`loc`).

## Requirements

- PHP 8.1+, `ext-curl`, `ext-json`
- The application's **client certificate** and private key issued by Sicredi

## Installation

```bash
composer require mendesalexandre/pix-sicredi
```

## Usage

```php
use PixSicredi\PixSicredi;
use PixSicredi\Config;
use PixSicredi\Enums\Environment;

$pix = new PixSicredi(new Config(
    clientId:        'YOUR_CLIENT_ID',
    clientSecret:    'YOUR_CLIENT_SECRET',
    certificatePath: '/path/certificate.pem',
    privateKeyPath:  '/path/application.key',
    environment:     Environment::Production,  // or Homologation
    keyPassword:     null,                     // if the private key has a passphrase
    cache:           $psr16Cache,              // optional: caches the token (~5 min)
    logger:          $psr3Logger,              // optional
));
```

### Create an immediate charge (`cob`)

```php
use PixSicredi\Builders\CobBuilder;
use PixSicredi\Support\Txid;
use PixSicredi\DTO\Charge;

$txid = Txid::fromSeed('ORDER-537253'); // deterministic => idempotent (or Txid::random())

$answer = $pix->cob()->create($txid, CobBuilder::make()
    ->expiration(3600)                         // seconds from creation
    ->debtor('05314742160', 'Jane Doe')        // CPF (11) or CNPJ (14), auto-detected
    ->amount('3046.18')
    ->pixKey('billing@example.com')
    ->payerRequest('Order 537253')
    ->addInfo('Order', '537253'));

$charge = Charge::fromArray($answer);
echo $charge->status;     // "ATIVA"
echo $charge->copyPaste;  // "00020126..." — build the QR code from this string
echo $charge->location;   // payload URL

$charge = Charge::fromArray($pix->cob()->get($txid));
```

Arrays work too: `create($txid, [...])`. The `txid` is validated (`[a-zA-Z0-9]{26,35}`) before any request, and a
`401` triggers one automatic re-authentication.

### Valid until a given day and hour

`cob` only takes a lifetime in **seconds from creation**. `expiresAt()` converts "until day X at 08:00":

```php
$pix->cob()->create($txid, CobBuilder::make()
    ->expiresAt(new DateTimeImmutable('2026-12-01 08:00', new DateTimeZone('America/Cuiaba')),
                maxSeconds: 30 * 86400)        // optional policy limit; Sicredi itself accepted 400+ days
    ->debtor('05314742160', 'Jane Doe')
    ->amount('100.00')
    ->pixKey('billing@example.com'));
```

### Cancel a charge

```php
$pix->cob()->cancel($txid);   // PATCH status REMOVIDA_PELO_USUARIO_RECEBEDOR — only while ATIVA and unpaid
```

This is **not** a refund (see below).

### Charge with due date (`cobv`)

Has a due date, a **mandatory debtor address** and charges (fine, interest, discount, abatement) typed by enum:

```php
use PixSicredi\Builders\CobvBuilder;
use PixSicredi\Enums\{FineMode, InterestMode, DiscountMode};

$pix->cobv()->create($txid, CobvBuilder::make()
    ->dueDate('2026-12-31')
    ->validityAfterDue(30)
    ->debtor('05314742160', 'Jane Doe')
    ->debtorAddress('Street X, 100', 'Sinop', 'MT', '78550000')
    ->amount('100.00')
    ->pixKey('billing@example.com')
    ->fine(FineMode::Percentage, '2.00')
    ->interest(InterestMode::PercentPerCalendarMonth, '1.00')
    ->discountByDate(DiscountMode::PercentByDate, [['date' => '2026-12-20', 'value' => '5.00']]));

$charge = Charge::fromArray($pix->cobv()->get($txid)); // also exposes ->dueDate
```

### Webhook

Register your URL, then receive notifications. Full guidance in [docs/webhook.md](docs/webhook.md).

```php
$pix->webhook()->configure('billing@example.com', 'https://example.com/webhook/pix');
```

```php
use PixSicredi\Events\PixReceivedEvent;

$handler = $pix->webhookHandler()
    ->withExpectedKeys(['billing@example.com'])
    ->onPixReceived(function (PixReceivedEvent $event) {
        // dispatch your own job/event; $event->pix->txid, ->endToEndId, ->amount
    });

if ($handler->isValidationCall($rawBody)) {
    return ok();
}
$handler->handle($rawBody);   // parse + emit one event per received Pix
```

> **Security:** Sicredi authenticates the webhook with **mTLS**. Verify the client certificate at the edge (nginx
> `ssl_verify_client on`) or in your app — the handler only parses.

### Refund

```php
use PixSicredi\DTO\Refund;

$refund = Refund::fromArray($pix->pix()->refund($endToEndId, 'refundId01', '10.00', 'reason'));
$refund->isCompleted(); // status DEVOLVIDO
```

### Validate / detect a Pix key

```php
use PixSicredi\Support\PixKey;

PixKey::type('billing@example.com'); // PixKeyType::Email
PixKey::type('+5566999998888');      // PixKeyType::Phone
PixKey::isValid('05314742160');      // true (CPF)
```

## Implementation notes

- **Auth:** OAuth2 `client_credentials` + Basic + mTLS. `grant_type` goes **only in the body**; repeating it in the
  token URL query makes Sicredi answer `400 gerarToken.grantType inválido`.
- **Endpoints:** base `https://api-pix.sicredi.com.br`, API under `/api/v2`, OAuth at `/oauth/token`.
- **Errors:** `RequestException` keeps `statusCode`, the raw body and `violacoes[]` (Bacen error object).
- More quirks (listing, lifetime limits, cancel vs refund): [docs/sicredi-api-notes.md](docs/sicredi-api-notes.md).

## Documentation

See [docs/](docs/README.md): architecture, webhook, Laravel integration without migrations, security, testing,
roadmap and the Sicredi API notes.

## Tests

```bash
composer test   # PHPUnit (unit, mocked HTTP)
composer stan   # PHPStan level 8
```

Integration tests against the real API auto-skip without credentials — see [docs/testing.md](docs/testing.md).

## License

MIT.
