<?php

namespace SoftLayer\Tests;

use PHPUnit\Framework\TestCase;
use SoftLayer\SoapClient;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
class SoapClientTest extends TestCase
{

    public function testGetClient()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $headers = $client->getHeaders();
        $this->assertEquals('apiUsername', $headers['authenticate']->data->username);
        $this->assertEquals('apiKey', $headers['authenticate']->data->apiKey);
        $this->assertEquals(123456, $headers['SoftLayer_TicketInitParameters']->data->id);
    }

    public function testSetObjectMask()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $mask = "mask[id,test]";
        $client->setObjectMask($mask);
        $headers = $client->getHeaders();
        $this->assertEquals($mask, $headers['SoftLayer_ObjectMask']->data->mask);
    }

    public function testSetObjecFilter()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $filter = new \stdClass();
        $filter->test1 = new \stdClass();
        $filter->test1->operation = "testFilter";
        $client->setObjectFilter($filter);

        $headers = $client->getHeaders();
        print_r($headers);
        $this->assertEquals("testFilter", $headers['SoftLayer_TicketObjectFilter']->data->test1->operation);
    }
}
