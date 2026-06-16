<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/**
 * Modalidade do desconto (valor.desconto.modalidade) — BACEN.
 *
 * 1 e 2 usam datas fixas (descontoDataFixa); 3–6 são por antecipação.
 */
enum DiscountMode: int
{
    case FixedValueByDate = 1;            // valor fixo até data informada
    case PercentByDate = 2;               // percentual até data informada
    case ValuePerAnticipationCalendarDay = 3;
    case ValuePerAnticipationBusinessDay = 4;
    case PercentPerAnticipationCalendarDay = 5;
    case PercentPerAnticipationBusinessDay = 6;
}
