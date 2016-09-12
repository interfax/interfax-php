<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package interfax/interfax
 * @author Interfax <dev@interfax.net>
 * @author Mike Smith <mike.smith@camc-ltd.co.uk>
 * @copyright Copyright (c) 2016, InterFAX
 * @license MIT
 */

namespace Interfax\Outbound;

class FaxTest extends \PHPUnit_Framework_TestCase
{

    public function test_successful_construction()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $fake_resource_url = 'https://rest.interfax.net/outbound/faxes/854759652';
        $response = new \GuzzleHttp\Psr7\Response(201, ['Location' => $fake_resource_url], -2);

        $fax = new Fax($client, $response);

        $this->assertInstanceOf('Interfax\Outbound\Fax', $fax);
        $this->assertEquals(-2, $fax->getStatus(false));
    }

    public function test_can_reload_the_status_of_the_fax()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $reload_response = new \GuzzleHttp\Psr7\Response(200, [], '{"id":279415116,"uri":"https://rest.interfax.net/outbound/faxes/279415116","status":0}');

        $client->expects($this->once())
            ->method('get')
            ->with('/outbound/faxes/854759652')
            ->will($this->returnValue($reload_response));

        $fake_resource_url = 'https://rest.interfax.net/outbound/faxes/854759652';
        $response = new \GuzzleHttp\Psr7\Response(201, ['Location' => $fake_resource_url], -2);

        $fax = new Fax($client, $response);

        $this->assertEquals(-2, $fax->getStatus(false));
        $this->assertEquals(0, $fax->getStatus());

    }
}