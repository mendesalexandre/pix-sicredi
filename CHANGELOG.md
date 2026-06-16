# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e
[SemVer](https://semver.org/lang/pt-BR/).

## [0.1.0] — não lançado

### Adicionado

- Autenticação OAuth2 `client_credentials` + mTLS (cert + chave) com cache opcional (PSR-16).
- Cobrança imediata (COB): criar (PUT/POST), consultar, revisar (PATCH), listar.
- Webhook (cliente): configurar, consultar, excluir, listar.
- Pix recebidos + devolução: consultar, listar, devolver, consultar devolução.
- `WebhookHandler` (receiver): parseia a notificação do Sicredi em DTOs `PixRecebido`,
  detecta ping de validação e filtra por chaves esperadas.
- Framework-agnostic (Guzzle + PSR-16/PSR-3). Testes PHPUnit + PHPStan level 8.
