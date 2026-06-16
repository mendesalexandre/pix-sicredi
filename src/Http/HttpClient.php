<?php

declare(strict_types=1);

namespace PixSicredi\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use PixSicredi\Config;
use PixSicredi\Exceptions\RequestException;

/**
 * Client HTTP fino sobre o Guzzle, com mTLS (cert + chave) sempre aplicado.
 *
 * NÃO lança em 4xx/5xx (http_errors=false): devolve sempre uma Response e deixa
 * o chamador decidir — o Auth trata 400 de schema, os resources tratam erros
 * de negócio. Só lança em falha de conexão/transporte.
 */
final class HttpClient
{
    private Client $guzzle;

    /**
     * @param callable|null $handler handler Guzzle opcional — usado só em testes
     *                               (ex: GuzzleHttp\Handler\MockHandler). Em
     *                               produção fica null e o mTLS é aplicado.
     */
    public function __construct(Config $config, ?callable $handler = null)
    {
        $options = [
            'base_uri' => $config->baseUrl(),
            'timeout' => $config->timeout,
            'http_errors' => false,
            'cert' => $config->certificatePath,
            'ssl_key' => $config->keyPassword !== null
                ? [$config->privateKeyPath, $config->keyPassword]
                : $config->privateKeyPath,
            'verify' => $config->caBundlePath ?? true,
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        $this->guzzle = new Client($options);
    }

    /**
     * @param array<string,string>     $headers
     * @param array<string,mixed>|null $json   corpo JSON
     * @param array<string,string>     $form   corpo x-www-form-urlencoded
     * @param array<string,scalar>     $query
     */
    public function send(
        string $method,
        string $url,
        array $headers = [],
        ?array $json = null,
        array $form = [],
        array $query = [],
    ): Response {
        $options = ['headers' => $headers];

        if ($json !== null) {
            $options['json'] = $json;
        }
        if ($form !== []) {
            $options['form_params'] = $form;
        }
        if ($query !== []) {
            $options['query'] = $query;
        }

        try {
            $res = $this->guzzle->request($method, $url, $options);
        } catch (ConnectException $e) {
            throw new RequestException('Falha de conexão com o Sicredi: ' . $e->getMessage());
        } catch (GuzzleException $e) {
            throw new RequestException('Erro de transporte na chamada ao Sicredi: ' . $e->getMessage());
        }

        return new Response($res->getStatusCode(), (string) $res->getBody());
    }
}
