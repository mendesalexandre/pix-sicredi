<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/** Modalidade do abatimento (valor.abatimento.modalidade) — BACEN. */
enum AbatementMode: int
{
    case FixedValue = 1;  // valor fixo
    case Percentage = 2;  // percentual
}
