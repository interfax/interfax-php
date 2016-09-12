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

use Interfax\Client;

class DeliveryTest extends \PHPUnit_Framework_TestCase
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
        $params['file'] = '/fake/file';


        $delivery = new Delivery($this->client, $params);
        $qp = $delivery->getQueryParams();
        $this->assertEquals(count($params), count($qp));
        foreach ($params as $k => $v) {
            $this->assertArrayHasKey($k, $qp);
            $this->assertEquals($v, $qp[$k]);
        }
    }

    /**
     * This will test the send method, need to work out the process of providing files to
     * the delivery object for sending.
     */
    public function _test_it_uses_the_client_to_post_a_delivery()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('post'))
            ->getMock();

        $client->expects($this->once())
            ->method('post')
            ->will($this->returnValue('stub_response'));

        $delivery = new Delivery($client, ['faxNumber' => 12345, 'bar' => 'foo']);

        $delivery->send();

    }


}