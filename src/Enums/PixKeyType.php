<?php

declare(strict_types=1);

namespace PixSicredi\Enums;

/** Tipo de chave PIX. */
enum PixKeyType: string
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    case Email = 'email';
    case Phone = 'phone';
    case Evp = 'evp'; // chave aleatória (UUID)
}
