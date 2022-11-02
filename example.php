<?php
/**
 * Copyright (c) 2010, SoftLayer Technologies, Inc. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *  * Neither SoftLayer Technologies, Inc. nor the names of its contributors may
 *    be used to endorse or promote products derived from this software without
 *   specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

use SoftLayer\Common\ObjectMask;
use SoftLayer\SoapClient;
use SoftLayer\XmlRpcClient;

/**
 * Start by including the autoload class.
 *
 * If you wish to use the XML-RPC API then replace mentions of
 * SoapClient with XmlRpcClient.
 */
$files = [
    __DIR__.'/vendor/autoload.php',
    __DIR__.'/../../autoload.php',
];

$autoload = false;
foreach ($files as $file) {
    if (is_file($file)) {
        $autoload = include_once $file;

        break;
    }
}

if (!$autoload) {
    die(<<<MSG
Unable to find autoload.php file, please use composer to load dependencies:

wget http://getcomposer.org/composer.phar
php composer.phar install

Visit http://getcomposer.org/ for more information.

MSG
);
}

/**
 * It's possible to define your SoftLayer API username and key directly in the
 * class file, but it's far easier to define them before creating your API client.
 */
$apiUsername = getenv('SL_USER');
$apiKey = getenv('SL_APIKEY');

/**
 * Usage:
 * SoapClient::getClient([API Service], <object id>, [username], [API key]);
 *
 * API Service: The name of the API service you wish to connect to.
 * id:          An optional id to initialize your API service with, if you're
 *              interacting with a specific object. If you don't need to specify
 *              an id then pass null to the client.
 * username:    Your SoftLayer API username.
 * API key:     Your SoftLayer API key,
 */
$client = SoapClient::getClient('SoftLayer_Account', null, $apiUsername, $apiKey);
$objectMask = "mask[id,companyName]";
$client->setObjectMask($objectMask);

/**
 * Once your client object is created you can call API methods for that service
 * directly against your client object. A call may throw an exception on error,
 * so it's best to try your call and catch exceptions.
 *
 * This example calls the getObject() method in the SoftLayer_Account API
 * service. <http://sldn.softlayer.com/reference/services/SoftLayer_Account/getObject>
 * It retrieves basic account information, and is a great way to test your API
 * account and connectivity.
 */

try {
    $result = $client->getObject();
    //$client->__getLastRequest();
    print_r($result);
} catch (\Exception $e) {
    die($e->getMessage());
}

/**
 * In this example we will get all of the VirtualGuests on our account. And for each guest we will print out
 * some basic information about them, along with make another API call to get its primaryIpAddress.
 */

// Declare an API client to connect to the SoftLayer_Ticket API service.
$client = SoapClient::getClient('SoftLayer_Account', null, $apiUsername, $apiKey);


// Assign an object mask to our API client:
$objectMask = "mask[id, hostname, datacenter[longName]]";
$client->setObjectMask($objectMask);

try {
    $virtualGuests = $client->getVirtualGuests();
    print("Id, Hostname, Datacenter, Ip Address\n");
    foreach ($virtualGuests as $guest) {
        $guestClient = SoapClient::getClient('SoftLayer_Virtual_Guest', $guest->id, $apiUsername, $apiKey);
        $ipAddress = $guestClient->getPrimaryIpAddress();
        print($guest->id . ", " . $guest->hostname . ", " . $guest->datacenter->longName . ", " . $ipAddress . "\n");
        break;
    }
} catch (\Exception $e) {
    die('Unable to retrieve virtual guests: ' . $e->getMessage());
}
