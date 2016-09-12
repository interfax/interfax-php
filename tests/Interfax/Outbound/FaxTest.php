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

class FaxTest extends \PHPUnit_Framework_TestCase
{

    public function test_successful_construction()
    {
        $fake_resource_url = 'https://rest.interfax.net/outbound/faxes/854759652';

        $response = new \GuzzleHttp\Psr7\Response(201, ['Location' => $fake_resource_url], -2);

        $fax = new Fax($response);
        $this->assertInstanceOf('Interfax\Outbound\Fax', $fax);
        $this->assertEquals(-2, $fax->getStatus());
    }
}