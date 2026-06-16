<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/** Status possíveis de uma cobrança imediata (COB) no padrão BACEN. */
enum ChargeStatus: string
{
    case Ativa = 'ATIVA';
    case Concluida = 'CONCLUIDA';
    case RemovidaPeloUsuarioRecebedor = 'REMOVIDA_PELO_USUARIO_RECEBEDOR';
    case RemovidaPeloPsp = 'REMOVIDA_PELO_PSP';

    /** Cobrança paga/liquidada. */
    public function isPaid(): bool
    {
        return $this === self::Concluida;
    }

    /** Cobrança aberta, aguardando pagamento. */
    public function isActive(): bool
    {
        return $this === self::Ativa;
    }
}
