# A SoftLayer API PHP client.

## Warning

```
The latest version 1.x is not backwards-compatible.
It is necessary to update scripts to function properly with the new version.
```

## Overview

The SoftLayer API PHP client classes provide a simple method for connecting to and making calls from the SoftLayer API and provides support for many of the SoftLayer API's features. Method calls and client management are handled by the PHP SOAP and XML-RPC extensions.

Making API calls using the `\SoftLayer\SoapClient` or `\SoftLayer\XmlRpcClient` classes is done in the following steps:

1. Instantiate a new `\SoftLayer\SoapClient` or `\SoftLayer\XmlRpcClient` object using the `\SoftLayer\SoapClient::getClient()` or `\SoftLayer\XmlRpcClient::getClient()` methods. Provide the name of the service that you wish to query, an optional id number of the object that you wish to instantiate, your SoftLayer API username, your SoftLayer API key, and an optional API endpoint base URL. The client classes default to connect over the public Internet. Enter `\SoftLayer\SoapClient::API_PRIVATE_ENDPOINT` or `\SoftLayer\XmlRpcClient::API_PRIVATE_ENDPOINT` to connect to the API over SoftLayer's private network. The system making API calls must be connected to SoftLayer's private network (eg. purchased from SoftLayer or connected via VPN) in order to use the private network API endpoints.
2. Define and add optional headers to the client, such as object masks and result limits.
3. Call the API method you wish to call as if it were local to your client object. This class throws exceptions if it's unable to execute a query, so it's best to place API method calls in try / catch statements for proper error handling.

Once your method is executed you may continue using the same client if you need to connect to the same service or define another client object if you wish to work with multiple services at once.

The most up to date version of this library can be found on the SoftLayer github public repositories: [http://github.com/softlayer/](http://github.com/softlayer/) . Please post to the SoftLayer forums [Stack Overflow](https://stackoverflow.com) or open a support ticket in the SoftLayer customer portal if you have any questions regarding use of this library. If you use Stack Overflow please tag your posts with “SoftLayer” so our team can easily find your post. 

## System Requirements

The `\SoftLayer\SoapClient` class requires at least PHP 5.3.0 and the PHP SOAP enxtension installed. The `\SoftLayer\XmlRpcClient` class requires PHP at least PHP 5 and the PHP XML-RPC extension installed.

A valid API username and key are required to call the SoftLayer API. A connection to the SoftLayer private network is required to connect to SoftLayer's private network API endpopints.

## Installation

Install the SoftLayer API client using [Composer](https://getcomposer.org/).
```bash
composer require softlayer/softlayer-api-php-client:~1.0@dev
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

This software is Copyright &copy; 2009 – 2010 [SoftLayer Technologies, Inc](http://www.softlayer.com/). See the bundled LICENSE.textile file for more information.
