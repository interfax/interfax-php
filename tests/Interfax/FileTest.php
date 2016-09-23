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


class FileTest extends \PHPUnit_Framework_TestCase
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

        $file = new File($missing_file_path);
    }

    public function test_it_sets_values_from_valid_file()
    {
        $file = new File(__DIR__ . '/test.pdf');
        $header = $file->getHeader();
        $this->assertArrayHasKey('Content-Type', $header);
        $this->assertEquals('application/pdf', $header['Content-Type']);
        $this->assertEquals('test.pdf', $file->getName());
    }

    public function test_attribute_overrides()
    {
        // this is not a real world use case, but the principle here is to allow both attributes to be set by
        // the method call to ensure erroneous details can be altered correctly
        $file = new File(__DIR__ . '/test.pdf', ['mime_type' => 'text/html', 'name' => 'foobar.html']);
        $header = $file->getHeader();
        $this->assertArrayHasKey('Content-Type', $header);
        $this->assertEquals('text/html', $header['Content-Type']);
        $this->assertEquals('foobar.html', $file->getName());
    }

    public function test_initialise_from_uri()
    {
        $file = new File('https://foo.com/bar.pdf');
        $header = $file->getHeader();
        $this->assertArrayHasKey('Location', $header);
        $this->assertEquals('https://foo.com/bar.pdf', $header['Location']);

    }

}