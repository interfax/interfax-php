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

    public function _test_it_can_send_an_outbound_fax()
    {
        $fax = new Outbound\Fax();

        $guzzle = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('request'))
            ->getMock();

        $client = $this->getClient(['http' => $guzzle]);

        $guzzle->expects($this->once())
            ->method('request')
            ->will($this->returnValue());




    }
}