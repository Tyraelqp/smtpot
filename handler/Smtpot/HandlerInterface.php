<?php

namespace Smtpot;

interface HandlerInterface
{
    public function handleMessage(array $message): void;
    public function handleCommand(string $command): string;
}
