# A SoftLayer API PHP client.

[![Build Status](https://travis-ci.org/softlayer/softlayer-api-php-client.svg?branch=master)](https://travis-ci.org/softlayer/softlayer-api-php-client)

## Warning

```
The latest version 1.x is not backwards-compatible.
It is necessary to update scripts to function properly with the new version.
```

Use [v1.2.0](https://github.com/softlayer/softlayer-api-php-client/releases/tag/v1.2) for older PHP versions. [v2.0.0](https://github.com/softlayer/softlayer-api-php-client/releases/tag/v2.0.0) for php 8.1 or higher.

[PHP 8.0 removed XMLRPC](https://php.watch/versions/8.0/xmlrpc) as a built in extension. As such, it is no longer required as part of the composer file in this project. The XmlRpcClient still exists here if you need it, but we assume most users are using the SoapClient. If there are any issues with this [Let us know on github](https://github.com/softlayer/softlayer-api-php-client/issues)

## Overview

The SoftLayer API PHP client classes provide a simple method for connecting to and making calls from the SoftLayer API and provides support for many of the SoftLayer API's features. Method calls and client management are handled by the PHP SOAP and XML-RPC extensions.

Making API calls using the `\SoftLayer\SoapClient` is done in the following steps:

1. Instantiate a new `\SoftLayer\SoapClient` object using the `\SoftLayer\SoapClient::getClient()` method. Provide the name of the service that you wish to query, an optional id number of the object that you wish to instantiate, your SoftLayer API username, your SoftLayer API key, and an optional API endpoint base URL. The client classes default to connect over the public Internet. 
2. Use `\SoftLayer\SoapClient::API_PRIVATE_ENDPOINT` to connect to the API over SoftLayer's private network. The system making API calls must be connected to SoftLayer's private network (eg. purchased from SoftLayer or connected via VPN) in order to use the private network API endpoints.
3. Define and add optional headers to the client, such as object masks and result limits.
4. Call the API method you wish to call as if it were local to your client object. This class throws exceptions if it's unable to execute a query, so it's best to place API method calls in try / catch statements for proper error handling.

Once your method is executed you may continue using the same client if you need to connect to the same service or define another client object if you wish to work with multiple services at once.


The most up to date version of this library can be found on the SoftLayer github public repositories: [https://github.com/softlayer/softlayer-api-php-client](https://github.com/softlayer/softlayer-api-php-client) . Any issues using this library, please open a [Github Issue](https://github.com/softlayer/softlayer-api-php-client/issues)


## System Requirements

The `\SoftLayer\SoapClient` class requires at least PHP 8.0.0 and the PHP SOAP enxtension installed and enabled (`extension=soap` in the php.ini file). 
Since [php 8.0 has removed xmlrpc extension](https://php.watch/versions/8.0/xmlrpc) you will need to manually install this library to use the `\SoftLayer\XmlRpcClient`. If you are using an earlier version of php that still includes ext-xml, please use v1.2.0 of this library.

A valid API username and key are required to call the SoftLayer API. A connection to the SoftLayer private network is required to connect to SoftLayer's private network API endpopints. See [Authenticating to the SoftLayer API](https://sldn.softlayer.com/article/authenticating-softlayer-api/) for how to get these API keys.

## Installation

Install the SoftLayer API client using [Composer](https://getcomposer.org/).
```bash
composer require softlayer/softlayer-api-php-client:~2.0.0
```

## Usage

These examples use the `\SoftLayer\SoapClient` class. If you wish to use the XML-RPC API then replace mentions of `SoapClient.class.php` with `XmlrpcClient.class.php` and `\SoftLayer\SoapClient` with `\SoftLayer\XmlRpcClient`.

Here's a simple usage example that retrieves account information by calling the [getObject()](http://sldn.softlayer.com/reference/services/SoftLayer_Account/getObject) method in the [SoftLayer_Account](http://sldn.softlayer.com/reference/services/SoftLayer_Account) service:

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

$apiUsername = 'set me';
$apiKey = 'set me too';

// Initialize an API client for the SoftLayer_Account service.
$client = \SoftLayer\SoapClient::getClient('SoftLayer_Account', null, $apiUsername, $apiKey);

// Retrieve our account record
try {
    $account = $client->getObject();
    print_r($account);
} catch (\Exception $e) {
    die('Unable to retrieve account information: ' . $e->getMessage());
}
```

For a more complex example we'll retrieve a support ticket with id 123456 along with the ticket's updates, the user it's assigned to, the servers attached to it, and the datacenter those servers are in. We'll retrieve our extra information using a nested object mask. After we have the ticket we'll update it with the text 'Hello!'.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

$apiUsername = 'set me';
$apiKey = 'set me too';

// Initialize an API client for ticket 123456
$client = \SoftLayer\SoapClient::getClient('SoftLayer_Ticket', 123456, $apiUsername, $apiKey);

// Create an object mask and assign it to our API client.
$objectMask = new \SoftLayer\Common\ObjectMask();
$objectMask->updates;
$objectMask->assignedUser;
$objectMask->attachedHardware->datacenter;
$client->setObjectMask($objectMask);

// Retrieve the ticket record
try {
    $ticket = $client->getObject();
} catch (\Exception $e) {
    die('Unable to retrieve ticket record: ' . $e->getMessage());
}

// Update the ticket
$update = new \stdClass();
$update->entry = 'Hello!';

try {
    $update = $client->addUpdate($update);
    echo "Updated ticket 123456. The new update's id is " . $update[0]->id . '.');
} catch (\Exception $e) {
    die('Unable to update ticket: ' . $e->getMessage());
}
```

## Author

This software is written by the SoftLayer Development Team <[sldn@softlayer.com](mailto:sldn@softlayer.com)>.

## Copyright

This software is Copyright &copy; 2009 – 2022 [SoftLayer Technologies, Inc](http://www.softlayer.com/). See the bundled LICENSE.textile file for more information.
