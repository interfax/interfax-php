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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    protected function getClient($properties = array())
    {
        $client = new Client(['username' => 'test_user', 'password' => 'test_password']);
        foreach ($properties as $k => $v) {
            $client->$k = $v;
        }

        return $client;
    }

    public function test_constructing_with_variables_overrides_env()
    {
        $current_username = getenv('INTERFAX_USERNAME');
        $current_password = getenv('INTERFAX_PASSWORD');
        putenv('INTERFAX_USERNAME=env_username');
        putenv('INTERFAX_PASSWORD=env_password');

        $client = new Client(['username' => 'override_username', 'password' => 'override_password']);
        $this->assertEquals('override_username', $client->username);
        $this->assertEquals('override_password', $client->password);

        putenv('INTERFAX_USERNAME=' . $current_username);
        putenv('INTERFAX_PASSWORD=' . $current_password);
    }

    public function test_constructing_without_variables_uses_env()
    {
        $current_username = getenv('INTERFAX_USERNAME');
        $current_password = getenv('INTERFAX_PASSWORD');
        putenv('INTERFAX_USERNAME=env_username');
        putenv('INTERFAX_PASSWORD=env_password');

        $client = new Client();
        $this->assertEquals('env_username', $client->username);
        $this->assertEquals('env_password', $client->password);

        putenv('INTERFAX_USERNAME=' . $current_username);
        putenv('INTERFAX_PASSWORD=' . $current_password);
    }

    public function test_construction_should_fail_without_credentials()
    {
        $this->setExpectedException('InvalidArgumentException');
        $client = new Client();
    }

    public function test_post_success()
    {
        $mock = new MockHandler([
            new Response(201, ['Location' => 'http://myfax.resource.uri'], '')
        ]);
        $stack = HandlerStack::create($mock);

        $container = [];
        $history = Middleware::history($container);

        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack]);

        $client = $this->getClient();
        $client->setHttpClient($guzzle);

        $response = $client->post('test/uri',['query' => ['foo' => 'bar']], [['name' => 'doc1', 'headers' => ['X-Bar' => 'FOO'], 'contents' => 'testString']]);

        $this->assertEquals('http://myfax.resource.uri', $response);
        $this->assertEquals(1, count($container));
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertNotNull($transaction['options']['auth']);
        $this->assertEquals('foo=bar', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('test/uri', $transaction['request']->getUri()->getPath());
        $this->assertEquals(1, preg_match('/testString/', $transaction['request']->getBody()));

    }

    public function test_get_success()
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/json'], '{"id":279415116,"uri":"https://rest.interfax.net/outbound/faxes/279415116","status":0}')
        ]);
        $stack = HandlerStack::create($mock);

        $container = [];
        $history = Middleware::history($container);

        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack]);

        $client = $this->getClient();
        $client->setHttpClient($guzzle);

        $response = $client->get('test/uri',['query' => ['foo' => 'bar']]);
        $this->assertTrue(is_array($response));

        $this->assertEquals(1, count($container));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertNotNull($transaction['options']['auth']);
        $this->assertEquals('foo=bar', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('test/uri', $transaction['request']->getUri()->getPath());

    }

    public function test_deliver_user_delivery_class_to_send_fax()
    {
        $delivery = $this->getMockBuilder('Interfax\Outbound\Delivery')
            ->disableOriginalConstructor()
            ->setMethods(array('send'))
            ->getMock();

        $fake_return = 'test';
        $delivery->expects($this->once())
            ->method('send')
            ->will($this->returnValue($fake_return));

        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['getDeliveryInstance'])
            ->getMock();

        $client->expects($this->once())
            ->method('getDeliveryInstance')
            ->will($this->returnValue($delivery));

        $params = ['foo' => 'bar'];

        $this->assertEquals($fake_return, $client->deliver($params));
    }

    public function test_completed_uses_outbound_class_to_get_results()
    {
        $outbound = $this->getMockBuilder('Interfax\Outbound')
            ->disableOriginalConstructor()
            ->setMethods(array('completed'))
            ->getMock();

        $fake_return = 'test';
        $ids = [1,5, 7];

        $outbound->expects($this->once())
            ->method('completed')
            ->with($ids)
            ->will($this->returnValue($fake_return));

        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['getOutboundInstance'])
            ->getMock();

        $client->expects($this->once())
            ->method('getOutboundInstance')
            ->will($this->returnValue($outbound));

        $this->assertEquals($fake_return, $client->completed($ids));
    }

}