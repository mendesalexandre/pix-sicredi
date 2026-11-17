# Using the SDK in Laravel — without migrations

This package is not a Laravel package and **creates no tables**. The host application owns persistence. This
page shows the wiring and compares the approach with
[`mateusjunges/laravel-pix`](https://github.com/mateusjunges/laravel-pix).

## Why no migrations

- A package that ships tables forces its column names and timestamps on every adopter and has to be migrated
  (and rolled back) with the host schema. Existing systems already have a table for charges.
- The SDK's job is the conversation with the PSP. What a charge *means* to your business (which order it pays,
  who may cancel it) is yours.
- The SDK's data is small and fully returned by the API: `txid`, `status`, `copyPaste`, `location`, creation and
  expiration. Store what you need.

## What the host stores

A minimal charge record (name the table and columns to match your conventions):

| Field | Source |
|---|---|
| `txid` (unique) | `Txid::random()` / `Txid::fromSeed($businessKey)` or the PSP's answer |
| `status` | `Charge::fromArray(...)->status` |
| `copy_paste` | `Charge::copyPaste` — the QR code is generated from this string |
| `amount` | what you sent |
| `created_at`, `lifetime_seconds` | `calendario.criacao`, `calendario.expiracao` — expiry is their sum |
| your business reference | e.g. order id |

## Wiring

```php
// AppServiceProvider::register()
$this->app->singleton(PixSicredi::class, fn () => new PixSicredi(new Config(
    clientId:        config('services.sicredi.client_id'),
    clientSecret:    config('services.sicredi.client_secret'),
    certificatePath: config('services.sicredi.certificate'),
    privateKeyPath:  config('services.sicredi.private_key'),
    environment:     Environment::Production,
    cache:           $this->app->make('cache.store'),   // PSR-16: caches the ~5 min token
    logger:          $this->app->make('log'),
)));
```

Create a charge valid until tomorrow at 08:00 (the PSP only takes seconds, so convert):

```php
$until = CarbonImmutable::tomorrow('America/Cuiaba')->setTime(8, 0);

$answer = $pix->cob()->create(Txid::fromSeed("order-{$order->id}"), CobBuilder::make()
    ->expiresAt($until, maxSeconds: 30 * 86400)
    ->debtor($order->cpfCnpj, $order->customerName)
    ->amount($order->total)
    ->pixKey(config('services.sicredi.pix_key')));
```

Cancel it:

```php
$pix->cob()->cancel($charge->txid);        // only works while ATIVA and unpaid
```

Webhook route (the handler returns nothing; you answer `200`):

```php
Route::post('/webhook/pix', function (Request $request, PixSicredi $pix) {
    $pix->webhookHandler()
        ->onPixReceived(fn (PixReceivedEvent $e) => SettlePix::dispatch($e->pix->txid, $e->pix->endToEndId))
        ->handle($request->getContent());

    return response()->json([], 200);
});
```

## Comparison with `mateusjunges/laravel-pix`

Facts below are from reading a local clone of that code base; check its current README for changes.

| | `pix-sicredi` (this) | `laravel-pix` |
|---|---|---|
| Framework | none (PSR-16/PSR-3/Guzzle) | Laravel (service provider, facade, published config) |
| PSP | Sicredi only, tested against its real API | several PSPs through a generic API |
| Database | none, no migrations | none in the clone we read (config + facade) |
| Strength | Sicredi quirks documented and handled (grant_type, mTLS, listing, cancel) | QR/payload generation, wide spec coverage |
| Webhook | `PixReceivedEvent` via callbacks | Laravel events |

They are not mutually exclusive: use `laravel-pix` for offline BR Code/payload generation if you need it, and
this SDK for the Sicredi API conversation.
