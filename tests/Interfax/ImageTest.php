<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package   interfax/interfax
 * @author    Interfax <dev@interfax.net>
 * @author    Mike Smith <mike.smith@camc-ltd.co.uk>
 * @copyright Copyright (c) 2016, InterFAX
 * @license   MIT
 */

namespace Test\Interfax;


use Interfax\Image;
use org\bovigo\vfs\vfsStream;

class ImageTest extends BaseTest
{

    public function test_save()
    {
        $stream = $this->getMockBuilder('GuzzleHttp\Psr7\Stream')->disableOriginalConstructor()->getMock();

        $stream->method('eof')
            ->willReturnOnConsecutiveCalls(false, true);
        $stream->expects($this->once())
            ->method('read')
            ->will($this->returnValue('abc'));

        $image = new Image($stream);

        $directory = vfsStream::setup('test_location');
        $this->assertFalse($directory->hasChild('save_test.txt'));
        $this->assertTrue($image->save(vfsStream::url('test_location/save_test.txt')));
        $this->assertTrue($directory->hasChild('save_test.txt'));
        $this->assertEquals('abc', $directory->getChild('save_test.txt')->getContent());
    }
}