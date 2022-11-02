<?php

/*
 * Copyright (c) 2009 - 2010, SoftLayer Technologies, Inc. All rights reserved.
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
 *    specific prior written permission.
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

namespace SoftLayer;

use SoftLayer\Common\ObjectMask;

/**
 * A SoftLayer API XML-RPC Client.
 *
 * Please see the bundled README.textile file for more details and usage
 * information.
 *
 * The most up to date version of this library can be found on the SoftLayer
 * github public repositories: http://github.com/softlayer/ . Please post to
 * the SoftLayer forums <http://forums.softlayer.com/> or open a support ticket
 * in the SoftLayer customer portal if you have any questions regarding use of
 * this library.
 *
 * @author     SoftLayer Technologies, Inc. <sldn@softlayer.com>
 * @copyright  Copyright (c) 2009 - 2010, Softlayer Technologies, Inc
 * @license    http://sldn.softlayer.com/article/License
 *
 * @see        http://sldn.softlayer.com/article/The_SoftLayer_API The SoftLayer API
 */
class XmlRpcClient
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
     * @see https://manage.softlayer.com/Administrative/apiKeychain API key management in the SoftLayer customer portal
     *
     * @var string
     */
    const API_KEY = 'set me';

    /**
     * The base URL of SoftLayer XML-RPC API's public network endpoints.
     *
     * @var string
     */
    const API_PUBLIC_ENDPOINT = 'https://api.softlayer.com/xmlrpc/v3/';

    /**
     * The base URL of SoftLayer XML-RPC API's private network endpoints.
     *
     * @var string
     */
    const API_PRIVATE_ENDPOINT = 'http://api.service.softlayer.com/xmlrpc/v3/';

    /**
     * The API endpoint base URL used by the client.
     *
     * @var string
     */
    const API_BASE_URL = self::API_PUBLIC_ENDPOINT;

    /**
     * The headers to send along with a SoftLayer API call.
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * The name of the SoftLayer API service you wish to query.
     *
     * @see http://sldn.softlayer.com/reference/services A list of SoftLayer API services
     *
     * @var string
     */
    protected $_serviceName;

    /**
     * The base URL of SoftLayer XML-RPC API's endpoints used by this client.
     *
     * @var string
     */
    protected $_endpointUrl;

    /**
     * Execute a SoftLayer API method.
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
                    'content' => $encodedRequest,
                ), ));

            $file = file_get_contents($this->_endpointUrl.$this->_serviceName, false, $context);

            if (false === $file) {
                throw new \Exception('Unable to contact the SoftLayer API at '.$this->_endpointUrl.$serviceName.'.');
            }

            $result = xmlrpc_decode($file);
        } catch (\Exception $e) {
            throw new \Exception('There was an error querying the SoftLayer API: '.$e->getMessage());
        }

        if (is_array($result) && xmlrpc_is_fault($result)) {
            throw new \Exception('There was an error querying the SoftLayer API: '.$result['faultString']);
        }

        // remove the resultLimit header if they set it
        $this->removeHeader('resultLimit');

        return self::_convertToObject(self::_convertXmlrpcTypes($result));
    }

    /**
     * Create a SoftLayer API XML-RPC Client.
     *
     * Retrieve a new XmlRpcClient object for a specific SoftLayer API
     * service using either the class' constants API_USER and API_KEY or a
     * custom username and API key for authentication. Provide an optional id
     * value if you wish to instantiate a particular SoftLayer API object.
     *
     * @param string $serviceName The name of the SoftLayer API service you wish to query
     * @param int    $id          An optional object id if you're instantiating a particular SoftLayer API object. Setting an id defines this client's initialization parameter header.
     * @param string $username    an optional API username if you wish to bypass XmlRpcClient's built-in username
     * @param string $apiKey      an optional API key if you wish to bypass XmlRpcClient's built-in API key
     * @param string $endpointUrl The API endpoint base URL you wish to connect to. Set this to XmlRpcClient::API_PRIVATE_ENDPOINT to connect via SoftLayer's private network.
     *
     * @return XmlRpcClient
     */
    public static function getClient($serviceName, $id = null, $username = null, $apiKey = null, $endpointUrl = null)
    {
        $serviceName = trim($serviceName);

        if ('' === $serviceName) {
            throw new \Exception('Please provide a SoftLayer API service name.');
        }

        $client = new self();

        /*
         * Default to use the public network API endpoint, otherwise use the
         * endpoint defined in API_PUBLIC_ENDPOINT, otherwise use the one
         * provided by the user.
         */
        if (null !== $endpointUrl) {
            $endpointUrl = trim($endpointUrl);

            if ('' === $endpointUrl) {
                throw new \Exception('Please provide a valid API endpoint.');
            }

            $client->_endpointUrl = $endpointUrl;
        } elseif (self::API_BASE_URL != null) {
            $client->_endpointUrl = self::API_BASE_URL;
        } else {
            $client->_endpointUrl = self::API_PUBLIC_ENDPOINT;
        }

        $username = trim($username);
        $apiKey = trim($apiKey);

        if ('' !== $username && '' !== $apiKey) {
            $client->setAuthentication($username, $apiKey);
        } else {
            $client->setAuthentication(self::API_USER, self::API_KEY);
        }

        $client->_serviceName = $serviceName;

        $id = trim($id);

        if ('' !== $id) {
            $client->setInitParameter($id);
        }

        return $client;
    }

    /**
     * Set a SoftLayer API call header.
     *
     * Every header defines a customization specific to an SoftLayer API call.
     * Most API calls require authentication and initialization parameter
     * headers, but can also include optional headers such as object masks and
     * result limits if they're supported by the API method you're calling.
     *
     * @see removeHeader()
     *
     * @param string $name  The name of the header you wish to set
     * @param object $value The object you wish to set in this header
     *
     * @return XmlRpcClient
     */
    public function addHeader($name, $value)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        $this->_headers[$name] = $value;

        return $this;
    }

    /**
     * Remove a SoftLayer API call header.
     *
     * Removing headers may cause API queries to fail.
     *
     * @see addHeader()
     *
     * @param string $name The name of the header you wish to remove
     *
     * @return XmlRpcClient
     */
    public function removeHeader($name)
    {
        unset($this->_headers[$name]);

        return $this;
    }

    /**
     * Set a user and key to authenticate a SoftLayer API call.
     *
     * Use this method if you wish to bypass the API_USER and API_KEY class
     * constants and set custom authentication per API call.
     *
     * @see https://manage.softlayer.com/Administrative/apiKeychain API key management in the SoftLayer customer portal
     *
     * @param string $username
     * @param string $apiKey
     *
     * @return XmlRpcClient
     */
    public function setAuthentication($username, $apiKey)
    {
        $username = trim($username);

        if ('' === $username) {
            throw new \Exception('Please provide a SoftLayer API username.');
        }

        $apiKey = trim($apiKey);

        if ('' === $apiKey) {
            throw new \Exception('Please provide a SoftLayer API key.');
        }

        $header = new \stdClass();
        $header->username = $username;
        $header->apiKey = $apiKey;

        $this->addHeader('authenticate', $header);

        return $this;
    }

    /**
     * Set an initialization parameter header on a SoftLayer API call.
     *
     * Initialization parameters instantiate a SoftLayer API service object to
     * act upon during your API method call. For instance, if your account has a
     * server with id number 1234, then setting an initialization parameter of
     * 1234 in the SoftLayer_Hardware_Server Service instructs the API to act on
     * server record 1234 in your method calls.
     *
     * @see http://sldn.softlayer.com/article/Using_Initialization_Parameters_in_the_SoftLayer_API Using Initialization Parameters in the SoftLayer API
     *
     * @param int $id the ID number of the SoftLayer API object you wish to instantiate
     *
     * @return XmlRpcClient
     */
    public function setInitParameter($id)
    {
        $id = trim($id);

        if ('' !== $id) {
            $initParameters = new \stdClass();
            $initParameters->id = $id;
            $this->addHeader($this->_serviceName.'InitParameters', $initParameters);
        }

        return $this;
    }

    /**
     * Set an object mask to a SoftLayer API call.
     *
     * Use an object mask to retrieve data related your API call's result.
     * Object masks are skeleton objects or strings that define nested relational
     * properties to retrieve along with an object's local properties.
     *
     * @see ObjectMask
     * @see http://sldn.softlayer.com/article/Using-Object-Masks-SoftLayer-API Using object masks in the SoftLayer API
     *
     * @param object $mask The object mask you wish to define
     *
     * @return SoapClient
     */
    public function setObjectMask($mask)
    {
        if (null !== $mask) {
            $header = 'ObjectMask';

            if ($mask instanceof ObjectMask) {
                $header = sprintf('%sObjectMask', $this->_serviceName);
            }

            $objectMask = new \stdClass();
            $objectMask->mask = $mask;
            $this->addHeader($header, $objectMask);
        }

        return $this;
    }

    /**
     * Set a result limit on a SoftLayer API call.
     *
     * Many SoftLayer API methods return a group of results. These methods
     * support a way to limit the number of results retrieved from the SoftLayer
     * API in a way akin to an SQL LIMIT statement.
     *
     * @see http://sldn.softlayer.com/article/Using_Result_Limits_in_the_SoftLayer_API Using Result Limits in the SoftLayer API
     *
     * @param int $limit  the number of results to limit your SoftLayer API call to
     * @param int $offset an optional offset to begin your SoftLayer API call's returned result set at
     *
     * @return XmlRpcClient
     */
    public function setResultLimit($limit, $offset = 0)
    {
        $resultLimit = new \stdClass();
        $resultLimit->limit = (int) $limit;
        $resultLimit->offset = (int) $offset;

        $this->addHeader('resultLimit', $resultLimit);

        return $this;
    }

    /**
     * Remove PHP xmlrpc type definition structures from a decoded request array.
     *
     * Certain xmlrpc types like base64 are decoded in PHP to a \stdClass with a
     * scalar property containing the decoded value of the xmlrpc member and an
     * xmlrpc_type property describing which xmlrpc type is being described. This
     * function removes xmlrpc_type data and moves the scalar value into the root of
     * the xmlrpc value for known xmlrpc types.
     *
     * @param mixed $result The decoded xmlrpc request to process
     *
     * @return mixed
     */
    private static function _convertXmlrpcTypes($result)
    {
        if (is_array($result)) {
            // Return case 1: The result is an empty array. Return the empty
            // array.
            if (empty($result)) {
                return $result;
            }

            // Return case 2: The result is a non-empty array. Loop through
            // array elements and recursively translate every element.
            // Return the fully translated array.
            foreach ($result as $key => $value) {
                $result[$key] = self::_convertXmlrpcTypes($value);
            }

            return $result;
        }

        // Return case 3: The result is an xmlrpc scalar. Convert it to a normal
        // variable and return it.
        if (is_object($result) && null !== $result->scalar && null !== $result->xmlrpc_type) {
            // Convert known xmlrpc types, otherwise unset the value.
            switch ($result->xmlrpc_type) {
                case 'base64':
                    return $result->scalar;
                default:
                    return null;
            }
        }

        // Return case 4: Otherwise the result is a non-array and non xml-rpc
        // scalar variable. Return it unmolested.

        return $result;
    }

    /**
     * Recursively convert an array to an object.
     *
     * Since xmlrpc_decode_result returns an array, but we want an object
     * result, so cast all array parts in our result set as objects.
     *
     * @param mixed $result A result or portion of a result to convert
     *
     * @return mixed
     */
    private static function _convertToObject($result)
    {
        return is_array($result) ? (object) array_map('\\SoftLayer\\XmlRpcClient::_convertToObject', $result) : $result;
    }
}
