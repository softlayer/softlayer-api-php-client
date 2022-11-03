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

    public function testGetClientDefaults()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket');
        $headers = $client->getHeaders();
        $this->assertEquals('set me', $headers['authenticate']->data->username);
        $this->assertEquals('set me', $headers['authenticate']->data->apiKey);
        $this->assertFalse(array_key_exists('SoftLayer_TicketInitParameters', $headers));
    }

    public function testGetClientNoService()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please provide a SoftLayer API service name.');
        $client = SoapClient::getClient('', 123456, 'apiUsername', 'apiKey');
    }

    public function testGetClientNoEndpoint()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please provide a valid API endpoint.');
        $client = SoapClient::getClient('SoftLayer_Account', 123456, 'apiUsername', 'apiKey', $endpointUrl=' ');
    }

    public function testSetObjectMask()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $mask = "mask[id,test]";
        $client->setObjectMask($mask);
        $headers = $client->getHeaders();
        $this->assertEquals($mask, $headers['SoftLayer_ObjectMask']->data->mask);
    }

    public function testSetObjectMaskClass()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $mask = new \SoftLayer\Common\ObjectMask();
        $mask->id;
        $mask->username;
        $client->setObjectMask($mask);
        $headers = $client->getHeaders();

        $this->assertEquals($mask, $headers['SoftLayer_TicketObjectMask']->data->mask);
    }

    public function testSetObjecFilter()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket', 123456, 'apiUsername', 'apiKey');
        $filter = new \stdClass();
        $filter->test1 = new \stdClass();
        $filter->test1->operation = "testFilter";
        $client->setObjectFilter($filter);
        $headers = $client->getHeaders();
        $this->assertEquals("testFilter", $headers['SoftLayer_TicketObjectFilter']->data->test1->operation);
    }

    public function testSetAuthentication()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket');
        $headers = $client->getHeaders();
        $this->assertEquals('set me', $headers['authenticate']->data->username);
        $this->assertEquals('set me', $headers['authenticate']->data->apiKey);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please provide a SoftLayer API key.');
        $client->setAuthentication('username1', '');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please provide a SoftLayer API username.');
        $client->setAuthentication(null, 'apikey2');

        $client->setAuthentication('username1', 'apikey2');
        $this->assertEquals('username1', $headers['authenticate']->data->username);
        $this->assertEquals('apikey2', $headers['authenticate']->data->apiKey);
    }

    public function testRemoveHeader()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket');
        $headers = $client->getHeaders();
        $this->assertTrue(array_key_exists('authenticate', $headers));
        $client->removeHeader('authenticate');
        $headers = $client->getHeaders();
        $this->assertFalse(array_key_exists('authenticate', $headers));
    }

    public function testSetInitParameters()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket');
        $headers = $client->getHeaders();
        $this->assertFalse(array_key_exists('SoftLayer_TicketInitParameters', $headers));;
        $client->setInitParameter(999);
        $headers = $client->getHeaders();
        $this->assertTrue(array_key_exists('authenticate', $headers));
        $this->assertEquals(999, $headers['SoftLayer_TicketInitParameters']->data->id);
    }

    public function testSetResultLimit()
    {
        $client = SoapClient::getClient('SoftLayer_Ticket');
        $headers = $client->getHeaders();
        $this->assertFalse(array_key_exists('resultLimit', $headers));;
        $client->setResultLimit(111, 999);
        $headers = $client->getHeaders();
        $this->assertTrue(array_key_exists('resultLimit', $headers));
        $this->assertEquals(111, $headers['resultLimit']->data->limit);
        $this->assertEquals(999, $headers['resultLimit']->data->offset);
    }

}

