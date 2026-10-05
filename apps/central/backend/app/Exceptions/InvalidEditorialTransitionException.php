<?php

namespace App\Exceptions;

use App\Enums\ResourceVersionStatus;
use RuntimeException;

class InvalidEditorialTransitionException extends RuntimeException
{
    public function __construct(
        ResourceVersionStatus $from,
        ResourceVersionStatus $to
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
