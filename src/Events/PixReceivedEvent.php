<?php

declare(strict_types=1);

namespace PixSicredi\Events;

use PixSicredi\DTO\ReceivedPix;

/**
 * Disparado pelo WebhookHandler quando um pix é recebido (pago), pra cada item
 * da notificação do Sicredi.
 *
 * Registre um listener com `WebhookHandler::onPixReceived()` pra reagir — no
 * cartório, é onde a baixa da cobrança/O.S. acontece. Em Laravel, o listener
 * normalmente só re-emite um evento nativo ou despacha um job.
 */
final class PixReceivedEvent
{
    public function __construct(
        public readonly ReceivedPix $pix,
    ) {
    }
}
