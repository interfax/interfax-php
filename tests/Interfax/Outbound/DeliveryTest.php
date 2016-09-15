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

use Interfax\BaseTest;
use Interfax\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class DeliveryTest extends BaseTest
{
    protected $client;

    public function setUp()
    {
        $this->client = new Client(['username' => 'test_user', 'password' => 'test_password']);
    }

    public function test_it_cant_be_constructed_without_minimum_requirements()
    {
        $this->setExpectedException('InvalidArgumentException');

        $delivery = new Delivery($this->client);

        $this->setExpectedException('InvalidArgumentException');

        $delivery2 = new Delivery($this->client, ['faxNumber' => '12345']);

        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'file' => '/fake/file']));

        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'files' => ['/fake/file']]));
    }

    public function test_it_stores_provided_params_for_the_query_string()
    {
        $params = [];
        for ($i = 0; $i < 5; $i++) {
            $params[substr( md5(mt_rand()), 0, 7)] = substr( md5(mt_rand()), 0, 7);
        }
        $params['faxNumber'] = '12345';
        $params['file'] = __DIR__ . '/../test.pdf';

        $delivery = new Delivery($this->client, $params);
        $qp = $delivery->getQueryParams();
        // file is not a query param
        unset($params['file']);
        $this->assertEquals(count($params), count($qp));
        foreach ($params as $k => $v) {
            $this->assertArrayHasKey($k, $qp);
            $this->assertEquals($v, $qp[$k]);
        }
    }

    public function test_it_uses_the_client_to_post_a_delivery_and_returns_fax()
    {
        $mock = new MockHandler([
            new Response(201, ['Location' => 'http://myfax.resource.uri/outbound/faxes/21'], '')
        ]);

        $stack = HandlerStack::create($mock);

        $container = [];
        $history = Middleware::history($container);

        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack]);

        $client = $this->getClientWithFactory([$guzzle]);

        $delivery = $this->getMockBuilder('Interfax\Outbound\Delivery')
            ->setConstructorArgs([$client, ['faxNumber' => 12345, 'bar' => 'foo', 'file' => ['fake/file']] ])
            ->setMethods(['createFax'])
            ->getMock();

        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->getMock();

        $delivery->expects($this->once())
            ->method('createFax')
            ->with(21)
            ->will($this->returnValue($fax));

        $this->assertEquals($fax, $delivery->send());
    }
}