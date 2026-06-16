# pix-sicredi

Integração PHP **framework-agnostic** com a API PIX do **Banco Sicredi**: cobrança imediata (COB), webhook e devolução, com autenticação **mTLS**.

> Escopo focado no que o cartório usa: **COB + Webhook + Devolução**. COBV / Lote-COBV ficam de fora de propósito.

## Requisitos

- PHP 8.1+
- ext-curl, ext-json
- Certificado **da aplicação** (cliente) + chave privada emitidos pelo Sicredi

## Instalação

```bash
composer require mendesalexandre/pix-sicredi
```

## Uso

```php
use PixSicredi\PixSicredi;
use PixSicredi\Config;
use PixSicredi\Enums\Environment;

$pix = new PixSicredi(new Config(
    clientId:        'SEU_CLIENT_ID',
    clientSecret:    'SEU_CLIENT_SECRET',
    certificatePath: '/caminho/certificado.pem',
    privateKeyPath:  '/caminho/aplicacao.key',
    environment:     Environment::Production,  // ou Homologation
    keyPassword:     null,                     // se a chave tiver senha
    cache:           $psr16Cache,              // opcional: cacheia o token (~5 min)
    logger:          $psr3Logger,              // opcional
));
```

### Criar cobrança (COB)

Com o `CobBuilder` (tipado, detecta CPF/CNPJ, valida valor/campos):

```php
use PixSicredi\Builders\CobBuilder;
use PixSicredi\Support\Txid;
use PixSicredi\DTO\Charge;

$txid = Txid::fromSeed('OS-537253'); // determinístico → idempotente (ou Txid::random())

$resposta = $pix->cob()->create($txid, CobBuilder::make()
    ->expiration(3600)
    ->debtor('05314742160', 'Fulano de Tal')   // CPF (11) ou CNPJ (14), auto-detectado
    ->amount('3046.18')
    ->pixKey('financeiro@cartorio.com.br')
    ->payerRequest('OS 537253')
    ->addInfo('Ordem de Serviço', '537253'));

// Acesso tipado à resposta (copia-e-cola pra montar o QR no front):
$charge = Charge::fromArray($resposta);
echo $charge->status;     // "ATIVA"
echo $charge->copyPaste;  // "00020126..." (pixCopiaECola)
echo $charge->location;   // URL do payload

$charge = Charge::fromArray($pix->cob()->get($txid));
```

> Também aceita array cru (`create($txid, [...])`). O `txid` é validado (`[a-zA-Z0-9]{26,35}`),
> e chamadas que recebem `401` reautenticam e tentam **uma vez** automaticamente.

### Webhook — registrar a URL no Sicredi

```php
$pix->webhook()->configure('financeiro@cartorio.com.br', 'https://seu-site.com.br/webhook/pix');
$pix->webhook()->get('financeiro@cartorio.com.br');
$pix->webhook()->delete('financeiro@cartorio.com.br');
```

### Webhook — receber a notificação (receiver)

O Sicredi faz um `POST` na sua URL quando um pix é pago. O pacote interpreta o corpo
e dispara um **`PixReceivedEvent`** pra cada pix recebido — você registra um listener
com `onPixReceived()` e é lá que a **baixa** acontece. Exemplo em Laravel:

```php
use PixSicredi\Events\PixReceivedEvent;

// routes/api.php  →  Route::post('/webhook/pix', PixWebhookController::class)
public function __invoke(Request $request)
{
    $handler = (new PixSicredi($config))->webhookHandler()
        ->withExpectedKeys(['financeiro@cartorio.com.br'])           // defesa extra
        ->onPixReceived(function (PixReceivedEvent $event) {          // <- a baixa acontece aqui
            DarBaixaPixJob::dispatch(
                $event->pix->txid,
                $event->pix->endToEndId,
                $event->pix->amount,
            );
        });

    if ($handler->isValidationCall($request->getContent())) {
        return response()->json([], 200); // validação do Sicredi
    }

    $handler->handle($request->getContent()); // parseia + dispara o evento pra cada pix

    return response()->json([], 200);
}
```

> Em Laravel o listener costuma só **re-emitir um evento nativo** (`event(new PixRecebido(...))`)
> ou **despachar um job**, mantendo a lógica de baixa no fluxo normal do framework.
> Se quiser só os dados, sem evento, use `$handler->parse($body)` (retorna `ReceivedPix[]`).

> **Segurança:** o Sicredi autentica o webhook por **mTLS** (apresenta certificado de cliente).
> Valide-o na borda (nginx `ssl_verify_client on`) ou na aplicação — o handler só faz o parsing.

### Devolução

```php
$pix->pix()->refund($endToEndId, 'idDevolucao01', '10.00', 'estorno');
```

## Notas de implementação

- **Auth:** OAuth2 `client_credentials` + Basic + mTLS. O `grant_type` vai **só no corpo**
  (form-urlencoded). Repetir na query da URL do token faz o Sicredi rejeitar com
  `400 gerarToken.grantType inválido`.
- **Endpoints:** base `https://api-pix.sicredi.com.br`, API em `/api/v2`, OAuth em `/oauth/token`.
- **Erros:** `RequestException` preserva `statusCode`, `corpoBruto` e `violacoes[]` (objeto BACEN).

## Testes

```bash
composer test   # PHPUnit
composer stan   # PHPStan level 8
```

## Licença

MIT.
