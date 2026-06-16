# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e
[SemVer](https://semver.org/lang/pt-BR/).

## [0.1.0] — não lançado

### Adicionado

- Autenticação OAuth2 `client_credentials` + mTLS (cert + chave) com cache opcional (PSR-16).
- Cobrança imediata (COB): criar (PUT/POST), consultar, revisar (PATCH), listar.
- Webhook (cliente): configurar, consultar, excluir, listar.
- Pix recebidos + devolução: consultar, listar, devolver, consultar devolução.
- `WebhookHandler` (receiver): parseia a notificação do Sicredi em DTOs `ReceivedPix`,
  detecta chamada de validação e filtra por chaves esperadas.
- Evento `PixReceivedEvent` + `WebhookHandler::onPixReceived()` / `handle()`: dispara
  um evento por pix recebido pra o consumidor dar baixa (registrando um listener),
  sem o pacote depender do `event()` de nenhum framework.
- `CobBuilder`: monta o payload da cobrança de forma tipada/fluente, detecta
  CPF/CNPJ e valida valor, campos obrigatórios e tamanho. Aceito direto no `cob()->create()`.
- Validação de `txid` (`[a-zA-Z0-9]{26,35}`) antes de chamar a API.
- Auto-retry: chamadas autenticadas que recebem `401` invalidam o token e tentam 1x.
- `Charge`: DTO com visão tipada da resposta da cobrança (status, amount, location,
  pixCopiaECola/copyPaste, expiration, createdAt) + `raw`.
- `Txid`: gera `txid` válido — `random()` ou `fromSeed()` (determinístico, idempotente).
- Framework-agnostic (Guzzle + PSR-16/PSR-3). 38 testes PHPUnit (com HTTP mockado,
  incluindo Webhook/Pix e auto-retry 401) + PHPStan level 8.
