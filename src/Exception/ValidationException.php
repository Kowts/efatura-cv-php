<?php

declare(strict_types=1);

namespace Kowts\Efatura\Exception;

/**
 * Representa uma violação de uma regra local ou fiscal.
 */
final class ValidationException extends EfaturaException
{
    public function __construct(
        public readonly string $field,
        string $message,
        public readonly string $errorCode = 'validation.invalid'
    ) {
        parent::__construct($message);
    }
}
