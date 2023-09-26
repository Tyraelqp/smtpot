<?php

namespace Smtpot;

use RuntimeException;

require_once __DIR__ . '/HandlerInterface.php';

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
