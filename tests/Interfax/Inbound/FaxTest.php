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
namespace Interfax\Inbound;

use Interfax\BaseTest;

class FaxTest extends BaseTest
{
    public function test_markRead()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('post'))
            ->getMock();

        $client->expects($this->once())
            ->method('post')
            ->with('/inbound/faxes/854759652', ['unread' => false])
            ->will($this->returnValue(''));

        $fax = new Fax($client, 854759652);

        $this->assertTrue($fax->markRead());
    }

    public function test_markUnread()
    {
        $client = $this->getMockBuilder('Interfax\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('post'))
            ->getMock();

        $client->expects($this->once())
            ->method('post')
            ->with('/inbound/faxes/854759652', ['unread' => true])
            ->will($this->returnValue(''));

        $fax = new Fax($client, 854759652);

        $this->assertTrue($fax->markUnread());
    }
}