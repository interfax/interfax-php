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

use GuzzleHttp\Psr7\Response;

class DocumentsTest extends BaseTest
{
    public function test_available()
    {
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

        $factory_returns = [$this->getMock('Interfax\Document'), $this->getMock('Interfax\Document')];
        $factory = $this->getFactory($factory_returns);

        $documents = new Documents($client, $factory);

        $this->assertEquals($factory_returns, $documents->available());
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
    }
}