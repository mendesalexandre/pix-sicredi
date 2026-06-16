<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/** Status de uma devolução PIX — BACEN. */
enum RefundStatus: string
{
    case EmProcessamento = 'EM_PROCESSAMENTO';
    case Devolvido = 'DEVOLVIDO';
    case NaoRealizado = 'NAO_REALIZADO';

    public function isCompleted(): bool
    {
        return $this === self::Devolvido;
    }
}
