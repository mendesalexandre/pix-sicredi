# Testing

Real commands, nothing aspirational.

```bash
composer install
composer test    # PHPUnit: unit tests, HTTP mocked with Guzzle's MockHandler (no network)
composer stan    # PHPStan level 8
```

Current state of this tree: 65 tests / 158 assertions pass, 2 integration tests are skipped without
credentials, PHPStan reports no errors. CI runs the same on PHP 8.1–8.4 (`.github/workflows/ci.yml`).

## Unit tests

- `tests/Unit/Support/MocksHttp.php` builds an `HttpClient` over a `MockHandler` and records the request
  history, so tests assert **the exact method, path, headers and body** sent — for example that `cancel()` sends
  `PATCH /api/v2/cob/{txid}` and never touches `/devolucao`.
- Cover failure as well as success: invalid txid does not call the API; `401` triggers exactly one retry;
  `expiresAt()` rejects too-close and too-far moments.
- Fixtures contain dummy certificates only. Never add a real certificate, key, document or token.

## Integration tests (real API)

`tests/Integration/SicrediHomologTest.php` talks to the real Sicredi API and **skips itself** when the
environment is not set, which keeps CI green and prevents accidental real charges.

```bash
export PIXSICREDI_CLIENT_ID=...       PIXSICREDI_CLIENT_SECRET=...
export PIXSICREDI_CERT=/path/cert.pem PIXSICREDI_KEY=/path/app.key
export PIXSICREDI_PIX_KEY=your-receiving-key
# optional: PIXSICREDI_KEY_PASS, PIXSICREDI_AMOUNT (default 0.01),
#           PIXSICREDI_ENV (homologation|production), PIXSICREDI_CA

vendor/bin/phpunit --testsuite Integration
```

Rules: homologation first; if you must use production, keep the amount at **R$ 0.01**, use a clearly named
payer, and **cancel what you create** (`$pix->cob()->cancel($txid)`). Do not leave long-lived test charges
behind — a 400-day charge is payable for 400 days.

## Limits of the evidence

Unit tests prove the SDK builds the requests we expect. They do not prove Sicredi accepts them; that is what
[sicredi-api-notes.md](sicredi-api-notes.md) records, with dates, from manual runs against the real API.
Skipped or unrun checks are reported as such, never as passes.
