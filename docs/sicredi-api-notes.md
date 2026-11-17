# Sicredi Pix API — verified behavior notes

What the Sicredi Pix API actually does, as opposed to what the Bacen specification says it should do. Every
entry says **how it was verified** and **when**, so you can tell evidence from assumption.

Legend: **Verified** = observed against the real Sicredi API by this project. **Spec** = taken from the Bacen
Pix API specification, not exercised by us. **Assumed** = believed, needs confirmation.

> The Sicredi API follows the [Bacen Pix API](https://bacen.github.io/pix-api/). Only the deviations and
> gotchas are listed here; read the Bacen spec for the full contract.

## Endpoints

| Item | Value | Status |
|---|---|---|
| Production base URL | `https://api-pix.sicredi.com.br` | Verified |
| Homologation base URL | `https://api-pix-h.sicredi.com.br` | Verified |
| Pix API prefix | `/api/v2` | Verified |
| OAuth token | `POST /oauth/token` — at the host root, **not** under `/api/v2` | Verified |
| Transport | mutual TLS (client certificate + private key) on every call, including the token call | Verified |

## Authentication

- Flow: OAuth2 `client_credentials`, HTTP Basic (`client_id:client_secret`) **plus** mTLS.
- **`grant_type` goes only in the form-urlencoded body.** Repeating it in the query string makes Sicredi answer
  `400` with `gerarToken.grantType inválido`. *Verified (production).*
- Tokens are short-lived (about 5 minutes). Cache them and re-authenticate once on `401`. *Verified.*
- An **expired mTLS client certificate** shows up as authentication failures, not as a clear "certificate
  expired" error. When auth suddenly breaks with unchanged credentials, check the certificate validity first.
  *Verified (production incident).*

## Immediate charge (`cob`)

### txid

- Must match `^[a-zA-Z0-9]{26,35}$`. A shorter txid is rejected with the generic message
  *"A requisição não respeita o schema ou está semanticamente errada."* — the same text used for almost every
  validation failure, so it does not tell you which field is wrong. *Verified (a 20-character txid, 2026-11-16).*
- `PUT /cob/{txid}` is idempotent: repeating the same txid does not create a second charge. *Spec.*

### Lifetime (`calendario.expiracao`)

- `expiracao` is **seconds counted from creation**. There is no "valid until" date field on `cob`; to expire at
  a given day and hour you must compute the seconds yourself (`CobBuilder::expiresAt()` does this).
- **Sicredi did not cap it at any value we tried.** Requested and echoed back unchanged, status `ATIVA`:
  7 days (`604800`), 30 days (`2592000`), 90 days (`7776000`), 400 days (`34560000`).
  *Verified against production with R$ 0.01 charges, 2026-11-16.* We did not test beyond 400 days.
- So the limit is a **policy decision of the integrator**, not the PSP's. The SINOP system uses 5 minutes to 30
  days. Long-lived charges are payable for their whole lifetime, so make sure you can cancel them (below).
- `cob` is the right type when you do not need due date, fines or interest. For those, use `cobv`.

### Debtor

- `devedor` needs a **valid** CPF (11 digits) or CNPJ (14). An empty or invalid document comes back as the same
  generic schema error as above. Validate the document yourself before calling, so the user gets a useful
  message. *Verified (production).*

### Listing (`GET /cob?inicio=&fim=`)

- **`fim` in the future is rejected with `400` and an empty body.** Use "now" or earlier. With `fim` equal to now
  the call succeeds. *Verified (2026-11-16).* The empty body makes this very hard to diagnose.
- Response shape: `{ "parametros": { "inicio", "fim", "paginacao": { "paginaAtual", "itensPorPagina",
  "quantidadeDePaginas", "quantidadeTotalDeItens" } }, "cobs": [ ... ] }`. *Verified.*
- `paginacao.itensPorPagina=100` was accepted. *Verified.*

### Cancelling a charge

- **Cancel** an `ATIVA`, unpaid charge with
  `PATCH /api/v2/cob/{txid}` and body `{"status":"REMOVIDA_PELO_USUARIO_RECEBEDOR"}`.
  Answer `200` with the charge and that status. *Verified on four charges (2026-11-16).*
  SDK: `$pix->cob()->cancel($txid)`.
- A charge that is already `CONCLUIDA` (paid) cannot be cancelled. *Spec* — not exercised.
- **Cancelling is not refunding.** A refund returns money already received and uses
  `PUT /api/v2/pix/{e2eid}/devolucao/{id}` (SDK: `$pix->pix()->refund(...)`). A path like
  `/cob/{txid}/devolucao` is **not** a cancel endpoint; an early helper in the SINOP system used it by mistake
  (it was never called). Do not name a refund method "cancel" or the reverse.

### Statuses

`ATIVA`, `CONCLUIDA`, `REMOVIDA_PELO_USUARIO_RECEBEDOR`, `REMOVIDA_PELO_PSP` (see `ChargeStatus`). *Spec.*
Expired charges are reported by the PSP; poll or use the webhook rather than computing expiry locally.

## Timestamps

- `calendario.criacao` is ISO 8601. Treat it as **UTC** and convert for display: the SINOP system does
  `criacao + expiracao` in UTC, then shows it in `America/Sao_Paulo`. *Assumed* — confirm the `Z` suffix on the
  raw payload in your own environment before relying on it.
- Brazil has no daylight saving time since 2019, so Cuiabá (UTC−4) is always exactly one hour behind Brasília
  (UTC−3). Customer-facing text should always say which timezone it uses.

## Webhook

- Sicredi `POST`s to the registered URL when a Pix is received, authenticating with a **client certificate
  (mTLS)**. Verify it at the edge (for nginx: `ssl_verify_client on`) or in the app; this SDK only parses.
  *Spec / Sicredi documentation.* See [webhook.md](webhook.md).
- Registration is per Pix key: `PUT /webhook/{chave}`. *Spec.*

## Errors

- Most validation errors share one generic message (see above). The Bacen error object may carry
  `violacoes[]` (field + reason); the SDK keeps it in `RequestException::$violacoes` — **log it**, it is usually
  the only place that names the offending field.
- Some failures come back with an **empty body** (future `fim`). Log the HTTP status too.

## Things we have not verified

- Maximum `expiracao` above 400 days.
- Behavior of cancelling an already-expired charge.
- `cobv` end to end in production (covered by unit tests with a mocked HTTP layer only).
- Webhook retries/redelivery policy and ordering.
