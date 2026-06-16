<?php

declare(strict_types=1);

namespace PixSicredi\Builders;

use PixSicredi\Enums\AbatementMode;
use PixSicredi\Enums\DiscountMode;
use PixSicredi\Enums\FineMode;
use PixSicredi\Enums\InterestMode;
use PixSicredi\Exceptions\ValidationException;

/**
 * Monta o payload de uma cobrança com vencimento (COBV) de forma tipada.
 *
 * Diferenças pro COB: tem `dataDeVencimento`, o endereço do devedor é
 * obrigatório, e há os encargos multa/juros/desconto/abatimento (cada um com
 * sua "modalidade" BACEN, modelada por enum).
 *
 * ```php
 * $payload = CobvBuilder::make()
 *     ->dueDate('2026-12-31')
 *     ->validityAfterDue(30)
 *     ->debtor('05314742160', 'Fulano')
 *     ->debtorAddress('Rua X, 100', 'Sinop', 'MT', '78550000')
 *     ->amount('100.00')
 *     ->pixKey('financeiro@cartorio.com.br')
 *     ->fine(FineMode::Percentage, '2.00')
 *     ->interest(InterestMode::PercentPerCalendarMonth, '1.00')
 *     ->discountByDate(DiscountMode::PercentByDate, [['date' => '2026-12-20', 'value' => '5.00']]);
 * ```
 */
final class CobvBuilder
{
    private ?string $dueDate = null;
    private ?int $validityAfterDue = null;
    /** @var array<string,string>|null */
    private ?array $debtor = null;
    /** @var array<string,string> */
    private array $debtorAddress = [];
    private ?string $amount = null;
    private ?string $pixKey = null;
    private ?string $payerRequest = null;
    /** @var array<string,mixed>|null */
    private ?array $fine = null;
    /** @var array<string,mixed>|null */
    private ?array $interest = null;
    /** @var array<string,mixed>|null */
    private ?array $abatement = null;
    /** @var array<string,mixed>|null */
    private ?array $discount = null;
    /** @var list<array{nome:string,valor:string}> */
    private array $additionalInfo = [];

    public static function make(): self
    {
        return new self();
    }

    /** Data de vencimento (YYYY-MM-DD). */
    public function dueDate(string $date): self
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new ValidationException('dueDate deve estar no formato YYYY-MM-DD.');
        }
        $this->dueDate = $date;

        return $this;
    }

    /** Dias em que a cobrança ainda é pagável após o vencimento. */
    public function validityAfterDue(int $days): self
    {
        if ($days < 0) {
            throw new ValidationException('validityAfterDue não pode ser negativo.');
        }
        $this->validityAfterDue = $days;

        return $this;
    }

    /** Devedor por CPF (11 dígitos) ou CNPJ (14) — detectado pelo tamanho. */
    public function debtor(string $document, string $name): self
    {
        $digits = preg_replace('/\D/', '', $document) ?? '';
        $field = match (strlen($digits)) {
            11 => 'cpf',
            14 => 'cnpj',
            default => throw new ValidationException('Documento do devedor deve ter 11 (CPF) ou 14 (CNPJ) dígitos.'),
        };

        $name = trim($name);
        if ($name === '') {
            throw new ValidationException('Nome do devedor é obrigatório.');
        }

        $this->debtor = [$field => $digits, 'nome' => $name];

        return $this;
    }

    /** Endereço do devedor (obrigatório no COBV). */
    public function debtorAddress(string $street, string $city, string $uf, string $cep): self
    {
        $cep = preg_replace('/\D/', '', $cep) ?? '';
        if (strlen($cep) !== 8) {
            throw new ValidationException('CEP deve ter 8 dígitos.');
        }
        if (strlen(trim($uf)) !== 2) {
            throw new ValidationException('UF deve ter 2 caracteres.');
        }

        $this->debtorAddress = [
            'logradouro' => trim($street),
            'cidade' => trim($city),
            'uf' => strtoupper(trim($uf)),
            'cep' => $cep,
        ];

        return $this;
    }

    public function amount(string $value): self
    {
        $this->amount = $this->assertMoney($value, 'amount');

        return $this;
    }

    public function pixKey(string $key): self
    {
        $key = trim($key);
        if ($key === '') {
            throw new ValidationException('chave PIX é obrigatória.');
        }
        $this->pixKey = $key;

        return $this;
    }

    public function payerRequest(string $text): self
    {
        if (mb_strlen($text) > 140) {
            throw new ValidationException('payerRequest excede 140 caracteres.');
        }
        $this->payerRequest = $text;

        return $this;
    }

    public function fine(FineMode $mode, string $value): self
    {
        $this->fine = ['modalidade' => $mode->value, 'valorPerc' => $this->assertMoney($value, 'fine')];

        return $this;
    }

    public function interest(InterestMode $mode, string $value): self
    {
        $this->interest = ['modalidade' => $mode->value, 'valorPerc' => $this->assertMoney($value, 'interest')];

        return $this;
    }

    public function abatement(AbatementMode $mode, string $value): self
    {
        $this->abatement = ['modalidade' => $mode->value, 'valorPerc' => $this->assertMoney($value, 'abatement')];

        return $this;
    }

    /**
     * Desconto com data(s) fixa(s) — modalidades FixedValueByDate / PercentByDate.
     *
     * @param list<array{date:string,value:string}> $items
     */
    public function discountByDate(DiscountMode $mode, array $items): self
    {
        if ($items === []) {
            throw new ValidationException('discountByDate exige ao menos um item.');
        }

        $fixed = [];
        foreach ($items as $item) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date'] ?? '')) {
                throw new ValidationException('discount: date deve estar no formato YYYY-MM-DD.');
            }
            $fixed[] = ['data' => $item['date'], 'valorPerc' => $this->assertMoney($item['value'] ?? '', 'discount')];
        }

        $this->discount = ['modalidade' => $mode->value, 'descontoDataFixa' => $fixed];

        return $this;
    }

    /** Desconto por antecipação — modalidades 3–6 (valorPerc único). */
    public function discountByAnticipation(DiscountMode $mode, string $value): self
    {
        $this->discount = ['modalidade' => $mode->value, 'valorPerc' => $this->assertMoney($value, 'discount')];

        return $this;
    }

    public function addInfo(string $name, string $value): self
    {
        $this->additionalInfo[] = ['nome' => $name, 'valor' => $value];

        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        if ($this->dueDate === null) {
            throw new ValidationException('Data de vencimento é obrigatória (use dueDate()).');
        }
        if ($this->debtor === null) {
            throw new ValidationException('Devedor é obrigatório (use debtor()).');
        }
        if ($this->debtorAddress === []) {
            throw new ValidationException('Endereço do devedor é obrigatório no COBV (use debtorAddress()).');
        }
        if ($this->amount === null) {
            throw new ValidationException('Valor é obrigatório (use amount()).');
        }
        if ($this->pixKey === null) {
            throw new ValidationException('Chave PIX é obrigatória (use pixKey()).');
        }

        $calendario = ['dataDeVencimento' => $this->dueDate];
        if ($this->validityAfterDue !== null) {
            $calendario['validadeAposVencimento'] = $this->validityAfterDue;
        }

        $valor = ['original' => $this->amount];
        foreach (['multa' => $this->fine, 'juros' => $this->interest, 'desconto' => $this->discount, 'abatimento' => $this->abatement] as $key => $value) {
            if ($value !== null) {
                $valor[$key] = $value;
            }
        }

        $payload = [
            'calendario' => $calendario,
            'devedor' => $this->debtor + $this->debtorAddress,
            'valor' => $valor,
            'chave' => $this->pixKey,
        ];

        if ($this->payerRequest !== null) {
            $payload['solicitacaoPagador'] = $this->payerRequest;
        }
        if ($this->additionalInfo !== []) {
            $payload['infoAdicionais'] = $this->additionalInfo;
        }

        return $payload;
    }

    private function assertMoney(string $value, string $field): string
    {
        if (! preg_match('/^\d+\.\d{2}$/', $value)) {
            throw new ValidationException("{$field} deve ser string com 2 casas decimais (ex: \"10.00\").");
        }

        return $value;
    }
}
