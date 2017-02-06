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
namespace Test\Interfax;

use GuzzleHttp\Psr7\Response;
use Interfax\Inbound;

class InboundTest extends BaseTest
{

    public function test_incoming()
    {
        $response = [['messageId' =>  12, 'phoneNumber' => '111'],['messageId' => 14, 'phoneNumber' => '2222']];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response('200', ['Content-type' => 'text/json'], json_encode($response))
        ], $container);

        // 2 inbound faxes will be crated for the 2 response structs
        $factory = $this->getFactory(
            [
                [new Inbound\Fax($client, 12), [$client, 12, $response[0]]],
                [new Inbound\Fax($client, 40), [$client, 14, $response[1]]]
            ]);

        $inbound = new Inbound($client, $factory);

        $faxes = $inbound->incoming(['unreadOnly' => false]);

        $this->assertCount(2, $faxes);
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/inbound/faxes', $transaction['request']->getUri()->getPath());
        $this->assertEquals('unreadOnly=FALSE', $transaction['request']->getUri()->getQuery());
    }

    public function test_find()
    {
        $response = ['messageId' =>  12, 'phoneNumber' => '111'];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response('200', ['Content-type' => 'text/json'], json_encode($response))
        ], $container);

        $fax = new Inbound\Fax($client, 12);
        $factory = $this->getFactory(
            [
                [$fax, [$client, 12, $response]],
            ]);

        $inbound = new Inbound($client, $factory);

        $this->assertEquals($fax, $inbound->find(12));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/inbound/faxes/12', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
    }

}