<?php

function readInput(Socket $socket): string|false
{
    $input = socket_read($socket, 1024);

    if (empty($input)) {
        return $input;
    }

    if (str_contains($input, "\r\n")) {
        return $input;
    }

    while (false !== $row = socket_read($socket, 1024)) {
        $input .= $row;

        if (str_contains($input, "\r\n")) {
            break;
        }
    }

    return $input;
}

function respond(Socket $socket, int $code, string $response): void
{
    debug("Server: $code $response");
    socket_write($socket, "$code $response" . "\n");
}

function disconnectClient(Socket $socket, int $index, string $reason = null): void
{
    global $connections;
    global $toSend;

    socket_close($socket);
    unset($connections[$index], $toSend[$index]);

    if (null !== $reason) {
        debug('Client disconnected by timeout');
    }
}

function debug(string $message): void
{
    global $config;

    if (!($config['debug'] ?? false)) {
        return;
    }

    error_log(trim($message));
}
