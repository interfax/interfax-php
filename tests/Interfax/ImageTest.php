<?php
/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2016
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2016, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */


namespace Test\Interfax;


use Interfax\Image;
use org\bovigo\vfs\vfsStream;

class ImageTest extends BaseTest
{

    public function test_save()
    {
        $stream = $this->getMockBuilder('GuzzleHttp\Psr7\Stream')->disableOriginalConstructor()->getMock();

        $stream->expects($this->at(0))
            ->method('eof')
            ->will($this->returnValue(false));
        $stream->expects($this->at(2))
            ->method('eof')
            ->will($this->returnValue(true));
        $stream->expects($this->once())
            ->method('read')
            ->will($this->returnValue('abc'));

        $image = new Image($stream);

        $directory = vfsStream::setup('test_location');
        $this->assertFalse($directory->hasChild('save_test.txt'));
        $this->assertTrue( $image->save(vfsStream::url('test_location/save_test.txt')) );
        $this->assertTrue($directory->hasChild('save_test.txt'));
    }
}