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

/**
 * Class BaseTest
 * @package Interfax
 */
abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    // Basic wrapper to allow mocking out of different classes a Client might need to instantiate
    protected function getClientWithFactory($returns = [])
    {
        $factory = $this->getMockBuilder('Interfax\GenericFactory')
            ->setMethods(['instantiateClass'])
            ->getMock();
        foreach ($returns as $i => $obj) {
            $factory->expects($this->at($i))
                ->method('instantiateClass')
                ->will($this->returnValue($obj));
        }

        return new Client(['username' => 'test_user', 'password' => 'test_password'], $factory);
    }
}