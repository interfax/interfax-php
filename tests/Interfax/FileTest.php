<?php
/**
 * Interfax
 *
 * (C) InterFAX, 2016
 *
 * @package interfax/interfax
 * @author Interfax <dev@interfax.net>
 * @copyright Copyright (c) 2016, InterFAX
 * @license MIT
 */

namespace Interfax;


use GuzzleHttp\Psr7\Response;

class FileTest extends BaseTest
{
    public function test_it_errors_for_invalid_path()
    {
        $i = 1;
        $missing_file_path = "/tmp/missing{$i}.txt";
        while (file_exists($missing_file_path)) {
            $i++;
            $missing_file_path = "/tmp/missing{$i}.txt";
        }

        $this->setExpectedException('InvalidArgumentException');

        $file = new File($this->getClientWithFactory(), $missing_file_path);
    }

    public function test_it_sets_values_from_valid_file()
    {
        $file = new File($this->getClientWithFactory(), __DIR__ . '/test.pdf');
        $header = $file->getHeader();
        $this->assertArrayHasKey('Content-Type', $header);
        $this->assertEquals('application/pdf', $header['Content-Type']);
        $this->assertEquals('test.pdf', $file->getName());
    }

    public function test_it_automatically_creates_document_for_large_files()
    {
        $container = [];

        $documents_client = $this->getClientWithResponses([
            new Response(200, ['Location' => 'http://test.com/foo/3425'], ''),
            new Response(202),
            new Response(200)
        ], $container);

        $file_client = $this->getClientWithFactory([
            new Documents($documents_client)
        ]);

        $file = new File($file_client, __DIR__ . '/test.pdf', ['chunk_size' => 5000]);
        // no base uri on guzzle client
        $this->assertEquals(['Location' => '/outbound/documents/3425'], $file->getHeader());

    }

    public function test_attribute_overrides()
    {
        // this is not a real world use case, but the principle here is to allow both attributes to be set by
        // the method call to ensure erroneous details can be altered correctly
        $file = new File($this->getClientWithFactory(), __DIR__ . '/test.pdf', ['mime_type' => 'text/html', 'name' => 'foobar.html']);
        $header = $file->getHeader();
        $this->assertArrayHasKey('Content-Type', $header);
        $this->assertEquals('text/html', $header['Content-Type']);
        $this->assertEquals('foobar.html', $file->getName());
    }

    public function test_initialise_from_uri()
    {
        $file = new File($this->getClientWithFactory(), 'https://foo.com/bar.pdf');
        $header = $file->getHeader();
        $this->assertArrayHasKey('Location', $header);
        $this->assertEquals('https://foo.com/bar.pdf', $header['Location']);

    }

}