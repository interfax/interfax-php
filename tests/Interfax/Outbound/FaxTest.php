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

namespace Test\Interfax\Outbound;

use GuzzleHttp\Psr7\Response;
use Interfax\Outbound\Fax;
use Test\Interfax\BaseTest;

class FaxTest extends BaseTest
{
    public function test_successful_construction()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $fax = new Fax($client, 854759652);

        $this->assertInstanceOf('Interfax\Outbound\Fax', $fax);
        $this->assertNotNull($fax->id);
        $this->assertEquals(854759652, $fax->id);
    }

    public function test_refresh()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $reload_response = ['id' => 854759652,'uri' => 'https://rest.interfax.net/outbound/faxes/279415116','status' => 0];

        $client->expects($this->once())
            ->method('get')
            ->with('/outbound/faxes/854759652')
            ->will($this->returnValue($reload_response));

        $fax = new Fax($client, 854759652);

        $this->assertNull($fax->status);
        $this->assertEquals($fax, $fax->refresh());
        $this->assertEquals(0, $fax->status);
    }

    public function test_getter_method_for_record_details()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('get'))
            ->getMock();

        $response = [];
        for ($i = 0; $i < 5; $i++) {
            $response[substr( md5(mt_rand()), 0, 7)] = substr( md5(mt_rand()), 0, 7);
        }
        $response['id'] = 82342453;
        $response['status'] = -2;

        $client->expects($this->once())
            ->method('get')
            ->with('/outbound/faxes/82342453')
            ->will($this->returnValue($response));

        $fax = new Fax($client, 82342453);
        $this->assertNull($fax->status);
        foreach ($response as $k => $v) {
            if ($k != 'id') {
                $this->assertNull($fax->$k);
            }
        }

        $this->assertInstanceOf('Interfax\Outbound\Fax', $fax->refresh());

        foreach ($response as $k => $v) {
            $this->assertEquals($v, $fax->$k);
        }

        $this->assertNull($fax->undefined_property);
    }

    public function test_resend()
    {
        $container = [];

        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://myfax.resource.uri/outbound/faxes/21'], '')
        ], $container);

        $resent_fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([[$resent_fax, [$client, 21]]]);

        $fax = new Fax($client, 45, [], $factory);

        $this->assertEquals($resent_fax, $fax->resend());
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/45/resend', $transaction['request']->getUri()->getPath());
    }

    public function test_resend_with_param()
    {
        $container = [];

        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://myfax.resource.uri/outbound/faxes/21'], '')
        ], $container);

        $resent_fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([[$resent_fax, [$client, 21]]]);

        $fax = new Fax($client, 45, [], $factory);

        $resend_number = '+1111111111';

        $this->assertEquals($resent_fax, $fax->resend($resend_number));
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/45/resend', $transaction['request']->getUri()->getPath());
        $this->assertEquals('faxNumber=' . urlencode($resend_number), $transaction['request']->getUri()->getQuery());
    }

    public function test_attributes()
    {
        $client = $this->getMockBuilder('Interfax\Client')->disableOriginalConstructor()->getMock();

        $definition = [
            'submitTime' => '2012-06-20T06:08:18',
            'contact' => '',
            'destinationFax' => '0081287282867',
            'replyEmail' => 'nadya@interfax.net',
            'subject' => 'test',
            'pagesSubmitted' => 1,
            'senderCSID' => 'INTERFAX',
            'attemptsToPerform' => 4,
            'pageSize' => 'A4',
            'resolution' => 'Portrait',
            'pageResolution' => 'Fine',
            'pageOrientation' => 'Portrait',
            'rendering' => 'Fine',
            'pageHeade' => '0',
            'userId' => 'nadya',
            'pagesSent' => 1,
            'completionTime' => '2012-06-20T06:09:08',
            'remoteCSID' => '81287282867',
            'duration' => 37,
            'priority' => 2,
            'units' => 1.00,
            'costPerUnit' => 0.9500,
            'attemptsMade' => 1,
            'id' => 279415116,
            'uri' => 'https://rest.interfax.net/outbound/faxes/279415116',
            'status' => 0
        ];

        $fax = new Fax($client, 279415116, $definition);

        $this->assertEquals($definition, $fax->attributes());
    }

    public function test_cancel()
    {
        $container = [];
         $client = $this->getClientWithResponses([
            new Response(200, [], '')
        ], $container);

        $fax = new Fax($client, 21);

        $this->assertEquals($fax, $fax->cancel());

        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/21/cancel', $transaction['request']->getUri()->getPath());
    }

    public function test_hide()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(200, [], '')
        ], $container);

        $fax = new Fax($client, 21);

        $this->assertEquals($fax, $fax->hide());
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/21/hide', $transaction['request']->getUri()->getPath());
    }

    public function test_image()
    {
        $container = [];
        $resp_resource = fopen(__DIR__ .'/../test.pdf', 'r');
        $stream = \GuzzleHttp\Psr7\stream_for($resp_resource);
        $client = $this->getClientWithResponses([
            new Response(200, [], $stream)
        ], $container);

        $result_image = $this->getMockBuilder('Interfax\Image')->disableOriginalConstructor()->getMock();
        $factory = $this->getFactory([
            [$result_image, [$stream]]
        ]);

        $fax = new Fax($client, 42, [], $factory);

        $this->assertEquals($result_image, $fax->image());
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes/42/image', $transaction['request']->getUri()->getPath());

        fclose($resp_resource);
    }
}