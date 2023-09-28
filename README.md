<p align="center">
    <img src="logo.png" alt="SMTPot">
</p>

# SMTPot
A witch pot for your emails.

___

SMTPot is a simple SMTP server which accept emails and passes them to you handler.

## Requirements

One of:

* PHP 8.0+, ext-pcntl, ext-sockets, composer
* [Docker](https://www.docker.com)

---

## Project setup

```shell
git clone https://github.com/Tyraelqp/smtpot.git
cd smtpot
cp config.example.php config.php
cp .env.example .env
```

Now you can change default configuration for your needs (WARNING! Do not change `port` if you want to use docker).

---

# Handlers

### General information

You can set your email handler in `config.php` (Check [Configuration reference](#configuration-reference) section).

Handler should implement `SMTPot\Handlers\HandlerInterface` interface.
You can use `SMTPot\Handlers\AbstractHandler` class as base class for your handler.

Server will pass email message data as first argument to your handler's `handleMessage` method. Email data scheme:

```
{
    "from": string,
    "to": Array<int, string>,
    "headers": Array<string, string[]>,
    "body": string
}
```

### Custom command

Also, you can create custom commands inside your handler.
These commands can be executed by opening new telnet session and sending `HNDL some_command with args` command to server.

`some_command with args` will be passed to your handler's `handleCommand` method as first argument.

See [HandlerExample](src/Handlers/HandlerExample.php) for example.

---

## Startup and connect

### Without docker

Startup:

```shell
composer install
php server.php
```

Server will be available on port specified in `config.php`.

### With docker

Startup:

```shell
docker compose build
docker compose up [-d]
```

Server will be available on port configured in `SMTP_PORT` ENV parameter (default is `2525`)

---

## Configuration reference

* `debug`: Toggle logging to stdout.
* `port`: Server port. Should be `25` if you want to use docker.
* `read_timeout`: Timeout for reading data from socket. Socket will be closed if timeout exceeded.
* `handler_filename`: Absolute path to php file with handler. File should return instance of `SMTPot\Handlers\HandlerInterface`.
