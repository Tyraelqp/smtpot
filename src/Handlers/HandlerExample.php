<?php

namespace SMTPot\Handlers;

class HandlerExample extends AbstractHandler
{
    private int $messagesCount = 0;

    public function handleMessage(array $message): void
    {
        $from = $message['from'];
        $to = $message['to'];
        $headers = $message['headers'];
        $body = $message['body'];

        $this->messagesCount++;

        error_log(sprintf(
            "Handler: new message:\n  From: %s\n  To: %s\n  Headers count: %d\n  Body length: %d",
            $from,
            implode(', ', $to),
            count($headers),
            strlen($body),
        ));
    }

    public function handleCommand(string $command): string
    {
        switch ($command) {
            case 'stats':
                return "Total count of processed messages: $this->messagesCount";
            case 'reset':
                $this->messagesCount = 0;
                return 'Total count was set to 0';
            default:
                return 'Unknown command';
        }
    }
}
