# Roadmap

Status of each item is honest: *done* means merged and tested; *planned* means not started.

## Done
- COB, COBV, Webhook, refunds, mTLS auth with token cache, typed builders/DTOs/enums — `v0.1`–`v0.3`.
- `Cob::cancel()` and `CobBuilder::expiresAt()` — lifetime until a date/time, charge cancellation.

## Planned
1. English documentation for everything under `docs/` (this set is the start); keep code identifiers in English.
2. Publish on Packagist so `composer require mendesalexandre/pix-sicredi` works.
3. Optional thin Laravel bridge **without migrations**: a service provider, config file, a facade and a
   verified-webhook route helper. Persistence stays in the host app
   ([laravel-integration.md](laravel-integration.md)).
4. `Cobv` verification against the real API (today: mocked unit tests only).
5. Batch of due charges (`lotecobv`) and payload locations (`loc`) if someone needs them.
6. Webhook redelivery/ordering notes once observed in production.
7. Upgrade PHPStan to 2.x (the tree is on 1.12).

## Non-goals
Other PSPs, a UI, and any database schema.
