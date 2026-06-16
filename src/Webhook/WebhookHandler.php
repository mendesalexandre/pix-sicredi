<?php

declare(strict_types=1);

namespace PixSicredi\Webhook;

use PixSicredi\DTO\PixRecebido;
use PixSicredi\Exceptions\ValidationException;

/**
 * Recebe e interpreta a notificação que o Sicredi envia (POST) quando um pix é
 * pago. Framework-agnostic: o consumidor expõe a rota (ex: no SINOP) e passa o
 * corpo cru pra cá; o pacote devolve os {@see PixRecebido} já parseados.
 *
 * SEGURANÇA: o Sicredi autentica a chamada por mTLS (apresenta certificado de
 * cliente). A validação do certificado é responsabilidade da borda (nginx
 * `ssl_verify_client optional/on` ou checagem na aplicação) — este handler só
 * cuida do parsing. Opcionalmente, valide também que a `chave` recebida é uma
 * das suas via {@see self::comChavesEsperadas()}.
 */
final class WebhookHandler
{
    /** @var list<string> */
    private array $chavesEsperadas = [];

    /**
     * Restringe o processamento a chaves PIX conhecidas (defesa extra).
     *
     * @param list<string> $chaves
     */
    public function comChavesEsperadas(array $chaves): self
    {
        $this->chavesEsperadas = $chaves;

        return $this;
    }

    /**
     * Processa o corpo cru do POST do Sicredi.
     *
     * @return list<PixRecebido>
     *
     * @throws ValidationException se o corpo não for JSON válido
     */
    public function processar(string $rawBody): array
    {
        $dados = json_decode($rawBody, true);

        if (! is_array($dados)) {
            throw new ValidationException('Corpo do webhook não é um JSON válido.');
        }

        // O Sicredi pode mandar um ping de validação sem o array `pix`.
        $itens = $dados['pix'] ?? [];
        if (! is_array($itens)) {
            return [];
        }

        $recebidos = [];
        foreach ($itens as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pix = PixRecebido::deArray($item);

            if ($this->chavesEsperadas !== [] && $pix->chave !== null
                && ! in_array($pix->chave, $this->chavesEsperadas, true)) {
                continue;
            }

            $recebidos[] = $pix;
        }

        return $recebidos;
    }

    /** Detecta o ping de validação do Sicredi (sem pix efetivo). */
    public function ehPingDeValidacao(string $rawBody): bool
    {
        $dados = json_decode($rawBody, true);
        if (! is_array($dados)) {
            return false;
        }

        $pix = $dados['pix'] ?? null;

        return ! is_array($pix) || $pix === [];
    }
}
