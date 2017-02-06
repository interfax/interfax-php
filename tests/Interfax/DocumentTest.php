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

use GuzzleHttp\Psr7\Response;
use Interfax\Document;

class DocumentTest extends BaseTest
{
    public function test_upload()
    {
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(202, [], '')
        ], $container);

        $document = new Document($client, 42, ['id' => 42]);

        $this->assertEquals($document, $document->upload(0, 300, 'the quick brown fox'));
        $transaction = $container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents/42', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
        $this->assertEquals('bytes=0-300',$transaction['request']->getHeaderLine('Range'));
    }

    public function test_refresh()
    {
        $response = [
            'userId' => 'nadya',
            'fileName' => 'sampledoc.pdf',
            'fileSize' => 82318,
            'uploaded' => 0,
            'uri' => 'https:/rest.interfax.net/outbound/documents/89a48657279d45429c646029bd9227e6',
            'creationTime' => '2012-06-23T17:49:25',
            'lastusageTime' => '2012-06-23T17:49:25',
            'status' => 'Created',
            'disposition' => 'SingleUse',
            'sharing' => 'Private'
        ];

        $container = [];
        $client  = $this->getClientWithResponses([
            new Response(200, ['Content-Type' => 'text/json'], json_encode($response))
        ], $container);

        $document = new Document($client, '89a48657279d45429c646029bd9227e6');
        $this->assertNull($document->status);
        $this->assertCount(0, $container);
        $this->assertInstanceOf('Interfax\Document', $document->refresh());
        $this->assertCount(1, $container);
        $transaction = $container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents/89a48657279d45429c646029bd9227e6', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
        $this->assertEquals($response,$document->attributes());
    }

    public function test_cancel()
    {
        $struct = $response = [
            'userId' => 'nadya',
            'fileName' => 'sampledoc.pdf',
            'fileSize' => 82318,
            'uploaded' => 0,
            'uri' => 'https:/rest.interfax.net/outbound/documents/123124124',
            'creationTime' => '2012-06-23T17:49:25',
            'lastusageTime' => '2012-06-23T17:49:25',
            'status' => 'Created',
            'disposition' => 'SingleUse',
            'sharing' => 'Private'
        ];
        $container = [];
        $client = $this->getClientWithResponses([
            new Response(200, [], '')
        ], $container);

        $document = new Document($client, '123124124', $struct);
        $this->assertEquals($document, $document->cancel());
        $this->assertCount(0, $document->attributes());
        $transaction = $container[0];
        $this->assertEquals('DELETE', $transaction['request']->getMethod());
        $this->assertEquals('/outbound/documents/123124124', $transaction['request']->getUri()->getPath());
        $this->assertEquals('', $transaction['request']->getUri()->getQuery());
    }

}