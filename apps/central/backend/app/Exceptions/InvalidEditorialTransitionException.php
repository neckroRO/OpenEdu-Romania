<?php

namespace App\Exceptions;

use BackedEnum;
use RuntimeException;

class InvalidEditorialTransitionException extends RuntimeException
{
    public function __construct(
        BackedEnum $from,
        BackedEnum $to
    ) {
        parent::__construct(
            sprintf(
                'Invalid editorial transition from "%s" to "%s".',
                $from->value,
                $to->value
            )
        );
    }
}
