<?php

declare(strict_types=1);

namespace PixSicredi\Builders;

use PixSicredi\Exceptions\ValidationException;

/**
 * Monta o payload de uma cobrança imediata (COB) de forma tipada/fluente, em
 * vez de array solto. Detecta CPF (11 dígitos) vs CNPJ (14) automaticamente.
 *
 * ```php
 * $payload = CobBuilder::make()
 *     ->expiration(3600)
 *     ->debtor('05314742160', 'Fulano de Tal')
 *     ->amount('3046.18')
 *     ->pixKey('financeiro@cartorio.com.br')
 *     ->payerRequest('OS 537253')
 *     ->addInfo('Ordem de Serviço', '537253');
 *
 * $pix->cob()->create($txid, $payload); // aceita o builder direto
 * ```
 */
final class CobBuilder
{
    private ?int $expiration = null;
    /** @var array<string,string>|null */
    private ?array $debtor = null;
    private ?string $amount = null;
    private ?string $pixKey = null;
    private ?string $payerRequest = null;
    /** @var list<array{nome:string,valor:string}> */
    private array $additionalInfo = [];

    public static function make(): self
    {
        return new self();
    }

    /** Tempo de vida da cobrança em segundos (calendario.expiracao). */
    public function expiration(int $seconds): self
    {
        if ($seconds <= 0) {
            throw new ValidationException('expiration deve ser positivo (segundos).');
        }
        $this->expiration = $seconds;

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

    /** Valor original, string com 2 casas (ex: "3046.18"). */
    public function amount(string $value): self
    {
        if (! preg_match('/^\d+\.\d{2}$/', $value)) {
            throw new ValidationException('amount deve ser string com 2 casas decimais (ex: "10.00").');
        }
        $this->amount = $value;

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

    /** Texto livre pro pagador (solicitacaoPagador, máx. 140 chars). */
    public function payerRequest(string $text): self
    {
        if (mb_strlen($text) > 140) {
            throw new ValidationException('payerRequest excede 140 caracteres.');
        }
        $this->payerRequest = $text;

        return $this;
    }

    /** Adiciona um par nome/valor em infoAdicionais. */
    public function addInfo(string $name, string $value): self
    {
        $this->additionalInfo[] = ['nome' => $name, 'valor' => $value];

        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        if ($this->debtor === null) {
            throw new ValidationException('Devedor é obrigatório (use debtor()).');
        }
        if ($this->amount === null) {
            throw new ValidationException('Valor é obrigatório (use amount()).');
        }
        if ($this->pixKey === null) {
            throw new ValidationException('Chave PIX é obrigatória (use pixKey()).');
        }

        $payload = [
            'calendario' => ['expiracao' => $this->expiration ?? 3600],
            'devedor' => $this->debtor,
            'valor' => ['original' => $this->amount],
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
}
