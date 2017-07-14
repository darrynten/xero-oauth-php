<?php
namespace DarrynTen\XeroOauth\Tests\XeroOauth\Exception;

use DarrynTen\XeroOauth\Exception\ExceptionMessages;
use DarrynTen\XeroOauth\Exception\ValidationException;

class ValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Extra string in Validation Exception message
     */
    const EXTRA = 'extra';

    /**
     * Tests if exception is valid: class, code, message
     * @throws ValidationException
     */
    public function testConstructor()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(ValidationException::UNDEFINED_VALIDATION_EXCEPTION);
        $this->expectExceptionMessageRegExp(
            sprintf("/%s$/", ExceptionMessages::$validationMessages[
                ValidationException::UNDEFINED_VALIDATION_EXCEPTION
            ])
        );

        throw new ValidationException(ValidationException::UNDEFINED_VALIDATION_EXCEPTION);
    }

    /**
     * Tests if exception is valid with extra argument: class, code, message
     * @throws ValidationException
     */
    public function testConstructorWithExtra()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(ValidationException::UNDEFINED_VALIDATION_EXCEPTION);
        $this->expectExceptionMessageRegExp(
            sprintf(
                "/%s %s$/",
                static::EXTRA,
                ExceptionMessages::$validationMessages[
                    ValidationException::UNDEFINED_VALIDATION_EXCEPTION
                ]
            )
        );

        throw new ValidationException(ValidationException::UNDEFINED_VALIDATION_EXCEPTION, static::EXTRA);
    }
}
