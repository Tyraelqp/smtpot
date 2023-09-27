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

function debug(string $message): void
{
    global $config;

    if (!($config['debug'] ?? false)) {
        return;
    }

    error_log(trim($message));
}


function onClose(): void
{
    global $connections;
    global $socket;

    foreach ($connections as $connection) {
        socket_close($connection);
    }

    socket_close($socket);
    debug('Server stopped');
    exit(0);
}
