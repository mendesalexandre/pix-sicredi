<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Builders\CobvBuilder;
use PixSicredi\Enums\AbatementMode;
use PixSicredi\Enums\DiscountMode;
use PixSicredi\Enums\FineMode;
use PixSicredi\Enums\InterestMode;
use PixSicredi\Exceptions\ValidationException;

final class CobvBuilderTest extends TestCase
{
    private function base(): CobvBuilder
    {
        return CobvBuilder::make()
            ->dueDate('2026-12-31')
            ->debtor('05314742160', 'Fulano')
            ->debtorAddress('Rua X, 100', 'Sinop', 'mt', '78550-000')
            ->amount('100.00')
            ->pixKey('financeiro@cartorio.com.br');
    }

    public function test_builds_full_payload_with_charges(): void
    {
        $payload = $this->base()
            ->validityAfterDue(30)
            ->fine(FineMode::Percentage, '2.00')
            ->interest(InterestMode::PercentPerCalendarMonth, '1.00')
            ->abatement(AbatementMode::FixedValue, '5.00')
            ->discountByDate(DiscountMode::PercentByDate, [['date' => '2026-12-20', 'value' => '5.00']])
            ->toArray();

        self::assertSame('2026-12-31', $payload['calendario']['dataDeVencimento']);
        self::assertSame(30, $payload['calendario']['validadeAposVencimento']);
        self::assertSame('cpf', array_key_first($payload['devedor']));
        self::assertSame('MT', $payload['devedor']['uf']);          // normalizado
        self::assertSame('78550000', $payload['devedor']['cep']);   // só dígitos
        self::assertSame(['modalidade' => 2, 'valorPerc' => '2.00'], $payload['valor']['multa']);
        self::assertSame(['modalidade' => 3, 'valorPerc' => '1.00'], $payload['valor']['juros']);
        self::assertSame(['modalidade' => 1, 'valorPerc' => '5.00'], $payload['valor']['abatimento']);
        self::assertSame(2, $payload['valor']['desconto']['modalidade']);
        self::assertSame([['data' => '2026-12-20', 'valorPerc' => '5.00']], $payload['valor']['desconto']['descontoDataFixa']);
    }

    public function test_anticipation_discount_uses_valorPerc(): void
    {
        $payload = $this->base()
            ->discountByAnticipation(DiscountMode::PercentPerAnticipationCalendarDay, '0.10')
            ->toArray();

        self::assertSame(['modalidade' => 5, 'valorPerc' => '0.10'], $payload['valor']['desconto']);
    }

    public function test_requires_due_date(): void
    {
        $this->expectException(ValidationException::class);
        CobvBuilder::make()
            ->debtor('05314742160', 'F')
            ->debtorAddress('R', 'C', 'MT', '78550000')
            ->amount('1.00')->pixKey('k')->toArray();
    }

    public function test_requires_debtor_address(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Endereço do devedor');
        CobvBuilder::make()
            ->dueDate('2026-12-31')
            ->debtor('05314742160', 'F')
            ->amount('1.00')->pixKey('k')->toArray();
    }

    public function test_invalid_due_date_format_throws(): void
    {
        $this->expectException(ValidationException::class);
        CobvBuilder::make()->dueDate('31/12/2026');
    }

    public function test_invalid_cep_throws(): void
    {
        $this->expectException(ValidationException::class);
        CobvBuilder::make()->debtorAddress('R', 'C', 'MT', '123');
    }
}
