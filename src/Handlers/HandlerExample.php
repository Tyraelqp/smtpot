<?php

namespace SMTPot\Handlers;

class HandlerExample extends AbstractHandler
{
    public function handleMessage(array $message): void
    {
        $from = $message['from'];
        $to = $message['to'];
        $headers = $message['headers'];
        $body = $message['body'];

        error_log(sprintf(
            "Handler: new message:\n  From: %s\n  To: %s\n  Headers count: %d\n  Body length: %d",
            $from,
            implode(', ', $to),
            count($headers),
            mb_strlen($body),
        ));
    }
}
