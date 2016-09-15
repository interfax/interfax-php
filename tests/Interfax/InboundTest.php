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
namespace Interfax;

class InboundTest extends BaseTest
{

    public function test_incoming()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $response = [['id' =>  12],['id' => 14]];

        $client->expects($this->once())
            ->method('get')
            ->with('/inbound/faxes', ['unreadOnly' => false])
            ->will($this->returnValue($response));

        // 2 inbound faxes will be crated for the 2 response structs
        $factory = $this->getFactory([new Inbound\Fax($client), new Inbound\Fax($client)]);

        $inbound = new Inbound($client, $factory);

        $faxes = $inbound->incoming(['unreadOnly' => false]);

        $this->assertCount(2, $faxes);
    }
}