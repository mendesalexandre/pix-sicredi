<?php

declare(strict_types=1);

namespace PixSicredi\Webhook;

use PixSicredi\DTO\ReceivedPix;
use PixSicredi\Events\PixReceivedEvent;
use PixSicredi\Exceptions\ValidationException;

/**
 * Recebe e interpreta a notificação que o Sicredi envia (POST) quando um pix é
 * pago. Framework-agnostic: o consumidor expõe a rota (ex: no SINOP) e passa o
 * corpo cru pra cá; o pacote devolve os {@see ReceivedPix} já parseados.
 *
 * SEGURANÇA: o Sicredi autentica a chamada por mTLS (apresenta certificado de
 * cliente). A validação do certificado é responsabilidade da borda (nginx
 * `ssl_verify_client optional/on` ou checagem na aplicação) — este handler só
 * cuida do parsing. Opcionalmente, valide também que a `chave` recebida é uma
 * das suas via {@see self::withExpectedKeys()}.
 */
final class WebhookHandler
{
    /** @var list<string> */
    private array $expectedKeys = [];

    /** @var list<callable(PixReceivedEvent):void> */
    private array $listeners = [];

    /**
     * Restringe o processamento a chaves PIX conhecidas (defesa extra).
     *
     * @param list<string> $keys
     */
    public function withExpectedKeys(array $keys): self
    {
        $this->expectedKeys = $keys;

        return $this;
    }

    /**
     * Registra um listener chamado (via {@see self::handle()}) pra cada pix
     * recebido. É aqui que o consumidor faz a baixa — em Laravel, normalmente
     * re-emite um evento nativo ou despacha um job.
     *
     * @param callable(PixReceivedEvent):void $listener
     */
    public function onPixReceived(callable $listener): self
    {
        $this->listeners[] = $listener;

        return $this;
    }

    /**
     * Processa o webhook E dispara o {@see PixReceivedEvent} pra cada pix
     * recebido, notificando os listeners registrados. Retorna os eventos
     * disparados (vazio em chamada de validação).
     *
     * @return list<PixReceivedEvent>
     *
     * @throws ValidationException se o corpo não for JSON válido
     */
    public function handle(string $rawBody): array
    {
        $events = [];

        foreach ($this->parse($rawBody) as $pix) {
            $event = new PixReceivedEvent($pix);

            foreach ($this->listeners as $listener) {
                $listener($event);
            }

            $events[] = $event;
        }

        return $events;
    }

    /**
     * Processa o corpo cru do POST do Sicredi.
     *
     * @return list<ReceivedPix>
     *
     * @throws ValidationException se o corpo não for JSON válido
     */
    public function parse(string $rawBody): array
    {
        $data = json_decode($rawBody, true);

        if (! is_array($data)) {
            throw new ValidationException('Corpo do webhook não é um JSON válido.');
        }

        // O Sicredi pode mandar um ping de validação sem o array `pix`.
        $items = $data['pix'] ?? [];
        if (! is_array($items)) {
            return [];
        }

        $received = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pix = ReceivedPix::fromArray($item);

            if ($this->expectedKeys !== [] && $pix->pixKey !== null
                && ! in_array($pix->pixKey, $this->expectedKeys, true)) {
                continue;
            }

            $received[] = $pix;
        }

        return $received;
    }

    /**
     * Detecta a chamada de validação do Sicredi (sem pix efetivo) — quando o
     * Sicredi só verifica se a URL responde 2xx ao cadastrar/checar o webhook.
     */
    public function isValidationCall(string $rawBody): bool
    {
        $data = json_decode($rawBody, true);
        if (! is_array($data)) {
            return false;
        }

        $pix = $data['pix'] ?? null;

        return ! is_array($pix) || $pix === [];
    }
}
