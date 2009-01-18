<?php

/**
 * A simple object mask implementation.
 *
 * Use this class instead of stdClass when defining object masks in SoftLayer
 * API calls. This one is a bit easier to use. For example, to declare a new
 * object mask using stdClass enter:
 *
 * $objectMask = new StdClass();
 * $objectMask->datacenter = new StdClass();
 * $objectMask->serverRoom = new StdClass();
 * $objectMask->provisionDate = new StdClass();
 * $objectMask->softwareComponents = new StdClass();
 * $objectMask->softwareComponents->passwords = new StdClass();
 *
 * Building an object mask using SoftLayer_ObjectMask is a bit easier to
 * type:
 *
 * $objectMask = new SoftLayer_ObjectMask();
 * $objectMask->datacenter;
 * $objectMask->serverRoom;
 * $objectMask->provisionDate;
 * $objectMask->sofwareComponents->passwords;
 *
 * Use SoftLayer_SoapClient::setObjectMask() to set these object masks before
 * making your SoftLayer API calls.
 *
 * For more on object mask usage in the SoftLayer API please see
 * http://sldn.softlayer.com/wiki/index.php/Using_Object_Masks_in_the_SoftLayer_API .
 *
 * @author      SoftLayer Technologies, Inc. <sldn@softlayer.com>
 * @copyright   Copyright (c) 2008, Softlayer Technologies, Inc
 * @license     http://creativecommons.org/licenses/by/3.0/us/ Creative Commons Attribution 3.0 US
 * @see         SoftLayer_SoapClient::setObjectMask()
 */
class SoftLayer_ObjectMask
{
    /**
     * Define an object mask value
     *
     * @param string $var
     */
    public function __get($var)
    {
        $this->{$var} = new SoftLayer_ObjectMask();

        return $this->{$var};
    }
}