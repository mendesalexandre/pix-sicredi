# Security

Scope of this page: what the SDK does for you, what it deliberately does not, and what the host application
must do. Written against the SAEB (Secure Application Engineering Baseline) conventions: say what is proven and what
is pending; never claim "secure".

## What the SDK does

- Sends **every** request, including the token call, over **mutual TLS** with the client certificate and key you
  configure. There is no code path that skips the client certificate.
- Keeps the token only in the PSR-16 cache you provide (or in memory); the lifetime follows `expires_in` minus a
  safety margin. It never writes credentials to disk or logs.
- Validates `txid`, amount, lifetime and CPF/CNPJ **before** calling the API.
- Parses webhook bodies strictly and can ignore Pix addressed to keys you did not expect
  (`withExpectedKeys()`).

## What the SDK does not do

- It does **not authenticate webhook callers**. Anyone who can reach your URL can post a forged notification
  unless you verify the client certificate at the edge. See [webhook.md](webhook.md).
- It does not persist anything, rate-limit, or deduplicate. Idempotency on `endToEndId` is the host's job.
- It does not prove a payment: a notification is a hint. Confirm with `cob()->get($txid)` before releasing
  goods or marking a record paid.

## Host application checklist

1. **Secrets out of the repository.** Certificate, private key, client id and secret come from the environment
   or a secret store. Test fixtures in this repo are dummy placeholders (`DUMMY-CERT-FOR-TESTS`).
2. **Protect the private key file**: readable only by the service user; not inside the web root; not in images
   built for distribution.
3. **Track certificate expiry.** An expired client certificate shows up as generic authentication failures
   ([sicredi-api-notes.md](sicredi-api-notes.md)). Alert well before the date.
4. **Verify the webhook at the edge** (mTLS with the PSP's CA) and keep the webhook URL non-guessable.
5. **Do not log** tokens, full webhook payloads or payer documents. Log `txid`, `endToEndId`, HTTP status and
   `RequestException::$violacoes`.
6. **Authorize cancellation and refunds** like any money-moving action. A charge that lives for weeks is payable
   for weeks; make sure the right people can cancel it.
7. **Make effects idempotent** and run them after the database transaction commits.
8. **Use homologation for experiments.** Integration tests that hit production must use the smallest amount
   (R$ 0.01) and cancel what they create.

## Reporting a vulnerability

Please open a private security advisory on the GitHub repository rather than a public issue.

## Status

Experimental (`0.x`). Unit tests use a mocked HTTP layer. Production behavior was exercised manually for
charge creation, lifetime, listing and cancellation (dates in [sicredi-api-notes.md](sicredi-api-notes.md)); the
webhook receiver has unit tests only. No external security audit has been done.
