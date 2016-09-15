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

        $response = [['messageId' =>  12, 'phoneNumber' => '111'],['messageId' => 14, 'phoneNumber' => '2222']];

        $client->expects($this->once())
            ->method('get')
            ->with('/inbound/faxes', ['query' => ['unreadOnly' => false]])
            ->will($this->returnValue($response));

        // 2 inbound faxes will be crated for the 2 response structs
        $factory = $this->getFactory(
            [
                [new Inbound\Fax($client, 12), [$client, 12, $response[0]]],
                [new Inbound\Fax($client, 40), [$client, 14, $response[1]]]
            ]);

        $inbound = new Inbound($client, $factory);

        $faxes = $inbound->incoming(['unreadOnly' => false]);

        $this->assertCount(2, $faxes);
    }

    public function test_find()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $response = ['messageId' =>  12, 'phoneNumber' => '111'];

        $client->expects($this->once())
            ->method('get')
            ->with('/inbound/faxes/12')
            ->will($this->returnValue($response));

        $fax = new Inbound\Fax($client, 12);
        $factory = $this->getFactory(
            [
                [$fax, [$client, 12, $response]],
            ]);

        $inbound = new Inbound($client, $factory);

        $this->assertEquals($fax, $inbound->find(12));
    }
}