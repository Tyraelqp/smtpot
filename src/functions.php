<?php

function respond($resource, int $code, string $response): void
{
    debug("Server: $code $response");
    fwrite($resource, "$code $response" . "\n");
}

function debug(string $message): void
{
    global $config;

    if (!($config['debug'] ?? false)) {
        return;
    }

    error_log(trim($message));
}
