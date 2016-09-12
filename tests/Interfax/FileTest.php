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
        $this->assertEquals('Content-Type: application/pdf', $file->getHeader());
        $this->assertEquals('test.pdf', $file->getName());
    }

}