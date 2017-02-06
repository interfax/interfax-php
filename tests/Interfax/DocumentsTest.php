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
use Interfax\Documents;

class DocumentsTest extends BaseTest
{
    public function test_available()
    {
        // sample taken from docs
        $response = [
            [
                'userId' => 'nadya',
                'fileName' => 'sampledoc.pdf',
                'fileSize' => 82318,
                'uploaded' => 0,
                'uri' => 'https://rest.interfax.net/outbound/documents/89a48657279d45429c646029bd9227e6',
                'creationTime' => '2012-06-23T17:49:25',
                'lastusageTime' => '2012-06-23T17:49:25',
                'status' => 'Created'
            ],
            [
                'userId' => 'nadya',
                'fileName' => 'sampledoc.pdf',
                'fileSize' => 82318,
                'uploaded' => 0,
                'uri' => 'https://rest.interfax.net/outbound/documents/89a48657279d45429c646029bd9227e6',
                'creationTime' => '2012-06-23T17:49:25',
                'lastusageTime' => '2012-06-23T17:49:25',
                'status' => 'Created'
            ]
        ];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(200, ['Content-Type' => 'text/json'], json_encode($response))
        ], $container);

        $factory_returns = [
            $this->getMockBuilder('Interfax\Document')->disableOriginalConstructor()->getMock(), $this->getMockBuilder('Interfax\Document')->disableOriginalConstructor()->getMock()];
        $factory = $this->getFactory($factory_returns);

        $documents = new Documents($client, $factory);

        $this->assertEquals($factory_returns, $documents->available());
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
    }

    public function test_available_with_params()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(200, ['Content-Type' => 'text/json'], '[]')
        ], $container);

        $documents = new Documents($client);

        $this->assertEquals([], $documents->available(['limit' => 20, 'foo' => 'bar']));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents', $transaction['request']->getUri()->getPath());
        $this->assertEquals('limit=20&foo=bar', $transaction['request']->getUri()->getQuery());
    }

    public function test_create_no_params()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://mydoc.resource.uri/outbound/documents/21'], '')
        ], $container);

        $document = $this->getMockBuilder('Interfax\Document')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([[$document, [$client, 21, ['id' => 21, 'size' => '200', 'name' => 'test.pdf']]]]);

        $documents = new Documents($client, $factory);

        $this->assertEquals($document, $documents->create('test.pdf', 200));
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents', $transaction['request']->getUri()->getPath());
        $this->assertEquals('name=test.pdf&size=200', $transaction['request']->getUri()->getQuery());
    }

    public function test_create_with_params()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://mydoc.resource.uri/outbound/documents/21'], '')
        ], $container);

        $document = $this->getMockBuilder('Interfax\Document')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([[$document, [$client, 21, ['id' => 21, 'size' => '200', 'name' => 'test.pdf', 'foo' => 'bar']]]]);

        $documents = new Documents($client, $factory);

        $this->assertEquals($document, $documents->create('test.pdf', 200, ['foo' => 'bar']));
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents', $transaction['request']->getUri()->getPath());
        $this->assertEquals('foo=bar&name=test.pdf&size=200', $transaction['request']->getUri()->getQuery());
    }
}