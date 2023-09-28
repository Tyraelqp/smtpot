<?php

declare(ticks = 1);

use SMTPot\Handlers\HandlerInterface;

require_once __DIR__ . '/vendor/autoload.php';

const CONFIG_FILENAME = __DIR__ . '/config.php';
const NO_ACTIVITY_THRESHOLD = 2;
const SLEEP_NO_ACTIVITY = 1e6 / 10;
const SLEEP_HAS_ACTIVITY = 1e6 / 1000;
const READ_TIMEOUT = 10;

try {
    if (!file_exists(CONFIG_FILENAME)) {
        throw new RuntimeException('Config file was not found.');
    }

    $config = require CONFIG_FILENAME;

    if (!is_array($config)) {
        throw new RuntimeException(sprintf(
            '%s should return array',
            CONFIG_FILENAME,
        ));
    }

    if (
        !isset($config['handler_filename'])
        || !is_file($config['handler_filename'])
    ) {
        throw new RuntimeException('Invalid value for handler_filename parameter');
    }

    $handler = require $config['handler_filename'];

    if (!$handler instanceof HandlerInterface) {
        throw new RuntimeException(sprintf(
            'handler_filename should return instance of %s',
            HandlerInterface::class,
        ));
    }
} catch (Throwable $e) {
    error_log("Error: {$e->getMessage()}");
    exit(1);
}

pcntl_signal(SIGTERM, "onClose");
pcntl_signal(SIGHUP, "onClose");
pcntl_signal(SIGINT, "onClose");

$port = $config['port'] ?? 25;
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, '0.0.0.0', $port);
socket_listen($socket, SOMAXCONN);
socket_set_nonblock($socket);

debug("Server started at 0.0.0.0:$port");

$connections = [];
$toSend = [];
$sendCount = 0;
$readingDataFrom = [];
$lastInputTime = 0;

while (true) {
    if (time() - $lastInputTime > NO_ACTIVITY_THRESHOLD) {
        usleep(SLEEP_NO_ACTIVITY);
    } else {
        usleep(SLEEP_HAS_ACTIVITY);
    }

    $socketsToWrite = $socketsToExcept = [];
    $socketsToRead = [
        $socket,
        ...array_column($connections, 'socket'),
    ];

    socket_select(
        $socketsToRead,
        $socketsToWrite,
        $socketsToExcept,
        1,
    );

    if (empty($socketsToRead)) {
        foreach ($connections as $i => $conn) {
            if ($conn['lastInputTime'] < time() - READ_TIMEOUT) {
                socket_close($conn['socket']);
                unset($connections[$i], $toSend[$i]);
                debug('Client disconnected by timeout');
            }
        }

        continue;
    }

    if (in_array($socket, $socketsToRead, true)) {
        $conn = socket_accept($socket);
        socket_set_nonblock($conn);
        socket_getpeername($conn, $clientIp);
        debug("Log: New connection from $clientIp");

        $connections[] = [
            'lastInputTime' => time(),
            'socket' => $conn,
        ];
        $lastInputTime = time();

        respond($conn, 220, "SMTPot Server");
    }

    foreach ($connections as $i => $conn) {
        $clientSocket = $conn['socket'];

        if (!in_array($clientSocket, $socketsToRead, true)) {
            continue;
        }

        if (in_array($i, $readingDataFrom, true)) {
            if (null === $toSend[$i]['body']) {
                $toSend[$i]['body'] = '';
            }

            while (false !== $row = readInput($clientSocket)) {
                if (empty($row)) {
                    socket_close($clientSocket);
                    unset($connections[$i], $toSend[$i]);
                    debug('Client disconnected');

                    break;
                }

                $toSend[$i]['body'] .= $row;
                $connections[$i]['lastInputTime'] = time();
            }

            if (isset($toSend[$i]) && preg_match('/\r\n\.\r\n/', $toSend[$i]['body'])) {
                $toSend[$i]['body'] = str_replace("\r\n.\r\n", '', $toSend[$i]['body']);
                $readingDataFrom = array_filter(
                    $readingDataFrom,
                    static fn(int $ii) => $ii !== $i,
                );

                respond($clientSocket, 250, 'Ok: queued as ' . ++$sendCount);

                [$headers, $body] = preg_split('/(\r?\n){2}/', $toSend[$i]['body'], 2);
                $toSend[$i]['body'] = trim($body);
                $toSend[$i]['headers'] = [];

                preg_match_all('/[\w-]+:.+(?:\r?\n\s+.+)*/m', $headers, $headersRows);

                foreach ($headersRows[0] as $header) {
                    [$name, $value] = explode(':', $header, 2);
                    $value = preg_replace(
                        ['/;\r?\n\s+/', '/\r?\n\s+/'],
                        ['; ', ''],
                        $value,
                    );
                    $toSend[$i]['headers'][strtolower(trim($name))][] = trim($value);
                }

                try {
                    $handler->handleMessage($toSend[$i]);
                } catch (Throwable $e) {
                    debug("Unhandled exception in handler: {$e->getMessage()}");
                }

                unset($toSend[$i]);
            }

            continue;
        }

        $input = readInput($clientSocket);

        if (false === $input) {
            continue;
        }

        if (empty($input)) {
            socket_close($clientSocket);
            unset($connections[$i], $toSend[$i]);
            debug('Client disconnected');
            continue;
        }

        debug("Client: $input");

        if (!preg_match('/^(\w+)(.+)?\r\n/', $input, $args)) {
            continue;
        }

        $connections[$i]['lastInputTime'] = time();
        $lastInputTime = time();

        array_shift($args);

        switch ($args[0]) {
            case 'HELO':
            case 'EHLO':
                if (1 === count($args)) {
                    respond($clientSocket, 501, "Syntax: {$args[0]} hostname");
                    break;
                }

                $args[1] = trim($args[1]);
                respond($clientSocket, 250, "Hello {$args[1]}");
                break;
            case 'MAIL':
                if (!preg_match('/FROM:<([^>]+)>/', $args[1], $from)) {
                    respond($conn, 501, "Syntax: MAIL FROM:<address>");
                    break;
                }

                $toSend[$i] = [
                    'from' => $from[1],
                    'to' => [],
                    'headers' => null,
                    'body' => null,
                ];

                respond($clientSocket, 250, 'Ok');
                break;
            case 'RCPT':
                if (!preg_match('/TO:<([^>]+)>/', $args[1], $to)) {
                    respond($conn, 501, "Syntax: RCPT TO:<address>");
                    break;
                }

                $toSend[$i]['to'][] = $to[1];

                respond($clientSocket, 250, 'Ok');
                break;
            case 'DATA':
                $readingDataFrom[] = $i;
                respond($clientSocket, 354, 'End data with CRLF.CRLF');
                break;
            case 'QUIT':
                respond($clientSocket, 221, 'Bye');
                socket_close($clientSocket);
                unset($connections[$i], $toSend[$i]);
                break;
            case 'NOOP':
                respond($clientSocket, 250, 'Ok');
                break;
            case 'HNDL':
                if (1 === count($args)) {
                    respond($clientSocket, 501, "Syntax: {$args[0]} payload");
                    break;
                }

                try {
                    respond($clientSocket, 250, $handler->handleCommand(trim($args[1])));
                } catch (Throwable $e) {
                    respond($clientSocket, 500, $e->getMessage());
                }

                break;
            default:
                debug("Unknown command: $args[0]");
                respond($clientSocket, 250, 'Ok');
        }
    }
}
