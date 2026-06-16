<?php

declare(strict_types=1);

namespace PixSicredi\Exceptions;

/** Falha ao obter o token OAuth (mTLS, credenciais ou schema da requisição). */
class AuthenticationException extends PixSicrediException
{
}
