<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/** Modalidade da multa (valor.multa.modalidade) — BACEN. */
enum FineMode: int
{
    case FixedValue = 1;  // valor fixo
    case Percentage = 2;  // percentual
}
