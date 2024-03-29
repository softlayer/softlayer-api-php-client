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

namespace SoftLayer\Common;

/**
 * A simple object mask implementation.
 *
 * Use this class instead of \stdClass when defining object masks in SoftLayer
 * API calls. This one is a bit easier to use. For example, to declare a new
 * object mask using \stdClass enter:
 *
 * $objectMask = new \stdClass();
 * $objectMask->datacenter = new \stdClass();
 * $objectMask->serverRoom = new \stdClass();
 * $objectMask->provisionDate = new \stdClass();
 * $objectMask->softwareComponents = new \stdClass();
 * $objectMask->softwareComponents->passwords = new \stdClass();
 *
 * Building an object mask using ObjectMask is a bit easier to
 * type:
 *
 * $objectMask = new ObjectMask();
 * $objectMask->datacenter;
 * $objectMask->serverRoom;
 * $objectMask->provisionDate;
 * $objectMask->sofwareComponents->passwords;
 *
 * Use SoapClient::setObjectMask() to set these object masks before
 * making your SoftLayer API calls.
 *
 * For more on object mask usage in the SoftLayer API please see
 * https://sldn.softlayer.com/article/object-masks/ .
 *
 * The most up to date version of this library can be found on the SoftLayer
 * github public repositories: http://github.com/softlayer/ . 
 *
 * @author      SoftLayer Technologies, Inc. <sldn@softlayer.com>
 * @copyright   Copyright (c) 2009 - 2022, Softlayer Technologies, Inc
 * @license     http://sldn.softlayer.com/article/License
 *
 * @see         SoapClient::setObjectMask()
 * @see         XmlRpcClient::setObjectMask()
 */
class ObjectMask
{
    /**
     * Define an object mask value.
     *
     * @param string $var
     */
    public function __get($var)
    {
        $this->{$var} = new self();

        return $this->{$var};
    }
}
