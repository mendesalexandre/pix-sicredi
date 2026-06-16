<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/**
 * Modalidade dos juros (valor.juros.modalidade) — BACEN.
 *
 * 1–4 contam em dias corridos; 5–8 em dias úteis.
 */
enum InterestMode: int
{
    case ValuePerCalendarDay = 1;       // valor (dias corridos)
    case PercentPerCalendarDay = 2;     // percentual ao dia (dias corridos)
    case PercentPerCalendarMonth = 3;   // percentual ao mês (dias corridos)
    case PercentPerCalendarYear = 4;    // percentual ao ano (dias corridos)
    case ValuePerBusinessDay = 5;       // valor (dias úteis)
    case PercentPerBusinessDay = 6;     // percentual ao dia (dias úteis)
    case PercentPerBusinessMonth = 7;   // percentual ao mês (dias úteis)
    case PercentPerBusinessYear = 8;    // percentual ao ano (dias úteis)
}
