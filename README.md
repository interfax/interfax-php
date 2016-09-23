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

[Client](#client) [Account Info](#account-info) [Outbound](#outbound) [Inbound](#inbound) [Query Parameters](#query-parameters) [Exceptions](#exceptions)

## Client

The client follows the [12-factor](http://12factor.net/config) apps principle and can be either set directly or via environment variables.

```php
$client = new Interfax\Client(['username' => '...', 'password' => '...']);

// Alternative: will utilise environment variables:
// * INTERFAX_USERNAME
// * INTERFAX_PASSWORD

$client = new Interfax\Client();
```

### Send a Fax

To send a fax, call the deliver method on the client with the appropriate array of parameters. 

```php
$client = new Interfax\Client(['username' => '...', 'password' => '...']);
$fax = $client->deliver([
    'faxNumber' => '+442086090368',
    'file' => __DIR__ . '/../tests/Interfax/test.pdf'
]);
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2918)

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

### Get the Fax record

```php
$fax = $client->outbound->find('id');
//Interfax\Outbound\Fax || null
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2921)

### Search for Faxes

```php
$faxes = $client->outbound->search([...]);
// Interfax\Outbound\Fax[]
```

takes a single hash array, keyed by the accepted search parameters for the outbound search API endpoint

[Documentation](https://www.interfax.net/en/dev/rest/reference/2959)

### Resend a Fax

The outbound resend will return a new outbound Fax representing the re-sent fax.

```php
$fax = $client->outbound->resend($id);
// Interfax\Outbound\Fax sent to the original number
$fax = $client->outbound->resend($id, $new_number);
// Interfax\Outbound\Fax sent to the $new_number
```


## Outbound Fax

The ```Interfax\Outbound\Fax``` class wraps the details of any fax sent, and is returned by most of the ```Outbound``` methods.

It offers several methods to manage or retrieve information about the fax.

### Refreshing the Fax Details

```php
// Interfax\Outbound\Fax
$fax = $interfax->deliver(['faxNumber' => '+11111111112', 'file' => 'folder/file.pdf']);
echo $fax->status;
// -2
echo $fax->refresh()->status;
// 0
```

### Cancel

```php
$fax->cancel();
//returns true on success
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2939)


### Resend

Resending a fax will create a new Fax object:

```php
$new_fax = $fax->resend('+1111111');
// Interfax\Outbound\Fax
$fax->id === $new_fax->id;
// false
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2908)


### Hide

```php
$fax->hide();
//returns true on success
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2940)

### Fax Image

```php
$image = $fax->image();
// Interfax\Image
$image->save('path/to/save/file/to.pdf');
// returns true on success
```

The fax image format is determined by the settings on the Interfax account being used, as detailed in the [Documentation](https://www.interfax.net/en/dev/rest/reference/2937)

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

Status should always be available. The values of the status codes are [Documented here](https://www.interfax.net/en/help/error_codes) 

## Inbound

```Interfax\Client``` has an ```inbound``` property that supports the API endpoints for receiving faxes, as described in the [Documentation](https://www.interfax.net/en/dev/rest/reference/2913)

```php
$inbound = $client->inbound;
//Interfax\Inbound
```

### Get a list of incoming faxes

```php
$faxes = $inbound->incoming(['unreadOnly' => true ...]); // see docs for list of supported query params
//\Interfax\Inbound\Fax[]
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2935)

### Get an individual fax record

```php
$fax = $inbound->find(123456);
//\Interfax\Inbound\Fax || null
```

null is returned if the resource is found. Note that this could be because the user is not permissioned for the specific fax.

[Documentation](https://www.interfax.net/en/dev/rest/reference/2938)

## Inbound Fax

The incoming equivalent of the outbound fax class, the ```Interfax\Inbound\Fax``` class wraps the details of any incoming fax, and is returned by the ```Interfax\Inbound``` methods where appropriate.

### Get the fax image

```php
$image = $fax->image();
$image->save('path/to/save/location/filename.tiff');
// bool
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2937)

### Get forwarding emails

```php
$email_array = $fax->emails();
```

The array is a reflection of the values returned from the emails endpoint:

```php
[
    [
       'emailAddress' => 'username@interfax.net',
       'messageStatus' => 0,
       'completionTime' => '2012-0623T17 => 24 => 11'
    ],
    //...
];
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2930)

### Mark Read/Unread

Mark the fax as read:

```php
$fax->markRead();
// returns true or throws exception
$fax->markUnread();
// returns true or throws exception
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2936)

### Resend the email

```php
$fax->resend();
```

### Properties

As with the outbound fax, the properties of the fax are available as a single hash array:

```php
$fax->attributes()
// ['k' => 'v' ...]
```
The properties can be refreshed:

```php
echo $fax->refresh()->status;
// 32
```

## Query parameters

Where methods support a hash array structure of query parameters, these will be passed through to the API endpoint as provided. This ensures that any future parameters that might be added will be supported by the API as is.
 
The only values that are manipulated are booleans, which will be translated to the text 'TRUE' and 'FALSE' as appropriate.

_TODO_ implement support for DateTime objects.

[Documentation](https://www.interfax.net/en/dev/rest/reference/2927)

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