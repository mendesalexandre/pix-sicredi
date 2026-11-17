# Project instructions

Rules for developing and reviewing this repository, derived from SAEB (Secure Application Engineering Baseline)
and adapted to a framework-agnostic PHP library. Do not assume that reading this file installs any control;
enforcement belongs in CI.

## Before changing anything

- Read `README.md`, `docs/` (especially `docs/sicredi-api-notes.md` and `docs/architecture.md`) and the tests of
  the area you touch.
- Real commands: `composer test` (PHPUnit), `composer stan` (PHPStan level 8). CI runs both on PHP 8.1–8.4.
- Preserve local user changes. Do not rename public classes, methods or payload keys without need — this is a
  published API.

## Code and language

- **Code identifiers in English** (classes, methods, properties, enum cases, test names). Classes are singular.
- **Documentation in English.** PHPDoc and comments in new code in English; translate old Portuguese ones when
  you touch the line.
- Public method signatures are typed; `declare(strict_types=1)` everywhere.
- Keep the SDK framework-agnostic: no Laravel/Symfony imports in `src/`. Integration code belongs in docs or a
  separate package.
- **No database: no migrations, no models, no tables, no filesystem state.** The host application owns
  persistence. A change that needs storage is a design change and must be discussed first.
- Every method accepts and returns plain arrays as well as typed objects, so a field the SDK does not model is
  never a blocker.

## Security

- Never commit secrets, real certificates, private keys, tokens, client ids/secrets, Pix keys, payer documents
  or webhook payloads. Fixtures are dummy values; use synthetic data in tests and docs.
- mTLS is always on. Do not add a code path that skips the client certificate.
- Never log tokens, credentials or full payloads. Error objects may carry `violacoes[]`; that is fine to log.
- Validate inputs before calling the PSP (txid, amount, lifetime, CPF/CNPJ). The PSP's error text is generic.
- The webhook handler only parses. Authenticating the caller (mTLS at the edge) is the host's duty; keep that
  stated in `docs/webhook.md` and `docs/security.md`.
- Cancelling a charge and refunding a payment are different operations with different endpoints; never merge or
  mis-name them.

## Tests and quality

- A behavior change needs proportional tests, including failure/denial and regressions. Assert the exact HTTP
  method, path and body for new endpoints (see `tests/Unit/Support/MocksHttp.php`).
- Unit tests never touch the network. Integration tests (`tests/Integration`) must auto-skip without
  credentials, use R$ 0.01, and cancel what they create.
- Do not weaken assertions or PHPStan level to make a check pass. Fix the cause or document the limitation.
- A tool that is missing, a timeout, or a test that was not run is reported as such, never as a pass.

## Documentation of Sicredi behavior

- When a real call teaches something (a limit, an error, an undocumented rule), record it in
  `docs/sicredi-api-notes.md` with **how it was verified and the date**. Mark anything not exercised as *Spec*
  or *Assumed*.
- Never state that the library is "secure" or "compliant". State the scope that was proven and what is pending.

## Delivery

- Conventional commits (`feat:`, `fix:`, `docs:`, `chore:`, `test:`), in English. **No `Co-Authored-By` trailers
  for AI assistants in this repository.** Commit directly to `main`; tag releases (`vX.Y.Z`).
- After checks pass, update `README.md` and the relevant page in `docs/`, and `docs/roadmap.md` if scope moved.
- State what changed, how it was validated, and the limits.
