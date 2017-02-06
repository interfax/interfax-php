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

use Interfax\Outbound\Delivery;
use Test\Interfax\BaseTest;
use Interfax\Client;
use GuzzleHttp\Psr7\Response;
use Interfax\File;

class DeliveryTest extends BaseTest
{
    protected $client;

    public function setUp()
    {
        $this->client = new Client(['username' => 'test_user', 'password' => 'test_password']);
    }

    public function test_it_cant_be_constructed_without_params()
    {
        $this->setExpectedException('InvalidArgumentException');

        $delivery = new Delivery($this->client);
    }

    public function test_it_cant_be_constructed_without_a_file()
    {

        $this->setExpectedException('InvalidArgumentException');

        $delivery2 = new Delivery($this->client, ['faxNumber' => '12345']);
    }

    public function test_it_can_be_constructed_with_a_file_path()
    {
        $file = $this->getMockBuilder('Interfax\File')->disableOriginalConstructor()->getMock();

        $factory = $this->getFactory([
            [$file, [$this->client, '/fake/file']],
            [$file, [$this->client, '/fake/file2']]
        ]);

        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'file' => '/fake/file'], $factory));
        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'files' => ['/fake/file2']], $factory));
    }

    public function test_it_can_be_constructed_with_a_stream()
    {
        $stream = fopen(__DIR__ . '/../test.pdf', 'rb');
        $file = $this->getMockBuilder('Interfax\File')->disableOriginalConstructor()->getMock();
        $params = ['name' => 'test.pdf', 'mime_type' => 'application/pdf'];

        $factory = $this->getFactory([
            [$file, [$this->client, $stream, $params]]
        ]);

        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'file' => [$stream, $params]], $factory));
    }

    public function test_it_can_be_constructed_with_a_uri()
    {
        $this->assertInstanceOf('Interfax\Outbound\Delivery', new Delivery($this->client, ['faxNumber' => '12345', 'file' => 'https://test.com/foo/bar']));
    }

    public function test_it_can_be_constructed_with_an_Interfax_File()
    {
        $client = $this->client;
        $file = new File($client, __DIR__ . '/../test.pdf');
        $delivery = new Delivery($this->client, ['faxNumber' => '12345', 'file' => $file]);
        $this->assertInstanceOf('Interfax\Outbound\Delivery', $delivery);
        $r = new \ReflectionClass($delivery);
        $rp = $r->getProperty('files');
        $rp->setAccessible(true);
        $this->assertEquals([$file], $rp->getValue($delivery));
    }

    public function test_it_can_be_constructed_with_an_Interfax_Document()
    {
        $file = $this->getMockBuilder('Interfax\File')->disableOriginalConstructor()->getMock();
        $factory = $this->getFactory([
            [$file, [$this->client, 'http://test.com/foo']]
        ]);

        $document = $this->getMockBuilder('Interfax\Document')
            ->disableOriginalConstructor()
            ->setMethods(['getHeaderLocation'])
            ->getMock();

        $document->expects($this->once())
            ->method('getHeaderLocation')
            ->will($this->returnValue('http://test.com/foo'));

        $delivery = new Delivery($this->client, ['faxNumber' => '12345', 'file' => $document], $factory);
        $this->assertInstanceOf('Interfax\Outbound\Delivery', $delivery);
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
            [$file, [$client, 'fake/file']],
            [$fax, [$client, 21]]
        ]);

        $delivery = new Delivery($client, ['faxNumber' => 12345, 'bar' => 'foo', 'file' => 'fake/file'], $factory);

        $this->assertEquals($fax, $delivery->send());
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes', $transaction['request']->getUri()->getPath());
        $this->assertEquals('faxNumber=12345&bar=foo', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('app/foo', $transaction['request']->getHeaderLine('Content-Type'));
        $body = $transaction['request']->getBody();
        //$this->assertInstanceOf('GuzzleHttp\Psr7\MultipartStream', $body);
        $contents = (string) $body;

        $this->assertEquals(1, preg_match('/foo bar car/', $contents));
    }

    /**
     * Helper function to generate a mock Interfax\File
     *
     * @param $headers
     * @param $body
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFakeFile($headers, $body)
    {
        // construct fake file to ensure it affects the request contents correctly
        $file = $this->getMockBuilder('Interfax\File')
            ->disableOriginalConstructor()
            ->setMethods(['getHeader', 'getBody'])
            ->getMock();

        $file->expects($this->any())
            ->method('getHeader')
            ->will($this->returnValue($headers));

        $file->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));
        return $file;
    }

    public function test_it_supports_multiple_file_delivery()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(201, ['Location' => 'http://myfax.resource.uri/outbound/faxes/21'], '')
        ], $container);

        $file1 = $this->getFakeFile(['Content-Type' => 'app/foo'], 'foo bar car');
        $file2 = $this->getFakeFile(['Content-Type' => 'app/bar'], 'test content');

        // fake fax to be returned
        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getFactory([
            [$file1, [$client, 'fake/file1']],
            [$file2, [$client, 'fake/file2']],
            [$fax, [$client, 21]]
        ]);

        $delivery = new Delivery($client, ['faxNumber' => 12345, 'bar' => 'foo', 'files' => ['fake/file1', 'fake/file2']], $factory);

        $this->assertEquals($fax, $delivery->send());
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/faxes', $transaction['request']->getUri()->getPath());
        $this->assertEquals('faxNumber=12345&bar=foo', $transaction['request']->getUri()->getQuery());
        $body = $transaction['request']->getBody();
        $this->assertInstanceOf('GuzzleHttp\Psr7\MultipartStream', $body);
        $contents = (string) $body;
        $this->assertEquals(1, preg_match('/Content-Type: app\/foo/', $contents));
        $this->assertEquals(1, preg_match('/foo bar car/', $contents));
        $this->assertEquals(1, preg_match('/Content-Type: app\/bar/', $contents));
        $this->assertEquals(1, preg_match('/test content/', $contents));
    }

}