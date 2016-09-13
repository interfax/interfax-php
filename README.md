# InterFAX PHP Library

[![Build Status](https://travis-ci.com/splatEric/interfax-php.svg?token=zvHvLCWt5Q8cuwRHBcBK&branch=master)](https://travis-ci.com/splatEric/interfax-php)

[Installation](#installation) | [Getting Started](#getting-started)

Send and receive faxes in Ruby with the [InterFAX](https://www.interfax.net/en/dev) REST API.

## Installation

The preferred method of installation is via [Packagist](http://www.packagist.org) and [Composer](http://www.composer.org). Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require interfax/interfax
```

## Getting started

To send a fax for a pdf file:

```php
use Interfax\Client;

$interfax = new Client(['username' => 'username', 'password' => 'password']);
$fax = $interfax->deliver(['faxNumber' => '+11111111112', 'file' => 'folder/file.pdf']);

// getStatus will refresh the status of the fax from the server, if it's less than 0, then the fax is still pending.
while ($fax->getStatus() < 0) {
    sleep(5); // wait 5 seconds
}

// false prevents another request for status
echo $fax->getStatus(false) === 0 ? 'SUCCESS' : 'FAILURE';
```

# Usage

[Client](#client)

## Client

The client follows the [12-factor](http://12factor.net/config) apps principle and can be either set directly or via environment variables.

```php
$client = new Interfax\Client(['username' => '...', 'password' => '...']);

// Alternative: will utilise environment variables:
// * INTERFAX_USERNAME
// * INTERFAX_PASSWORD

$client = new Interfax\Client();
```

## Account Info

### Balance

Determine the remaining faxing credits in your account.

```php
echo $client->getBalance();
// (string) 9.86
```

## Outbound

```Interfax\Client``` has an outbound property that should be accessed:

```php
$outbound = $client->outbound;
```

### Get recent outbound fax list

```php
$faxes = $client->outbound->recent();
// Interfax\Outbound\Fax[]
```
