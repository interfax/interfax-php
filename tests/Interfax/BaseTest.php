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
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Interfax\Client;

/**
 * Class BaseTest
 * @package Interfax
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    private function getExpectedClassForFactory($obj)
    {
        $cls = get_class($obj);
        if (strpos($cls,'Mock') === 0) {
            $r = new \ReflectionClass($obj);
            $cls = $r->getParentClass()->name;
        }
        return $cls;
    }

    /**
     * Provides a mock generic factory for handling instantiation of objects within the code structure
     *
     * @param array $returns
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFactory($returns = [])
    {
        $factory = $this->getMockBuilder('Interfax\GenericFactory')
            ->setMethods(['instantiateClass'])
            ->getMock();
        foreach ($returns as $i => $obj) {
            if (is_array($obj)) {
                $factory->expects($this->at($i))
                    ->method('instantiateClass')
                    ->with($this->getExpectedClassForFactory($obj[0]), $obj[1])
                    ->will($this->returnValue($obj[0]));
            } else {
               $factory->expects($this->at($i))
                    ->method('instantiateClass')
                    ->with($this->getExpectedClassForFactory($obj))
                    ->will($this->returnValue($obj));
            }
        }
        return $factory;
    }

    // Basic wrapper to allow mocking out of different classes a Client might need to instantiate
    protected function getClientWithFactory($returns = [])
    {
        $factory = $this->getFactory($returns);

        return new Client(['username' => 'test_user', 'password' => 'test_password'], $factory);
    }

    protected function constructGuzzleWithResponses($responses = [], &$container)
    {
        $mock = new MockHandler($responses);

        $stack = HandlerStack::create($mock);

        $history = Middleware::history($container);

        $stack->push($history);

        $guzzle = new GuzzleClient(['handler' => $stack]);

        return $guzzle;
    }

    // wrapper to allow "simple" inspection of the requests sent to the API endpoints
    protected function getClientWithResponses($responses = [], &$container)
    {
        $client = $this->getClientWithFactory([$this->constructGuzzleWithResponses($responses, $container)]);

        return $client;
    }
}