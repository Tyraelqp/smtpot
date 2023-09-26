![SMTPot](logo.png)

# SMTPot
A witch pot for your emails.

___

SMTPot is a simple SMTP server which accept emails and passes them to you handler.

## Requirements

One of:

* PHP 7.4+
* [Docker](https://www.docker.com)

---

## Project setup

```shell
git clone git@github.com:Tyraelqp/smtpot.git
cd smtpot
cp config.example.php config.php
```

Now you can change default configuration for your needs (WARNING! Do not change `port` if you want to use docker).

---

# Handlers

You can set your email handler in `config.php` (Check [Configuration reference](#configuration-reference) section).

Server will pass email data as first argument to your handler. Email data scheme:

```
{
    "from": string,
    "to": Array<int, string>,
    "headers": Array<string, string>,
    "body": string
}
```

---

## Startup and connect

### Without docker

Startup:

```shell
php server.php
```

Server will be available on port specified in `config.php`.

### With docker

Startup:

```shell
docker-compose up [-d]
```

Server will be available on port `2525`

---

## Configuration reference

* `debug`: Toggle logging to stdout
* `port`: Server port. Should be `25` if you want to use docker
* `handler_filename`: Absolute path to php file with handler. File should return instance of `Smtpot\HandlerInterface`
