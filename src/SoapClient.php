<?php
/**
 * Copyright (c) 2009 - 2022, SoftLayer Technologies, Inc. All rights reserved.
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
use SoftLayer\SoapClient\AsynchronousAction;

/**
 * A SoftLayer API SOAP Client.
 *
 * Please see the bundled README file for more details and usage information.
 *
 * This client supports sending multiple calls in parallel to the SoftLaye API. 
 * Please see the documentation in the AsynchronousAction class in
 * SoapClient/AsynchronousAction.php for details.
 *
 * The most up to date version of this library can be found on the SoftLayer
 * github public repositories: https://github.com/softlayer/softlayer-api-php-client .
 * For any issues with this library, please open a github issue
 *
 * @author      SoftLayer Technologies, Inc. <sldn@softlayer.com>
 * @copyright   Copyright (c) 2009 - 2022, Softlayer Technologies, Inc
 * @license     http://sldn.softlayer.com/article/License
 * @link        https://sldn.softlayer.com/article/php/ The SoftLayer API
 * @see         AsynchronousAction

 */
class SoapClient extends \SoapClient
{
    /**
     * Your SoftLayer API username. You may overide this value when calling getClient().
     *
     * @var string
     */
    const API_USER = 'set me';

    /**
     * Your SoftLayer API user's authentication key. You may overide this value when calling getClient().
     *
     * @link https://sldn.softlayer.com/article/authenticating-softlayer-api/ API key management in the SoftLayer customer portal
     * @var string
     */
    const API_KEY = 'set me';

    /**
     * The base URL of the SoftLayer SOAP API's WSDL files over the public Internet.
     *
     * @var string
     */
    const API_PUBLIC_ENDPOINT = 'https://api.softlayer.com/soap/v3.1/';

    /**
     * The base URL of the SoftLayer SOAP API's WSDL files over SoftLayer's private network.
     *
     * @var string
     */
    const API_PRIVATE_ENDPOINT = 'http://api.service.softlayer.com/soap/v3.1/';

    /**
     * The namespace to use for calls to the API.
     *
     * $var string
     */
    const DEFAULT_NAMESPACE = 'http://api.service.softlayer.com/soap/v3.1/';

    /**
     * The API endpoint base URL used by the client.
     *
     * @var string
     */
    const API_BASE_URL = SoapClient::API_PUBLIC_ENDPOINT;

    /**
     * An optional SOAP timeout if you want to set a timeout independent of PHP's socket timeout.
     *
     * @var int
     */
    const SOAP_TIMEOUT = null;

    /**
     * The SOAP headers to send along with a SoftLayer API call.
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
     * The base URL to the SoftLayer API's WSDL files being used by this
     * client.
     *
     * @var string
     */
    protected $_endpointUrl;

    /**
     * Whether or not the current call is an asynchronous call.
     *
     * @var bool
     */
    protected $_asynchronous = false;

    /**
     * The object that handles asynchronous calls if the current call is an
     * asynchronous call.
     *
     * @var AsynchronousAction
     */
    private $_asyncAction = null;

    /**
     * If making an asynchronous call, then this is the name of the function
     * we're calling.
     *
     * @var string
     */
    public $asyncFunctionName = null;

    /**
     * If making an asynchronous call, then this is the result of an
     * asynchronous call as retuned from the
     * AsynchronousAction class.
     *
     * @var object
     */
    private $_asyncResult = null;

    /**
     * Used when making asynchronous calls.
     *
     * @var bool
     */
    public $oneWay;


    /**
     * Execute a SoftLayer API method.
     *
     * @return object
     */
    public function __call($functionName, $arguments = null)
    {
        // Determine if we shoud be making an asynchronous call. If so strip
        // "Async" from the end of the method name.
        if ($this->_asyncResult === null) {
            $this->_asynchronous = false;
            $this->_asyncAction = null;

            if (preg_match('/Async$/', $functionName)) {
                $this->_asynchronous = true;
                $functionName = str_replace('Async', '', $functionName);

                $this->asyncFunctionName = $functionName;
            }
        }

        try {
            $result = parent::__soapCall($functionName, $arguments, null, $this->_headers);
        } catch (\SoapFault $e) {
            throw new \Exception('There was an error querying the SoftLayer API: ' . $e->getMessage(), 0, $e);
        }

        if ($this->_asynchronous) {
            return $this->_asyncAction;
        }

        // remove the resultLimit header if they set it
        $this->removeHeader('resultLimit');

        return $result;
    }

    /**
     * Create a SoftLayer API SOAP Client.
     *
     * Retrieve a new SoapClient object for a specific SoftLayer API service using either the class' 
     * constants API_USER and API_KEY or a custom username and API key for authentication. 
     * Provide an optional id value if you wish to instantiate a particular SoftLayer API object.
     *
     * @param string $serviceName The name of the SoftLayer API service you wish to query
     * @param int    $id          An optional object id if you're instantiating a particular SoftLayer API object. Setting an id defines this client's initialization parameter header.
     * @param string $username    an optional API username if you wish to bypass SoapClient's built-in username
     * @param string $apiKey      an optional API key if you wish to bypass SoapClient's built-in API key
     * @param string $endpointUrl The API endpoint base URL you wish to connect to. Set this to SoapClient::API_PRIVATE_ENDPOINT to connect via SoftLayer's private network.
     * @param bool $trace Enabled SOAP trace in the client object.
     * @return SoapClient
     */
    public static function getClient($serviceName, $id = null, $username = null, $apiKey = null, $endpointUrl = null, $trace = false)
    {
        $serviceName = trim($serviceName);

        if (empty($serviceName)) {
            throw new \Exception('Please provide a SoftLayer API service name.');
        }

        /*
         * Default to use the public network API endpoint, otherwise use the
         * endpoint defined in API_PUBLIC_ENDPOINT, otherwise use the one
         * provided by the user.
         */
        if (!empty($endpointUrl)) {
            $endpointUrl = trim($endpointUrl);

            if (empty($endpointUrl)) {
                throw new \Exception('Please provide a valid API endpoint.');
            }
        } elseif (self::API_BASE_URL !== null) {
            $endpointUrl = self::API_BASE_URL;
        } else {
            $endpointUrl = self::API_PUBLIC_ENDPOINT;
        }

        $soapOptions = ['trace' => $trace];
        if (is_null(self::SOAP_TIMEOUT) == false) {
            $soapOptions['connection_timeout'] = self::SOAP_TIMEOUT;
        }

        $soapClient = new self($endpointUrl . $serviceName . '?wsdl', $soapOptions);


        $soapClient->_serviceName = $serviceName;
        $soapClient->_endpointUrl = $endpointUrl;

        if (!empty($username) && !empty($apiKey)) {
            $soapClient->setAuthentication($username, $apiKey);
        } else {
            $soapClient->setAuthentication(self::API_USER, self::API_KEY);
        }

        if (!empty($id)) {
            $soapClient->setInitParameter($id);
        }

        return $soapClient;
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
     * @return SoapClient
     */
    public function addHeader($name, $value)
    {
        $this->_headers[$name] = new \SoapHeader(self::DEFAULT_NAMESPACE, $name, $value);

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
     * @return SoapClient
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
     * @see https://sldn.softlayer.com/article/authenticating-softlayer-api/ API key management in the SoftLayer customer portal
     *
     * @param string $username
     * @param string $apiKey
     *
     * @return SoapClient
     */
    public function setAuthentication($username, $apiKey)
    {
        $username = trim($username);

        if (empty($username)) {
            throw new \Exception('Please provide a SoftLayer API username.');
        }

        $apiKey = trim($apiKey);

        if (empty($apiKey)) {
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
     * @link https://sldn.softlayer.com/article/using-initialization-parameters-softlayer-api/ Using Initialization Parameters in the SoftLayer API
     * @param int $id The ID number of the SoftLayer API object you wish to instantiate.

     * @return SoapClient
     */
    public function setInitParameter($id)
    {
        $id = trim($id);

        if ($id !== '') {
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
        if (!empty($mask)) {
            $header = 'SoftLayer_ObjectMask';

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
     * Set an object filter to a SoftLayer API call.
     *
     * Use an object filter to limit what data you get back
     * from the API. Very similar to objectMasks
     *
     * @see ObjectMask
     *
     * @param object $objectFilter The object filter you wish to define
     *
     * @return SoapClient
     */
    public function setObjectFilter($objectFilter)
    {
        if (!empty($objectFilter)) {
            $header = sprintf('%sObjectFilter', $this->_serviceName);
            $this->addHeader($header, $objectFilter);
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
     * @link https://sldn.softlayer.com/article/using-result-limits-softlayer-api/ Using Result Limits in the SoftLayer API
     * @param int $limit The number of results to limit your SoftLayer API call to.
     * @param int $offset An optional offset to begin your SoftLayer API call's returned result set at.

     * @return SoapClient
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
     * Process a SOAP request.
     *
     * We've overwritten the PHP \SoapClient's __doRequest() to allow processing
     * asynchronous SOAP calls. If an asynchronous call was detected in the
     * __call() method then send processing to the
     * AsynchronousAction class. Otherwise use the
     * \SoapClient's built-in __doRequest() method. The results of this method
     * are sent back to __call() for post-processing. Asynchronous calls use
     * handleAsyncResult() to send he results of the call back to __call().
     *
     * @return object
     */
    public function __doRequest($request, $location, $action, $version, $one_way = false)
    {
        // Don't make a call if we already have an asynchronous result.
        if ($this->_asyncResult !== null) {
            $result = $this->_asyncResult;
            $this->_asyncResult = null;

            return $result;
        }

        if ($this->oneWay) {
            $one_way = true;
            $this->oneWay = false;
        }

        // Use either the \SoapClient or AsynchronousAction
        // class to handle the call.
        if (!$this->_asynchronous) {
            $result = parent::__doRequest($request, $location, $action, $version, $one_way);

            return $result;
        }

        $this->_asyncAction = new AsynchronousAction($this, $this->asyncFunctionName, $request, $location, $action);

        return '';
    }

    /**
     * Process the results of an asynchronous call.
     *
     * The AsynchronousAction class uses
     * handleAsyncResult() to return it's call results back to this classes'
     * __call() method for post-processing.
     *
     * @param string $functionName the name of the SOAP method called
     * @param string $result       The raw SOAP XML output from a SOAP call
     *
     * @return object
     */
    public function handleAsyncResult($functionName, $result)
    {
        $this->_asynchronous = false;
        $this->_asyncResult = $result;

        return $this->__call($functionName, array());
    }

    /**
     * Returns the headers set for this client object.
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Returns the service name
     * 
     * @return string
     */
    public function getServiceName()
    {
        return $this->_serviceName;
    }

    /**
     * Returns the endpoint URL
     * 
     * @return string
     */
    public function getEndpointUrl()
    {
        return $this->_endpointUrl;
    }
}
