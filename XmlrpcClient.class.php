<?php

require_once dirname(__FILE__) . '/Common/ObjectMask.class.php';

if (!extension_loaded('xmlrpc')) {
    throw new Exception('Please load the PHP XML-RPC extension.');
}

if (version_compare(PHP_VERSION, '5', '<')) {
    throw new Exception('The SoftLayer API XML-RPC client class requires at least PHP version 5.');
}

/**
 * A SoftLayer API XML-RPC Client
 *
 * SoftLayer_XmlrpcClient provides a simple method for connecting to and making
 * calls from the SoftLayer XML-RPC API and provides support for many of the
 * SoftLayer API's features. XML-RPC method calls and client maangement are handled
 * by PHP's built-in xmlrpc extension. Your PHP installation should load the
 * XML-RPC extension in order to use this class. Furthemore, this library is
 * supported by PHP version 5 and higher. See
 * <http://us2.php.net/manual/en/soap.setup.php> for assistance loading the PHP
 * XML-RPC extension.
 *
 * Currently the SoftLayer API only allows connections from within the SoftLayer
 * private network. The system using this class must be either directly
 * connected to the SoftLayer private network (eg. purchased from SoftLayer) or
 * has access to the SoftLayer private network via a VPN connection.
 *
 * Making API calls using the SoftLayer_XmlrpcClient class is done in the
 * following steps:
 *
 * 1) Instantiate a new SoftLayer_XmlrpcClient using the
 * SoftLayer_XmlrpcClient::getClient() method. Provide the name of the service
 * that you wish to query and optionally the id number of an object that you
 * wish to instantiate.
 *
 * 2) Define and add optional headers to the client, such as object masks and
 * result limits.
 *
 * 3) Call the API method you wish to call as if it were local to your
 * SoftLayer_XmlrpcClient object. This class throws exceptions if it's unable to
 * execute a query, so it's best to place API method calls in try / catch
 * statements for proper error handling.
 *
 * Once your method is done executing you may continue using the same client if
 * you need to conenct to the same service or define another
 * SoftLayer_XmlrpcClient object if you wish to work with multiple services at
 * once.
 *
 * Here's a simple usage example that retrieves account information by calling
 * the getObject() method in the SoftLayer_Account service:
 *
 * ----------
 *
 * // Initialize an API client for the SoftLayer_Account service.
 * $client = SoftLayer_XmlrpcClient::getClient('SoftLayer_Account');
 *
 * // Retrieve our account record
 * try {
 *     $account = $client->getObject();
 *     var_dump($account);
 * } catch (Exception $e) {
 *     die('Unable to retrieve account information: ' . $e->getMessage());
 * }
 *
 * ----------
 *
 * For a more complex example we'll retrieve a support ticket with id 123456
 * along with the ticket's updates, the user it's assigned to, the servers
 * attached to it, and the datacenter those servers are in. We'll retrieve our
 * extra information using a nested object mask. After we have the ticket we'll
 * update it with the text 'Hello!'.
 *
 * ----------
 *
 * // Initialize an API client for ticket 123456
 * $client = SoftLayer_XmlrpcClient::getClient('SoftLayer_Ticket', 123456);
 *
 * // Create an object mask and assign it to our API client.
 * $objectMask = new SoftLayer_ObjectMask();
 * $objectMask->updates;
 * $objectMask->assignedUser;
 * $objectMask->attachedHardware->datacenter;
 * $client->setObjectMask($objectMask);
 *
 * // Retrieve the ticket record
 * try {
 *     $ticket = $client->getObject();
 *     var_dump($ticket);
 * } catch (Exception $e) {
 *     die('Unable to retrieve ticket record: ' . $e->getMessage());
 * }
 *
 * // Update the ticket
 * $update = new stdClass();
 * $update->entry = 'Hello!';
 *
 * try {
 *     $update = $client->addUpdate($update);
 *     echo 'Updated ticket 123456. The new update\'s id is ' . $update->id . '.');
 * } catch (Exception $e) {
 *     die('Unable to update ticket: ' . $e->getMessage());
 * }
 *
 * The most up to date version of this library can be found on the SoftLayer
 * Development Network wiki: http://sldn.softlayer.com/wiki/ . Please post to
 * the SoftLayer forums <http://forums.softlayer.com/> or open a support ticket
 * in the SoftLayer customer portal if you have any questions regarding use of
 * this library.
 *
 * @author      SoftLayer Technologies, Inc. <sldn@softlayer.com>
 * @copyright   Copyright (c) 2008, Softlayer Technologies, Inc
 * @license     http://creativecommons.org/licenses/by/3.0/us/ Creative Commons Attribution 3.0 US
 * @link        http://sldn.softlayer.com/wiki/index.php/The_SoftLayer_API The SoftLayer API
 * @see         SoftLayer_XmlrpcClient_AsynchronousAction
 */
class Softlayer_XmlrpcClient
{
    /**
     * Your SoftLayer API username. You may overide this value when calling
     * getClient().
     *
     * @var string
     */
    const API_USER = 'set me';

    /**
     * Your SoftLayer API user's authentication key. You may overide this value
     * when calling getClient().
     *
     * @link https://manage.softlayer.com/Administrative/apiKeychain API key management in the SoftLayer customer portal
     * @var string
     */
    const API_KEY = 'set me';

    /**
     * The base URL of SoftLayer XML-RPC API's endpoints.
     *
     * @var string
     */
    const API_BASE_URL = 'http://api.service.softlayer.com/xmlrpc/v3/';

    /**
     * The headers to send along with a SoftLayer API call
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * The name of the SoftLayer API service you wish to query.
     *
     * @link http://sldn.softlayer.com/wiki/index.php/Category:API_Services A list of SoftLayer API services
     * @var string
     */
    protected $_serviceName;

    /**
     * Execute a SoftLayer API method
     *
     * @return object
     */
    public function __call($functionName, $arguments = null)
    {
        $request = array();
        $request[0] = array('headers' => $this->_headers);
        $request = array_merge($request, $arguments);

        try {
            $encodedRequest = xmlrpc_encode_request($functionName, $request);

            // Making the XML-RPC call and interpreting the response is adapted
            // from the PHP manual:
            // http://www.php.net/manual/en/function.xmlrpc-encode-request.php
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $encodedRequest
                )));

            $file = file_get_contents(self::API_BASE_URL . $this->_serviceName, false, $context);

            if ($file === false) {
                throw new Exception('Unable to contact the SoftLayer API at ' . self::API_BASE_URL . $serviceName . '.');
            }

            $result = xmlrpc_decode($file);
        } catch (Exception $e) {
            throw new Exception('There was an error querying the SoftLayer API: ' . $e->getMessage());
        }

        if (xmlrpc_is_fault($result)) {
            throw new Exception('There was an error querying the SoftLayer API: ' . $result['faultString']);
        }

        // remove the resultLimit header if they set it
        $this->removeHeader('resultLimit');

        return self::convertToObject(self::convertXmlrpcTypes($result));
    }

    /**
     * Create a SoftLayer API XML-RPC Client
     *
     * Retrieve a new SoftLayer_XmlrpcClient object for a specific SoftLayer API
     * service using either the class' constants API_USER and API_KEY or a
     * custom username and API key for authentication. Provide an optional id
     * value if you wish to instantiate a particular SoftLayer API object.
     *
     * @param string $serviceName The name of the SoftLayer API service you wish to query
     * @param int $id An optional object id if you're instantiating a particular SoftLayer API object. Setting an id defines this client's initialization parameter header.
     * @param string $username An optional API username if you wish to bypass SoftLayer_XmlrpcClient's built-in username.
     * @param string $username An optional API key if you wish to bypass SoftLayer_XmlrpcClient's built-in API key.
     * @return SoftLayer_XmlrpcClient
     */
    public static function getClient($serviceName, $id = null, $username = null, $apiKey = null)
    {
        $serviceName = trim($serviceName);
        $id = trim($id);
        $username = trim($username);
        $apiKey = trim($apiKey);

        if ($serviceName == null) {
            throw new Exception('Please provide a SoftLayer API service name.');
        }

        $client = new Softlayer_XmlrpcClient();

        if ($username != null && $apiKey != null) {
            $client->setAuthentication($username, $apiKey);
        } else {
            $client->setAuthentication(self::API_USER, self::API_KEY);
        }

        $client->_serviceName = $serviceName;

        if ($id != null) {
            $client->setInitParameter($id);
        }

        return $client;
    }

    /**
     * Set a SoftLayer API call header
     *
     * Every header defines a customization specific to an SoftLayer API call.
     * Most API calls require authentication and initialization parameter
     * headers, but can also include optional headers such as object masks and
     * result limits if they're supported by the API method you're calling.
     *
     * @see removeHeader()
     * @param string $name The name of the header you wish to set
     * @param object $value The object you wish to set in this header
     */
    public function addHeader($name, $value)
    {
        if (is_object($value)) {
            $value = (array)$value;
        }

        $this->_headers[$name] = $value;
    }

    /**
     * Remove a SoftLayer API call header
     *
     * Removing headers may cause API queries to fail.
     *
     * @see addHeader()
     * @param string $name The name of the header you wish to remove
     */
    public function removeHeader($name)
    {
        unset($this->_headers[$name]);
    }

    /**
     * Set a user and key to authenticate a SoftLayer API call
     *
     * Use this method if you wish to bypass the API_USER and API_KEY class
     * constants and set custom authentication per API call.
     *
     * @link https://manage.softlayer.com/Administrative/apiKeychain API key management in the SoftLayer customer portal
     * @param string $username
     * @param string $apiKey
     */
    public function setAuthentication($username, $apiKey)
    {
        $username = trim($username);
        $apiKey = trim($apiKey);

        if ($username == null) {
            throw new Exception('Please provide a SoftLayer API username.');
        }

        if ($apiKey == null) {
            throw new Exception('Please provide a SoftLayer API key.');
        }

        $header = new stdClass();
        $header->username = $username;
        $header->apiKey   = $apiKey;

        $this->addHeader('authenticate', $header);
    }


    /**
     * Set an initialization parameter header on a SoftLayer API call
     *
     * Initialization parameters instantiate a SoftLayer API service object to
     * act upon during your API method call. For instance, if your account has a
     * server with id number 1234, then setting an initialization parameter of
     * 1234 in the SoftLayer_Hardware_Server Service instructs the API to act on
     * server record 1234 in your method calls.
     *
     * @link http://sldn.softlayer.com/wiki/index.php/Using_Initialization_Parameters_in_the_SoftLayer_API Using Initialization Parameters in the SoftLayer API
     * @param int $id The ID number of the SoftLayer API object you wish to instantiate.
     */
    public function setInitParameter($id)
    {
        $id = trim($id);

        if (!is_null($id)) {
            $initParameters = new stdClass();
            $initParameters->id = $id;
            $this->addHeader($this->_serviceName . 'InitParameters', $initParameters);
        }
    }

    /**
     * Set an object mask to a SoftLayer API call
     *
     * Use an object mask to retrieve data related your API call's result.
     * Object masks are skeleton objects that define nested relational
     * properties to retrieve along with an object's local properties.
     *
     * @see SoftLayer_ObjectMask
     * @link http://sldn.softlayer.com/wiki/index.php/Using_Object_Masks_in_the_SoftLayer_API Using object masks in the SoftLayer API
     * @link http://sldn.softlayer.com/wiki/index.php/Category:API_methods_that_can_use_object_masks API methods that can use object masks
     * @param object $mask The object mask you wish to define
     */
    public function setObjectMask($mask)
    {
        if (!is_null($mask)) {
            if (!($mask instanceof SoftLayer_ObjectMask)) {
                throw new Exception('Please provide a SoftLayer_ObjectMask to define an object mask.');
            }

            $objectMask = new stdClass();
            $objectMask->mask = $mask;

            $this->addHeader($this->_serviceName . 'ObjectMask', $objectMask);
        }
    }

    /**
     * Set a result limit on a SoftLayer API call
     *
     * Many SoftLayer API methods return a group of results. These methods
     * support a way to limit the number of results retrieved from the SoftLayer
     * API in a way akin to an SQL LIMIT statement.
     *
     * @link http://sldn.softlayer.com/wiki/index.php/Using_Result_Limits_in_the_SoftLayer_API Using Result Limits in the SoftLayer API
     * @link http://sldn.softlayer.com/wiki/index.php/Category:API_methods_that_can_use_result_limits API methods that can use result limits
     * @param int $limit The number of results to limit your SoftLayer API call to.
     * @param int $offset An optional offset to begin your SoftLayer API call's returned result set at.
     */
    public function setResultLimit($limit, $offset = 0)
    {
        $resultLimit = new stdClass();
        $resultLimit->limit = intval($limit);
        $resultLimit->offset = intval($offset);

        $this->addHeader('resultLimit', $resultLimit);
    }

    /**
     * Remove PHP xmlrpc type definition structures from a decoded request array
     *
     * Certain xmlrpc types like base64 are decoded in PHP to a stdClass with a
     * scalar property containing the decoded value of the xmlrpc member and an
     * xmlrpc_type property describing which xmlrpc type is being described. This
     * function removes xmlrpc_type data and moves the scalar value into the root of
     * the xmlrpc value for known xmlrpc types.
     *
     * @param array $result The decoded xmlrpc request to process
     * @return array
     */
    private static function convertXmlrpcTypes($result) {
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::convertXmlrpcTypes($value);
            } elseif (is_object($value) && $value->scalar != null && $value->xmlrpc_type != null) {

                // Convert known xmlrpc types, otherwise unset the value.
                switch ($value->xmlrpc_type) {
                    case 'base64':
                        $result[$key] = $value->scalar;
                        break;
                    default:
                        unset ($result[$key]);
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Recursively convert an array to an object
     *
     * Since xmlrpc_decode_result returns an array, but we want an object
     * result, so cast all array parts in our result set as objects.
     *
     * @param mixed $result A result or portion of a result to convert
     * @return mixed
     */
    private static function convertToObject($result) {
        return is_array($result) ? (object) array_map('SoftLayer_XmlrpcClient::convertToObject', $result) : $result;
    }
}