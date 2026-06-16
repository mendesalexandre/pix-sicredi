<?php

declare(strict_types=1);

namespace PixSicredi\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PixSicredi\Enums\PixKeyType;
use PixSicredi\Exceptions\ValidationException;
use PixSicredi\Support\PixKey;

final class PixKeyTest extends TestCase
{
    /**
     * @return iterable<string,array{string,PixKeyType|null}>
     */
    public static function keys(): iterable
    {
        yield 'email'   => ['financeiro@cartorio.com.br', PixKeyType::Email];
        yield 'phone'   => ['+5566999998888', PixKeyType::Phone];
        yield 'cpf'     => ['05314742160', PixKeyType::Cpf];
        yield 'cnpj'    => ['12345678000195', PixKeyType::Cnpj];
        yield 'evp'     => ['123e4567-e89b-12d3-a456-426614174000', PixKeyType::Evp];
        yield 'invalid' => ['xyz', null];
        yield 'email ruim' => ['a@', null];
    }

    /**
     * @dataProvider keys
     */
    public function test_detects_type(string $key, ?PixKeyType $expected): void
    {
        self::assertSame($expected, PixKey::type($key));
    }

    public function test_assert_valid_throws_on_unknown(): void
    {
        $this->expectException(ValidationException::class);
        PixKey::assertValid('not-a-key');
    }

    public function test_is_valid(): void
    {
        self::assertTrue(PixKey::isValid('05314742160'));
        self::assertFalse(PixKey::isValid('123'));
    }
}
