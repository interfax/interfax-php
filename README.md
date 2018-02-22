# InterFAX PHP Library

[![PHP version](https://badge.fury.io/ph/interfax%2Finterfax.svg)](https://badge.fury.io/ph/interfax%2Finterfax)[![Build status](https://travis-ci.org/interfax/interfax-php.svg?branch=master)](https://travis-ci.org/interfax/interfax-php)

[Installation](#installation) | [Getting Started](#getting-started) | [Contributing](#contributing) | [Usage](#usage) | [License](#license)

Send and receive faxes in PHP with the [InterFAX](https://www.interfax.net/en/dev) REST API.

## Installation

This library requires PHP 5.5 and above. You may use any of the following 3 approaches to add it to your project:

### Composer

The preferred method of installation is via [Packagist](http://www.packagist.org) and [Composer](http://www.composer.org). Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require interfax/interfax
```

### Download the release

You can download the package in its entirety (from 1.0.2 onward). The [Releases](https://github.com/interfax/interfax-php/releases) page lists all stable versions. Download any file
with the name `interFAX-PHP-[RELEASE_NAME].zip` for a package including this library and its dependencies.

Uncompress the zip file you download, and include the autoloader in your project:

```php
require_once '/path/to/interFAX-PHP-[RELEASE_NAME]/vendor/autoload.php';
```

You may wish to rename the release folder to not include the RELEASE_NAME, so that you can drop in future versions without changing the include.

### Build it yourself

The [installation](INSTALLATION.md) docs explain how to create a standalone installation.

## Getting started

To send a fax for a pdf file:

```php
use Interfax\Client;

$interfax = new Client(['username' => 'username', 'password' => 'password']);
$fax = $interfax->deliver(['faxNumber' => '+11111111112', 'file' => 'folder/file.pdf']);

// get the latest status:
$fax->refresh()->status; // Pending if < 0. Error if > 0

// Simple polling
while ($fax->refresh()->status < 0) {
    sleep(5);
}
```

# Usage

[Client](#client) | [Account](#account) | [Outbound](#outbound) | [Inbound](#inbound) | [Documents](#documents) | [Helper Classes](#helper-classes) | [Query Parameters](#query-parameters) | [Exceptions](#exceptions)

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

The ```deliver``` method will take either a ```file``` or ```files``` argument. The ```files``` is an array of file values.

Each ```file``` entry can be:

* local path - if the file is larger than the allowed limit, it will be automatically uploaded as an ```Interfax\Document```
* uri (from an ```Interfax\Document```)
* an array defining a streamed resource (see below)
* ```Interfax\File``` 
* ```Interfax\Document``` 

#### Sending a stream

Because of the high degree of flexibility that PHP stream resources offer, it's not practical to retrieve information automatically from a stream to send as a fax. As such, there are certain required parameters that must be provided:

```php
$stream = fopen('/tmp/fax.pdf', 'rb');
$fax = $client->deliver([
    'faxNumber' => '+442086090368',
    'file' => [$stream, ['name' => 'fax.pdf', 'mime_type' => 'application/pdf']]
```

Note that it is assumed that the stream will not exceed the file size limitation for an inline file to be sent. However, if a size parameter is provided for the stream, and this exceeds the limit, it will be automatically uploaded as a ```Interfax\Document``` 

[Documentation](https://www.interfax.net/en/dev/rest/reference/2918)

## Account

### Balance

Determine the remaining faxing credits in your account.

```php
echo $client->getBalance();
// (string) 9.86
```

**More:** [documentation](https://www.interfax.net/en/dev/rest/reference/3001)

## Outbound

[Get list](#get-outbound-fax-list) | [Get completed list](#get-completed-fax-list) | [Get record](#get-outbound-fax-record) | [Get image](#get-outbound-fax-image) | [Cancel fax](#cancel-an-outbound-fax) | [Search](#search-fax-list) | [Resend fax](#resend-a-fax)

```Interfax\Client``` has an outbound property that can be accessed:

```php
$outbound = $client->outbound;
```

### Get outbound fax list

```php
$faxes = $client->outbound->recent();
// Interfax\Outbound\Fax[]
```

**Options:** [`limit`, `lastId`, `sortOrder`, `userId`](https://www.interfax.net/en/dev/rest/reference/2920)

----

### Get completed fax list

```php
$fax_ids = [ ... ]; // array of fax ids
$client->outbound->completed($fax_ids);
// Interfax\Outbound\Fax[]
```

### Get outbound fax record

`$client->outbound->find(fax_id)`

Retrieves information regarding a previously-submitted fax, including its current status.

```php
$fax = $client->outbound->find(123456);
//Interfax\Outbound\Fax || null
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2921)

### Get outbound fax image

`$client->outbound->find(fax_id)->image()`

The image is retrieved via a method on the outbound fax image object.

```php
$fax = $client->outbound->find(123456);
if ($fax) {
    $image = $fax->image();
    $image->save('path/to/save/file/to.pdf');
}
```

The fax image format is determined by the settings on the Interfax account being used, as detailed in the [Documentation](https://www.interfax.net/en/dev/rest/reference/2937)

### Cancel an outbound fax

`$client->outbound->find(fax_id)->cancel();`

A fax is cancelled via a method on the `Interfax\Outbound\Fax` model.

```php
$fax = $client->outbound->find(123456);
if ($fax) {
    $fax->cancel();
}
```

### Search fax list

`$client->outbound->search($options)`

Search for outbound faxes with a hash array of options keyed by the accepted search parameters for the outbound search API endpoint.

```php
$faxes = $client->outbound->search(['faxNumber' => '+1230002305555']);
// Interfax\Outbound\Fax[]
```

**Options:** [`ids`, `reference`, `dateFrom`, `dateTo`, `status`, `userId`, `faxNumber`, `limit`, `offset`](https://www.interfax.net/en/dev/rest/reference/2959)

### Resend a Fax

`$client->outbound->resend($id[,$newNumber])`

Resend the fax identified by the given id (optionally to a new fax number).

```php
$fax = $client->outbound->resend($id);
// Interfax\Outbound\Fax sent to the original number
$fax = $client->outbound->resend($id, $new_number);
// Interfax\Outbound\Fax sent to the $new_number
```

Returns a new `Interfax\Outbound\Fax` representing the newly created outbound fax.

## Inbound

[Get list](#get-inbound-fax-list) | [Get record](#get-inbound-fax-record) | [Get image](#get-inbound-fax-image) | [Get emails](#get-forwarding-emails) | [Mark as read](#mark-as-readunread) | [Resend to email](#resend-inbound-fax)

```Interfax\Client``` has an ```inbound``` property that supports the API endpoints for receiving faxes, as described in the [Documentation](https://www.interfax.net/en/dev/rest/reference/2913)

```php
$inbound = $client->inbound;
//Interfax\Inbound
```

### Get inbound fax list

`$inbound->incoming($options = []);`

Retrieves a user's list of inbound faxes.

```php
$faxes = $inbound->incoming();
//\Interfax\Inbound\Fax[]
$faxes = $inbound->incoming(['unreadOnly' => true ...]); // see docs for list of supported query params
//\Interfax\Inbound\Fax[]
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2935)

--- 

### Get inbound fax record

`$inbound->find($id);`

Retrieves a single fax's metadata (receive time, sender number, etc.).

```php
$fax = $inbound->find(123456);
//\Interfax\Inbound\Fax || null
```

null is returned if the resource is not found. Note that this could be because the user is not permissioned for the specific fax.

[Documentation](https://www.interfax.net/en/dev/rest/reference/2938)

---

### Get inbound fax image

`$inbound->find($id)->image()`

The image is retrieved via a method on the inbound fax object.

```php
$fax = $client->inbound->find(123456);
if ($fax) {
    $image = $fax->image();
    $image->save('path/to/save/file/to.pdf');
}
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2937)

---

### Get forwarding emails

`$inbound->find($id)->emails()`

The forwarding email details are retrieved via a method on the inbound fax object.

```php
$fax = $client->inbound->find(123456);
if ($fax) {
    $emails = $fax->emails(); // array structure of forwarding emails.
}
```

The returned array is a reflection of the values returned from the emails endpoint of the REST API:

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

---

### Mark as read/unread

`$inbound->find($id)->markRead();`
`$inbound->find($id)->markUnread();`

The inbound fax object provides methods to change the read status.

```php
$fax = $client->inbound->find(123456);
if ($fax) {
    $fax->markUnread(); // returns true
    $fax->markRead(); //returns true
}
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2936)

---

### Resend inbound fax

`$inbound->find($id)->resend();`

The resend method is on the inbound fax object.

```php
$fax = $client->inbound->find(123456);
if ($fax) {
    $fax->resend(); 
}
```

---

## Documents

[Create](#create-document) | [Upload chunk](#upload-chunk) | [Properties](#document-properties) | [Cancel](#cancel-document)

The ```Interfax\Document``` class allows for the uploading of larger files for faxing. The following is an example of how one should be created:

```php
$document = $client->documents->create('test.pdf', filesize('test.pdf'));
$stream = fopen('test.pdf', 'rb');
$current = 0;
while (!feof($stream)) {
    $chunk = fread($stream, 500);
    $end = $current + strlen($chunk);
    $doc->upload($current, $end-1, $chunk);
    $current = $end;
}
fclose($stream);
```

### Create document

```php
$params = [...]; // see documentation for possible params
$document = $client->documents->create($filename, filesize($filename), $params);
// Interfax\Document
```

[Documentation](https://www.interfax.net/en/dev/rest/reference/2967)

### Upload chunk

```php
$document->upload($start, $end, $data); // returns the document object.
```

Note no verification of data takes place - an exception wil be raised if values do not match appropriately.

### Document properties

As per the [documentation](https://www.interfax.net/en/dev/rest/reference/2965) a Document has a number of properties which are accessible:

```php
$document->status;
$document->fileName;
$document->refresh()->attributes();
```

```php
$document->location;
// or as returned by the API:
$document->uri;
```

### Cancel document

`$document->cancel(); //returns the $document instance`

Can be done prior to completion or afterward

## Helper Classes

### Outbound Fax

The `Interfax\Outbound\Fax` class wraps the details of any fax sent, and is returned by most of the ```Outbound``` methods.

It offers several methods to manage or retrieve information about the fax.

```php
// fluent methods that return the $fax instance
$fax->refresh(); // refreshes the data on the fax object
$fax->cancel(); // cancel the fax, returns true on success
$fax->hide(); // hides the faxes from the fax lists

$image = $fax->image(); // returns Interfax\Image
$new_fax = $fax->resend('+1111111'); // returns a new Interfax\Outbound\Fax
$fax->attributes(); // hash array of fax data properties - see details below
```

### Outbound fax properties

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

### Inbound Fax

The incoming equivalent of the outbound fax class, the ```Interfax\Inbound\Fax``` class wraps the details of any incoming fax, and is returned by the ```Interfax\Inbound``` methods where appropriate.

```php
// fluent methods that return the $fax instance for method chaining
$fax->refresh(); // reload properties of the inbound fax
$fax->markRead(); // mark the fax read - returns true or throws exception
$fax->markUnread(); // mark the fax unread - returns true or throws exception
$fax->resend();

$image = $fax->image(); // Returns a Interfax\Image for this fax
$email_array = $fax->emails(); // see below for details on the structure of this array
$fax->attributes(); // hash array of properties
```

## Query parameters

Where methods support a hash array structure of query parameters, these will be passed through to the API endpoint as provided. This ensures that any future parameters that might be added will be supported by the API as is.
 
The only values that are manipulated are booleans, which will be translated to the text 'TRUE' and 'FALSE' as appropriate.

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

## Contributing

 1. **Fork** the repo on GitHub
 2. **Clone** the project to your own machine
 3. **Commit** changes to your own branch
 4. **Push** your work back up to your fork
 5. Submit a **Pull request** so that we can review your changes
 
### Running tests

Ensure that composer is installed, then run the following commands.

```sh
composer install
./vendor/bin/phpunit
```

## License

This library is released under the [MIT License](LICENSE).
