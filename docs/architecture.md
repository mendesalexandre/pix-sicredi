# Architecture

`mendesalexandre/pix-sicredi` is a **framework-agnostic** PHP SDK for the Sicredi Pix API. It has no Laravel
dependency, owns **no database tables and ships no migrations**, and keeps no state besides an optional token
cache.

## Layers

```
PixSicredi (facade)
 ├─ cob()      Resources\Cob      immediate charge        /api/v2/cob
 ├─ cobv()     Resources\Cobv     charge with due date    /api/v2/cobv
 ├─ pix()      Resources\Pix      received Pix + refunds  /api/v2/pix
 ├─ webhook()  Resources\Webhook  register the callback   /api/v2/webhook
 ├─ webhookHandler()  Webhook\WebhookHandler   parse the callback body, emit events
 └─ auth()     Auth               OAuth2 client_credentials + mTLS, optional PSR-16 token cache
Http\HttpClient (Guzzle, http_errors=false, mTLS always, injectable handler for tests)
```

| Part | Responsibility |
|---|---|
| `Config` | Credentials, certificate and key paths, environment, optional PSR-16 cache and PSR-3 logger. Immutable. |
| `Auth` | Fetches and caches the token; one automatic retry after a `401`. |
| `Resources\*` | One class per Sicredi resource. Thin: build the request, call, return decoded JSON. |
| `Builders\CobBuilder`, `CobvBuilder` | Typed construction of the Bacen payload with validation (`expiresAt()`, CPF/CNPJ detection, amount). |
| `DTO\*` | Typed read-only views of responses: `Charge`, `ReceivedPix`, `Refund`. |
| `Enums\*` | Closed sets from the Bacen spec: `ChargeStatus`, `Environment`, fine/interest/discount modes, `PixKeyType`. |
| `Support\Txid`, `Support\PixKey` | txid generation (random or seeded for idempotency) and key-type detection. |
| `Events\PixReceivedEvent` | Emitted once per received Pix by the webhook handler. |
| `Exceptions\*` | `RequestException` keeps `statusCode`, raw body and Bacen `violacoes[]`. |

## Design decisions

- **No persistence.** The SDK returns data; storing charges, matching payments to your business records and
  marking things paid is the host application's job. This is what keeps it usable outside Laravel and free of
  schema conflicts. See [laravel-integration.md](laravel-integration.md).
- **Events, not jobs.** The webhook handler emits `PixReceivedEvent` to plain callbacks. The host decides whether
  that becomes a queued job, a framework event or a direct call.
- **Typed in, typed out, raw allowed.** Builders and DTOs are conveniences; every method also accepts and
  returns plain arrays so a field the SDK does not model yet is never a blocker.
- **Fail before the network.** Invalid txid, amount or lifetime throws `ValidationException` without calling
  the API, because the PSP's errors for these are generic (see [sicredi-api-notes.md](sicredi-api-notes.md)).
- **Cancel is not refund.** `Cob::cancel()` withdraws an unpaid charge; `Pix::refund()` returns money received.
  They are different endpoints and different methods on purpose.

## Out of scope

Batch of due charges (`lotecobv`), payload locations (`loc`), other PSPs. Tracked in [roadmap.md](roadmap.md).
