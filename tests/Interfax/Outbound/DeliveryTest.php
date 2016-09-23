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
        $r = new \ReflectionClass('Interfax\Outbound\Delivery');
        $r_qp = $r->getProperty('query_params');
        $r_qp->setAccessible(true);
        $qp = $r_qp->getValue($delivery);

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
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://myfax.resource.uri/outbound/faxes/21'], '')
        ], $container);

        // construct fake file to ensure it affects the request contents correctly
        $file = $this->getMockBuilder('Interfax\File')
            ->disableOriginalConstructor()
            ->setMethods(['getHeader', 'getBody'])
            ->getMock();

        $file->expects($this->any())
            ->method('getHeader')
            ->will($this->returnValue(['Content-Type' => 'app/foo']));

        $file->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue('foo bar car'));

        // fake fax to be returned
        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([
            [$file, ['fake/file']],
            [$fax, [$client, 21]]
        ]);

        $delivery = new Delivery($client, ['faxNumber' => 12345, 'bar' => 'foo', 'file' => 'fake/file'], $factory);

        $this->assertEquals($fax, $delivery->send());
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes', $transaction['request']->getUri()->getPath());
        $this->assertEquals('faxNumber=12345&bar=foo', $transaction['request']->getUri()->getQuery());
        $body = $transaction['request']->getBody();
        $this->assertInstanceOf('GuzzleHttp\Psr7\MultipartStream', $body);
        $contents = (string) $body;
        $this->assertEquals(1, preg_match('/foo bar car/', $contents));
        $this->assertEquals(1, preg_match('/Content-Type: app\/foo/', $contents));

    }
}