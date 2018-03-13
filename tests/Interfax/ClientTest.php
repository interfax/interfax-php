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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Interfax\Client;

class ClientTest extends BaseTest
{

    protected function getClient($properties = [])
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

        $client = $this->getClientWithFactory([$guzzle]);

        $response = $client->post('test/uri',['query' => ['foo' => 'bar']], [['name' => 'doc1', 'headers' => ['X-Bar' => 'FOO'], 'contents' => 'testString']]);

        $this->assertEquals('http://myfax.resource.uri', $response);
        $this->assertCount(1, $container);
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertNotNull($transaction['options']['auth']);
        $this->assertEquals('foo=bar', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('test/uri', $transaction['request']->getUri()->getPath());
        $this->assertEquals(1, preg_match('/testString/', $transaction['request']->getBody()));
        $this->assertEquals(1, preg_match('/InterFAX PHP/', $transaction['request']->getHeaderLine('User-Agent')));

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

        $client = $this->getClientWithFactory([$guzzle]);

        $response = $client->get('test/uri',['query' => ['foo' => 'bar']]);
        $this->assertTrue(is_array($response));

        $this->assertCount(1, $container);
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertNotNull($transaction['options']['auth']);
        $this->assertEquals('foo=bar', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('test/uri', $transaction['request']->getUri()->getPath());
        $this->assertEquals(1, preg_match('/InterFAX PHP/', $transaction['request']->getHeaderLine('User-Agent')));
    }

    public function test_delete_success()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(200)
        ], $container);

        $response = $client->delete('test/uri');
        $this->assertEquals(200, $response);
        $transaction = $container[0];
        $this->assertEquals('DELETE', $transaction['request']->getMethod());
        $this->assertNotNull($transaction['options']['auth']);
        $this->assertEquals(1, preg_match('/InterFAX PHP/', $transaction['request']->getHeaderLine('User-Agent')));
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

        $client = $this->getClientWithFactory([$delivery]);

        $params = ['foo' => 'bar'];

        $this->assertEquals($fake_return, $client->deliver($params));
    }

    public function test_get_balance()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $client->expects($this->once())
            ->method('get')
            ->with('/accounts/self/ppcards/balance')
            ->will($this->returnValue('4.35'));

        $this->assertEquals('4.35', $client->getBalance());
    }

    public function test_outbound_property_returns_outbound_instance()
    {
        $outbound = $this->getMockBuilder('Interfax\Outbound')
            ->disableOriginalConstructor()
            ->getMock();

        $client = $this->getClientWithFactory([$outbound]);

        $this->assertEquals($outbound, $client->outbound);
    }

    public function test_inbound_property()
    {
        $client = $this->getClient();

        $this->assertInstanceOf('Interfax\Inbound', $client->inbound);
    }

    public function test_documents_property()
    {
        $client = $this->getClient();

        $this->assertInstanceOf('Interfax\Documents', $client->documents);
    }

    public function test_boolean_parsing_for_query_string()
    {
        $mock = new MockHandler([
            new Response(201, ['Location' => 'http://myfax.resource.uri'], '')
        ]);
        $stack = HandlerStack::create($mock);

        $container = [];
        $history = Middleware::history($container);

        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack]);

        $client = $this->getClientWithFactory([$guzzle]);

        $response = $client->get('test/uri',['query' => ['foo' => true, 'bar' => false]]);

        $this->assertEquals('http://myfax.resource.uri', $response);
        $this->assertEquals(1, count($container));
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('foo=TRUE&bar=FALSE', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('test/uri', $transaction['request']->getUri()->getPath());
    }

    public function test_getBaseUri()
    {
        $guzzle = new GuzzleClient(['base_uri' => 'http://test.foo.bar.com']);
        $client = $this->getClientWithFactory([$guzzle]);
        $this->assertEquals('http://test.foo.bar.com', $client->getBaseUri());
    }

    public function test_prevents_trailing_slash()
    {
        $client = new Client(['base_uri' => 'http://test.foo.com/', 'username' => 'foo', 'password' => 'bar']);

        $this->assertEquals('http://test.foo.com', $client->getBaseUri());
    }

    /**
     * Accept any 2xx status codes
     */
    public function test_success_response_status_parsing()
    {
        for ($i = 0; $i < 10; $i++) {
            $container = [];
            $client = $this->getClientWithResponses([
                new Response(rand(200, 299), [], 'foo')
            ], $container);

            $response = $client->get('test/uri', ['query' => ['foo' => true, 'bar' => false]]);
            
            $this->assertEquals('foo', $response);
        }
    }

    public function errorCodeProvider()
    {
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $status_code = rand(100, 550);
            if ($status_code >= 200 && $status_code <=299) {
                $status_code += 100;
            }
            $data[] = [$status_code];
        }
        return $data;
    }
        
    /**
     * @dataProvider errorCodeProvider
     */
    public function test_error_response_status_parsing($status_code)
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response($status_code, [], 'foo')
        ], $container);

        $this->setExpectedException('Interfax\Exception\RequestException', 'Unsuccessful request: foo');

        $response = $client->get('test/uri', ['query' => ['foo' => true, 'bar' => false]]);
    }

}