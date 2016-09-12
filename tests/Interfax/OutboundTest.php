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

class OutboundTest extends \PHPUnit_Framework_TestCase
{
    public function test_completed()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $response = [['id' =>  12],['id' => 14]];

        $client->expects($this->once())
            ->method('get')
            ->with('/outbound/faxes/completed')
            ->will($this->returnValue($response));


        $outbound = $this->getMockBuilder('Interfax\Outbound')
            ->setConstructorArgs([$client])
            ->setMethods(['createFaxes'])
            ->getMock();

        $fax = $this->getMockBuilder('Interfax\Outbound\Fax')->disableOriginalConstructor()->getMock();

        $outbound->expects($this->once())
            ->method('createFaxes')
            ->with([['id' =>  12],['id' => 14]])
            ->will($this->returnValue([$fax]));

        $res = $outbound->completed(['12', '14']);

        $this->assertEquals([$fax], $res);
    }

    public function test_create_faxes()
    {
        $outbound = $this->getMockBuilder('Interfax\Outbound')
            ->disableOriginalConstructor()
            ->setMethods(['createFax'])
            ->getMock();

        $outbound->expects($this->exactly(3))
            ->method('createFax')
            ->will($this->returnValue('foo'));

        $this->assertCount(3, $outbound->createFaxes([['id' => 12],['id' => 14],['id' => 21]]) );
    }

    public function test_create_fax()
    {
        $client = new Client(['username' => 'test_user', 'password' => 'test_password']);
        $outbound = new Outbound($client);

        $this->assertInstanceOf('Interfax\Outbound\Fax', $outbound->createFax(['id' => 12]));
    }
}