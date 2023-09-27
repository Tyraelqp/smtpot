<?php

namespace SMTPot\Handlers;

use RuntimeException;

abstract class AbstractHandler implements HandlerInterface
{
    public function handleCommand(string $command): string
    {
        throw new RuntimeException(sprintf(
            '%s::handleCommand is not implemented',
            static::class,
        ));
    }
}
