# Webhook

Sicredi calls your URL when a Pix is received. The SDK parses the body and emits one `PixReceivedEvent` per Pix.
It does **not** authenticate the caller — see Security.

## Register

```php
$pix->webhook()->configure('your-pix-key', 'https://example.com/webhook/pix');
$pix->webhook()->get('your-pix-key');
$pix->webhook()->delete('your-pix-key');
```

Registration is per Pix key (`PUT /webhook/{key}`).

## Receive

```php
use PixSicredi\Events\PixReceivedEvent;

$handler = $pix->webhookHandler()
    ->withExpectedKeys(['your-pix-key'])          // extra defense: ignore Pix for other keys
    ->onPixReceived(function (PixReceivedEvent $event) {
        // dispatch your own job/event here; keep this callback fast
        // $event->pix->txid, ->endToEndId, ->amount
    });

if ($handler->isValidationCall($rawBody)) {
    return ok(); // Sicredi's registration ping
}

$handler->handle($rawBody); // parse + emit one event per Pix
return ok();
```

Only want the data? `$handler->parse($rawBody)` returns `ReceivedPix[]` without emitting events.

## Rules for the host application

1. **Answer fast with `200`**, then do the work asynchronously. Slow answers cause redelivery.
2. **Be idempotent.** Key your side effect on `endToEndId` (unique per Pix) — never assume one delivery.
3. **Confirm before crediting.** Treat the webhook as a hint and, for anything that moves money, confirm the
   charge status with `cob()->get($txid)` before marking a business record as paid.
4. **Authenticate at the edge.** Sicredi presents a client certificate (mTLS). Verify it in the reverse proxy
   (nginx: `ssl_verify_client on` with the Sicredi CA) or in the app. Without that, anyone who finds the URL can
   forge a payment notification.
5. **Do not log full payloads** — they contain payer data. Log `txid` and `endToEndId`.

## Not verified yet

Sicredi's redelivery policy and ordering guarantees are not documented by us; design for duplicates and
reordering. Polling `cob()->list()` with `fim = now` (see [sicredi-api-notes.md](sicredi-api-notes.md)) is a
sound fallback if a notification is missed.
