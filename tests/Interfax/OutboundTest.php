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
use Interfax\Outbound;

class OutboundTest extends BaseTest
{
    public function test_completed()
    {
        $response = [['id' =>  12, 'senderCSID' => 'Interfax'],['id' => 14, 'senderCSID' => 'Interfax']];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response('200', ['Content-type' => 'text/json'], json_encode($response))
        ], $container);

        $fax1 = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();
        $fax2 = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();
        $factory = $this->getFactory([
            [$fax1, [$client, 12, $response[0]]],
            [$fax2, [$client, 14, $response[1]]]
        ]);

        $outbound = new Outbound($client, $factory);

        $res = $outbound->completed(['12', '14']);

        $this->assertEquals([$fax1, $fax2], $res);
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/completed', $transaction['request']->getUri()->getPath());
        $this->assertEquals('ids=' . urlencode('12,14'), $transaction['request']->getUri()->getQuery());
    }

    public function test_recent()
    {
        $response = [['id' =>  21]];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response('200', ['Content-type' => 'text/json'], json_encode($response))
        ], $container);

        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();

        $factory = $this->getFactory([[$fax, [$client, 21, $response[0]]]]);

        $outbound = new Outbound($client, $factory);

        $res = $outbound->recent(['limit' => 5]);

        $this->assertEquals([$fax], $res);
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes', $transaction['request']->getUri()->getPath());
        $this->assertEquals('limit=5', $transaction['request']->getUri()->getQuery());
    }

    public function test_resend_uses_outbound_fax()
    {
        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->setMethods(['resend'])
            ->getMock();

        $resent_fax = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();

        $fax->expects($this->once())
            ->method('resend')
            ->will($this->returnValue($resent_fax));

        $factory = $this->getFactory([$fax]);

        $client = $this->getMockBuilder('Interfax\Client')->disableOriginalConstructor()->getMock();

        $outbound = new Outbound($client, $factory);

        $this->assertEquals($resent_fax, $outbound->resend(34552));
    }

    public function test_resend_with_new_number_uses_outbound_fax()
    {
        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->setMethods(['resend'])
            ->getMock();

        $fax_number = '+112122323';

        $resent_fax = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();

        $fax->expects($this->once())
            ->method('resend')
            ->with($fax_number)
            ->will($this->returnValue($resent_fax));

        $factory = $this->getFactory([$fax]);

        $client = $this->getMockBuilder('Interfax\Client')->disableOriginalConstructor()->getMock();

        $outbound = new Outbound($client, $factory);

        $this->assertEquals($resent_fax, $outbound->resend(34552, $fax_number));
    }

    public function test_search()
    {
        $container = [];

        $search_results = [
            ['id' => 5, 'status' => -32],
            ['id' => 9, 'status' => 40],
        ];

        $client = $this->getClientWithResponses([
            new Response(200, ['Content-Type' => 'text/json'], json_encode($search_results))
        ], $container);

        $test_params = ['status' => 'Inprocess'];

        $factory = $this->getFactory([
            [new Outbound\Fax($client,5),[$client, 5, $search_results[0]] ],
            [new Outbound\Fax($client, 9),[$client, 9, $search_results[1]] ]
        ]);

        $outbound = new Outbound($client, $factory);

        $this->assertCount(2, $outbound->search($test_params));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/search', $transaction['request']->getUri()->getPath());
        $this->assertEquals('status=Inprocess', $transaction['request']->getUri()->getQuery());
    }

    public function test_find()
    {
        $response = ['id' =>  42, 'status' => 0, 'duration' => 4];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response('200', ['Content-type' => 'text/json'], json_encode($response))
        ], $container);

        $fax = new Outbound\Fax($client, 42, $response);
        $factory = $this->getFactory(
            [
                [$fax, [$client, 42, $response]],
            ]);

        $outbound = new Outbound($client, $factory);

        $this->assertEquals($fax, $outbound->find(12));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/12', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
    }
}