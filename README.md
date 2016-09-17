# InterFAX PHP Library

[![Build Status](https://travis-ci.com/splatEric/interfax-php.svg?token=zvHvLCWt5Q8cuwRHBcBK&branch=master)](https://travis-ci.com/splatEric/interfax-php)

[Installation](#installation) | [Getting Started](#getting-started)

Send and receive faxes in Ruby with the [InterFAX](https://www.interfax.net/en/dev) REST API.

## Installation

_TODO:_ submit to packagist

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

[Client](#client) [Outbound](#outbound) [Exceptions](#exceptions)

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

### Get completed outbound fax list

```php
$fax_ids = [ ... ]; // array of fax ids
$client->outbound->completed($fax_ids);
// Interfax\Outbound\Fax[]
```

### Resend a Fax

The outbound resend will return a new outbound Fax representing the re-sent fax.

```php
$fax = $client->outbound->resend($id);
// Interfax\Outbound\Fax
```

### Search for Faxes

```php
$faxes = $client->outbound->search([...]);
// Interfax\Outbound\Fax[]
```

takes a single hash array, keyed by the accepted search parameters for the outbound search API endpoint

[Documentation](https://www.interfax.net/en/dev/rest/reference/2959)

## Outbound Fax

The ```Interfax\Outbound\Fax``` class wraps the details of any fax sent, and is returned by most of the ```Outbound``` methods.

### Fax Status

```php
// Interfax\Outbound\Fax
$fax = $interfax->deliver(['faxNumber' => '+11111111112', 'file' => 'folder/file.pdf']);
// get the status without refreshing against the API
$status = $fax->getStatus(false);
// get the status with a refresh against the API
$status = $fax->getStatus();
```

The values for the different status codes are [Documented here](https://www.interfax.net/en/help/error_codes)

### Fax Location

Each Fax has a resource location property. This is accessible as

```php
$fax->getLocation();
```

### Resend

Resending a fax will create a new Fax object:

```php
$new_fax = $fax->resend('+1111111');
// Interfax\Outbound\Fax
$fax->getLocation() === $new_fax->getLocation();
// false
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2908)

### Fax Cancel

```php
$fax->cancel();
//returns true on success
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2939)

### Fax Hide

```php
$fax->hide();
//returns true on success
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2940)

### Fax Properties

Properties on the Fax vary depending on which method call has been used to create the instance. Requesting a property that has not been received will raise a SPL ```\OutOfBoundsException```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2921)

These are all accessible on a fax instance:

```php
echo $fax->completionTime
echo $fax->duration
...
```

Note values will all be returned as strings.

For convenience, a hash array of the properties can be retrieved

```php
$fax->attributes();
```

## Exceptions

Any method call that involves a call to the Interfax RESTful API may throw an instance of ```Interfax\Exception\RequestEception```. 
 
An exception is thrown for any requests that do not return a successful HTTP Status code. The goal of this Exception is to provide a convenience wrapper around information that may have been returned.

Certain responses from the API will provide more detail, and where this occurs, it will be appended to the message of the Exception.

```
try {
    $interfax->deliver(...);
} catch (Interfax\Exception\RequestException $e) {
    echo $e->getMessage();
    // contains text detail that is available
    echo $e->getStatusCode();
    // the http status code that was received
    throw $e->getWrappedException();
    // The underlying Guzzle exception that was caught by the Interfax Client.
}
```